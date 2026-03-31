<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cargar_clientes':
        cargar_clientes($conexion);
        break;
    case 'buscar_historial':
        buscar_historial($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function cargar_clientes($conexion) {
    $sql = "SELECT 
                c.id_cliente, 
                IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', p.apellido_p)) AS nombre_cliente,
                p.cedula
            FROM Cliente c
            JOIN Persona p ON c.id_persona = p.id_persona
            WHERE c.estado = 'activo'
            ORDER BY nombre_cliente ASC";
    
    $resultado = $conexion->query($sql);
    $data = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function buscar_historial($conexion) {
    $id_cliente = (int)($_GET['id_cliente'] ?? 0);

    if ($id_cliente === 0) {
        echo json_encode(['success' => false, 'message' => 'ID de cliente inválido']);
        exit;
    }

    // 1. Obtener los totales del cliente
    $sql_totales = "SELECT 
                        c.limite_credito,
                        IFNULL(SUM(cr.saldo_pendiente), 0) as deuda_total
                    FROM Cliente c
                    LEFT JOIN Credito cr ON c.id_cliente = cr.id_cliente AND cr.estado_credito IN ('Activo', 'Vencido')
                    WHERE c.id_cliente = ?";
    
    $stmt = $conexion->prepare($sql_totales);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $res_totales = $stmt->get_result()->fetch_assoc();

    $limite = (float)$res_totales['limite_credito'];
    $deuda = (float)$res_totales['deuda_total'];
    $disponible = $limite - $deuda;

    // 2. Obtener el historial de líneas de crédito
    $sql_historial = "SELECT 
                        id_credito,
                        monto_credito,
                        saldo_pendiente,
                        fecha_aprobacion,
                        fecha_vencimiento,
                        estado_credito,
                        referencia_datacredito
                      FROM Credito
                      WHERE id_cliente = ?
                      ORDER BY id_credito DESC";
                      
    $stmt2 = $conexion->prepare($sql_historial);
    $stmt2->bind_param("i", $id_cliente);
    $stmt2->execute();
    $res_historial = $stmt2->get_result();
    
    $historial = [];
    while ($fila = $res_historial->fetch_assoc()) {
        $historial[] = $fila;
    }

    echo json_encode([
        'success' => true, 
        'totales' => [
            'limite' => $limite,
            'deuda' => $deuda,
            'disponible' => $disponible
        ],
        'historial' => $historial
    ]);
}
?>