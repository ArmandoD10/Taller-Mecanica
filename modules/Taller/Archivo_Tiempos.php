<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar': listar($conexion); break;
    case 'cargar_dependencias': cargar_dependencias($conexion); break;
    case 'cargar_servicios_orden': cargar_servicios_orden($conexion); break;
    case 'guardar_asignacion': guardar_asignacion($conexion); break;
    case 'obtener_asignacion': obtener_asignacion($conexion); break;
    case 'iniciar_tiempo': iniciar_tiempo($conexion); break;
    case 'finalizar_tiempo': finalizar_tiempo($conexion); break;
    case 'eliminar_asignacion': eliminar_asignacion($conexion); break;
    default: echo json_encode(['success' => false, 'message' => 'Acción no válida']); break;
}

function listar($conexion) {
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
            LEFT JOIN registro_tiempos rt ON ap.id_asignacion = rt.id_asignacion
            WHERE ap.estado != 'eliminado' GROUP BY ap.id_asignacion ORDER BY ap.id_asignacion DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function cargar_dependencias($conexion) {
    $data = [];
    
    $sqlOrdenes = "SELECT DISTINCT o.id_orden, o.descripcion,
                    (SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado 
                     WHERE oe.id_orden = o.id_orden ORDER BY oe.fecha_creacion DESC LIMIT 1) as ultimo_estado,
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
    
    $data['mecanicos'] = $conexion->query("SELECT e.id_empleado, CONCAT(p.nombre, ' ', p.apellido_p) AS nombre_completo FROM Empleado e JOIN Persona p ON e.id_persona = p.id_persona WHERE e.estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    
    $data['precios'] = $conexion->query("SELECT id_precio, monto FROM Precio WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_servicios_orden($conexion) {
    $id = (int)$_GET['id_orden'];
    $id_asig_edit = isset($_GET['id_asig']) ? (int)$_GET['id_asig'] : 0;
    
    $sqlEstado = "SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = $id ORDER BY oe.fecha_creacion DESC LIMIT 1";
    $resEst = $conexion->query($sqlEstado)->fetch_assoc();
    $estadoOrden = $resEst ? $resEst['nombre'] : '';
    
    $rework_states = ['Reparación', 'En Proceso', 'En Reparación', 'Revisión', 'Rechazado'];
    $is_rework = in_array($estadoOrden, $rework_states);
    
    $exclude_sql = "";
    if (!$is_rework) {
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

function guardar_asignacion($conexion) {
    $id_asignacion = $_POST['id_asignacion'] ?? ''; 
    $id_orden = $_POST['id_orden'] ?? '';
    $id_tipo_serv = $_POST['id_tipo_servicio'] ?? '';
    $id_bahia = $_POST['id_bahia'] ?? '';
    $id_precio = $_POST['id_precio'] ?? ''; 
    $fecha_prog = $_POST['fecha_asignacion'] ?? date('Y-m-d');
    $hora_prog = $_POST['hora_asignacion'] ?? date('H:i:s');
    $usuario = $_SESSION['id_usuario'] ?? 1;

    $mecanicos = json_decode(stripslashes($_POST['mecanicos'] ?? '[]'), true);
    $maquinarias = json_decode(stripslashes($_POST['maquinarias'] ?? '[]'), true);

    if(empty($id_orden) || empty($id_tipo_serv) || empty($id_bahia) || empty($id_precio) || empty($mecanicos)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']); return;
    }

    $excludeAsig = !empty($id_asignacion) ? "AND ap.id_asignacion != $id_asignacion" : "";

    $sqlCheckServicio = "SELECT 1 FROM asignacion_orden ao 
                         JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                         WHERE ao.id_orden = $id_orden AND ap.id_tipo_servicio = $id_tipo_serv 
                         AND ap.estado != 'eliminado' $excludeAsig LIMIT 1";
                         
    if ($conexion->query($sqlCheckServicio)->num_rows > 0) {
        $sqlEstadoOrden = "SELECT e.nombre FROM Orden_Estado oe 
                           JOIN Estado e ON oe.id_estado = e.id_estado 
                           WHERE oe.id_orden = $id_orden 
                           ORDER BY oe.fecha_creacion DESC LIMIT 1";
        $resEstado = $conexion->query($sqlEstadoOrden)->fetch_assoc();
        $estadoActual = $resEstado ? $resEstado['nombre'] : '';
        
        if (!in_array($estadoActual, ['Reparación', 'En Proceso', 'En Reparación', 'Revisión', 'Rechazado'])) {
            echo json_encode(['success' => false, 'message' => "Este servicio ya fue asignado a esta orden. Solo puedes volver a asignarlo si la orden fue devuelta por Control de Calidad."]); 
            return;
        }
    }

    $sqlCheckBahia = "SELECT 1 FROM taller t 
                      JOIN asignacion_orden ao ON t.id_orden = ao.id_orden 
                      JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                      WHERE t.id_bahia = $id_bahia 
                      AND ap.estado_asignacion IN ('Pendiente', 'En Curso') 
                      AND ao.id_orden != $id_orden LIMIT 1";
                      
    if ($conexion->query($sqlCheckBahia)->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "La Bahía seleccionada está siendo ocupada por OTRO vehículo."]); return;
    }

    foreach ($mecanicos as $id_emp) {
        $sqlHorario = "SELECT d.hora_ini, d.hora_fin, CONCAT(per.nombre, ' ', per.apellido_p) as nombre 
                       FROM Empleado e 
                       JOIN Puesto p ON e.id_puesto = p.id_puesto 
                       JOIN Departamento d ON p.id_departamento = d.id_departamento 
                       JOIN Persona per ON e.id_persona = per.id_persona 
                       WHERE e.id_empleado = $id_emp";
        $resH = $conexion->query($sqlHorario)->fetch_assoc();
        
        if ($resH && ($hora_prog < $resH['hora_ini'] || $hora_prog > $resH['hora_fin'])) {
            echo json_encode(['success' => false, 'message' => "El empleado {$resH['nombre']} está fuera de su horario ({$resH['hora_ini']} - {$resH['hora_fin']})."]); 
            return;
        }

        $sqlDisp = "SELECT COUNT(*) as ocupado 
                    FROM detalle_asignacion_p dap 
                    JOIN asignacion_personal ap ON dap.id_asignacion = ap.id_asignacion 
                    WHERE dap.id_empleado = $id_emp AND ap.estado_asignacion = 'En Curso' $excludeAsig";
        $resD = $conexion->query($sqlDisp)->fetch_assoc();
        
        if ($resD['ocupado'] > 0) {
            echo json_encode(['success' => false, 'message' => "El empleado {$resH['nombre']} ya tiene un trabajo EN CURSO."]); 
            return;
        }
    }

    $conexion->begin_transaction();
    try {
        $fp = $fecha_prog.' '.$hora_prog;

        if (empty($id_asignacion)) {
            $stmtA = $conexion->prepare("INSERT INTO asignacion_personal (id_tipo_servicio, fecha_asignacion, hora_asignacion, estado_asignacion, estado, usuario_creacion) VALUES (?, ?, ?, 'Pendiente', 'activo', ?)"); 
            $stmtA->bind_param("issi", $id_tipo_serv, $fp, $hora_prog, $usuario);
            $stmtA->execute(); $id_asig = $conexion->insert_id;

            $conexion->query("INSERT IGNORE INTO asignacion_orden (id_orden, id_asignacion, estado) VALUES ($id_orden, $id_asig, 'activo')");
            
            $checkTaller = $conexion->query("SELECT 1 FROM taller WHERE id_orden = $id_orden");
            if($checkTaller->num_rows > 0) {
                $conexion->query("UPDATE taller SET id_bahia = $id_bahia, id_precio = $id_precio WHERE id_orden = $id_orden");
            } else {
                $conexion->query("INSERT INTO taller (id_orden, id_bahia, id_precio, estado, usuario_creacion) VALUES ($id_orden, $id_bahia, $id_precio, 'activo', $usuario)");
            }
            
            if(!empty($maquinarias)) {
                $stmtMaq = $conexion->prepare("INSERT INTO Orden_Maquinaria (id_orden, id_maquinaria, tiempo_estimado, estado) VALUES (?, ?, '01:00:00', 'activo') ON DUPLICATE KEY UPDATE estado='activo'");
                foreach($maquinarias as $m) { $stmtMaq->bind_param("ii", $id_orden, $m); $stmtMaq->execute(); }
            }
            
            $resEstRep = $conexion->query("SELECT id_estado FROM Estado WHERE nombre IN ('Reparación', 'En Proceso', 'Proceso', 'En Reparación') LIMIT 1");
            if($resEstRep->num_rows > 0) {
                $id_est = $resEstRep->fetch_assoc()['id_estado'];
                $conexion->query("INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_est, $usuario) ON DUPLICATE KEY UPDATE fecha_creacion = CURRENT_TIMESTAMP");
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
                $stmtMaq = $conexion->prepare("INSERT INTO Orden_Maquinaria (id_orden, id_maquinaria, tiempo_estimado, estado) VALUES (?, ?, '01:00:00', 'activo') ON DUPLICATE KEY UPDATE estado='activo'");
                foreach($maquinarias as $m) { $stmtMaq->bind_param("ii", $id_orden, $m); $stmtMaq->execute(); }
            }

            $conexion->query("DELETE FROM detalle_asignacion_p WHERE id_asignacion = $id_asig");
            $msg = 'Asignación actualizada correctamente.';
        }

        $stmtDet = $conexion->prepare("INSERT INTO detalle_asignacion_p (id_asignacion, id_empleado, estado) VALUES (?, ?, 'activo')");
        foreach ($mecanicos as $id_emp) { $stmtDet->bind_param("ii", $id_asig, $id_emp); $stmtDet->execute(); }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => $msg]);
    } catch (Exception $e) {
        $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function iniciar_tiempo($conexion) {
    $id = $_POST['id_asignacion']; $usuario = $_SESSION['id_usuario'] ?? 1;
    $conexion->begin_transaction();
    try {
        $conexion->query("INSERT INTO registro_tiempos (id_asignacion, hora_inicio, estado, usuario_creacion) VALUES ($id, NOW(), 'activo', $usuario)");
        $conexion->query("UPDATE asignacion_personal SET estado_asignacion = 'En Curso' WHERE id_asignacion = $id");
        
        $sqlBahia = "SELECT t.id_bahia FROM asignacion_orden ao JOIN taller t ON ao.id_orden = t.id_orden WHERE ao.id_asignacion = $id LIMIT 1";
        $resBahia = $conexion->query($sqlBahia)->fetch_assoc();
        if($resBahia && $resBahia['id_bahia']) $conexion->query("UPDATE Bahia SET estado_bahia = 'Ocupada' WHERE id_bahia = {$resBahia['id_bahia']}");

        $sqlMaq = "SELECT om.id_maquinaria FROM asignacion_orden ao JOIN Orden_Maquinaria om ON ao.id_orden = om.id_orden WHERE ao.id_asignacion = $id AND om.id_maquinaria IS NOT NULL";
        $resMaq = $conexion->query($sqlMaq);
        while($row = $resMaq->fetch_assoc()) {
            $conexion->query("UPDATE Maquinaria SET estado_maquina = 'Ocupada' WHERE id_maquinaria = {$row['id_maquinaria']}");
        }

        $conexion->commit(); echo json_encode(['success' => true]);
    } catch (Exception $e) { $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
}

function finalizar_tiempo($conexion) {
    $id_asignacion = $_POST['id_asignacion_tiempo']; 
    $notas = $_POST['notas_hallazgos'];
    $forzar_calidad = isset($_POST['forzar_calidad']) ? $_POST['forzar_calidad'] : '0'; 
    
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

        $sqlMaq = "SELECT om.id_maquinaria FROM Orden_Maquinaria om WHERE om.id_orden = $id_orden AND om.id_maquinaria IS NOT NULL";
        $resMaq = $conexion->query($sqlMaq);
        while($row = $resMaq->fetch_assoc()) {
            $conexion->query("UPDATE Maquinaria SET estado_maquina = 'Activo' WHERE id_maquinaria = {$row['id_maquinaria']}");
        }

        $sqlTotalServicios = "SELECT COUNT(DISTINCT id_tipo_servicio) as total FROM Orden_Servicio WHERE id_orden = $id_orden AND estado = 'activo'";
        $totalServicios = (int)$conexion->query($sqlTotalServicios)->fetch_assoc()['total'];

        $sqlFechaEstado = "SELECT fecha_creacion FROM Orden_Estado WHERE id_orden = $id_orden ORDER BY fecha_creacion DESC LIMIT 1";
        $resFecha = $conexion->query($sqlFechaEstado)->fetch_assoc();
        $fecha_estado_actual = $resFecha ? $resFecha['fecha_creacion'] : '2000-01-01 00:00:00';

        $sqlPendientes = "SELECT COUNT(*) as activos FROM asignacion_orden ao 
                          JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                          WHERE ao.id_orden = $id_orden AND ap.estado_asignacion IN ('Pendiente', 'En Curso') AND ap.estado != 'eliminado'";
        $activos = (int)$conexion->query($sqlPendientes)->fetch_assoc()['activos'];

        $sqlCompletadosFase = "SELECT COUNT(DISTINCT ap.id_tipo_servicio) as completados 
                               FROM asignacion_orden ao 
                               JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                               JOIN registro_tiempos rt ON ap.id_asignacion = rt.id_asignacion
                               WHERE ao.id_orden = $id_orden AND ap.estado_asignacion = 'Completado' AND ap.estado != 'eliminado' 
                               AND rt.hora_fin >= '$fecha_estado_actual'";
        $completados_fase = (int)$conexion->query($sqlCompletadosFase)->fetch_assoc()['completados'];

        if ($forzar_calidad === '1' || ($activos == 0 && $completados_fase >= $totalServicios)) {
            
            // MAGIA ANTI-DOBLE COBRO (Solo aplica a servicios. Los repuestos se suman todos)
            $sqlServicios = "SELECT ap.id_tipo_servicio 
                             FROM asignacion_orden ao 
                             JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                             WHERE ao.id_orden = $id_orden AND ap.estado != 'eliminado' ORDER BY ap.id_asignacion ASC";
            $resServicios = $conexion->query($sqlServicios)->fetch_all(MYSQLI_ASSOC);
            
            $sqlPrecios = "SELECT p.monto FROM taller t JOIN Precio p ON t.id_precio = p.id_precio WHERE t.id_orden = $id_orden AND t.estado = 'activo' ORDER BY t.id_taller ASC";
            $resPrecios = $conexion->query($sqlPrecios)->fetch_all(MYSQLI_ASSOC);
            
            $monto_servicios = 0;
            $vistos = [];
            foreach ($resServicios as $index => $serv) {
                $id_serv = $serv['id_tipo_servicio'];
                // Filtramos servicios duplicados
                if (!in_array($id_serv, $vistos)) {
                    $vistos[] = $id_serv;
                    $monto_servicios += isset($resPrecios[$index]) ? (float)$resPrecios[$index]['monto'] : 0;
                }
            }

            // Sumamos TODOS los repuestos sin filtrar
            $sqlRepuestos = "SELECT SUM(cantidad * precio_base) as total_repuestos FROM Orden_Repuesto WHERE id_orden = $id_orden AND estado = 'activo'";
            $resRep = $conexion->query($sqlRepuestos)->fetch_assoc();
            $monto_repuestos = $resRep ? (float)$resRep['total_repuestos'] : 0;

            // Total Final para la Orden
            $monto_total = $monto_servicios + $monto_repuestos;

            $stmtUpd = $conexion->prepare("UPDATE Orden SET monto_total = ? WHERE id_orden = ?");
            $stmtUpd->bind_param("di", $monto_total, $id_orden);
            $stmtUpd->execute();
            
            $usuario = $_SESSION['id_usuario'] ?? 1;
            
            $resEstCC = $conexion->query("SELECT id_estado FROM Estado WHERE nombre = 'Control Calidad' LIMIT 1");
            if($resEstCC->num_rows > 0) {
                $id_estado_cc = $resEstCC->fetch_assoc()['id_estado'];
                $checkEstCC = $conexion->query("SELECT 1 FROM Orden_Estado WHERE id_orden = $id_orden AND id_estado = $id_estado_cc");
                if($checkEstCC->num_rows > 0) {
                    $conexion->query("UPDATE Orden_Estado SET fecha_creacion = CURRENT_TIMESTAMP WHERE id_orden = $id_orden AND id_estado = $id_estado_cc");
                } else {
                    $conexion->query("INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_estado_cc, $usuario)");
                }
            }
        }

        $conexion->commit(); 
        echo json_encode(['success' => true, 'message' => 'Servicio Finalizado exitosamente.']);
    } catch (Exception $e) { 
        $conexion->rollback(); 
        echo json_encode(['success' => false, 'message' => $e->getMessage()]); 
    }
}

function eliminar_asignacion($conexion) {
    $id = (int)$_POST['id_asignacion'];
    $conexion->begin_transaction();
    try {
        $conexion->query("UPDATE asignacion_personal SET estado = 'eliminado', estado_asignacion = 'Eliminado' WHERE id_asignacion = $id");
        
        $sqlOrden = "SELECT id_orden FROM asignacion_orden WHERE id_asignacion = $id LIMIT 1";
        $resOrden = $conexion->query($sqlOrden)->fetch_assoc();
        if($resOrden) {
            $id_orden = $resOrden['id_orden'];
            
            $sqlActivas = "SELECT COUNT(*) as quedan FROM asignacion_orden ao 
                           JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                           WHERE ao.id_orden = $id_orden AND ap.estado_asignacion IN ('Pendiente', 'En Curso') 
                           AND ap.estado != 'eliminado'";
            $quedan = (int)$conexion->query($sqlActivas)->fetch_assoc()['quedan'];
            
            if ($quedan == 0) {
                $sqlBahia = "SELECT id_bahia FROM taller WHERE id_orden = $id_orden LIMIT 1";
                $resB = $conexion->query($sqlBahia)->fetch_assoc();
                if($resB && $resB['id_bahia']) {
                    $conexion->query("UPDATE Bahia SET estado_bahia = 'Disponible' WHERE id_bahia = {$resB['id_bahia']}");
                }
                $sqlMaq = "SELECT id_maquinaria FROM Orden_Maquinaria WHERE id_orden = $id_orden AND id_maquinaria IS NOT NULL";
                $resM = $conexion->query($sqlMaq);
                while($row = $resM->fetch_assoc()) {
                    $conexion->query("UPDATE Maquinaria SET estado_maquina = 'Activo' WHERE id_maquinaria = {$row['id_maquinaria']}");
                }
            }
        }
        
        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Asignación eliminada y recursos liberados correctamente.']);
    } catch(Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>