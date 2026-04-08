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
    
    // =========================================================================================
    // AÑADIDO: Extracción de datos del Cliente y Vehículo para mostrarlos en el Frontend
    // =========================================================================================
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
    $sql = "SELECT os.id_tipo_servicio, ts.nombre AS nombre_servicio, ts.precio 
            FROM Orden_Servicio os 
            JOIN Tipo_Servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio 
            WHERE os.id_orden = $id AND os.estado = 'activo'";
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

    $sqlCheckBahia = "SELECT ap.id_asignacion FROM taller t JOIN asignacion_orden ao ON t.id_orden = ao.id_orden JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion WHERE t.id_bahia = $id_bahia AND ap.estado_asignacion IN ('Pendiente', 'En Curso') $excludeAsig";
    if ($conexion->query($sqlCheckBahia)->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "La Bahía seleccionada ya está reservada o en uso."]); return;
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
            echo json_encode(['success' => false, 'message' => "El empleado {$resH['nombre']} ya tiene un trabajo EN CURSO actualmente."]); 
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

            $conexion->query("INSERT INTO asignacion_orden (id_orden, id_asignacion, estado) VALUES ($id_orden, $id_asig, 'activo')");
            $conexion->query("INSERT INTO taller (id_orden, id_bahia, id_precio, estado, usuario_creacion) VALUES ($id_orden, $id_bahia, $id_precio, 'activo', $usuario)");
            
            if(!empty($maquinarias)) {
                $stmtMaq = $conexion->prepare("INSERT INTO Orden_Maquinaria (id_orden, id_maquinaria, tiempo_estimado, estado) VALUES (?, ?, '01:00:00', 'activo')");
                foreach($maquinarias as $m) { $stmtMaq->bind_param("ii", $id_orden, $m); $stmtMaq->execute(); }
            }
            
            $resEstRep = $conexion->query("SELECT id_estado FROM Estado WHERE nombre = 'Reparación' LIMIT 1");
            if($resEstRep->num_rows > 0) {
                $id_est = $resEstRep->fetch_assoc()['id_estado'];
                $conexion->query("INSERT IGNORE INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_est, $usuario)");
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
                $stmtMaq = $conexion->prepare("INSERT INTO Orden_Maquinaria (id_orden, id_maquinaria, tiempo_estimado, estado) VALUES (?, ?, '01:00:00', 'activo')");
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

        $sqlCheck = "SELECT COUNT(*) as pendientes FROM asignacion_orden ao JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion WHERE ao.id_orden = $id_orden AND ap.estado_asignacion != 'Completado'";
        $resCheck = $conexion->query($sqlCheck)->fetch_assoc();

        if ($resCheck['pendientes'] == 0) {
            
            $sqlSum = "SELECT SUM(p.monto) as total_monto FROM taller t JOIN Precio p ON t.id_precio = p.id_precio WHERE t.id_orden = $id_orden AND t.estado = 'activo'";
            $monto_total = (float)$conexion->query($sqlSum)->fetch_assoc()['total_monto'];

            $stmtUpd = $conexion->prepare("UPDATE Orden SET monto_total = ? WHERE id_orden = ?");
            $stmtUpd->bind_param("di", $monto_total, $id_orden);
            $stmtUpd->execute();
            
            $usuario = $_SESSION['id_usuario'] ?? 1;
            
            $resEstCC = $conexion->query("SELECT id_estado FROM Estado WHERE nombre = 'Control Calidad' LIMIT 1");
            
            if($resEstCC->num_rows > 0) {
                $id_estado_cc = $resEstCC->fetch_assoc()['id_estado'];
                $conexion->query("INSERT INTO Orden_Estado (id_orden, id_estado, usuario_creacion) VALUES ($id_orden, $id_estado_cc, $usuario)");
            }
        }

        $conexion->commit(); 
        echo json_encode(['success' => true, 'message' => 'Finalizado. Las dependencias fueron liberadas.']);
    } catch (Exception $e) { 
        $conexion->rollback(); 
        echo json_encode(['success' => false, 'message' => $e->getMessage()]); 
    }
}
?>