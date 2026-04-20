<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'buscar_mecanico':
        $term = isset($_GET['term']) ? $conexion->real_escape_string($_GET['term']) : '';
        $data = [];

        // CORRECCIÓN: Usamos id_puesto para el JOIN y pu.nombre para el filtro
        $sql = "SELECT 
                    e.id_empleado, 
                    p.cedula, 
                    p.nombre, 
                    p.apellido_p,
                    pu.nombre as nombre_puesto
                FROM Empleado e
                JOIN Persona p ON e.id_persona = p.id_persona
                JOIN Puesto pu ON e.id_puesto = pu.id_puesto
                WHERE (p.nombre LIKE '%$term%' 
                   OR p.apellido_p LIKE '%$term%' 
                   OR p.cedula LIKE '%$term%' 
                   OR e.id_empleado LIKE '%$term%')
                AND (pu.nombre = 'Mecanico' OR pu.nombre = 'Mecánico' OR pu.nombre = 'Tec. Mecanica')
                AND e.estado = 'activo' 
                LIMIT 5";

        $res = $conexion->query($sql);
        
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $data[] = [
                    'id_empleado' => $row['id_empleado'],
                    'nombre' => $row['nombre'] . ' ' . $row['apellido_p'],
                    'cedula' => $row['cedula'],
                    'puesto' => $row['nombre_puesto']
                ];
            }
        } else {
            // Si hay error en el SQL, lo capturamos para no romper el JSON
            echo json_encode(['error' => $conexion->error]);
            exit;
        }
        
        echo json_encode($data);
        break;

    case 'cargar_catalogo':
        $res = $conexion->query("SELECT id_especialidad, nombre FROM Especialidad WHERE estado='activo'");
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'guardar':
        $id_emp = (int)$_POST['id_empleado'];
        $id_esp = (int)$_POST['id_especialidad'];
        
        // 1. Verificamos si ya existe la relación (activa o eliminada)
        // Ya que es llave primaria, no podemos simplemente insertar si ya existe el par
        $check = $conexion->query("SELECT estado FROM Empleado_Especialidad WHERE id_empleado=$id_emp AND id_especialidad=$id_esp");
        
        if($check->num_rows > 0) {
            $row = $check->fetch_assoc();
            if($row['estado'] == 'activo') {
                echo json_encode(['success' => false, 'message' => 'El mecánico ya tiene esta especialidad activa.']);
            } else {
                // Si existía pero estaba 'eliminado', la reactivamos
                $upd = $conexion->query("UPDATE Empleado_Especialidad SET estado='activo', fecha_asignacion=NOW() WHERE id_empleado=$id_emp AND id_especialidad=$id_esp");
                echo json_encode(['success' => $upd]);
            }
        } else {
            // 2. Si no existe, insertamos normal. 
            // sec_empleado es AUTO_INCREMENT, no es necesario incluirlo.
            $ins = $conexion->prepare("INSERT INTO Empleado_Especialidad (id_empleado, id_especialidad, estado) VALUES (?, ?, 'activo')");
            $ins->bind_param("ii", $id_emp, $id_esp);
            echo json_encode(['success' => $ins->execute()]);
        }
        break;

    case 'listar_por_empleado':
        $id_emp = (int)$_GET['id_empleado'];
        // Seleccionamos los campos de la llave compuesta y el nombre de la especialidad
        $sql = "SELECT ee.id_empleado, ee.id_especialidad, esp.nombre, ee.fecha_asignacion 
                FROM Empleado_Especialidad ee
                JOIN Especialidad esp ON ee.id_especialidad = esp.id_especialidad
                WHERE ee.id_empleado = $id_emp AND ee.estado = 'activo'";
        $res = $conexion->query($sql);
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'eliminar':
        // Ahora eliminamos usando la combinación de IDs
        $id_emp = (int)$_POST['id_empleado'];
        $id_esp = (int)$_POST['id_especialidad'];
        $sql = "UPDATE Empleado_Especialidad SET estado='eliminado' WHERE id_empleado = $id_emp AND id_especialidad = $id_esp";
        echo json_encode(['success' => $conexion->query($sql)]);
        break;
}