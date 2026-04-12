<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'cargar_dependencias': cargar_dependencias($conexion); break;
    case 'listar_planes': listar_planes($conexion); break;
    case 'obtener_plan': obtener_plan($conexion); break;
    case 'guardar_plan': guardar_plan($conexion, $id_usuario); break;
    case 'cambiar_estado_plan': cambiar_estado_plan($conexion); break;
    case 'reporte_suscripciones': reporte_suscripciones($conexion); break;
    case 'buscar_clientes': buscar_clientes($conexion); break;
    case 'asignar_membresia': asignar_membresia($conexion, $id_usuario); break;
    default: echo json_encode(['success' => false, 'message' => 'Acción no válida']); break;
}

function cargar_dependencias($conexion) {
    $data = [];
    $data['tipos'] = $conexion->query("SELECT id_tipo_membresia, nombre FROM tipo_membresia WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['precios'] = $conexion->query("SELECT id_precio, monto FROM precio WHERE estado = 'activo' ORDER BY monto ASC")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

function listar_planes($conexion) {
    $sql = "SELECT pm.id_plan, tm.nombre as tipo_membresia, pr.monto as precio_mensual, pm.limite_lavado, pm.estado 
            FROM plan_membresia pm
            JOIN tipo_membresia tm ON pm.id_tipo_membresia = tm.id_tipo_membresia
            JOIN precio pr ON pm.precio_mensual = pr.id_precio
            ORDER BY pm.id_plan DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function obtener_plan($conexion) {
    $id = (int)$_GET['id_plan'];
    $sql = "SELECT pm.*, tm.nombre as nombre_tipo 
            FROM plan_membresia pm 
            JOIN tipo_membresia tm ON pm.id_tipo_membresia = tm.id_tipo_membresia 
            WHERE pm.id_plan = $id";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_assoc() : null]);
}

function guardar_plan($conexion, $id_usuario) {
    $id_plan = (int)($_POST['id_plan'] ?? 0);
    $nombre_tipo = trim($_POST['nombre_tipo_membresia']); 
    $precio_mensual = (int)$_POST['id_precio']; 
    $limite_lavado = (int)$_POST['limite_lavado'];

    $conexion->begin_transaction();
    try {
        $id_tipo_membresia = 0;
        $stmtTipo = $conexion->prepare("SELECT id_tipo_membresia FROM tipo_membresia WHERE nombre = ? LIMIT 1");
        $stmtTipo->bind_param("s", $nombre_tipo);
        $stmtTipo->execute();
        $resTipo = $stmtTipo->get_result();

        if ($row = $resTipo->fetch_assoc()) {
            $id_tipo_membresia = $row['id_tipo_membresia'];
        } else {
            $stmtNew = $conexion->prepare("INSERT INTO tipo_membresia (nombre, estado, usuario_creacion) VALUES (?, 'activo', ?)");
            $stmtNew->bind_param("si", $nombre_tipo, $id_usuario);
            $stmtNew->execute();
            $id_tipo_membresia = $conexion->insert_id;
        }

        if ($id_plan > 0) {
            $stmt = $conexion->prepare("UPDATE plan_membresia SET id_tipo_membresia=?, precio_mensual=?, limite_lavado=? WHERE id_plan=?");
            $stmt->bind_param("iiii", $id_tipo_membresia, $precio_mensual, $limite_lavado, $id_plan);
            $msg = "Plan actualizado correctamente.";
        } else {
            $stmt = $conexion->prepare("INSERT INTO plan_membresia (id_tipo_membresia, precio_mensual, limite_lavado, estado, usuario_creacion) VALUES (?, ?, ?, 'activo', ?)");
            $stmt->bind_param("iiii", $id_tipo_membresia, $precio_mensual, $limite_lavado, $id_usuario);
            $msg = "Plan creado exitosamente.";
        }
        
        $stmt->execute();
        $conexion->commit();
        echo json_encode(['success' => true, 'message' => $msg]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function cambiar_estado_plan($conexion) {
    $id = (int)$_POST['id_plan'];
    $estado = $_POST['estado'];
    $conexion->query("UPDATE plan_membresia SET estado = '$estado' WHERE id_plan = $id");
    echo json_encode(['success' => true]);
}

function buscar_clientes($conexion) {
    $term = $_GET['term'] ?? '';
    
    // Ahora buscamos por nombre, apellido o la columna exacta "cedula"
    $sql = "SELECT c.id_cliente, p.cedula as num_documento,
                   CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente
            FROM cliente c
            JOIN persona p ON c.id_persona = p.id_persona
            WHERE (p.nombre LIKE '%$term%' 
                   OR IFNULL(p.apellido_p, '') LIKE '%$term%' 
                   OR p.cedula LIKE '%$term%')
            AND c.estado = 'activo' 
            LIMIT 10";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function asignar_membresia($conexion, $id_usuario) {
    $id_cliente = (int)$_POST['id_cliente_asig'];
    $id_plan = (int)$_POST['id_plan_asig'];
    $fecha_inicio = $_POST['fecha_inicio_asig'] . " 00:00:00"; 
    $fecha_vencimiento = $_POST['fecha_vencimiento_asig'] . " 23:59:59";
    $lavados = (int)$_POST['lavados_asig'];

    try {
        $stmt = $conexion->prepare("INSERT INTO membresia_usuario 
            (id_plan, id_cliente, fecha_inicio, fecha_vencimiento, lavado_restantes, estado, usuario_creacion) 
            VALUES (?, ?, ?, ?, ?, 'activo', ?)");
        $stmt->bind_param("iissii", $id_plan, $id_cliente, $fecha_inicio, $fecha_vencimiento, $lavados, $id_usuario);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Suscripción asignada con éxito.']);
        } else {
            throw new Exception("Error al insertar suscripción.");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function reporte_suscripciones($conexion) {
    $sql = "SELECT mu.id_membresia, tm.nombre as plan_nombre, pr.monto as precio,
                   CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente,
                   DATE_FORMAT(mu.fecha_inicio, '%d/%m/%Y') as inicio,
                   DATE_FORMAT(mu.fecha_vencimiento, '%d/%m/%Y') as fin,
                   mu.lavado_restantes,
                   mu.estado
            FROM membresia_usuario mu
            JOIN plan_membresia pm ON mu.id_plan = pm.id_plan
            JOIN tipo_membresia tm ON pm.id_tipo_membresia = tm.id_tipo_membresia
            JOIN precio pr ON pm.precio_mensual = pr.id_precio
            JOIN cliente c ON mu.id_cliente = c.id_cliente
            JOIN persona p ON c.id_persona = p.id_persona
            ORDER BY mu.fecha_vencimiento ASC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}
?>