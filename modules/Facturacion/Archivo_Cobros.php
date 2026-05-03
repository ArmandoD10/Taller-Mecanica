<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'listar_pendientes':
        listar_pendientes($conexion);
        break;
    case 'listar_cuotas':
    $id_factura = (int)$_GET['id_factura'];
    $sql = "SELECT id_cuota, numero_cuota, monto_cuota, DATE_FORMAT(fecha_programada, '%d/%m/%Y') as fecha_vencimiento 
            FROM Acuerdo_Pago_Cuotas 
            WHERE id_factura = $id_factura AND estado_cuota = 'Pendiente' AND estado = 'activo'
            ORDER BY numero_cuota ASC";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
    break;
    case 'procesar_pago':
        procesar_pago($conexion, $id_usuario);
        break;
    case 'obtener_recibo':
        obtener_recibo($conexion);
        break;
    case 'obtener_detalle':
        obtener_detalle_factura($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function listar_pendientes($conexion) {
    $sql = "SELECT 
                f.*, 
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                fc.id_credito,
                IFNULL((SELECT SUM(monto) FROM Abono_Factura WHERE id_factura = f.id_factura AND estado = 'activo'), 0) AS total_pagado
            FROM Factura_Central f
            JOIN Cliente c ON f.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            JOIN Factura_Credito fc ON f.id_factura = fc.id_factura
            WHERE f.estado_pago = 'Pendiente' AND f.estado = 'activo'
            ORDER BY f.id_factura ASC";
            
    $res = $conexion->query($sql);
    
    if ($res) {
        $data = [];
        while($row = $res->fetch_assoc()) {
            $fechaRaw = $row['fecha_creacion'] ?? ($row['fecha_emision'] ?? ($row['fecha'] ?? null));
            $row['restante'] = (float)$row['monto_total'] - (float)$row['total_pagado'];
            $row['fecha_emision'] = $fechaRaw ? date('d/m/Y', strtotime($fechaRaw)) : 'N/A';
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $conexion->error]);
    }
}

// --- REEMPLAZAR FUNCIÓN COMPLETA ---
function procesar_pago($conexion, $id_usuario) {
    $id_factura = (int)$_POST['id_factura'];
    $id_credito = (int)$_POST['id_credito'];
    $id_cuota = !empty($_POST['id_cuota']) ? (int)$_POST['id_cuota'] : null; // NUEVO[cite: 21]
    $monto_pago = (float)$_POST['monto_pago'];
    $metodo_pago = $_POST['metodo_pago'];
    $referencia = $_POST['referencia'] ?? 'N/A';

    if ($monto_pago <= 0) {
        echo json_encode(['success' => false, 'message' => 'El monto debe ser mayor a cero.']); return;
    }

    $conexion->begin_transaction();

    try {
        // 1. Insertar el Abono General
        $sqlAbono = "INSERT INTO Abono_Factura (id_factura, monto, metodo_pago, referencia, usuario_creacion) VALUES (?, ?, ?, ?, ?)";
        $stmtA = $conexion->prepare($sqlAbono);
        $stmtA->bind_param("idssi", $id_factura, $monto_pago, $metodo_pago, $referencia, $id_usuario);
        $stmtA->execute();
        $id_abono = $conexion->insert_id;

        // 2. Si es pago de una cuota específica, marcarla como Pagada[cite: 21]
        if ($id_cuota) {
            $sqlUpdCuota = "UPDATE Acuerdo_Pago_Cuotas SET estado_cuota = 'Pagada' WHERE id_cuota = ?";
            $stmtQ = $conexion->prepare($sqlUpdCuota);
            $stmtQ->bind_param("i", $id_cuota);
            $stmtQ->execute();
        }

        // 3. Restaurar balance en la tabla Credito
        $sqlCredito = "UPDATE Credito 
                       SET saldo_disponible = saldo_disponible + ?, 
                           saldo_pendiente = saldo_pendiente - ? 
                       WHERE id_credito = ?";
        $stmtC = $conexion->prepare($sqlCredito);
        $stmtC->bind_param("ddi", $monto_pago, $monto_pago, $id_credito);
        $stmtC->execute();

        // 4. Verificar si la factura se liquidó por completo[cite: 22]
        $sqlCheck = "SELECT f.monto_total, IFNULL(SUM(a.monto), 0) AS pagado 
                     FROM Factura_Central f 
                     LEFT JOIN Abono_Factura a ON f.id_factura = a.id_factura AND a.estado = 'activo'
                     WHERE f.id_factura = $id_factura";
        $resCheck = $conexion->query($sqlCheck)->fetch_assoc();
        
        if (($resCheck['monto_total'] - $resCheck['pagado']) <= 0.05) { // Margen pequeño para centavos
            $conexion->query("UPDATE Factura_Central SET estado_pago = 'Pagado' WHERE id_factura = $id_factura");
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'id_abono' => $id_abono, 'message' => 'Pago procesado exitosamente.']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtener_recibo($conexion) {
    $id_abono = (int)$_GET['id_abono'];
    
    $sql = "SELECT 
                a.id_abono, a.monto, a.metodo_pago, a.referencia, DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y %h:%i %p') AS fecha,
                f.id_factura, f.id_orden, f.monto_total,
                CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS cliente,
                IFNULL(u.username, 'Caja') AS cajero,
                (f.monto_total - (SELECT SUM(monto) FROM Abono_Factura WHERE id_factura = f.id_factura AND id_abono <= a.id_abono AND estado='activo')) AS balance_restante
            FROM Abono_Factura a
            JOIN Factura_Central f ON a.id_factura = f.id_factura
            JOIN Cliente c ON f.id_cliente = c.id_cliente
            JOIN Persona per ON c.id_persona = per.id_persona
            LEFT JOIN Usuario u ON a.usuario_creacion = u.id_usuario
            WHERE a.id_abono = $id_abono";
            
    $res = $conexion->query($sql);
    
    if ($res && $res->num_rows > 0) {
        echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Recibo no encontrado.']);
    }
}

function obtener_detalle_factura($conexion) {
    $id_factura = (int)$_GET['id_factura'];
    $detalles = [];

    $sqlPOS = "SELECT ra.nombre as descripcion, df.cantidad, df.precio, df.subtotal 
               FROM Detalle_Factura df 
               JOIN Repuesto_Articulo ra ON df.id_articulo = ra.id_articulo 
               WHERE df.id_factura = $id_factura";
    $resPOS = $conexion->query($sqlPOS);
    if ($resPOS && $resPOS->num_rows > 0) {
        while($row = $resPOS->fetch_assoc()) {
            $detalles[] = $row;
        }
    } 
    else {
        $sqlFac = "SELECT id_orden FROM Factura_Central WHERE id_factura = $id_factura LIMIT 1";
        $resFac = $conexion->query($sqlFac);
        if($resFac && $resFac->num_rows > 0) {
            $id_orden = $resFac->fetch_assoc()['id_orden'];
            if($id_orden) {
                // ========================================================
                // MAGIA ANTI-DOBLE COBRO EN EL DETALLE DE CUENTAS POR COBRAR
                // ========================================================
                $sqlServ = "SELECT ap.id_tipo_servicio, ts.nombre AS descripcion, 1 AS cantidad
                            FROM asignacion_orden ao
                            JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion
                            JOIN Tipo_Servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
                            WHERE ao.id_orden = $id_orden AND ap.estado != 'eliminado' ORDER BY ap.id_asignacion ASC";
                $resServ = $conexion->query($sqlServ);
                
                if($resServ) {
                    $servs = $resServ->fetch_all(MYSQLI_ASSOC);
                    $sqlPrecios = "SELECT p.monto AS precio FROM taller t JOIN Precio p ON t.id_precio = p.id_precio WHERE t.id_orden = $id_orden AND t.estado = 'activo' ORDER BY t.id_taller ASC";
                    $precios = $conexion->query($sqlPrecios)->fetch_all(MYSQLI_ASSOC);
                    
                    $vistos = []; // Array de control EXCLUSIVO para servicios
                    foreach($servs as $idx => $s) {
                        $id_serv = $s['id_tipo_servicio'];
                        
                        // Si el servicio ya se cobró, lo ignoramos para que no salga doble en la tabla.
                        if(in_array($id_serv, $vistos)) continue; 
                        $vistos[] = $id_serv;
                        
                        $precio = isset($precios[$idx]) ? (float)$precios[$idx]['precio'] : 0;
                        if($precio > 0) {
                            $detalles[] = [
                                'descripcion' => $s['descripcion'],
                                'cantidad' => 1,
                                'precio' => $precio,
                                'subtotal' => $precio
                            ];
                        }
                    }
                }
                
                // B. Buscar Repuestos del Taller (ESTOS NO SE FILTRAN, VAN COMPLETOS)
                $sqlRep = "SELECT ra.nombre AS descripcion, orp.cantidad, ra.precio_venta AS precio, (orp.cantidad * ra.precio_venta) AS subtotal
                           FROM Orden_Repuesto orp 
                           JOIN Repuesto_Articulo ra ON orp.id_articulo = ra.id_articulo
                           WHERE orp.id_orden = $id_orden AND orp.estado = 'activo'";
                $resRep = $conexion->query($sqlRep);
                if($resRep) {
                    while($r = $resRep->fetch_assoc()) {
                        $detalles[] = $r;
                    }
                }
            }
        }
    }
    
    echo json_encode(['success' => true, 'data' => $detalles]);
}
?>