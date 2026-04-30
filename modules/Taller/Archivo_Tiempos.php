<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$action = $_GET['action'] ?? '';
$id_usuario_sesion = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'listar': listar($conexion); break;
    case 'cargar_dependencias': cargar_dependencias($conexion); break;
    case 'cargar_servicios_orden': cargar_servicios_orden($conexion); break;
    case 'guardar_asignacion': guardar_asignacion($conexion, $id_usuario_sesion); break;
    case 'obtener_asignacion': obtener_asignacion($conexion); break;
    case 'iniciar_tiempo': iniciar_tiempo($conexion, $id_usuario_sesion); break;
    case 'finalizar_tiempo': finalizar_tiempo($conexion); break;
    case 'eliminar_asignacion': eliminar_asignacion($conexion); break;
    default: echo json_encode(['success' => false, 'message' => 'Acción no válida']); break;
}

function listar($conexion) {
    try {
        $sql = "SELECT ap.id_asignacion, ao.id_orden, o.descripcion AS orden_desc, ts.nombre AS servicio,
                    GROUP_CONCAT(DISTINCT CONCAT(p.nombre, ' ', p.apellido_p) SEPARATOR ', ') AS mecanicos_nombres,
                    ap.estado_asignacion, rt.id_tiempo, DATE_FORMAT(rt.hora_inicio, '%h:%i %p') AS hora_inicio_fmt,
                    DATE_FORMAT(rt.hora_fin, '%h:%i %p') AS hora_fin_fmt
                FROM asignacion_personal ap
                JOIN asignacion_orden ao ON ap.id_asignacion = ao.id_asignacion
                JOIN Orden o ON ao.id_orden = o.id_orden
                JOIN Tipo_Servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
                LEFT JOIN detalle_asignacion_p dap ON ap.id_asignacion = dap.id_asignacion
                LEFT JOIN Empleado e ON dap.id_empleado = e.id_empleado
                LEFT JOIN Persona p ON e.id_persona = p.id_persona
                LEFT JOIN registro_tiempos rt ON ap.id_asignacion = rt.id_asignacion AND rt.estado = 'activo'
                WHERE ap.estado != 'eliminado' 
                GROUP BY ap.id_asignacion 
                ORDER BY ap.id_asignacion DESC";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()]);
    }
}

function cargar_dependencias($conexion) {
    try {
        $data = [];
        $sqlOrdenes = "SELECT DISTINCT o.id_orden, o.descripcion,
                        (SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado 
                         WHERE oe.id_orden = o.id_orden ORDER BY oe.sec_orden_estado DESC LIMIT 1) as ultimo_estado,
                        CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                        CONCAT(mar.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') AS vehiculo
                       FROM Orden o 
                       INNER JOIN Orden_Servicio os ON o.id_orden = os.id_orden 
                       JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                       JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                       JOIN Marca mar ON v.id_marca = mar.id_marca
                       JOIN Cliente c ON v.id_cliente = c.id_cliente
                       JOIN Persona per ON c.id_persona = per.id_persona
                       WHERE o.estado != 'eliminado' 
                       HAVING IFNULL(ultimo_estado, '') NOT IN ('Control Calidad', 'Listo', 'Entregado')
                       ORDER BY o.id_orden DESC";
        $data['ordenes'] = $conexion->query($sqlOrdenes)->fetch_all(MYSQLI_ASSOC);
        
        $sqlBahias = "SELECT b.id_bahia, b.descripcion, 
                     (CASE WHEN EXISTS (SELECT 1 FROM taller t JOIN asignacion_orden ao ON t.id_orden = ao.id_orden JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion WHERE t.id_bahia = b.id_bahia AND ap.estado_asignacion IN ('Pendiente', 'En Curso')) THEN 1 ELSE 0 END) as en_uso
                     FROM Bahia b WHERE b.estado = 'activo'";
        $data['bahias'] = $conexion->query($sqlBahias)->fetch_all(MYSQLI_ASSOC);
        
        $sqlMaq = "SELECT m.id_maquinaria, m.nombre, 
                   (CASE WHEN EXISTS (SELECT 1 FROM Orden_Maquinaria om JOIN asignacion_orden ao ON om.id_orden = ao.id_orden JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion WHERE om.id_maquinaria = m.id_maquinaria AND ap.estado_asignacion IN ('Pendiente', 'En Curso')) THEN 1 ELSE 0 END) as en_uso
                   FROM Maquinaria m WHERE m.estado = 'activo'";
        $data['maquinaria'] = $conexion->query($sqlMaq)->fetch_all(MYSQLI_ASSOC);
        
        $data['mecanicos'] = $conexion->query("
            SELECT e.id_empleado, CONCAT(p.nombre, ' ', p.apellido_p) AS nombre_completo 
            FROM Empleado e 
            JOIN Persona p ON e.id_persona = p.id_persona 
            JOIN Puesto pu ON e.id_puesto = pu.id_puesto
            WHERE e.estado = 'activo' AND (pu.nombre LIKE '%Mecanico%' OR pu.nombre LIKE '%Tec. Mecanica%')
        ")->fetch_all(MYSQLI_ASSOC);

        $data['precios'] = $conexion->query("SELECT id_precio, monto FROM Precio WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function cargar_servicios_orden($conexion) {
    $id = (int)($_GET['id_orden'] ?? 0);
    $id_asig_edit = (int)($_GET['id_asig'] ?? 0);
    
    $sqlCC = "SELECT COUNT(*) as cc_count FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = $id AND e.nombre = 'Control Calidad'";
    $paso_por_cc = (int)$conexion->query($sqlCC)->fetch_assoc()['cc_count'] > 0;
    
    $sqlEstado = "SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = $id ORDER BY oe.sec_orden_estado DESC LIMIT 1";
    $resEst = $conexion->query($sqlEstado)->fetch_assoc();
    $estadoOrden = $resEst ? $resEst['nombre'] : '';
    
    $es_devuelta = ($paso_por_cc && in_array($estadoOrden, ['Reparación', 'Diagnóstico', 'En Proceso']));
    
    $exclude_sql = "";
    if (!$es_devuelta) {
        $exclude_sql = " AND os.id_tipo_servicio NOT IN (
            SELECT ap.id_tipo_servicio 
            FROM asignacion_orden ao 
            JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
            WHERE ao.id_orden = $id AND ap.estado != 'eliminado' AND ap.id_asignacion != $id_asig_edit
        )";
    }
    
    $sql = "SELECT os.id_tipo_servicio, ts.nombre AS nombre_servicio, ts.precio 
            FROM Orden_Servicio os 
            JOIN Tipo_Servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio 
            WHERE os.id_orden = $id AND os.estado = 'activo' $exclude_sql";
            
    echo json_encode(['success' => true, 'data' => $conexion->query($sql)->fetch_all(MYSQLI_ASSOC)]);
}

function obtener_asignacion($conexion) {
    $id = (int)$_GET['id'];
    $sql = "SELECT ap.id_asignacion, ao.id_orden, ap.id_tipo_servicio, 
                   DATE(ap.fecha_asignacion) AS fecha_asignacion, 
                   TIME(ap.hora_asignacion) AS hora_asignacion, 
                   t.id_bahia, t.id_precio
            FROM asignacion_personal ap
            JOIN asignacion_orden ao ON ap.id_asignacion = ao.id_asignacion
            LEFT JOIN taller t ON t.id_orden = ao.id_orden
            WHERE ap.id_asignacion = $id LIMIT 1";
    $res = $conexion->query($sql);
    if($data = $res->fetch_assoc()) {
        $idOrd = $data['id_orden'];
        $maquinarias = [];
        $resMaq = $conexion->query("SELECT id_maquinaria FROM Orden_Maquinaria WHERE id_orden = $idOrd AND id_maquinaria IS NOT NULL");
        while($row = $resMaq->fetch_assoc()) { $maquinarias[] = $row['id_maquinaria']; }
        $data['maquinarias'] = $maquinarias;
        
        $mecanicos = [];
        $resMec = $conexion->query("SELECT id_empleado FROM detalle_asignacion_p WHERE id_asignacion = $id AND estado='activo'");
        while($row = $resMec->fetch_assoc()) { $mecanicos[] = $row['id_empleado']; }
        $data['mecanicos'] = $mecanicos;
        
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la asignación.']);
    }
}

function guardar_asignacion($conexion, $usuario) {
    $id_asignacion = $_POST['id_asignacion'] ?? ''; 
    $id_orden = (int)($_POST['id_orden'] ?? 0);
    $id_tipo_serv = (int)($_POST['id_tipo_servicio'] ?? 0);
    $id_bahia = (int)($_POST['id_bahia'] ?? 0);
    $id_precio = (int)($_POST['id_precio'] ?? 0); 
    $fecha_prog = $_POST['fecha_asignacion'] ?? date('Y-m-d');
    $hora_prog = $_POST['hora_asignacion'] ?? date('H:i:s');

    $mecanicos = json_decode(stripslashes($_POST['mecanicos'] ?? '[]'), true);
    $maquinarias = json_decode(stripslashes($_POST['maquinarias'] ?? '[]'), true);

    if(empty($id_orden) || empty($id_tipo_serv) || empty($id_bahia) || empty($id_precio) || empty($mecanicos)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']); return;
    }

    $excludeAsig = !empty($id_asignacion) ? "AND ap.id_asignacion != $id_asignacion" : "";

    // --- RESTAURACIÓN: VALIDACIÓN 1 - SERVICIO DUPLICADO O REWORK ---
    $sqlCheckServicio = "SELECT 1 FROM asignacion_orden ao 
                         JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                         WHERE ao.id_orden = $id_orden AND ap.id_tipo_servicio = $id_tipo_serv 
                         AND ap.estado != 'eliminado' $excludeAsig LIMIT 1";
                         
    if ($conexion->query($sqlCheckServicio)->num_rows > 0) {
        $sqlCC = "SELECT COUNT(*) as cc_count FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = $id_orden AND e.nombre = 'Control Calidad'";
        $paso_por_cc = (int)$conexion->query($sqlCC)->fetch_assoc()['cc_count'] > 0;
        
        $sqlEstadoOrden = "SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = $id_orden ORDER BY oe.sec_orden_estado DESC LIMIT 1";
        $resEstado = $conexion->query($sqlEstadoOrden)->fetch_assoc();
        $estadoActual = $resEstado ? $resEstado['nombre'] : '';
        
        if (!($paso_por_cc && in_array($estadoActual, ['Reparación', 'Diagnóstico', 'En Proceso']))) {
            echo json_encode(['success' => false, 'message' => "❌ Este servicio ya fue realizado. Solo se puede reasignar si la orden ha sido DEVUELTA desde Control de Calidad."]); 
            return;
        }
    }

    // --- RESTAURACIÓN: VALIDACIÓN 2 - BAHÍA OCUPADA ---
    $sqlCheckBahia = "SELECT 1 FROM taller t 
                      JOIN asignacion_orden ao ON t.id_orden = ao.id_orden 
                      JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                      WHERE t.id_bahia = $id_bahia AND ap.estado_asignacion IN ('Pendiente', 'En Curso') 
                      AND ap.estado != 'eliminado' $excludeAsig LIMIT 1";
                      
    if ($conexion->query($sqlCheckBahia)->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "❌ La Bahía seleccionada ya está siendo ocupada por otro trabajo."]); return;
    }

    // --- RESTAURACIÓN: VALIDACIÓN 3 - ESTRICTA DE MECÁNICOS ---
    foreach ($mecanicos as $id_emp) {
        $sqlDisp = "SELECT ap.estado_asignacion, ts.nombre as servicio, CONCAT(p.nombre, ' ', p.apellido_p) as nombre_mec 
                    FROM detalle_asignacion_p dap 
                    JOIN asignacion_personal ap ON dap.id_asignacion = ap.id_asignacion 
                    JOIN Tipo_Servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
                    JOIN Empleado e ON dap.id_empleado = e.id_empleado
                    JOIN Persona p ON e.id_persona = p.id_persona
                    WHERE dap.id_empleado = $id_emp AND ap.estado_asignacion IN ('Pendiente', 'En Curso') 
                    AND ap.estado != 'eliminado' $excludeAsig LIMIT 1";
        $resDisp = $conexion->query($sqlDisp);
        
        if ($resDisp->num_rows > 0) {
            $rowDisp = $resDisp->fetch_assoc();
            echo json_encode(['success' => false, 'message' => "❌ Operación Denegada: El mecánico {$rowDisp['nombre_mec']} no puede recibir esta tarea porque ya tiene un trabajo '{$rowDisp['estado_asignacion']}' ({$rowDisp['servicio']}). Debe finalizarlo primero."]); 
            return;
        }
    }

    $conexion->begin_transaction();
    try {
        $resM = $conexion->query("SELECT monto FROM Precio WHERE id_precio = $id_precio LIMIT 1");
        $montoReal = ($rowM = $resM->fetch_assoc()) ? (float)$rowM['monto'] : 0;
        $conexion->query("UPDATE Orden_Servicio SET precio_estimado = $montoReal WHERE id_orden = $id_orden AND id_tipo_servicio = $id_tipo_serv");

        $fp = $fecha_prog.' '.$hora_prog;

        if (empty($id_asignacion)) {
            $stmtA = $conexion->prepare("INSERT INTO asignacion_personal (id_tipo_servicio, fecha_asignacion, hora_asignacion, estado_asignacion, estado, usuario_creacion) VALUES (?, ?, ?, 'Pendiente', 'activo', ?)"); 
            $stmtA->bind_param("issi", $id_tipo_serv, $fp, $hora_prog, $usuario);
            $stmtA->execute(); $id_asig = $conexion->insert_id;
            $conexion->query("INSERT IGNORE INTO asignacion_orden (id_orden, id_asignacion, estado) VALUES ($id_orden, $id_asig, 'activo')");
            $conexion->query("INSERT INTO taller (id_orden, id_bahia, id_precio, estado, usuario_creacion) VALUES ($id_orden, $id_bahia, $id_precio, 'activo', $usuario) ON DUPLICATE KEY UPDATE id_bahia = $id_bahia, id_precio = $id_precio");
            
            if(!empty($maquinarias)) {
                foreach($maquinarias as $m) { $conexion->query("INSERT INTO Orden_Maquinaria (id_orden, id_maquinaria, tiempo_estimado, estado) VALUES ($id_orden, $m, '01:00:00', 'activo') ON DUPLICATE KEY UPDATE estado='activo'"); }
            }
            $msg = 'Asignación creada exitosamente.';
        } else {
            $id_asig = $id_asignacion;
            $stmtU = $conexion->prepare("UPDATE asignacion_personal SET id_tipo_servicio=?, fecha_asignacion=?, hora_asignacion=? WHERE id_asignacion=?");
            $stmtU->bind_param("issi", $id_tipo_serv, $fp, $hora_prog, $id_asig);
            $stmtU->execute();
            $conexion->query("UPDATE taller SET id_bahia = $id_bahia, id_precio = $id_precio WHERE id_orden = $id_orden");
            $conexion->query("DELETE FROM Orden_Maquinaria WHERE id_orden = $id_orden");
            if(!empty($maquinarias)) {
                foreach($maquinarias as $m) { $conexion->query("INSERT INTO Orden_Maquinaria (id_orden, id_maquinaria, tiempo_estimado, estado) VALUES ($id_orden, $m, '01:00:00', 'activo')"); }
            }
            $conexion->query("DELETE FROM detalle_asignacion_p WHERE id_asignacion = $id_asig");
            $msg = 'Asignación actualizada correctamente.';
        }
        foreach ($mecanicos as $id_emp) { $conexion->query("INSERT INTO detalle_asignacion_p (id_asignacion, id_empleado, estado) VALUES ($id_asig, $id_emp, 'activo')"); }
        $conexion->commit();
        echo json_encode(['success' => true, 'message' => $msg]);
    } catch (Exception $e) { $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
}

function iniciar_tiempo($conexion, $id_usuario_sesion) {
    $id = (int)$_POST['id_asignacion'];
    
    $conexion->begin_transaction();
    try {
        $sqlAdmin = "SELECT n.nombre FROM usuario u JOIN nivel n ON u.id_nivel = n.id_nivel WHERE u.id_usuario = ? LIMIT 1";
        $stmtA = $conexion->prepare($sqlAdmin);
        $stmtA->bind_param("i", $id_usuario_sesion);
        $stmtA->execute();
        $resA = $stmtA->get_result()->fetch_assoc();
        $esAdmin = ($resA && $resA['nombre'] === 'Administrador');

        if (!$esAdmin) {
            $sqlCheck = "SELECT 1 FROM detalle_asignacion_p dap 
                         JOIN Empleado e ON dap.id_empleado = e.id_empleado 
                         JOIN usuario u ON e.id_persona = u.id_persona 
                         WHERE dap.id_asignacion = ? AND u.id_usuario = ? LIMIT 1";
            
            $stmtCheck = $conexion->prepare($sqlCheck);
            
            if (!$stmtCheck) {
                $sqlCheckFallback = "SELECT 1 FROM detalle_asignacion_p dap 
                                     JOIN usuario u ON dap.id_empleado = u.id_empleado 
                                     WHERE dap.id_asignacion = ? AND u.id_usuario = ? LIMIT 1";
                $stmtCheck = $conexion->prepare($sqlCheckFallback);
            }

            if ($stmtCheck) {
                $stmtCheck->bind_param("ii", $id, $id_usuario_sesion);
                $stmtCheck->execute();
                
                if ($stmtCheck->get_result()->num_rows === 0) {
                    throw new Exception("Acceso Denegado: Usted no es el mecánico asignado a este trabajo ni tiene permisos de Administrador.");
                }
            }
        }

        $conexion->query("INSERT INTO registro_tiempos (id_asignacion, hora_inicio, estado, usuario_creacion) VALUES ($id, NOW(), 'activo', $id_usuario_sesion)");
        $conexion->query("UPDATE asignacion_personal SET estado_asignacion = 'En Curso' WHERE id_asignacion = $id");
        
        $sqlBahia = "SELECT t.id_bahia FROM asignacion_orden ao JOIN taller t ON ao.id_orden = t.id_orden WHERE ao.id_asignacion = $id LIMIT 1";
        $resBahia = $conexion->query($sqlBahia)->fetch_assoc();
        if($resBahia && $resBahia['id_bahia']) $conexion->query("UPDATE Bahia SET estado_bahia = 'Ocupada' WHERE id_bahia = {$resBahia['id_bahia']}");

        $sqlMaq = "SELECT om.id_maquinaria FROM asignacion_orden ao JOIN Orden_Maquinaria om ON ao.id_orden = om.id_orden WHERE ao.id_asignacion = $id";
        $resMaq = $conexion->query($sqlMaq);
        while($row = $resMaq->fetch_assoc()) {
            if($row['id_maquinaria']) $conexion->query("UPDATE Maquinaria SET estado_maquina = 'Ocupada' WHERE id_maquinaria = {$row['id_maquinaria']}");
        }

        $conexion->commit(); 
        echo json_encode(['success' => true]);
    } catch (Exception $e) { 
        $conexion->rollback(); 
        echo json_encode(['success' => false, 'message' => $e->getMessage()]); 
    }
}

function finalizar_tiempo($conexion) {
    $id_asignacion = $_POST['id_asignacion_tiempo']; 
    $notas = $_POST['notas_hallazgos'];
    $forzar_calidad = isset($_POST['forzar_calidad']) ? $_POST['forzar_calidad'] : '0'; 
    $usuario = $_SESSION['id_usuario'];

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("UPDATE registro_tiempos SET hora_fin = NOW(), notas_hallazgos = ? WHERE id_asignacion = ? AND hora_fin IS NULL");
        $stmt->bind_param("si", $notas, $id_asignacion); 
        $stmt->execute();
        $conexion->query("UPDATE asignacion_personal SET estado_asignacion = 'Completado' WHERE id_asignacion = $id_asignacion");
        
        $sqlInfo = "SELECT ao.id_orden, t.id_bahia FROM asignacion_orden ao LEFT JOIN taller t ON ao.id_orden = t.id_orden WHERE ao.id_asignacion = $id_asignacion LIMIT 1";
        $resInfo = $conexion->query($sqlInfo)->fetch_assoc();
        $id_orden = $resInfo['id_orden'];
        if($resInfo['id_bahia']) $conexion->query("UPDATE Bahia SET estado_bahia = 'Disponible' WHERE id_bahia = {$resInfo['id_bahia']}");
        $resMaq = $conexion->query("SELECT id_maquinaria FROM Orden_Maquinaria WHERE id_orden = $id_orden");
        while($row = $resMaq->fetch_assoc()) { if($row['id_maquinaria']) $conexion->query("UPDATE Maquinaria SET estado_maquina = 'Activo' WHERE id_maquinaria = {$row['id_maquinaria']}"); }

        $sqlTotal = "SELECT COUNT(DISTINCT id_tipo_servicio) as total FROM Orden_Servicio WHERE id_orden = $id_orden AND estado = 'activo'";
        $totalS = (int)$conexion->query($sqlTotal)->fetch_assoc()['total'];
        $sqlComp = "SELECT COUNT(DISTINCT ap.id_tipo_servicio) as comp FROM asignacion_orden ao JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion WHERE ao.id_orden = $id_orden AND ap.estado_asignacion = 'Completado' AND ap.estado != 'eliminado'";
        $compS = (int)$conexion->query($sqlComp)->fetch_assoc()['comp'];
        $sqlAct = "SELECT COUNT(*) as act FROM asignacion_orden ao JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion WHERE ao.id_orden = $id_orden AND ap.estado_asignacion IN ('Pendiente', 'En Curso') AND ap.estado != 'eliminado'";
        $actS = (int)$conexion->query($sqlAct)->fetch_assoc()['act'];

        if ($forzar_calidad === '1' || ($actS == 0 && $compS >= $totalS)) {
            $montoS = (float)$conexion->query("SELECT SUM(precio_estimado) as t FROM Orden_Servicio WHERE id_orden = $id_orden AND estado = 'activo'")->fetch_assoc()['t'];
            $resR = $conexion->query("SELECT SUM(cantidad * precio_base) as t FROM Orden_Repuesto WHERE id_orden = $id_orden AND estado = 'activo'")->fetch_assoc();
            $montoR = $resR ? (float)$resR['t'] : 0;
            $conexion->query("UPDATE Orden SET monto_total = ".($montoS + $montoR)." WHERE id_orden = $id_orden");
            $id_cc = $conexion->query("SELECT id_estado FROM Estado WHERE nombre = 'Control Calidad' LIMIT 1")->fetch_assoc()['id_estado'];
            $conexion->query("INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_cc, $usuario)");
        }
        $conexion->commit(); echo json_encode(['success' => true, 'message' => 'Servicio Finalizado.']);
    } catch (Exception $e) { $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
}

function eliminar_asignacion($conexion) {
    $id = (int)$_POST['id_asignacion'];
    $conexion->begin_transaction();
    try {
        $conexion->query("UPDATE asignacion_personal SET estado = 'eliminado', estado_asignacion = 'Eliminado' WHERE id_asignacion = $id");
        $id_o = $conexion->query("SELECT id_orden FROM asignacion_orden WHERE id_asignacion = $id LIMIT 1")->fetch_assoc()['id_orden'];
        $quedan = (int)$conexion->query("SELECT COUNT(*) as q FROM asignacion_orden ao JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion WHERE ao.id_orden = $id_o AND ap.estado_asignacion IN ('Pendiente', 'En Curso') AND ap.estado != 'eliminado'")->fetch_assoc()['q'];
        if ($quedan == 0) {
            $id_b = $conexion->query("SELECT id_bahia FROM taller WHERE id_orden = $id_o LIMIT 1")->fetch_assoc()['id_bahia'];
            if($id_b) $conexion->query("UPDATE Bahia SET estado_bahia = 'Disponible' WHERE id_bahia = $id_b");
        }
        $conexion->commit(); echo json_encode(['success' => true, 'message' => 'Eliminado.']);
    } catch(Exception $e) { $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
}
?>