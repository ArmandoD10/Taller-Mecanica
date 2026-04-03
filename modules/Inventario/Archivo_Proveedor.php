<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    // --- RUTAS PROVEEDOR ---
    case 'listar':
        listar($conexion);
        break;
    case 'cargar_dependencias':
        cargar_dependencias($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    case 'obtener':
        obtener($conexion);
        break;
    case 'eliminar':
        eliminar($conexion);
        break;
        
    // --- RUTAS TELÉFONOS ---
    case 'listar_telefonos':
        listar_telefonos($conexion);
        break;
    case 'guardar_telefono':
        guardar_telefono($conexion);
        break;
    case 'eliminar_telefono':
        eliminar_telefono($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

// ==========================================
// FUNCIONES DE PROVEEDOR
// ==========================================
function listar($conexion) {
    $sql = "SELECT p.id_proveedor, p.nombre_comercial, p.RNC, p.correo, p.estado, 
                   CONCAT(per.nombre, ' ', IFNULL(per.apellido_p, '')) AS representante,
                   per.tipo_persona,
                   (SELECT COUNT(*) FROM proveedor_telefono pt WHERE pt.id_proveedor = p.id_proveedor AND pt.estado != 'eliminado') as total_telefonos
            FROM proveedor p
            JOIN persona per ON p.representante = per.id_persona
            WHERE p.estado != 'eliminado'
            ORDER BY p.id_proveedor DESC";
            
    $res = $conexion->query($sql);
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function cargar_dependencias($conexion) {
    $data = [];
    $data['paises'] = $conexion->query("SELECT id_pais, nombre FROM pais WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['provincias'] = $conexion->query("SELECT id_provincia, nombre, id_pais FROM provincia WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    $data['ciudades'] = $conexion->query("SELECT id_ciudad, nombre, id_provincia FROM ciudad WHERE estado = 'activo'")->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'data' => $data
    ]);
}

function guardar($conexion) {
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    $id_persona = $_POST['id_persona'] ?? '';
    $id_direccion = $_POST['id_direccion'] ?? '';
    
    $tipo_persona = $_POST['tipo_persona'] ?? 'Fisica';
    $nombre = $_POST['nombre'] ?? '';
    $apellido_p = $_POST['apellido_p'] ?? '';
    $cedula = $_POST['cedula'] ?? '';
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? date('Y-m-d');
    $nacionalidad = $_POST['nacionalidad'] ?? ''; 
    
    $nombre_comercial = trim($_POST['nombre_comercial'] ?? '');
    $rnc = $_POST['RNC'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';
    
    // Lógica DGII
    if ($tipo_persona === 'Fisica') {
        if (empty($nombre_comercial)) { 
            $nombre_comercial = trim($nombre . ' ' . $apellido_p); 
        }
        if (!empty($rnc)) { 
            $cedula = $rnc; 
        }
    }
    
    $id_ciudad = $_POST['id_ciudad'] ?? '';
    $descripcion_dir = $_POST['descripcion_dir'] ?? '';
    $usuario = $_SESSION['id_usuario'] ?? 1;

    try {
        $conexion->begin_transaction();

        if ($id_proveedor == '') {
            // INSERTAR DIRECCIÓN
            $sqlDir = "INSERT INTO direccion (id_ciudad, Descripcion, estado) VALUES (?, ?, 'activo')";
            $stmtDir = $conexion->prepare($sqlDir);
            $stmtDir->bind_param("is", $id_ciudad, $descripcion_dir);
            $stmtDir->execute();
            $new_id_direccion = $conexion->insert_id;

            // INSERTAR PERSONA
            $sqlPer = "INSERT INTO persona (tipo_persona, nombre, apellido_p, cedula, fecha_nacimiento, id_direccion, nacionalidad, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')";
            $stmtPer = $conexion->prepare($sqlPer);
            $stmtPer->bind_param("sssssii", $tipo_persona, $nombre, $apellido_p, $cedula, $fecha_nacimiento, $new_id_direccion, $nacionalidad);
            $stmtPer->execute();
            $new_id_persona = $conexion->insert_id;

            // INSERTAR PROVEEDOR
            $sqlProv = "INSERT INTO proveedor (nombre_comercial, representante, correo, RNC, id_direccion, estado, usuario_creacion) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtProv = $conexion->prepare($sqlProv);
            $stmtProv->bind_param("sissisi", $nombre_comercial, $new_id_persona, $correo, $rnc, $new_id_direccion, $estado, $usuario);
            $stmtProv->execute();

        } else {
            // ACTUALIZAR DIRECCIÓN
            $sqlDir = "UPDATE direccion SET id_ciudad = ?, Descripcion = ? WHERE id_direccion = ?";
            $stmtDir = $conexion->prepare($sqlDir);
            $stmtDir->bind_param("isi", $id_ciudad, $descripcion_dir, $id_direccion);
            $stmtDir->execute();

            // ACTUALIZAR PERSONA
            $sqlPer = "UPDATE persona SET tipo_persona = ?, nombre = ?, apellido_p = ?, cedula = ?, fecha_nacimiento = ?, nacionalidad = ? WHERE id_persona = ?";
            $stmtPer = $conexion->prepare($sqlPer);
            $stmtPer->bind_param("sssssii", $tipo_persona, $nombre, $apellido_p, $cedula, $fecha_nacimiento, $nacionalidad, $id_persona);
            $stmtPer->execute();

            // ACTUALIZAR PROVEEDOR
            $sqlProv = "UPDATE proveedor SET nombre_comercial = ?, correo = ?, RNC = ?, estado = ? WHERE id_proveedor = ?";
            $stmtProv = $conexion->prepare($sqlProv);
            $stmtProv->bind_param("ssssi", $nombre_comercial, $correo, $rnc, $estado, $id_proveedor);
            $stmtProv->execute();
        }
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Proveedor guardado correctamente.'
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback(); 
        
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function obtener($conexion) {
    $id = (int)$_GET['id_proveedor'];
    
    $sql = "SELECT p.*, 
                   per.id_persona, per.tipo_persona, per.nombre, per.apellido_p, per.cedula, per.fecha_nacimiento, per.nacionalidad,
                   dir.id_direccion, dir.id_ciudad, dir.Descripcion as descripcion_dir,
                   c.id_provincia, prov.id_pais as id_pais_dir
            FROM proveedor p
            JOIN persona per ON p.representante = per.id_persona
            JOIN direccion dir ON p.id_direccion = dir.id_direccion
            LEFT JOIN ciudad c ON dir.id_ciudad = c.id_ciudad
            LEFT JOIN provincia prov ON c.id_provincia = prov.id_provincia
            WHERE p.id_proveedor = ?";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true, 
        'data' => $stmt->get_result()->fetch_assoc()
    ]);
}

function eliminar($conexion) {
    $id = (int)$_POST['id_proveedor'];
    $conexion->query("UPDATE proveedor SET estado = 'eliminado' WHERE id_proveedor = $id");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Proveedor eliminado.'
    ]);
}

// ==========================================
// FUNCIONES DE TELÉFONOS
// ==========================================
function listar_telefonos($conexion) {
    $id_proveedor = (int)$_GET['id_proveedor'];
    
    $sql = "SELECT t.id_telefono, t.numero, pt.estado 
            FROM telefono t
            JOIN proveedor_telefono pt ON t.id_telefono = pt.id_telefono
            WHERE pt.id_proveedor = $id_proveedor AND pt.estado != 'eliminado'";
            
    $res = $conexion->query($sql);
    
    echo json_encode([
        'success' => true, 
        'data' => $res->fetch_all(MYSQLI_ASSOC)
    ]);
}

function guardar_telefono($conexion) {
    $id_proveedor = (int)$_POST['prov_tel_id'];
    $numero = $_POST['numero_telefono'] ?? '';
    $estado = $_POST['estado_telefono'] ?? 'activo';
    $id_telefono_edit = $_POST['id_telefono_edit'] ?? ''; 

    try {
        // 1. Obtener o crear el ID del número de teléfono escrito en la tabla maestra
        $stmt = $conexion->prepare("SELECT id_telefono FROM telefono WHERE numero = ? LIMIT 1");
        $stmt->bind_param("s", $numero);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $id_telefono = $res->fetch_assoc()['id_telefono'];
        } else {
            $stmt2 = $conexion->prepare("INSERT INTO telefono (numero, estado) VALUES (?, 'activo')");
            $stmt2->bind_param("s", $numero);
            $stmt2->execute();
            $id_telefono = $conexion->insert_id;
        }

        // 2. Si estamos editando y cambió el número, damos de baja la relación vieja
        if ($id_telefono_edit !== '' && $id_telefono_edit != $id_telefono) {
            $stmtDel = $conexion->prepare("UPDATE proveedor_telefono SET estado = 'eliminado' WHERE id_proveedor = ? AND id_telefono = ?");
            $stmtDel->bind_param("ii", $id_proveedor, $id_telefono_edit);
            $stmtDel->execute();
        }

        // 3. Vincular o actualizar la relación con el teléfono actual
        $stmt3 = $conexion->prepare("SELECT estado FROM proveedor_telefono WHERE id_proveedor = ? AND id_telefono = ?");
        $stmt3->bind_param("ii", $id_proveedor, $id_telefono);
        $stmt3->execute();
        $res3 = $stmt3->get_result();

        if ($res3->num_rows > 0) {
            $stmt4 = $conexion->prepare("UPDATE proveedor_telefono SET estado = ? WHERE id_proveedor = ? AND id_telefono = ?");
            $stmt4->bind_param("sii", $estado, $id_proveedor, $id_telefono);
            $stmt4->execute();
        } else {
            $stmt5 = $conexion->prepare("INSERT INTO proveedor_telefono (id_proveedor, id_telefono, estado) VALUES (?, ?, ?)");
            $stmt5->bind_param("iis", $id_proveedor, $id_telefono, $estado);
            $stmt5->execute();
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Teléfono guardado correctamente.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function eliminar_telefono($conexion) {
    $id_proveedor = (int)$_POST['id_proveedor'];
    $id_telefono = (int)$_POST['id_telefono'];
    
    $sql = "UPDATE proveedor_telefono SET estado = 'eliminado' WHERE id_proveedor = ? AND id_telefono = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_proveedor, $id_telefono);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Teléfono desvinculado.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al desvincular.'
        ]);
    }
}
?>