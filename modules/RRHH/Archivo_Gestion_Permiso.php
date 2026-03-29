<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
session_start();

switch ($action) {
    case 'buscarEmpleado':
        buscarEmpleado($conexion);
        break;
    case 'obtenerMotivos':
        obtenerMotivos($conexion);
        break;
    case 'guardarPermiso':
        guardarPermiso($conexion);
        break;
    case 'listarPermisos':
        listarPermisos($conexion);
        break;
    case 'obtenerKPIs':
        obtenerKPIs($conexion);
        break;
    default:
        echo json_encode(['success'=>false,'message'=>'Acción no válida']);
}

function buscarEmpleado($conexion){
    $filtro = $_GET['filtro'] ?? '';
    // Buscamos por ID, Nombre o Username (uniendo tabla usuario)
    $sql = "SELECT 
                e.id_empleado, 
                pu.nombre AS nombre_puesto, 
                p.cedula, 
                CONCAT(p.nombre, ' ', p.apellido_p) AS nombre, 
                u.username
            FROM empleado e
            INNER JOIN persona p ON e.id_persona = p.id_persona
            INNER JOIN puesto pu ON e.id_puesto = pu.id_puesto
            LEFT JOIN usuario u ON u.id_usuario = (
                SELECT id_usuario FROM empleado_usuario 
                WHERE id_empleado = e.id_empleado AND estado = 'activo' LIMIT 1
            )
            WHERE e.id_empleado = '$filtro' 
               OR p.nombre LIKE '%$filtro%' 
               OR p.cedula LIKE '%$filtro%'
               OR u.username LIKE '%$filtro%'
            LIMIT 1";
    $res = $conexion->query($sql);
    echo json_encode($res->fetch_assoc());
}

function obtenerMotivos($conexion){
    $res = $conexion->query("SELECT id_motivo, nombre FROM Tipo_Motivo WHERE estado = 'activo'");
    $data = [];
    while($row = $res->fetch_assoc()) $data[] = $row;
    echo json_encode($data);
}

function guardarPermiso($conexion){
        // 🔐 USUARIO DE SESIÓN
        $usuario_creacion = $_SESSION['id_usuario'] ?? null;

    $id_emp = $_POST['id_empleado'];
    $id_mot = $_POST['id_motivo'];
    $f_ini = $_POST['fecha_inicio'];
    $f_fin = $_POST['fecha_fin'];
    $obs   = $_POST['motivo_texto'];
    $user  = $usuario_creacion; // Aquí deberías usar el ID de sesión: $_SESSION['id_usuario']

    $sql = "INSERT INTO Permiso_Empleado (id_empleado, id_motivo, fecha_inicio, fecha_fin, motivo, estado, usuario_creacion, fecha_creacion) 
            VALUES ('$id_emp', '$id_mot', '$f_ini', '$f_fin', '$obs', 'activo', '$user', NOW())";
    
    echo json_encode(['success' => $conexion->query($sql)]);
}

function listarPermisos($conexion){
    // Usamos un CASE en SQL para determinar el estado visual dinámicamente
    $sql = "SELECT 
                pe.*, 
                p.nombre as emp_nombre, 
                p.apellido_p, 
                tm.nombre as tipo_nombre,
                CASE 
                    WHEN pe.fecha_fin < CURDATE() THEN 'inactivo'
                    ELSE pe.estado 
                END AS estado_real
            FROM Permiso_Empleado pe
            JOIN empleado e ON pe.id_empleado = e.id_empleado
            JOIN persona p ON e.id_persona = p.id_persona
            JOIN Tipo_Motivo tm ON pe.id_motivo = tm.id_motivo
            ORDER BY pe.fecha_creacion DESC";
            
    $res = $conexion->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
}

// Nueva función
function obtenerKPIs($conexion){
    $sql = "SELECT 
        (SELECT COUNT(*) FROM empleado WHERE estado = 'activo') as totales,
        (SELECT COUNT(*) FROM Permiso_Empleado WHERE estado = 'activo' AND fecha_fin >= CURDATE()) as activos,
        (SELECT COUNT(*) FROM Permiso_Empleado pe 
         JOIN Tipo_Motivo tm ON pe.id_motivo = tm.id_motivo 
         WHERE tm.nombre = 'Vacaciones' AND pe.estado = 'activo' AND pe.fecha_fin >= CURDATE()) as vacaciones,
        (SELECT COUNT(*) FROM Permiso_Empleado pe 
         JOIN Tipo_Motivo tm ON pe.id_motivo = tm.id_motivo 
         WHERE tm.nombre != 'Vacaciones' AND pe.estado = 'activo' AND pe.fecha_fin >= CURDATE()) as otros
    ";
    
    $res = $conexion->query($sql);
    echo json_encode($res->fetch_assoc());
}