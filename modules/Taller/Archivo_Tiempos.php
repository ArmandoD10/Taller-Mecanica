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
            JOIN orden o ON ao.id_orden = o.id_orden
            JOIN tipo_servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
            LEFT JOIN detalle_asignacion_p dap ON ap.id_asignacion = dap.id_asignacion
            LEFT JOIN empleado e ON dap.id_empleado = e.id_empleado
            LEFT JOIN persona p ON e.id_persona = p.id_persona
            LEFT JOIN registro_tiempos rt ON ap.id_asignacion = rt.id_asignacion
            WHERE ap.estado != 'eliminado' GROUP BY ap.id_asignacion ORDER BY ap.id_asignacion DESC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function cargar_dependencias($conexion) {
    $data = [];
    $data['ordenes'] = $conexion->query("SELECT DISTINCT o.id_orden, o.descripcion FROM orden o INNER JOIN orden_servicio os ON o.id_orden = os.id_orden WHERE o.estado != 'eliminado' AND (o.estado_orden != 'Entregado' OR o.estado_orden IS NULL)")->fetch_all(MYSQLI_ASSOC);
    $data['bahias'] = $conexion->query("SELECT id_bahia, descripcion, estado_bahia FROM bahia WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['maquinaria'] = $conexion->query("SELECT id_maquinaria, nombre, estado_maquina FROM maquinaria WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['mecanicos'] = $conexion->query("SELECT e.id_empleado, CONCAT(p.nombre, ' ', p.apellido_p) AS nombre_completo FROM empleado e JOIN persona p ON e.id_persona = p.id_persona WHERE e.estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_servicios_orden($conexion) {
    $id = (int)$_GET['id_orden'];
    $sql = "SELECT os.id_tipo_servicio, ts.nombre AS nombre_servicio FROM orden_servicio os JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio WHERE os.id_orden = $id AND os.estado = 'activo'";
    echo json_encode(['success' => true, 'data' => $conexion->query($sql)->fetch_all(MYSQLI_ASSOC)]);
}

function obtener_asignacion($conexion) {
    $id = (int)$_GET['id'];
    $sql = "SELECT ap.id_asignacion, ao.id_orden, ap.id_tipo_servicio, 
                   DATE(ap.fecha_asignacion) AS fecha_asignacion, 
                   TIME(ap.hora_asignacion) AS hora_asignacion, 
                   t.id_bahia, do.id_maquinaria 
            FROM asignacion_personal ap
            JOIN asignacion_orden ao ON ap.id_asignacion = ao.id_asignacion
            LEFT JOIN taller t ON t.id_orden = ao.id_orden
            LEFT JOIN detalle_orden do ON do.id_orden = ao.id_orden
            WHERE ap.id_asignacion = $id LIMIT 1";
    
    $res = $conexion->query($sql);
    if($data = $res->fetch_assoc()) {
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
    $id_maq = $_POST['id_maquinaria'] ?? null;
    $mecanicos = isset($_POST['mecanicos']) ? json_decode($_POST['mecanicos']) : [];
    $fecha_prog = $_POST['fecha_asignacion'] ?? date('Y-m-d');
    $hora_prog = $_POST['hora_asignacion'] ?? date('H:i:s');
    $usuario = $_SESSION['id_usuario'] ?? 1;

    if(empty($id_orden) || empty($mecanicos) || empty($id_bahia) || empty($id_tipo_serv)) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios.']); return;
    }

    // --- 1. VALIDACIÓN DE BAHÍA ---
    // Verificamos si la bahía está realmente ocupada por un trabajo que ya inició
    $resBahia = $conexion->query("SELECT estado_bahia FROM bahia WHERE id_bahia = $id_bahia")->fetch_assoc();
    if($resBahia && $resBahia['estado_bahia'] === 'Ocupada') {
        echo json_encode(['success' => false, 'message' => "La Bahía seleccionada ya se encuentra en uso por otro trabajo."]); return;
    }

    // --- 2. VALIDACIÓN DE MAQUINARIA ---
    if(!empty($id_maq)) {
        $resMaq = $conexion->query("SELECT estado_maquina, nombre FROM maquinaria WHERE id_maquinaria = $id_maq")->fetch_assoc();
        if($resMaq && ($resMaq['estado_maquina'] === 'Ocupada' || $resMaq['estado_maquina'] === 'En Uso')) {
            echo json_encode(['success' => false, 'message' => "La maquinaria ({$resMaq['nombre']}) ya se encuentra siendo utilizada."]); return;
        }
    }

    // --- 3. VALIDACIÓN DE MECÁNICOS ---
    foreach ($mecanicos as $id_emp) {
        $sqlHorario = "SELECT d.hora_ini, d.hora_fin, CONCAT(per.nombre, ' ', per.apellido_p) as nombre 
                       FROM empleado e JOIN puesto p ON e.id_puesto = p.id_puesto 
                       JOIN departamento d ON p.id_departamento = d.id_departamento 
                       JOIN persona per ON e.id_persona = per.id_persona WHERE e.id_empleado = $id_emp";
        $resH = $conexion->query($sqlHorario)->fetch_assoc();
        if ($resH && ($hora_prog < $resH['hora_ini'] || $hora_prog > $resH['hora_fin'])) {
            echo json_encode(['success' => false, 'message' => "El empleado {$resH['nombre']} está fuera de su horario ({$resH['hora_ini']} - {$resH['hora_fin']})."]); return;
        }

        $excludeAsig = !empty($id_asignacion) ? "AND ap.id_asignacion != $id_asignacion" : "";
        $sqlDisp = "SELECT COUNT(*) as ocupado FROM detalle_asignacion_p dap 
                    JOIN asignacion_personal ap ON dap.id_asignacion = ap.id_asignacion 
                    WHERE dap.id_empleado = $id_emp AND ap.estado_asignacion = 'En Curso' $excludeAsig";
        $resD = $conexion->query($sqlDisp)->fetch_assoc();
        if ($resD['ocupado'] > 0) {
            echo json_encode(['success' => false, 'message' => "El empleado {$resH['nombre']} ya tiene un trabajo EN CURSO actualmente."]); return;
        }
    }

    $conexion->begin_transaction();
    try {
        $fp = $fecha_prog.' '.$hora_prog;

        if (empty($id_asignacion)) {
            // MODO INSERT (NUEVO)
            $sqlA = "INSERT INTO asignacion_personal (id_tipo_servicio, fecha_asignacion, hora_asignacion, estado_asignacion, estado, usuario_creacion) VALUES (?, ?, ?, 'Pendiente', 'activo', ?)";
            $stmtA = $conexion->prepare($sqlA); 
            $stmtA->bind_param("issi", $id_tipo_serv, $fp, $hora_prog, $usuario);
            $stmtA->execute(); $id_asig = $conexion->insert_id;

            $conexion->query("INSERT INTO asignacion_orden (id_orden, id_asignacion, estado) VALUES ($id_orden, $id_asig, 'activo')");

            $conexion->query("INSERT INTO taller (id_orden, id_bahia, id_precio, estado, usuario_creacion) VALUES ($id_orden, $id_bahia, 1, 'activo', $usuario)");
            if($id_maq) $conexion->query("INSERT INTO detalle_orden (id_orden, id_maquinaria, tiempo_estimado) VALUES ($id_orden, $id_maq, '01:00:00')");

            $msg = 'Asignación creada exitosamente.';
        } else {
            // MODO UPDATE (EDICIÓN)
            $id_asig = $id_asignacion;
            
            $sqlU = "UPDATE asignacion_personal SET id_tipo_servicio=?, fecha_asignacion=?, hora_asignacion=? WHERE id_asignacion=?";
            $stmtU = $conexion->prepare($sqlU);
            $stmtU->bind_param("issi", $id_tipo_serv, $fp, $hora_prog, $id_asig);
            $stmtU->execute();

            $conexion->query("UPDATE taller SET id_bahia = $id_bahia WHERE id_orden = $id_orden");

            if($id_maq) {
                $check = $conexion->query("SELECT id_orden FROM detalle_orden WHERE id_orden = $id_orden");
                if($check->num_rows > 0) {
                    $conexion->query("UPDATE detalle_orden SET id_maquinaria = $id_maq WHERE id_orden = $id_orden");
                } else {
                    $conexion->query("INSERT INTO detalle_orden (id_orden, id_maquinaria, tiempo_estimado) VALUES ($id_orden, $id_maq, '01:00:00')");
                }
            } else {
                $conexion->query("DELETE FROM detalle_orden WHERE id_orden = $id_orden");
            }

            $conexion->query("DELETE FROM detalle_asignacion_p WHERE id_asignacion = $id_asig");
            $msg = 'Asignación actualizada correctamente.';
        }

        $stmtDet = $conexion->prepare("INSERT INTO detalle_asignacion_p (id_asignacion, id_empleado, estado) VALUES (?, ?, 'activo')");
        foreach ($mecanicos as $id_emp) { 
            $stmtDet->bind_param("ii", $id_asig, $id_emp);
            $stmtDet->execute(); 
        }

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
        
        // Ocupar Bahía y Maquinaria asociadas a la orden
        $sqlInfo = "SELECT t.id_bahia, do.id_maquinaria FROM asignacion_orden ao 
                    LEFT JOIN taller t ON ao.id_orden = t.id_orden 
                    LEFT JOIN detalle_orden do ON ao.id_orden = do.id_orden 
                    WHERE ao.id_asignacion = $id LIMIT 1";
        $resInfo = $conexion->query($sqlInfo)->fetch_assoc();
        
        if($resInfo['id_bahia']) $conexion->query("UPDATE bahia SET estado_bahia = 'Ocupada' WHERE id_bahia = {$resInfo['id_bahia']}");
        if($resInfo['id_maquinaria']) $conexion->query("UPDATE maquinaria SET estado_maquina = 'Ocupada' WHERE id_maquinaria = {$resInfo['id_maquinaria']}");

        $conexion->commit(); echo json_encode(['success' => true]);
    } catch (Exception $e) { $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
}

function finalizar_tiempo($conexion) {
    $id = $_POST['id_asignacion_tiempo']; $notas = $_POST['notas_hallazgos'];
    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("UPDATE registro_tiempos SET hora_fin = NOW(), notas_hallazgos = ? WHERE id_asignacion = ? AND hora_fin IS NULL");
        $stmt->bind_param("si", $notas, $id); $stmt->execute();
        $conexion->query("UPDATE asignacion_personal SET estado_asignacion = 'Completado' WHERE id_asignacion = $id");
        
        // Liberar Bahía y Maquinaria asociadas a la orden
        $sqlInfo = "SELECT t.id_bahia, do.id_maquinaria FROM asignacion_orden ao 
                    LEFT JOIN taller t ON ao.id_orden = t.id_orden 
                    LEFT JOIN detalle_orden do ON ao.id_orden = do.id_orden 
                    WHERE ao.id_asignacion = $id LIMIT 1";
        $resInfo = $conexion->query($sqlInfo)->fetch_assoc();
        
        if($resInfo['id_bahia']) $conexion->query("UPDATE bahia SET estado_bahia = 'Disponible' WHERE id_bahia = {$resInfo['id_bahia']}");
        if($resInfo['id_maquinaria']) $conexion->query("UPDATE maquinaria SET estado_maquina = 'Activo' WHERE id_maquinaria = {$resInfo['id_maquinaria']}");

        $conexion->commit(); echo json_encode(['success' => true, 'message' => 'Finalizado y recursos liberados.']);
    } catch (Exception $e) { $conexion->rollback(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
}
?>