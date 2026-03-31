<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cargar_vehiculos':
        cargar_vehiculos($conexion);
        break;
    case 'buscar_historial':
        buscar_historial($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function cargar_vehiculos($conexion) {
    // 🔥 ACTUALIZADO: Agregamos el JOIN con la tabla Modelo
    $sql = "SELECT 
                v.id_vehiculo, 
                v.placa, 
                v.vin_chasis, 
                m.nombre AS marca_nombre, 
                mo.nombre AS modelo -- Aquí traemos el nombre desde la nueva tabla
            FROM Vehiculo v
            JOIN Marca m ON v.id_marca = m.id_marca
            JOIN Modelo mo ON v.id_modelo = mo.id_modelo
            WHERE v.estado = 'activo'
            ORDER BY v.id_vehiculo DESC";
    
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
    $id_vehiculo = (int)($_GET['id_vehiculo'] ?? 0);

    if ($id_vehiculo === 0) {
        echo json_encode(['success' => false, 'message' => 'ID de vehículo inválido']);
        exit;
    }

    // 🔥 ACTUALIZADO: Agregamos el JOIN con la tabla Modelo
    $sql_vehiculo = "SELECT 
                        v.id_vehiculo,
                        v.vin_chasis,
                        v.placa,
                        mo.nombre AS modelo, -- Nombre desde la tabla Modelo
                        v.anio,
                        v.kilometraje_actual,
                        m.nombre AS marca,
                        col.nombre AS color,
                        IF(p.tipo_persona = 'Juridica', p.nombre, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, ''))) AS propietario,
                        p.cedula AS documento_propietario,
                        (SELECT t.numero FROM Telefono t JOIN Cliente_Telefono ct ON t.id_telefono = ct.id_telefono WHERE ct.id_cliente = c.id_cliente AND ct.estado = 'activo' LIMIT 1) AS telefono_propietario
                    FROM Vehiculo v
                    JOIN Marca m ON v.id_marca = m.id_marca
                    JOIN Modelo mo ON v.id_modelo = mo.id_modelo -- Relación nueva
                    JOIN Color col ON v.id_color = col.id_color
                    JOIN Cliente c ON v.id_cliente = c.id_cliente
                    JOIN Persona p ON c.id_persona = p.id_persona
                    WHERE v.id_vehiculo = ?";
    
    $stmt = $conexion->prepare($sql_vehiculo);
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();
    $vehiculo_info = $stmt->get_result()->fetch_assoc();

    $historial_servicios = []; 

    echo json_encode([
        'success' => true, 
        'vehiculo' => $vehiculo_info,
        'historial' => $historial_servicios
    ]);
}
?>