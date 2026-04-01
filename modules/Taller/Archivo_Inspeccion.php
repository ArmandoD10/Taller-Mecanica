<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
session_start();

switch ($action) {
    case 'cargar_datos_iniciales':
        cargar_datos_iniciales($conexion);
        break;
    case 'cargar_vehiculos_cliente':
        cargar_vehiculos_cliente($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function cargar_datos_iniciales($conexion) {
    $data = [];
    // Cargamos Clientes para el buscador
    $resClientes = $conexion->query("SELECT c.id_cliente, IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) AS nombre_cliente, p.cedula FROM Persona p JOIN Cliente c ON p.id_persona = c.id_persona WHERE c.estado = 'activo'");
    $data['clientes'] = $resClientes ? $resClientes->fetch_all(MYSQLI_ASSOC) : [];

    // Cargamos Asesores
    $resEmpleados = $conexion->query("SELECT e.id_empleado, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) AS nombre_empleado FROM Empleado e JOIN Persona p ON e.id_persona = p.id_persona WHERE e.estado = 'activo'");
    $data['empleados'] = $resEmpleados ? $resEmpleados->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_vehiculos_cliente($conexion) {
    $id_cliente = (int)($_GET['id_cliente'] ?? 0);
    $sql = "SELECT v.id_vehiculo, v.placa, v.vin_chasis, m.nombre AS marca, mo.nombre AS modelo 
            FROM Vehiculo v 
            JOIN Marca m ON v.id_marca = m.id_marca 
            JOIN Modelo mo ON v.id_modelo = mo.id_modelo 
            WHERE v.id_cliente = ? AND v.estado = 'activo'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function guardar($conexion) {
    $usuario_creacion = $_SESSION['id_usuario'] ?? 1;
    $id_vehiculo = $_POST['id_vehiculo'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $kilometraje = $_POST['kilometraje_recepcion'] ?? 0;
    $combustible = $_POST['nivel_combustible'] ?? '';
    $motivo_visita = $_POST['motivo_visita'] ?? '';

    try {
        $conexion->begin_transaction();
        $sql = "INSERT INTO Inspeccion (id_vehiculo, id_empleado, kilometraje_recepcion, nivel_combustible, observacion, estado, usuario_creacion) VALUES (?, ?, ?, ?, ?, 'activo', ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iiissi", $id_vehiculo, $id_empleado, $kilometraje, $combustible, $motivo_visita, $usuario_creacion);
        $stmt->execute();
        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Inspección guardada con éxito.']);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}