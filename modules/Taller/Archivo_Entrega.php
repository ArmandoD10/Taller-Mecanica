<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listar_entregas($conexion);
        break;
    case 'procesar_entrega':
        procesar_entrega($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida en módulo de entregas']);
        break;
}

function listar_entregas($conexion) {
    // Esta consulta es potente: Viaja desde Orden -> Inspeccion -> Vehiculo -> Cliente -> Persona
    // También cruza con Factura Central para ver el estado de pago y con Marca para el vehículo.
    // Solo muestra órdenes que están en fases finales o entregadas hoy.
    
    $sql = "SELECT 
                o.id_orden, 
                o.descripcion, 
                IFNULL(o.monto_total, 0) AS monto_total,
                CONCAT('RD$ ', FORMAT(IFNULL(o.monto_total, 0), 2)) AS monto_total_fmt,
                o.estado_orden,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                CONCAT(mar.nombre, ' ', IFNULL(v.modelo, ''), ' [', v.placa, ']') AS vehiculo,
                IFNULL(fc.estado_pago, 'Sin Facturar') AS estado_pago,
                DATE(o.fecha_creacion) as fecha_orden
            FROM orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN marca mar ON v.id_marca = mar.id_marca
            JOIN cliente c ON v.id_cliente = c.id_cliente
            JOIN persona per ON c.id_persona = per.id_persona
            LEFT JOIN factura_central fc ON o.id_orden = fc.id_orden
            WHERE o.estado != 'eliminado' 
              AND (
                  o.estado_orden IN ('Control Calidad', 'Listo') 
                  OR (o.estado_orden = 'Entregado' AND DATE(o.fecha_creacion) = CURDATE())
              )
            GROUP BY o.id_orden
            ORDER BY 
                CASE o.estado_orden 
                    WHEN 'Listo' THEN 1 
                    WHEN 'Control Calidad' THEN 2 
                    WHEN 'Entregado' THEN 3 
                    ELSE 4 
                END, 
                o.id_orden ASC";
                
    $res = $conexion->query($sql);
    
    if ($res) {
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $conexion->error]);
    }
}

function procesar_entrega($conexion) {
    $id_orden = $_POST['id_orden_entrega'] ?? '';
    $estado_anterior = $_POST['estado_anterior'] ?? 'Listo'; // Por defecto Listo
    $usuario = $_SESSION['id_usuario'] ?? 1;

    if (empty($id_orden)) {
        echo json_encode(['success' => false, 'message' => 'Falta el ID de la orden.']);
        return;
    }

    // Iniciar transacción de base de datos
    $conexion->begin_transaction();

    try {
        // 1. Validar estado actual de la orden (por seguridad si alguien más la alteró)
        $resCheck = $conexion->query("SELECT estado_orden FROM orden WHERE id_orden = $id_orden");
        if($resCheck && $resCheck->num_rows > 0) {
            $rowCheck = $resCheck->fetch_assoc();
            if($rowCheck['estado_orden'] === 'Entregado') {
                throw new Exception("Esta orden ya había sido marcada como Entregada anteriormente.");
            }
            // Si el estado real de la BD es distinto al que llegó del frontend, actualizamos la variable
            if($rowCheck['estado_orden'] != '') {
                $estado_anterior = $rowCheck['estado_orden'];
            }
        } else {
            throw new Exception("La orden no existe o fue eliminada.");
        }

        // 2. Actualizar el estado en la tabla ORDEN
        $sqlUpdate = "UPDATE orden SET estado_orden = 'Entregado' WHERE id_orden = ?";
        $stmtUpdate = $conexion->prepare($sqlUpdate);
        $stmtUpdate->bind_param("i", $id_orden);
        $stmtUpdate->execute();

        // 3. Registrar la auditoría en HISTORIAL_ESTADO_ORDEN
        $sqlHistorial = "INSERT INTO historial_estado_orden (id_orden, estado_anterior, estado_nuevo, estado, usuario_creacion) 
                         VALUES (?, ?, 'Entregado', 'activo', ?)";
        $stmtHistorial = $conexion->prepare($sqlHistorial);
        $stmtHistorial->bind_param("isi", $id_orden, $estado_anterior, $usuario);
        $stmtHistorial->execute();

        // Confirmar transacción
        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'El rastro de auditoría ha sido guardado en el historial.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>