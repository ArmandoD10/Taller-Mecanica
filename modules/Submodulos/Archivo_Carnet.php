<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

// Obtenemos el ID del usuario logueado desde la sesión
$id_usuario_sesion = $_SESSION['id_usuario'] ?? 0;

if ($id_usuario_sesion == 0) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida o expirada.']);
    exit;
}

try {
    // Query robusto que une Usuario -> Empleado -> Persona -> Puesto -> Sucursal -> Ciudad
    $sql = "SELECT 
                e.id_empleado,           -- El código del empleado para el carnet
                u.username, 
                u.correo_org, 
                n.nombre as nivel_acceso,
                p.nombre as emp_nombre, 
                p.apellido_p, 
                p.apellido_m, 
                p.cedula,
                p.sexo,
                p.fecha_nacimiento,
                p.email as correo_personal,
                puesto.nombre as puesto,
                dep.nombre as departamento,
                suc.nombre as sucursal,
                dir.Descripcion as calle, 
                ciu.nombre as ciudad,    -- Ciudad para la dirección completa
                (SELECT t.numero FROM Telefono t 
                 JOIN Empleado_Telefono et ON t.id_telefono = et.id_telefono 
                 WHERE et.id_empleado = e.id_empleado 
                 AND et.estado = 'activo' LIMIT 1) as telefono
            FROM Usuario u
            JOIN Nivel n ON u.id_nivel = n.id_nivel
            JOIN Empleado_Usuario eu ON u.id_usuario = eu.id_usuario
            JOIN Empleado e ON eu.id_empleado = e.id_empleado
            JOIN Persona p ON e.id_persona = p.id_persona
            JOIN Puesto puesto ON e.id_puesto = puesto.id_puesto
            JOIN Departamento dep ON puesto.id_departamento = dep.id_departamento
            JOIN Empleado_Sucursal es ON e.id_empleado = es.id_empleado
            JOIN Sucursal suc ON es.id_sucursal = suc.id_sucursal
            JOIN Direccion dir ON p.id_direccion = dir.id_direccion
            JOIN Ciudad ciu ON dir.id_ciudad = ciu.id_ciudad
            WHERE u.id_usuario = ? 
            AND es.estado = 'activo'    -- Solo traemos la sucursal donde está laborando actualmente
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario_sesion);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($data = $res->fetch_assoc()) {
        // Formateamos el ID para que siempre tenga 4 dígitos (ej: 0001, 0025)
        $data['codigo_empleado'] = str_pad($data['id_empleado'], 4, "0", STR_PAD_LEFT);
        
        echo json_encode([
            'success' => true, 
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No se encontraron datos vinculados a este empleado.'
        ]);
    }

} catch (Exception $e) {
    // En caso de error de SQL o conexión
    echo json_encode([
        'success' => false, 
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>