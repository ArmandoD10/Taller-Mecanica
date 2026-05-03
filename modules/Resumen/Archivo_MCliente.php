<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'datos_clientes':
    // 1. Top 5 Clientes (Orden -> Inspeccion -> Vehiculo -> Cliente -> Persona)
    $sqlClientes = "SELECT CONCAT(p.nombre, ' ', p.apellido_p) as nombre, COUNT(o.id_orden) as total 
                    FROM Orden o
                    JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                    JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                    JOIN Cliente c ON v.id_cliente = c.id_cliente
                    JOIN Persona p ON c.id_persona = p.id_persona
                    WHERE o.estado != 'eliminado'
                    GROUP BY c.id_cliente, p.nombre, p.apellido_p 
                    ORDER BY total DESC LIMIT 5";
    $resClientes = $conexion->query($sqlClientes)->fetch_all(MYSQLI_ASSOC);

    // 2. Vehículos con mayor gasto (Gasto acumulado por placa a través de la inspección)
    $sqlGastos = "SELECT v.placa, SUM(o.monto_total) as total_gastado 
                  FROM Orden o
                  JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                  JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                  WHERE o.estado != 'eliminado'
                  GROUP BY v.placa 
                  ORDER BY total_gastado DESC LIMIT 5";
    $resGastos = $conexion->query($sqlGastos)->fetch_all(MYSQLI_ASSOC);

    // 3. Marcas más frecuentes (Nace de la tabla Vehiculo directamente)
    $sqlMarcas = "SELECT m.nombre, COUNT(v.sec_vehiculo) as total 
                  FROM Vehiculo v 
                  JOIN Marca m ON v.id_marca = m.id_marca 
                  GROUP BY m.id_marca, m.nombre 
                  ORDER BY total DESC";
    $resMarcas = $conexion->query($sqlMarcas)->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'clientes' => $resClientes,
        'gastos' => $resGastos,
        'marcas' => $resMarcas
    ]);
    break;

    case 'listar_cards_clientes':
        // Datos para las cards de Clientes y sus Vehículos
        $sqlCards = "SELECT CONCAT(p.nombre, ' ', p.apellido_p) as cliente, 
                            m.nombre as marca, v.modelo, v.anio, col.nombre as color, v.placa
                     FROM Cliente c
                     JOIN Persona p ON c.id_persona = p.id_persona
                     JOIN Vehiculo v ON c.id_cliente = v.id_cliente
                     JOIN Marca m ON v.id_marca = m.id_marca
                     JOIN Color col ON v.id_color = col.id_color
                     WHERE v.estado = 'activo'
                     ORDER BY p.nombre ASC";
        $res = $conexion->query($sqlCards);
        echo json_encode(['data' => $res ? $res->fetch_all(MYSQLI_ASSOC) : []]);
        break;
}