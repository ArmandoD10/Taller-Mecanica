<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start(); // Indispensable para acceder a $_SESSION

$action = $_GET['action'] ?? '';
// Obtenemos el ID del usuario logueado desde la sesión
$id_usuario_sesion = $_SESSION['id_usuario'] ?? null; 

switch ($action) {
   case 'buscar_orden':
    $id = (int)$_GET['id'];

    // 1. Primero verificamos si esa orden ya existe en la tabla de resultados
    $checkSql = "SELECT id_resultado_master FROM encuesta_resultado_master WHERE id_orden = ?";
    $stmtCheck = $conexion->prepare($checkSql);
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows > 0) {
        // Si ya existe, enviamos un mensaje de advertencia[cite: 2, 4]
        echo json_encode([
            'success' => false, 
            'is_evaluated' => true, 
            'message' => 'Esta orden ya ha sido evaluada anteriormente.'
        ]);
        exit;
    }

    // 2. Si no ha sido evaluada, procedemos con la búsqueda normal
    $sql = "SELECT o.id_orden, v.modelo, v.placa, v.id_cliente,
                   CONCAT(p.nombre, ' ', p.apellido_p) as cliente
            FROM Orden o
            JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
            JOIN Vehiculo v ON i.id_vehiculo = v.sec_vehiculo
            JOIN Cliente c ON v.id_cliente = c.id_cliente
            JOIN Persona p ON c.id_persona = p.id_persona
            WHERE o.id_orden = $id AND o.estado != 'eliminado'";
    
    $res = $conexion->query($sql);
    if ($res && $res->num_rows > 0) {
        echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Orden no encontrada']);
    }
    break;

    case 'listar_preguntas':
        $sql = "SELECT * FROM encuesta_pregunta WHERE estado = 'activo'";
        $res = $conexion->query($sql);
        echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
        break;

    // Añade estos casos al switch($action) existente
case 'listar_preguntas_completo':
    $sql = "SELECT * FROM encuesta_pregunta WHERE estado != 'eliminado'";
    $res = $conexion->query($sql);
    echo json_encode(['success' => true, 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
    break;

case 'guardar_pregunta':
    $id = $_POST['id_pregunta'] ?? '';
    $pregunta = $_POST['pregunta'];
    $tipo = $_POST['tipo_respuesta'];

    if ($id == '') {
        $sql = "INSERT INTO encuesta_pregunta (pregunta, tipo_respuesta) VALUES (?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $pregunta, $tipo);
    } else {
        $sql = "UPDATE encuesta_pregunta SET pregunta = ?, tipo_respuesta = ? WHERE id_pregunta = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssi", $pregunta, $tipo, $id);
    }
    echo json_encode(['success' => $stmt->execute()]);
    break;

case 'eliminar_pregunta_fisico':
    $id = (int)$_POST['id'];
    
    // Usamos DELETE para borrar el registro de la tabla permanentemente
    $sql = "DELETE FROM encuesta_pregunta WHERE id_pregunta = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    
    try {
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pregunta eliminada físicamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar']);
        }
    } catch (Exception $e) {
        // Esto ocurrirá si la pregunta ya tiene respuestas asociadas (restricción FK)
        echo json_encode([
            'success' => false, 
            'message' => 'No puedes eliminarla porque ya tiene evaluaciones registradas. Sugerimos solo desactivarla.'
        ]);
    }
    break;

    case 'guardar_evaluacion':
        // Validamos que haya una sesión activa antes de procesar[cite: 2]
        if (!$id_usuario_sesion) {
            echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor inicie sesión nuevamente.']);
            exit;
        }

        $id_orden = $_POST['id_orden'];
        $id_cliente = $_POST['id_cliente'];
        $comentario = $_POST['comentario_general'];
        $respuestas = $_POST['respuesta']; 

        $conexion->begin_transaction();
        try {
            // Se añade la columna usuario_registro en el INSERT[cite: 2]
            $stmt = $conexion->prepare("INSERT INTO encuesta_resultado_master (id_orden, id_cliente, comentario_general, usuario_registro) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $id_orden, $id_cliente, $comentario, $id_usuario_sesion);
            $stmt->execute();
            $id_master = $conexion->insert_id;

            $stmtDet = $conexion->prepare("INSERT INTO encuesta_resultado_detalle (id_resultado_master, id_pregunta, valor_respuesta) VALUES (?, ?, ?)");
            foreach ($respuestas as $id_pregunta => $valor) {
                $stmtDet->bind_param("iis", $id_master, $id_pregunta, $valor);
                $stmtDet->execute();
            }

            $conexion->commit();
            echo json_encode(['success' => true, 'message' => 'Evaluación guardada correctamente por el usuario activo.']);
        } catch (Exception $e) {
            $conexion->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
}