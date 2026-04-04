<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cargar_datos_iniciales':
        $resSuc = $conexion->query("SELECT id_sucursal, nombre FROM Sucursal WHERE estado='activo'");
        echo json_encode([
            'sucursales' => $resSuc->fetch_all(MYSQLI_ASSOC)
        ]);
        break;

    case 'buscar_empleado':
        buscar_empleado($conexion);
        break;

    case 'guardar':
        guardar($conexion); // Mueve la lógica a una función para limpiar el switch
        break;

    case 'listar':
    // Agregamos e.id_empleado y p.cedula a la consulta
    $sql = "SELECT e.id_empleado, p.cedula, p.nombre, p.apellido_p, s.nombre as sucursal, es.fecha_inicio, es.estado 
            FROM Empleado_Sucursal es
            JOIN Empleado e ON es.id_empleado = e.id_empleado
            JOIN Persona p ON e.id_persona = p.id_persona
            JOIN Sucursal s ON es.id_sucursal = s.id_sucursal
            WHERE es.estado = 'activo'
            ORDER BY es.fecha_inicio DESC";
    $res = $conexion->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    break;
}

function buscar_empleado($conexion) {
    if (ob_get_length()) ob_clean(); // Limpia cualquier eco o warning previo
    
    $term = $conexion->real_escape_string($_GET['term'] ?? '');
    $data = [];

    if (!empty($term)) {
        // Usamos e.id_empleado para evitar ambigüedad con Persona
        $sql = "SELECT e.id_empleado, p.cedula, p.nombre, p.apellido_p 
                FROM Empleado e 
                JOIN Persona p ON e.id_persona = p.id_persona 
                WHERE (e.id_empleado LIKE '%$term%' 
                   OR p.nombre LIKE '%$term%' 
                   OR p.apellido_p LIKE '%$term%' 
                   OR p.cedula LIKE '%$term%') 
                AND e.estado = 'activo' 
                LIMIT 5";
        
        $res = $conexion->query($sql);

        if ($res) { // Verificamos que la consulta no falló
            while($row = $res->fetch_assoc()) {
                $data[] = [
                    'id' => $row['id_empleado'],
                    'nombre' => $row['nombre'] . ' ' . $row['apellido_p'],
                    'cedula' => $row['cedula']
                ];
            }
        }
    }

    header('Content-Type: application/json'); // Forzamos el encabezado justo antes de enviar
    echo json_encode($data);
    exit; // Importante para que no se ejecute nada más abajo
}

function guardar($conexion) {
    // Validar que el usuario esté logueado
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor, inicie sesión nuevamente.']);
        exit;
    }

    $id_emp = $_POST['id_empleado'];
    $id_suc = $_POST['id_sucursal'];
    $user_sistema = $_SESSION['id_usuario']; // Captura el ID del usuario logueado

    $conexion->begin_transaction();
    try {
        // 1. Finalizar asignación activa previa
        $upd = $conexion->prepare("UPDATE Empleado_Sucursal SET estado='inactivo' WHERE id_empleado=? AND estado='activo'");
        $upd->bind_param("i", $id_emp);
        $upd->execute();

        // 2. Insertar nueva asignación con el usuario de la sesión
        // Nota: Asegúrate que el nombre de la columna sea 'fecha' o 'fecha_inicio' según tu DB
        $ins = $conexion->prepare("INSERT INTO Empleado_Sucursal (id_empleado, id_sucursal, fecha_inicio, estado, usuario_creacion) VALUES (?, ?, CURDATE(), 'activo', ?)");
        $ins->bind_param("iii", $id_emp, $id_suc, $user_sistema);
        $ins->execute();

        $conexion->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}