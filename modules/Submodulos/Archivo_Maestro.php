<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar_departamentos':
        $sql = "SELECT DISTINCT nombre FROM Departamento WHERE estado = 'activo'";
        $res = $conexion->query($sql);
        echo json_encode(['data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;

    case 'buscar_directorio':
        $codigo = "%" . ($_GET['codigo'] ?? '') . "%";
        $nombre = "%" . ($_GET['nombre'] ?? '') . "%";
        $depto  = "%" . ($_GET['depto'] ?? '') . "%";

        $sql = "SELECT e.id_empleado, u.username, u.correo_org, p.nombre, p.apellido_p, 
                       pue.nombre AS puesto, dep.nombre AS departamento, suc.nombre AS sucursal,
                       tel.numero AS telefono
                FROM Empleado e
                INNER JOIN Persona p ON e.id_persona = p.id_persona
                INNER JOIN Puesto pue ON e.id_puesto = pue.id_puesto
                INNER JOIN Departamento dep ON pue.id_departamento = dep.id_departamento
                INNER JOIN Empleado_Sucursal es ON e.id_empleado = es.id_empleado AND es.estado = 'activo'
                INNER JOIN Sucursal suc ON es.id_sucursal = suc.id_sucursal
                LEFT JOIN Empleado_Usuario eu ON e.id_empleado = eu.id_empleado
                LEFT JOIN Usuario u ON eu.id_usuario = u.id_usuario
                LEFT JOIN Empleado_Telefono et ON e.id_empleado = et.id_empleado AND et.estado = 'activo'
                LEFT JOIN Telefono tel ON et.id_telefono = tel.id_telefono
                WHERE (u.username LIKE ? OR ? = '%%')
                  AND (CONCAT(p.nombre, ' ', p.apellido_p) LIKE ? OR ? = '%%')
                  AND (dep.nombre LIKE ? OR ? = '%%')
                  AND e.estado = 'activo'
                GROUP BY e.id_empleado";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssss", $codigo, $codigo, $nombre, $nombre, $depto, $depto);
        $stmt->execute();
        echo json_encode(['data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        break;
}