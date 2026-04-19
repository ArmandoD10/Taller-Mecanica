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
    case 'cargar_datos_orden':
        cargar_datos_orden($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    case 'listar_historial':
        listar_historial($conexion);
        break;
    case 'ver_detalle':
        ver_detalle($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function cargar_datos_iniciales($conexion) {
    $data = [];
    $resClientes = $conexion->query("SELECT c.id_cliente, IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) AS nombre_cliente, p.cedula FROM persona p JOIN cliente c ON p.id_persona = c.id_persona WHERE c.estado = 'activo'");
    $data['clientes'] = $resClientes ? $resClientes->fetch_all(MYSQLI_ASSOC) : [];

    $resEmpleados = $conexion->query("SELECT e.id_empleado, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) AS nombre_empleado FROM empleado e JOIN persona p ON e.id_persona = p.id_persona WHERE e.estado = 'activo'");
    $data['empleados'] = $resEmpleados ? $resEmpleados->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_datos_orden($conexion) {
    $id_orden = (int)($_GET['id_orden'] ?? 0);
    $sql = "SELECT o.id_orden, o.descripcion, v.id_cliente, v.sec_vehiculo as id_vehiculo, 
                   IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) AS nombre_cliente,
                   CONCAT(m.nombre, ' ', v.modelo, ' [', v.placa, ']') as vehiculo_desc
            FROM orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona p ON c.id_persona = p.id_persona
            JOIN marca m ON v.id_marca = m.id_marca
            WHERE o.id_orden = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_orden);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
}

function cargar_vehiculos_cliente($conexion) {
    $id_cliente = (int)($_GET['id_cliente'] ?? 0);
    $sql = "SELECT v.sec_vehiculo AS id_vehiculo, v.placa, v.vin_chasis, m.nombre AS marca, v.modelo AS modelo 
            FROM vehiculo v 
            LEFT JOIN marca m ON v.id_marca = m.id_marca 
            WHERE v.id_cliente = ? AND v.estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function guardar($conexion) {
    $usuario_creacion = $_SESSION['id_usuario'] ?? 0;
    $id_vehiculo = $_POST['id_vehiculo'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $id_orden_previa = (int)($_POST['id_orden'] ?? 0);
    $kilometraje = $_POST['kilometraje_recepcion'] ?? 0;
    $combustible = $_POST['nivel_combustible'] ?? '';
    $motivo_visita = $_POST['motivo_visita'] ?? ''; // El campo de texto antiguo como notas opcionales
    
    // RECIBIMOS EL ARRAY DE TRABAJOS (CHECKBOXES / TAGS)
    $trabajos_seleccionados = $_POST['trabajos'] ?? [];

    if ($usuario_creacion == 0) {
        echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
        return;
    }

    try {
        $conexion->begin_transaction();

        $sql_s = "SELECT es.id_sucursal FROM empleado_usuario eu
                  INNER JOIN empleado_sucursal es ON eu.id_empleado = es.id_empleado
                  WHERE eu.id_usuario = ? AND es.estado = 'activo' LIMIT 1";
        $stmt_s = $conexion->prepare($sql_s);
        $stmt_s->bind_param("i", $usuario_creacion);
        $stmt_s->execute();
        $res_s = $stmt_s->get_result()->fetch_assoc();
        $id_sucursal_actual = $res_s['id_sucursal'] ?? 0;

        if ($id_sucursal_actual == 0) {
            throw new Exception("El usuario no tiene una sucursal activa asignada.");
        }

        if ($id_orden_previa > 0) {
            // LÓGICA DE TRANSICIÓN: Viene desde Cotización
            $sqlGetIns = "SELECT id_inspeccion FROM orden WHERE id_orden = ?";
            $stmtGet = $conexion->prepare($sqlGetIns);
            $stmtGet->bind_param("i", $id_orden_previa);
            $stmtGet->execute();
            $id_inspeccion = $stmtGet->get_result()->fetch_assoc()['id_inspeccion'];

            $updIns = "UPDATE inspeccion SET id_empleado = ?, kilometraje_recepcion = ?, nivel_combustible = ?, observacion = ?, estado = 'activo' WHERE id_inspeccion = ?";
            $stmtUpd = $conexion->prepare($updIns);
            $stmtUpd->bind_param("iissi", $id_empleado, $kilometraje, $combustible, $motivo_visita, $id_inspeccion);
            $stmtUpd->execute();
            
            // Avanzar el estado de la orden a "Reparación"
            $resEst = $conexion->query("SELECT id_estado FROM estado WHERE nombre = 'Reparación' LIMIT 1");
            if($resEst->num_rows > 0) {
                $id_est_rep = $resEst->fetch_assoc()['id_estado'];
                $conexion->query("INSERT INTO orden_estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden_previa, $id_est_rep, $usuario_creacion)");
            }
            $msg = "Inspección completada. La Orden ORD-$id_orden_previa ha pasado directo a Reparación.";
            
        } else {
            // LÓGICA DIRECTA: Cliente de calle. (SOLO SE CREA LA INSPECCIÓN)
            $sql = "INSERT INTO inspeccion (id_vehiculo, id_empleado, id_sucursal, kilometraje_recepcion, nivel_combustible, observacion, estado, usuario_creacion) 
                    VALUES (?, ?, ?, ?, ?, ?, 'activo', ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iiiissi", $id_vehiculo, $id_empleado, $id_sucursal_actual, $kilometraje, $combustible, $motivo_visita, $usuario_creacion);
            $stmt->execute();
            
            $id_inspeccion = $conexion->insert_id;
            $msg = "Inspección guardada. Se encuentra lista para procesar en Gestión de Órdenes.";
        }

        // =========================================================================
        // GUARDAR LOS TRABAJOS SOLICITADOS EN LA TABLA PUENTE
        // =========================================================================
        if (!empty($trabajos_seleccionados)) {
            // Si es orden previa, borramos primero por si se está re-guardando
            if ($id_orden_previa > 0) {
                $conexion->query("DELETE FROM inspeccion_trabajo WHERE id_inspeccion = $id_inspeccion");
            }
            
            $sql_trabajo = "INSERT INTO inspeccion_trabajo (id_inspeccion, id_trabajo) VALUES (?, ?)";
            $stmtTrabajo = $conexion->prepare($sql_trabajo);
            
            foreach ($trabajos_seleccionados as $id_trabajo) {
                $id_trab_limpio = (int)$id_trabajo;
                $stmtTrabajo->bind_param("ii", $id_inspeccion, $id_trab_limpio);
                $stmtTrabajo->execute();
            }
        }
        // =========================================================================

        // Guardar el Checklist Dinámico
        $items_interior = ['Beeper', 'Pito/Bocina', 'Luces int.', 'Aire Cond.', 'Radio', 'Cristales', 'Seguros', 'Retrovisor'];
        $items_ext = ['Goma Rep.', 'Gato', 'Herram.', 'Llave Rueda', 'Luces Tras.', 'Tapa Comb.', 'Botiquín', 'Triángulo'];
        $items_mot = ['Varilla Aceite', 'Tapón Aceite', 'Radiador', 'Batería', 'Agua L/V', 'Filtro Aire', 'Correas', 'Tapas'];

        if ($id_orden_previa > 0) {
            $conexion->query("DELETE FROM inspeccion_detalle WHERE id_inspeccion = $id_inspeccion");
        }

        $sqlDet = "INSERT INTO inspeccion_detalle (id_inspeccion, categoria, elemento, estado) VALUES (?, ?, ?, ?)";
        $stmtDet = $conexion->prepare($sqlDet);

        $insertarCategoria = function($prefijo, $items, $categoria) use ($stmtDet, $id_inspeccion) {
            foreach ($items as $i => $item) {
                if (isset($_POST[$prefijo . '_' . $i])) {
                    $estado = $_POST[$prefijo . '_' . $i];
                    $stmtDet->bind_param("isss", $id_inspeccion, $categoria, $item, $estado);
                    $stmtDet->execute();
                }
            }
        };

        $insertarCategoria('int', $items_interior, 'Interior');
        $insertarCategoria('ext', $items_ext, 'Exterior');
        $insertarCategoria('mot', $items_mot, 'Motor');

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => $msg]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// =========================================================================
// NUEVAS FUNCIONES PARA EL HISTORIAL DE INSPECCIONES
// =========================================================================

function listar_historial($conexion) {
    // Busca las últimas 500 inspecciones. Se incluye fecha_db para el filtro de JS
    $sql = "SELECT i.id_inspeccion, 
                   DATE_FORMAT(i.fecha_inspeccion, '%d/%m/%Y %h:%i %p') as fecha,
                   DATE(i.fecha_inspeccion) as fecha_db,
                   v.placa, CONCAT(IFNULL(m.nombre, ''), ' ', v.modelo) as vehiculo,
                   IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) as cliente,
                   i.estado
            FROM inspeccion i
            JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona p ON c.id_persona = p.id_persona
            LEFT JOIN marca m ON v.id_marca = m.id_marca
            ORDER BY i.id_inspeccion DESC LIMIT 500";
            
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
}

function ver_detalle($conexion) {
    $id = (int)($_GET['id'] ?? 0);
    
    // 1. Información General de Recepción
    $sql_info = "SELECT i.id_inspeccion, DATE_FORMAT(i.fecha_inspeccion, '%d/%m/%Y %h:%i %p') as fecha, 
                        i.kilometraje_recepcion, i.nivel_combustible, i.observacion,
                        v.placa, CONCAT(IFNULL(m.nombre, ''), ' ', v.modelo) as vehiculo,
                        IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) as cliente,
                        CONCAT(emp_p.nombre, ' ', IFNULL(emp_p.apellido_p, '')) as asesor
                 FROM inspeccion i
                 JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                 JOIN cliente c ON v.id_cliente = c.id_cliente
                 JOIN persona p ON c.id_persona = p.id_persona
                 LEFT JOIN marca m ON v.id_marca = m.id_marca
                 JOIN empleado e ON i.id_empleado = e.id_empleado
                 JOIN persona emp_p ON e.id_persona = emp_p.id_persona
                 WHERE i.id_inspeccion = ?";
                 
    $stmt = $conexion->prepare($sql_info);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();

    // 2. Trabajos Solicitados (Las nuevas etiquetas azules)
    $sql_trabajos = "SELECT ts.descripcion 
                     FROM inspeccion_trabajo it 
                     JOIN trabajo_solicitado ts ON it.id_trabajo = ts.id_trabajo 
                     WHERE it.id_inspeccion = ?";
    $stmt_t = $conexion->prepare($sql_trabajos);
    $stmt_t->bind_param("i", $id);
    $stmt_t->execute();
    $trabajos = $stmt_t->get_result()->fetch_all(MYSQLI_ASSOC);

    // 3. Checklist Dinámico Completo (para rearmar la hoja de inspección en el modal)
    $sql_check = "SELECT categoria, elemento, estado FROM inspeccion_detalle WHERE id_inspeccion = ?";
    $stmt_c = $conexion->prepare($sql_check);
    $stmt_c->bind_param("i", $id);
    $stmt_c->execute();
    $checklist = $stmt_c->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true, 
        'info' => $info, 
        'trabajos' => $trabajos, 
        'checklist' => $checklist
    ]);
}
?>