<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function jsonError($conexion){
    echo json_encode([
        'error' => true,
        'detalle' => $conexion->error
    ]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'buscarEmpleado':
        buscarEmpleado($conexion);
        break;

    case 'usuariosEmpleado':
        usuariosEmpleado($conexion);
        break;

    case 'usuariosDisponibles':
        usuariosDisponibles($conexion);
        break;

    case 'asignar':
        asignar($conexion);
        break;

    case 'quitar':
        quitar($conexion);
        break;

    case 'activar':
        activar($conexion);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Acción no válida']);
}

function buscarEmpleado($conexion){

    $filtro = $_GET['filtro'] ?? '';

    if(empty($filtro)){
        echo json_encode(null);
        return;
    }

    $sql = "SELECT 
                e.id_empleado,
                p.cedula,
                CONCAT(p.nombre, ' ', p.apellido_p) AS nombre
            FROM empleado e
            INNER JOIN persona p ON e.id_persona = p.id_persona
            WHERE 
                e.id_empleado = '$filtro'
                OR p.cedula LIKE '%$filtro%'
                OR p.nombre LIKE '%$filtro%'
                OR p.apellido_p LIKE '%$filtro%'
            LIMIT 1";

    $res = $conexion->query($sql);

    if(!$res){
        jsonError($conexion);
    }

    echo json_encode($res->fetch_assoc());
}

function usuariosEmpleado($conexion){

    $id = $_GET['id_empleado'] ?? 0;

    $sql = "SELECT 
                u.id_usuario, 
                u.username, 
                n.nombre AS nivel, 
                eu.estado
            FROM empleado_usuario eu
            JOIN usuario u ON u.id_usuario = eu.id_usuario
            JOIN nivel n ON n.id_nivel = u.id_nivel
            WHERE eu.id_empleado = '$id'";

    $res = $conexion->query($sql);

    if(!$res){
        jsonError($conexion);
    }

    $data = [];
    while($row = $res->fetch_assoc()){
        $data[] = $row;
    }

    echo json_encode($data);
}

function usuariosDisponibles($conexion){
    $id = $_GET['id_empleado'];

    $sql = "SELECT u.id_usuario, u.username
            FROM usuario u
            WHERE u.estado = 'activo'
            AND NOT EXISTS (
                SELECT 1
                FROM empleado_usuario eu
                WHERE eu.id_usuario = u.id_usuario
                AND eu.estado = 'activo'
            )";

    $res = $conexion->query($sql);

    $data = [];
    while($row = $res->fetch_assoc()){
        $data[] = $row;
    }

    echo json_encode($data);
}

function asignar($conexion){

    $id_usuario = $_POST['id_usuario'] ?? 0;
    $id_empleado = $_POST['id_empleado'] ?? 0;
    $estado = $_POST['estado'] ?? 'activo';

    $sql = "INSERT INTO empleado_usuario 
            (id_usuario, id_empleado, fecha_creacion, estado)
            VALUES ('$id_usuario', '$id_empleado', NOW(), '$estado')";

    $ok = $conexion->query($sql);

    if(!$ok){
        jsonError($conexion);
    }

    echo json_encode(['success'=>true]);
}

function quitar($conexion){

    $id_usuario = $_POST['id_usuario'] ?? 0;
    $id_empleado = $_POST['id_empleado'] ?? 0;

    $sql = "UPDATE empleado_usuario 
            SET estado='inactivo'
            WHERE id_usuario='$id_usuario' AND id_empleado='$id_empleado'";

    $ok = $conexion->query($sql);

    if(!$ok){
        jsonError($conexion);
    }

    echo json_encode(['success'=>true]);
}

function activar($conexion){
    $id_usuario = $_POST['id_usuario'];
    $id_empleado = $_POST['id_empleado'];

    // 🔍 VALIDAR SI YA ESTÁ ACTIVO EN OTRO EMPLEADO
    $sqlCheck = "SELECT * 
                 FROM empleado_usuario 
                 WHERE id_usuario = $id_usuario 
                 AND estado = 'activo'
                 AND id_empleado != $id_empleado";

    $res = $conexion->query($sqlCheck);

    if($res->num_rows > 0){
        echo json_encode([
            'success' => false,
            'msg' => 'usuario asignado a otro empleado'
        ]);
        return;
    }

    // ✅ ACTIVAR
    $sql = "UPDATE empleado_usuario 
            SET estado='activo'
            WHERE id_usuario=$id_usuario 
            AND id_empleado=$id_empleado";

    $ok = $conexion->query($sql);

    echo json_encode(['success'=>$ok]);
}