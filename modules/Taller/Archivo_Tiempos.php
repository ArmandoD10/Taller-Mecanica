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
    
    $data['mecanicos'] = $conexion->query("SELECT e.id_empleado, CONCAT(p.nombre, ' ', p.apellido_p) AS nombre_completo FROM Empleado e JOIN Persona p ON e.id_persona = p.id_persona WHERE e.estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['precios'] = $conexion->query("SELECT id_precio, monto FROM Precio WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_servicios_orden($conexion) {
    $id = (int)($_GET['id_orden'] ?? 0);
    $id_asig_edit = (int)($_GET['id_asig'] ?? 0);
    
    // 1. ¿Esta orden pasó por Control de Calidad alguna vez en su historia?
    $sqlCC = "SELECT COUNT(*) as cc_count FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = $id AND e.nombre = 'Control Calidad'";
    $paso_por_cc = (int)$conexion->query($sqlCC)->fetch_assoc()['cc_count'] > 0;
    
    // 2. ¿Cuál es su estado actual?
    $sqlEstado = "SELECT e.nombre FROM Orden_Estado oe JOIN Estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = $id ORDER BY oe.sec_orden_estado DESC LIMIT 1";
    $resEst = $conexion->query($sqlEstado)->fetch_assoc();
    $estadoOrden = $resEst ? $resEst['nombre'] : '';
    
    // Lógica Rework: Es devuelta SI pasó por CC y actualmente está de regreso en Reparación o Diagnóstico
    $es_devuelta = ($paso_por_cc && in_array($estadoOrden, ['Reparación', 'Diagnóstico', 'En Proceso']));
    
    $exclude_sql = "";
    if (!$es_devuelta) {
        // Bloqueo normal: Ocultar servicios que ya fueron asignados a mecánicos
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
    $id_orden = (int)($_POST['id_orden'] ?? 0);
    $id_tipo_serv = (int)($_POST['id_tipo_servicio'] ?? 0);
    $id_bahia = (int)($_POST['id_bahia'] ?? 0);
    $id_precio = (int)($_POST['id_precio'] ?? 0); 
    $fecha_prog = $_POST['fecha_asignacion'] ?? date('Y-m-d');
    $hora_prog = $_POST['hora_asignacion'] ?? date('H:i:s');
    $usuario = $_SESSION['id_usuario'] ?? 1;

    $mecanicos = json_decode(stripslashes($_POST['mecanicos'] ?? '[]'), true);
    $maquinarias = json_decode(stripslashes($_POST['maquinarias'] ?? '[]'), true);

    if(empty($id_orden) || empty($id_tipo_serv) || empty($id_bahia) || empty($id_precio) || empty($mecanicos)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']); return;
    }

    // --- VALIDACIÓN 1: ¿Intenta reasignar un servicio duplicado sin permiso de CC? ---
    $excludeAsig = !empty($id_asignacion) ? "AND ap.id_asignacion != $id_asignacion" : "";
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

    // --- VALIDACIÓN 2: Bahías y Horarios ---
    $sqlCheckBahia = "SELECT 1 FROM taller t 
                      JOIN asignacion_orden ao ON t.id_orden = ao.id_orden 
                      JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                      WHERE t.id_bahia = $id_bahia AND ap.estado_asignacion IN ('Pendiente', 'En Curso') 
                      AND ao.id_orden != $id_orden LIMIT 1";
                      
    if ($conexion->query($sqlCheckBahia)->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "La Bahía seleccionada está siendo ocupada por OTRO vehículo."]); return;
    }

    foreach ($mecanicos as $id_emp) {
        $sqlHorario = "SELECT d.hora_ini, d.hora_fin, CONCAT(per.nombre, ' ', per.apellido_p) as nombre 
                       FROM Empleado e JOIN Puesto p ON e.id_puesto = p.id_puesto 
                       JOIN Departamento d ON p.id_departamento = d.id_departamento 
                       JOIN Persona per ON e.id_persona = per.id_persona WHERE e.id_empleado = $id_emp";
        $resH = $conexion->query($sqlHorario)->fetch_assoc();
        if ($resH && ($hora_prog < $resH['hora_ini'] || $hora_prog > $resH['hora_fin'])) {
            echo json_encode(['success' => false, 'message' => "El empleado {$resH['nombre']} está fuera de su horario ({$resH['hora_ini']} - {$resH['hora_fin']})."]); return;
        }

        $sqlDisp = "SELECT COUNT(*) as ocupado FROM detalle_asignacion_p dap 
                    JOIN asignacion_personal ap ON dap.id_asignacion = ap.id_asignacion 
                    WHERE dap.id_empleado = $id_emp AND ap.estado_asignacion = 'En Curso' $excludeAsig";
        if ($conexion->query($sqlDisp)->fetch_assoc()['ocupado'] > 0) {
            echo json_encode(['success' => false, 'message' => "El empleado {$resH['nombre']} ya tiene un trabajo EN CURSO."]); return;
        }
    }

    $conexion->begin_transaction();
    try {
        // Obtenemos el monto real y lo guardamos individualmente
        $resM = $conexion->query("SELECT monto FROM Precio WHERE id_precio = $id_precio LIMIT 1");
        $montoReal = ($rowM = $resM->fetch_assoc()) ? (float)$rowM['monto'] : 0;
        $conexion->query("UPDATE Orden_Servicio SET precio_estimado = $montoReal WHERE id_orden = $id_orden AND id_tipo_servicio = $id_tipo_serv");

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
        // 1. Terminar el tiempo y actualizar la asignación
        $stmt = $conexion->prepare("UPDATE registro_tiempos SET hora_fin = NOW(), notas_hallazgos = ? WHERE id_asignacion = ? AND hora_fin IS NULL");
        $stmt->bind_param("si", $notas, $id_asignacion); 
        $stmt->execute();
        
        $conexion->query("UPDATE asignacion_personal SET estado_asignacion = 'Completado' WHERE id_asignacion = $id_asignacion");
        
        // 2. Liberar la bahía y la maquinaria
        $sqlInfo = "SELECT ao.id_orden, t.id_bahia FROM asignacion_orden ao LEFT JOIN taller t ON ao.id_orden = t.id_orden WHERE ao.id_asignacion = $id_asignacion LIMIT 1";
        $resInfo = $conexion->query($sqlInfo)->fetch_assoc();
        $id_orden = $resInfo['id_orden'];
        
        if($resInfo['id_bahia']) {
            $conexion->query("UPDATE Bahia SET estado_bahia = 'Disponible' WHERE id_bahia = {$resInfo['id_bahia']}");
        }

        $sqlMaq = "SELECT om.id_maquinaria FROM Orden_Maquinaria om WHERE om.id_orden = $id_orden AND om.id_maquinaria IS NOT NULL";
        $resMaq = $conexion->query($sqlMaq);
        while($row = $resMaq->fetch_assoc()) {
            $conexion->query("UPDATE Maquinaria SET estado_maquina = 'Activo' WHERE id_maquinaria = {$row['id_maquinaria']}");
        }

        // 3. LÓGICA MATEMÁTICA PARA EL PASE A CONTROL DE CALIDAD
        // Total de servicios distintos que exige la orden
        $sqlTotalServicios = "SELECT COUNT(DISTINCT id_tipo_servicio) as total FROM Orden_Servicio WHERE id_orden = $id_orden AND estado = 'activo'";
        $totalServicios = (int)$conexion->query($sqlTotalServicios)->fetch_assoc()['total'];

        // Total de servicios distintos que YA fueron completados
        $sqlCompletados = "SELECT COUNT(DISTINCT ap.id_tipo_servicio) as completados 
                           FROM asignacion_orden ao 
                           JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                           WHERE ao.id_orden = $id_orden AND ap.estado_asignacion = 'Completado' AND ap.estado != 'eliminado'";
        $completados = (int)$conexion->query($sqlCompletados)->fetch_assoc()['completados'];

        // ¿Hay mecánicos aún trabajando en esta orden?
        $sqlPendientes = "SELECT COUNT(*) as activos FROM asignacion_orden ao 
                          JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                          WHERE ao.id_orden = $id_orden AND ap.estado_asignacion IN ('Pendiente', 'En Curso') AND ap.estado != 'eliminado'";
        $activos = (int)$conexion->query($sqlPendientes)->fetch_assoc()['activos'];

        // CONDICIÓN INFALIBLE: Forzado manual OR (Nadie más está trabajando Y se completaron todos los servicios)
        if ($forzar_calidad === '1' || ($activos == 0 && $completados >= $totalServicios)) {
            
            // Actualizamos los montos (La corrección de precios que hicimos antes)
            $sqlSum = "SELECT SUM(precio_estimado) as total_serv FROM Orden_Servicio WHERE id_orden = $id_orden AND estado = 'activo'";
            $monto_servicios = (float)$conexion->query($sqlSum)->fetch_assoc()['total_serv'];

            $sqlRepuestos = "SELECT SUM(cantidad * precio_base) as total_repuestos FROM Orden_Repuesto WHERE id_orden = $id_orden AND estado = 'activo'";
            $resRep = $conexion->query($sqlRepuestos)->fetch_assoc();
            $monto_repuestos = $resRep ? (float)$resRep['total_repuestos'] : 0;

            $monto_total = $monto_servicios + $monto_repuestos;

            $stmtUpd = $conexion->prepare("UPDATE Orden SET monto_total = ? WHERE id_orden = ?");
            $stmtUpd->bind_param("di", $monto_total, $id_orden);
            $stmtUpd->execute();
            
            // Cambiamos la Orden a Control Calidad
            $resEstCC = $conexion->query("SELECT id_estado FROM Estado WHERE nombre = 'Control Calidad' LIMIT 1");
            if($resEstCC->num_rows > 0) {
                $id_estado_cc = $resEstCC->fetch_assoc()['id_estado'];
                // Borramos y reinsertamos para asegurar que el AUTO_INCREMENT de 'sec_orden_estado' suba correctamente
                $conexion->query("DELETE FROM Orden_Estado WHERE id_orden = $id_orden AND id_estado = $id_estado_cc");
                $conexion->query("INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_estado_cc, {$_SESSION['id_usuario']})");
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