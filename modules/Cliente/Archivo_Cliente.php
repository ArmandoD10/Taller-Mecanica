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
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}

function cargar($conexion) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    $offset = ($page - 1) * $limit;

    $count_sql = "SELECT COUNT(*) as total FROM Cliente";
    $count_result = $conexion->query($count_sql);
    $total_rows = $count_result ? $count_result->fetch_assoc()['total'] : 0;

    $clientes = [];
    
    $sql = "SELECT 
                c.id_cliente, 
                p.nombre, 
                p.apellido_p AS apellido, 
                p.cedula AS cedula_rnc, 
                p.email AS correo, 
                d.Descripcion AS direccion, 
                c.estado, 
                c.fecha_creacion AS fecha_registro,
                (SELECT t.numero 
                 FROM Telefono t 
                 JOIN Cliente_Telefono ct ON t.id_telefono = ct.id_telefono 
                 WHERE ct.id_cliente = c.id_cliente AND ct.estado = 'activo' LIMIT 1) AS telefono
            FROM Cliente c
            INNER JOIN Persona p ON c.id_persona = p.id_persona
            INNER JOIN Direccion d ON p.id_direccion = d.id_direccion
            ORDER BY c.id_cliente DESC
            LIMIT $limit OFFSET $offset";

    $resultado = $conexion->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $fila;
        }
    }

    echo json_encode([
        'data' => $clientes,
        'total_records' => (int)$total_rows,
        'page' => $page,
        'limit' => $limit
    ]);
}

function guardar($conexion) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = $conexion->real_escape_string($_POST['nombre'] ?? '');
        $apellido = $conexion->real_escape_string($_POST['apellido'] ?? '');
        $cedula = $conexion->real_escape_string($_POST['cedula_rnc'] ?? '');
        $telefono = $conexion->real_escape_string($_POST['telefono'] ?? '');
        $correo = $conexion->real_escape_string($_POST['correo'] ?? '');
        $direccion = $conexion->real_escape_string($_POST['direccion'] ?? 'No especificada');
        
        $id_ciudad = 1; 
        $nacionalidad = 1; 
        $fecha_nac = '2000-01-01'; 
        $usuario_creacion = 1; 

        if (empty($nombre) || empty($cedula)) {
            echo json_encode(['success' => false, 'message' => 'Nombre y Cédula son obligatorios.']);
            exit;
        }

        $check = "SELECT id_persona FROM Persona WHERE cedula = '$cedula'";
        $result = $conexion->query($check);
        if ($result && $result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Este RNC o Cédula ya está registrado.']);
            exit;
        }

        $conexion->begin_transaction();

        try {
            $sqlDir = "INSERT INTO Direccion (id_ciudad, Descripcion, estado) VALUES ($id_ciudad, '$direccion', 'activo')";
            $conexion->query($sqlDir);
            $id_direccion = $conexion->insert_id;

            $sqlPersona = "INSERT INTO Persona (nombre, apellido_p, cedula, email, fecha_nacimiento, id_direccion, nacionalidad, estado)
                           VALUES ('$nombre', '$apellido', '$cedula', '$correo', '$fecha_nac', $id_direccion, $nacionalidad, 'activo')";
            $conexion->query($sqlPersona);
            $id_persona = $conexion->insert_id;

            $sqlCliente = "INSERT INTO Cliente (id_persona, fecha_creacion, usuario_creacion, estado)
                           VALUES ($id_persona, NOW(), $usuario_creacion, 'activo')";
            $conexion->query($sqlCliente);
            $id_cliente = $conexion->insert_id;

            if (!empty($telefono)) {
                $sqlTel = "INSERT INTO Telefono (numero, estado) VALUES ('$telefono', 'activo')";
                $conexion->query($sqlTel);
                $id_telefono = $conexion->insert_id;

                $sqlCliTel = "INSERT INTO Cliente_Telefono (id_cliente, id_telefono, fecha_creacion, estado)
                              VALUES ($id_cliente, $id_telefono, NOW(), 'activo')";
                $conexion->query($sqlCliTel);
            }

            $conexion->commit();
            echo json_encode(['success' => true, 'message' => 'Cliente registrado con éxito.']);

        } catch (Exception $e) {
            $conexion->rollback();
            echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }
}

function actualizar($conexion) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $nombre = $conexion->real_escape_string($_POST['nombre'] ?? '');
        $apellido = $conexion->real_escape_string($_POST['apellido'] ?? '');
        $cedula = $conexion->real_escape_string($_POST['cedula_rnc'] ?? '');
        $telefono_nuevo = $conexion->real_escape_string($_POST['telefono'] ?? '');
        $correo = $conexion->real_escape_string($_POST['correo'] ?? '');
        $direccion_nueva = $conexion->real_escape_string($_POST['direccion'] ?? '');

        if (!$id_cliente) {
            echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
            exit;
        }

        $conexion->begin_transaction();

        try {
            $query_ids = "SELECT p.id_persona, p.id_direccion, ct.id_telefono 
                          FROM Cliente c 
                          JOIN Persona p ON c.id_persona = p.id_persona 
                          LEFT JOIN Cliente_Telefono ct ON c.id_cliente = ct.id_cliente AND ct.estado = 'activo'
                          WHERE c.id_cliente = $id_cliente LIMIT 1";
            
            $resultado_ids = $conexion->query($query_ids);
            if (!$resultado_ids || $resultado_ids->num_rows === 0) {
                throw new Exception("Cliente no encontrado en la base de datos.");
            }
            
            $ids = $resultado_ids->fetch_assoc();
            $id_persona = $ids['id_persona'];
            $id_direccion = $ids['id_direccion'];
            $id_telefono_viejo = $ids['id_telefono'];

            $sqlPersona = "UPDATE Persona SET nombre='$nombre', apellido_p='$apellido', cedula='$cedula', email='$correo' WHERE id_persona=$id_persona";
            $conexion->query($sqlPersona);

            if ($id_direccion) {
                $sqlDir = "UPDATE Direccion SET Descripcion='$direccion_nueva' WHERE id_direccion=$id_direccion";
                $conexion->query($sqlDir);
            }

            if (!empty($telefono_nuevo)) {
                if ($id_telefono_viejo) {
                    $sqlTel = "UPDATE Telefono SET numero='$telefono_nuevo' WHERE id_telefono=$id_telefono_viejo";
                    $conexion->query($sqlTel);
                } else {
                    $sqlTel = "INSERT INTO Telefono (numero, estado) VALUES ('$telefono_nuevo', 'activo')";
                    $conexion->query($sqlTel);
                    $nuevo_id_tel = $conexion->insert_id;
                    
                    $sqlCliTel = "INSERT INTO Cliente_Telefono (id_cliente, id_telefono, fecha_creacion, estado)
                                  VALUES ($id_cliente, $nuevo_id_tel, NOW(), 'activo')";
                    $conexion->query($sqlCliTel);
                }
            }

            $conexion->commit();
            echo json_encode(['success' => true, 'message' => 'Datos actualizados correctamente.']);

        } catch (Exception $e) {
            $conexion->rollback();
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }
}

function cambiar_estado($conexion) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $nuevo_estado = $conexion->real_escape_string($_POST['estado'] ?? 'inactivo');
        
        if ($id_cliente > 0) {
            $sql = "UPDATE Cliente SET estado = '$nuevo_estado' WHERE id_cliente = $id_cliente";
            if ($conexion->query($sql)) {
                $mensaje = $nuevo_estado == 'activo' ? 'Cliente reactivado con éxito.' : 'Cliente desactivado correctamente.';
                echo json_encode(['success' => true, 'message' => $mensaje]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cambiar estado: ' . $conexion->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de cliente inválido.']);
        }
    }
}
?>