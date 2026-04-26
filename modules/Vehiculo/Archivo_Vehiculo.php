<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cargar':
        cargar($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    case 'actualizar':
        actualizar($conexion);
        break;
    case 'cambiar_estado':
        cambiar_estado($conexion);
        break;
    case 'buscar_cliente': 
        buscar_cliente($conexion); 
        break;
    case 'get_modelos_por_marca': 
        get_modelos_por_marca($conexion); 
        break;
    case 'reporte_pdf':
        reporte_pdf($conexion);
        break;
    case 'get_selects':
        get_selects($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}

function get_modelos_por_marca($conexion) {
    $id_marca = (int)($_GET['id_marca'] ?? 0);
    $sql = "SELECT nombre FROM Modelo WHERE id_marca = $id_marca AND estado = 'activo' ORDER BY nombre ASC";
    $res = $conexion->query($sql);
    $modelos = [];
    while($row = $res->fetch_assoc()) { $modelos[] = $row['nombre']; }
    echo json_encode($modelos);
}


function get_selects($conexion) {
    $data = ['marcas' => [], 'colores' => []];
    $resMar = $conexion->query("SELECT id_marca, nombre FROM Marca WHERE estado = 'activo' ORDER BY nombre ASC");
    while($row = $resMar->fetch_assoc()) $data['marcas'][] = $row;
    $resCol = $conexion->query("SELECT id_color, nombre FROM Color WHERE estado = 'activo' ORDER BY nombre ASC");
    while($row = $resCol->fetch_assoc()) $data['colores'][] = $row;
    echo json_encode($data);
}

function reporte_pdf($conexion) {
    $sql = "SELECT 
                v.sec_vehiculo, 
                CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) AS cliente,
                v.vin_chasis, 
                v.placa, 
                m.nombre AS marca,
                v.modelo, 
                v.anio, 
                v.estado 
            FROM Vehiculo v
            INNER JOIN Cliente cli ON v.id_cliente = cli.id_cliente
            INNER JOIN Persona p ON cli.id_persona = p.id_persona
            INNER JOIN Marca m ON v.id_marca = m.id_marca
            ORDER BY v.sec_vehiculo ASC";
            
    $res = $conexion->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function cargar($conexion) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    $offset = ($page - 1) * $limit;

    $count_sql = "SELECT COUNT(*) as total FROM Vehiculo";
    $count_result = $conexion->query($count_sql);
    $total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;

    $vehiculos = [];
    
    // Cambiamos v.id_vehiculo por v.sec_vehiculo
    $sql = "SELECT 
                v.sec_vehiculo, 
                v.id_cliente,
                CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) AS cliente_nombre,
                p.cedula AS cliente_cedula,
                v.vin_chasis, 
                v.placa, 
                v.id_marca,
                m.nombre AS marca_nombre,
                v.id_color,
                c.nombre AS color_nombre,
                v.modelo, 
                v.anio, 
                v.kilometraje_actual, 
                v.estado, 
                v.fecha_ingreso
            FROM Vehiculo v
            INNER JOIN Cliente cli ON v.id_cliente = cli.id_cliente
            INNER JOIN Persona p ON cli.id_persona = p.id_persona
            INNER JOIN Marca m ON v.id_marca = m.id_marca
            INNER JOIN Color c ON v.id_color = c.id_color
            ORDER BY v.sec_vehiculo DESC
            LIMIT $limit OFFSET $offset";

    $resultado = $conexion->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $vehiculos[] = $fila;
        }
    }

    echo json_encode([
        'data' => $vehiculos,
        'total_records' => (int)$total_rows,
        'page' => $page,
        'limit' => $limit
    ]);
    exit;
}

function guardar($conexion) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $vin_chasis = $conexion->real_escape_string($_POST['vin_chasis'] ?? '');
        $placa = $conexion->real_escape_string($_POST['placa'] ?? '');
        $id_marca = (int)($_POST['id_marca'] ?? 0);
        $id_color = (int)($_POST['id_color'] ?? 0);
        $modelo = $conexion->real_escape_string($_POST['modelo'] ?? '');
        $anio = (int)($_POST['anio'] ?? 0);
        $kilometraje = (int)($_POST['kilometraje_actual'] ?? 0);
        $usuario_creacion = 1; // Asumiendo Admin

        if ($id_cliente === 0 || empty($vin_chasis)) {
            echo json_encode(['success' => false, 'message' => 'El Cliente y el Chasis/VIN son obligatorios.']);
            exit;
        }

        // Validar Chasis duplicado
        $check = "SELECT sec_vehiculo FROM Vehiculo WHERE vin_chasis = '$vin_chasis'";
        $result = $conexion->query($check);
        if ($result && $result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Este Chasis/VIN ya está registrado.']);
            exit;
        }

        $sql = "INSERT INTO Vehiculo (id_cliente, vin_chasis, placa, id_marca, id_color, modelo, anio, kilometraje_actual, estado, fecha_ingreso, usuario_creacion)
                VALUES ($id_cliente, '$vin_chasis', '$placa', $id_marca, $id_color, '$modelo', $anio, $kilometraje, 'activo', NOW(), $usuario_creacion)";

        if ($conexion->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Vehículo registrado con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $conexion->error]);
        }
    }
}

function actualizar($conexion) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_vehiculo = (int)($_POST['sec_vehiculo'] ?? 0);
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $vin_chasis = $conexion->real_escape_string($_POST['vin_chasis'] ?? '');
        $placa = $conexion->real_escape_string($_POST['placa'] ?? '');
        $id_marca = (int)($_POST['id_marca'] ?? 0);
        $id_color = (int)($_POST['id_color'] ?? 0);
        $modelo = $conexion->real_escape_string($_POST['modelo'] ?? '');
        $anio = (int)($_POST['anio'] ?? 0);
        $kilometraje = (int)($_POST['kilometraje_actual'] ?? 0);

        if (!$id_vehiculo) {
            echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
            exit;
        }

        $sql = "UPDATE Vehiculo 
                SET id_cliente=$id_cliente, vin_chasis='$vin_chasis', placa='$placa', id_marca=$id_marca, 
                    id_color=$id_color, modelo='$modelo', anio=$anio, kilometraje_actual=$kilometraje 
                WHERE sec_vehiculo = $id_vehiculo";
        
        if ($conexion->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Vehículo actualizado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $conexion->error]);
        }
    }
}

function cambiar_estado($conexion) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_vehiculo = (int)($_POST['sec_vehiculo'] ?? 0);
        $nuevo_estado = $conexion->real_escape_string($_POST['estado'] ?? 'inactivo');
        
        if ($id_vehiculo > 0) {
            $sql = "UPDATE Vehiculo SET estado = '$nuevo_estado' WHERE sec_vehiculo = $id_vehiculo";
            if ($conexion->query($sql)) {
                $mensaje = $nuevo_estado == 'activo' ? 'Vehículo reactivado con éxito.' : 'Vehículo desactivado correctamente.';
                echo json_encode(['success' => true, 'message' => $mensaje]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cambiar estado: ' . $conexion->error]);
            }
        }
    }
}

function buscar_cliente($conexion) {
    // Limpiamos cualquier salida previa para evitar errores de JSON
    if (ob_get_length()) ob_clean();

    $term = $conexion->real_escape_string($_GET['term'] ?? '');
    $data = [];

    if (!empty($term)) {
        // Consulta SQL para buscar en Persona vinculada al Cliente
        $sql = "SELECT c.id_cliente, p.cedula, p.nombre, p.apellido_p 
                FROM Cliente c 
                JOIN Persona p ON c.id_persona = p.id_persona 
                WHERE (c.id_cliente LIKE '%$term%' OR p.cedula LIKE '%$term%') 
                AND c.estado = 'activo' 
                LIMIT 5";
        
        $res = $conexion->query($sql);

        if ($res) {
            while($row = $res->fetch_assoc()) {
                $data[] = [
                    'id' => $row['id_cliente'],
                    'doc' => $row['cedula'],
                    'nombre' => $row['nombre'] . ' ' . $row['apellido_p']
                ];
            }
        }
    }

    // Enviamos el JSON y matamos el proceso para que no salga nada más
    echo json_encode($data);
    die(); 
}
?>