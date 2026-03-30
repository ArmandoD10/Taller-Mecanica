<?php
include("../../controller/conexion.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
session_start();

switch ($action) {
    case 'cargar_selects':
        cargar_selects($conexion);
        break;
    case 'cargar':
        cargar($conexion);
        break;
    case 'cargar_provincias':
        cargar_provincias($conexion);
        break;
    case 'cargar_ciudades':
        cargar_ciudades($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    case 'actualizar':
        actualizar($conexion);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function cargar($conexion) {
    $sql = "SELECT 
            c.id_cliente,
            -- PERSONA
            p.tipo_persona, 
            p.nombre,
            p.apellido_p,
            p.apellido_m,
            p.cedula,
            p.email,
            p.fecha_nacimiento,
            p.sexo,
            p.nacionalidad,
            -- DIRECCION
            d.descripcion AS direccion,
            ci.id_ciudad,
            ci.nombre AS ciudad,
            pr.id_provincia,
            pr.nombre AS provincia,
            pa.id_pais,
            pa.nombre AS pais,
            -- TELEFONO CLIENTE
            (SELECT t.numero 
             FROM Telefono t 
             JOIN Cliente_Telefono ct ON t.id_telefono = ct.id_telefono 
             WHERE ct.id_cliente = c.id_cliente AND ct.estado = 'activo' LIMIT 1) AS telefono,
            -- CLIENTE
            c.limite_credito,
            c.estado,
            c.fecha_creacion AS fecha_registro
        FROM Cliente c
        JOIN Persona p ON c.id_persona = p.id_persona
        JOIN Direccion d ON p.id_direccion = d.id_direccion
        JOIN Ciudad ci ON d.id_ciudad = ci.id_ciudad
        JOIN Provincia pr ON ci.id_provincia = pr.id_provincia
        JOIN Pais pa ON pr.id_pais = pa.id_pais
        ORDER BY c.id_cliente DESC";

    $resultado = $conexion->query($sql);
    $data = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_selects($conexion) {
    $data = [];
    $res = $conexion->query("SELECT id_pais, nombre FROM Pais ORDER BY nombre ASC");
    if($res) $data['pais'] = $res->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
}

function cargar_provincias($conexion) {
    $id_pais = $_GET['id_pais'] ?? 0;
    $stmt = $conexion->prepare("SELECT id_provincia, nombre FROM Provincia WHERE id_pais=?");
    $stmt->bind_param("i", $id_pais);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($result);
}

function cargar_ciudades($conexion) {
    $id_provincia = $_GET['id_provincia'] ?? 0;
    $stmt = $conexion->prepare("SELECT id_ciudad, nombre FROM Ciudad WHERE id_provincia=?");
    $stmt->bind_param("i", $id_provincia);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($result);
}

function guardar($conexion) {
    $usuario_creacion = $_SESSION['id_usuario'] ?? 1; 

    // Recibimos si es física o jurídica desde el formulario
    $tipo_persona_input = $_POST['tipo_persona'] ?? 'fisica';
    $es_empresa = ($tipo_persona_input === 'juridica');
    
    // Valor exacto para el ENUM de la base de datos
    $tipo_persona_db = $es_empresa ? 'Juridica' : 'Fisica';

    $nombre = $_POST['nombre'] ?? '';
    $apellido_p = $es_empresa ? '' : ($_POST['apellido_p'] ?? '');
    $apellido_m = $es_empresa ? '' : ($_POST['apellido_m'] ?? '');
    $sexo = $es_empresa ? NULL : ($_POST['sexo'] ?? '');
    
    $cedula = $_POST['cedula'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $nacionalidad = $_POST['nacionalidad'] ?? '';

    $id_ciudad = $_POST['ciudad'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $telefono = $_POST['telefono'] ?? '';

    if (empty($nombre) || empty($cedula) || empty($id_ciudad) || empty($fecha_nacimiento) || (!$es_empresa && empty($apellido_p))) {
        echo json_encode(['success' => false, 'message' => 'Por favor complete todos los campos obligatorios']);
        exit;
    }

    try {
        $conexion->begin_transaction();

        $sql = "INSERT INTO Direccion (id_ciudad, Descripcion, estado) VALUES (?, ?, 'activo')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("is", $id_ciudad, $direccion);
        $stmt->execute();
        $id_direccion = $conexion->insert_id;

        $sql = "INSERT INTO Persona (tipo_persona, nombre, apellido_p, apellido_m, sexo, cedula, email, fecha_nacimiento, id_direccion, nacionalidad, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssssssii", $tipo_persona_db, $nombre, $apellido_p, $apellido_m, $sexo, $cedula, $correo, $fecha_nacimiento, $id_direccion, $nacionalidad);
        $stmt->execute();
        $id_persona = $conexion->insert_id;

        $sql = "INSERT INTO Cliente (id_persona, limite_credito, fecha_creacion, usuario_creacion, estado)
                VALUES (?, 0.00, NOW(), ?, 'activo')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $id_persona, $usuario_creacion);
        $stmt->execute();
        $id_cliente = $conexion->insert_id;

        if (!empty($telefono)) {
            $sql = "INSERT INTO Telefono (numero, estado) VALUES (?, 'activo')";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("s", $telefono);
            $stmt->execute();
            $id_telefono = $conexion->insert_id;

            $sql = "INSERT INTO Cliente_Telefono (id_cliente, id_telefono, fecha_creacion, estado) VALUES (?, ?, NOW(), 'activo')";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $id_cliente, $id_telefono);
            $stmt->execute();
        }

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Cliente guardado correctamente']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function actualizar($conexion) {
    try {
        $conexion->begin_transaction();

        $id_cliente = $_POST['id_cliente'];
        
        $sql = "SELECT p.id_persona, p.id_direccion, 
                       (SELECT ct.id_telefono FROM Cliente_Telefono ct WHERE ct.id_cliente = e.id_cliente AND ct.estado = 'activo' LIMIT 1) as id_telefono
                FROM Cliente e
                JOIN Persona p ON e.id_persona = p.id_persona
                WHERE e.id_cliente=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        $id_persona   = $data['id_persona'];
        $id_direccion = $data['id_direccion'];
        $id_telefono_viejo = $data['id_telefono'];

        $tipo_persona_input = $_POST['tipo_persona'] ?? 'fisica';
        $es_empresa = ($tipo_persona_input === 'juridica');
        
        $tipo_persona_db = $es_empresa ? 'Juridica' : 'Fisica';

        $nombre = $_POST['nombre'];
        $apellido_p = $es_empresa ? '' : ($_POST['apellido_p'] ?? '');
        $apellido_m = $es_empresa ? '' : ($_POST['apellido_m'] ?? '');
        $sexo = $es_empresa ? NULL : ($_POST['sexo'] ?? '');
        $cedula = $_POST['cedula'];
        $correo = $_POST['correo'];
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $nacionalidad = $_POST['nacionalidad'];
        $direccion = $_POST['direccion'];
        $id_ciudad = $_POST['ciudad'];
        $telefono = $_POST['telefono'];
        $estado = $_POST['estado'] ?? 'activo';

        $sql = "UPDATE Direccion SET id_ciudad=?, Descripcion=? WHERE id_direccion=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isi", $id_ciudad, $direccion, $id_direccion);
        $stmt->execute();

        $sql = "UPDATE Persona SET tipo_persona=?, nombre=?, apellido_p=?, apellido_m=?, sexo=?, cedula=?, email=?, fecha_nacimiento=?, nacionalidad=? WHERE id_persona=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssssssii", $tipo_persona_db, $nombre, $apellido_p, $apellido_m, $sexo, $cedula, $correo, $fecha_nacimiento, $nacionalidad, $id_persona);
        $stmt->execute();

        if (!empty($telefono)) {
            if ($id_telefono_viejo) {
                $sql = "UPDATE Telefono SET numero=? WHERE id_telefono=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("si", $telefono, $id_telefono_viejo);
                $stmt->execute();
            } else {
                $sql = "INSERT INTO Telefono (numero, estado) VALUES (?, 'activo')";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("s", $telefono);
                $stmt->execute();
                $nuevo_id = $conexion->insert_id;

                $sql = "INSERT INTO Cliente_Telefono (id_cliente, id_telefono, fecha_creacion, estado) VALUES (?, ?, NOW(), 'activo')";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ii", $id_cliente, $nuevo_id);
                $stmt->execute();
            }
        }

        $sql = "UPDATE Cliente SET estado=? WHERE id_cliente=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $estado, $id_cliente);
        $stmt->execute();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Cliente actualizado correctamente']);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>