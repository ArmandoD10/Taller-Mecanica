<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$action = $_GET['action'] ?? '';
$id_sucursal = (!empty($_SESSION['id_sucursal']) && $_SESSION['id_sucursal'] != 0) ? (int)$_SESSION['id_sucursal'] : 1;
$id_usuario = $_SESSION['id_usuario'] ?? 1;

switch ($action) {
    case 'verificar_estado':
        try {
            $sql = "SELECT id_sesion, DATE_FORMAT(fecha_apertura, '%d/%m/%Y %h:%i %p') as fecha_apertura_fmt, 
                           monto_inicial, u.username 
                    FROM caja_sesion c
                    JOIN usuario u ON c.id_usuario = u.id_usuario
                    WHERE c.id_sucursal = ? AND c.estado = 'Abierta' 
                    ORDER BY id_sesion DESC LIMIT 1";
                    
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id_sucursal);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $caja = $res->fetch_assoc();
                echo json_encode(['success' => true, 'estado' => 'Abierta', 'data' => $caja]);
            } else {
                echo json_encode(['success' => true, 'estado' => 'Cerrada']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'abrir_caja':
        try {
            $monto_inicial = (float)($_POST['monto_inicial'] ?? 0);
            $notas = trim($_POST['notas'] ?? '');

            $check = $conexion->query("SELECT id_sesion FROM caja_sesion WHERE id_sucursal = $id_sucursal AND estado = 'Abierta'");
            if ($check->num_rows > 0) {
                throw new Exception("Ya existe una caja abierta en esta sucursal.");
            }

            $sql = "INSERT INTO caja_sesion (id_sucursal, id_usuario, monto_inicial, estado, notas) 
                    VALUES (?, ?, ?, 'Abierta', ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iids", $id_sucursal, $id_usuario, $monto_inicial, $notas);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Caja aperturada exitosamente.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'cerrar_caja':
        try {
            $monto_cierre = (float)($_POST['monto_cierre'] ?? 0);
            $notas_cierre = trim($_POST['notas_cierre'] ?? '');

            // 1. Obtener la sesión abierta actual
            $sqlSesion = "SELECT id_sesion, monto_inicial, fecha_apertura FROM caja_sesion WHERE id_sucursal = ? AND estado = 'Abierta' ORDER BY id_sesion DESC LIMIT 1";
            $stmtS = $conexion->prepare($sqlSesion);
            $stmtS->bind_param("i", $id_sucursal);
            $stmtS->execute();
            $resSesion = $stmtS->get_result();

            if ($resSesion->num_rows === 0) {
                throw new Exception("No se encontró una caja abierta para cerrar.");
            }
            $sesion = $resSesion->fetch_assoc();
            $id_sesion = $sesion['id_sesion'];
            $fecha_apertura = $sesion['fecha_apertura'];
            $monto_inicial = (float)$sesion['monto_inicial'];

            // 2. Calcular las ventas en EFECTIVO (id_metodo = 1) desde que se abrió la caja
            $sqlVentas = "SELECT IFNULL(SUM(monto_total), 0) as total_efectivo 
                          FROM factura_central 
                          WHERE id_sucursal = ? AND id_metodo = 1 AND estado_pago = 'Pagado' AND estado = 'activo' AND fecha_emision >= ?";
            $stmtV = $conexion->prepare($sqlVentas);
            $stmtV->bind_param("is", $id_sucursal, $fecha_apertura);
            $stmtV->execute();
            $ventas_efectivo = (float)$stmtV->get_result()->fetch_assoc()['total_efectivo'];

            // 3. Cálculos de Cuadre
            $monto_esperado = $monto_inicial + $ventas_efectivo;
            $diferencia = $monto_cierre - $monto_esperado;

            $nota_final = empty($notas_cierre) ? "" : "\n[Cierre]: " . $notas_cierre;

            // 4. Actualizar la caja a Cerrada
            $sqlClose = "UPDATE caja_sesion 
                         SET fecha_cierre = NOW(), monto_cierre = ?, diferencia = ?, estado = 'Cerrada', notas = CONCAT(IFNULL(notas,''), ?) 
                         WHERE id_sesion = ?";
            $stmtC = $conexion->prepare($sqlClose);
            $stmtC->bind_param("ddsi", $monto_cierre, $diferencia, $nota_final, $id_sesion);
            $stmtC->execute();

            echo json_encode([
                'success' => true, 
                'esperado' => $monto_esperado,
                'diferencia' => $diferencia,
                'message' => 'Turno cerrado y guardado en el historial.'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'listar_historial':
        try {
            $sql = "SELECT id_sesion, u.username, 
                           DATE_FORMAT(fecha_apertura, '%d/%m/%Y %h:%i %p') as apertura,
                           DATE_FORMAT(fecha_cierre, '%d/%m/%Y %h:%i %p') as cierre,
                           monto_inicial, monto_cierre, diferencia, c.estado, c.notas
                    FROM caja_sesion c
                    JOIN usuario u ON c.id_usuario = u.id_usuario
                    WHERE c.id_sucursal = ?
                    ORDER BY id_sesion DESC LIMIT 50";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id_sucursal);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;    
}
?>