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

// Cargar
function cargar($conexion) {

    $sql = "SELECT 
            e.id_empleado,

            -- PERSONA
            p.nombre,
            p.nombre_dos,
            p.apellido_p,
            p.apellido_m,
            p.cedula,
            p.email,
            p.fecha_nacimiento,
            p.sexo,
            p.nacionalidad,

            -- DIRECCION
            d.descripcion AS direccion,
            c.id_ciudad,
            c.nombre AS ciudad,
            pr.id_provincia,
            pr.nombre AS provincia,
            pa.id_pais,
            pa.nombre AS pais,

            -- TELEFONO EMPLEADO
            t.numero AS telefono,

            -- CONTACTO
            co.nombre AS contacto_nombre,
            tc.numero AS contacto_tel,

            -- EMPLEADO
            e.id_puesto,
            e.id_sueldo,
            e.estado,

            pu.nombre AS puesto,
            s.sueldo AS sueldo

        FROM Empleado e

        JOIN Persona p ON e.id_persona = p.id_persona
        JOIN Direccion d ON p.id_direccion = d.id_direccion

        JOIN Ciudad c ON d.id_ciudad = c.id_ciudad
        JOIN Provincia pr ON c.id_provincia = pr.id_provincia
        JOIN Pais pa ON pr.id_pais = pa.id_pais

        -- TELEFONO EMPLEADO
        JOIN Empleado_Telefono et ON e.id_empleado = et.id_empleado
        JOIN Telefono t ON et.id_telefono = t.id_telefono

        -- CONTACTO
        JOIN Contacto co ON e.id_contacto = co.id_contacto
        JOIN Telefono tc ON co.id_telefono = tc.id_telefono

        -- OTROS
        JOIN Puesto pu ON e.id_puesto = pu.id_puesto
        JOIN Sueldo s ON e.id_sueldo = s.id_sueldo
";


    $resultado = $conexion->query($sql);

    $data = [];

    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}


//////////////////////////////////////////////////////////
// 🔵 CARGAR SELECTS PRINCIPALES
//////////////////////////////////////////////////////////
function cargar_selects($conexion) {

    $data = [];

    // 🔹 PAIS
    $res = $conexion->query("SELECT id_pais, nombre FROM Pais ORDER BY nombre ASC");
    $data['pais'] = $res->fetch_all(MYSQLI_ASSOC);

    // 🔹 PUESTO
    $res = $conexion->query("SELECT id_puesto, nombre FROM Puesto where estado='activo' ORDER BY nombre ASC");
    $data['puesto'] = $res->fetch_all(MYSQLI_ASSOC);

    // 🔹 SUELDO
    $res = $conexion->query("SELECT id_sueldo, sueldo FROM Sueldo where estado='activo' ORDER BY sueldo ASC");
    $data['sueldo'] = $res->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
}

//////////////////////////////////////////////////////////
// 🟡 PROVINCIAS POR PAIS
//////////////////////////////////////////////////////////
function cargar_provincias($conexion) {

    $id_pais = $_GET['id_pais'];

    $stmt = $conexion->prepare("SELECT id_provincia, nombre FROM Provincia WHERE id_pais=?");
    $stmt->bind_param("i", $id_pais);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode($result);
}

//////////////////////////////////////////////////////////
// 🟡 CIUDADES POR PROVINCIA
//////////////////////////////////////////////////////////
function cargar_ciudades($conexion) {

    $id_provincia = $_GET['id_provincia'];

    $stmt = $conexion->prepare("SELECT id_ciudad, nombre FROM Ciudad WHERE id_provincia=?");
    $stmt->bind_param("i", $id_provincia);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode($result);
}

//////////////////////////////////////////////////////////
// 🔴 GUARDAR
//////////////////////////////////////////////////////////
function guardar($conexion) {

    try {
        $conexion->begin_transaction();

        // 🔐 USUARIO DE SESIÓN
        $usuario_creacion = $_SESSION['id_usuario'] ?? null;

        if (!$usuario_creacion) {
            echo json_encode([
                'success' => false,
                'message' => 'Sesión expirada'
            ]);
            return;
        }

        //////////////////////////////////////////////////
        // 🔹 PERSONA
        //////////////////////////////////////////////////
        $nombre1 = $_POST['nombre1'];
        $nombre2 = $_POST['nombre2'];
        $apellido_p = $_POST['apellido_p'];
        $apellido_m = $_POST['apellido_m'];
        $sexo = $_POST['sexo'];
        $cedula = $_POST['cedula'];
        $correo = $_POST['correo'];
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $nacionalidad = $_POST['nacionalidad'];

        //////////////////////////////////////////////////
        // 🔹 DIRECCIÓN
        //////////////////////////////////////////////////
        $id_ciudad = $_POST['ciudad'];
        $direccion = $_POST['direccion'];

        //////////////////////////////////////////////////
        // 🔹 OTROS
        //////////////////////////////////////////////////
        $telefono = $_POST['telefono'];
        $telefono_e = $_POST['telefono_e'];
        $nombre_e = $_POST['nombre_e'];
        $puesto = $_POST['puesto'];
        $sueldo = $_POST['sueldo'];

        //////////////////////////////////////////////////
        // 🏠 DIRECCION
        //////////////////////////////////////////////////
        $sql = "INSERT INTO Direccion (id_ciudad, Descripcion, estado)
                VALUES (?, ?, 'activo')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("is", $id_ciudad, $direccion);
        $stmt->execute();
        $id_direccion = $conexion->insert_id;

        //////////////////////////////////////////////////
        // 👤 PERSONA
        //////////////////////////////////////////////////
        $sql = "INSERT INTO Persona 
        (nombre, nombre_dos, apellido_p, apellido_m, sexo, cedula, email, fecha_nacimiento, id_direccion, nacionalidad, estado, fecha_creacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW())";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssssssii",
            $nombre1, $nombre2, $apellido_p, $apellido_m, $sexo,
            $cedula, $correo, $fecha_nacimiento,
            $id_direccion, $nacionalidad
        );
        $stmt->execute();
        $id_persona = $conexion->insert_id;

        //////////////////////////////////////////////////
        // 📞 TELEFONO
        //////////////////////////////////////////////////
        $sql = "INSERT INTO telefono (numero, estado)
                VALUES (?, 'activo')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $telefono);
        $stmt->execute();
        $id_telefono = $conexion->insert_id;

        $sql = "INSERT INTO telefono (numero, estado)
                VALUES (?, 'activo')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $telefono_e);
        $stmt->execute();
        $id_telefono_contacto = $conexion->insert_id;

        //////////////////////////////////////////////////
        // 🧑‍🤝‍🧑 CONTACTO
        //////////////////////////////////////////////////
        $sql = "INSERT INTO Contacto (nombre, id_telefono, estado)
                VALUES (?, ?, 'activo')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $nombre_e, $id_telefono_contacto);
        $stmt->execute();
        $id_contacto = $conexion->insert_id;

        //////////////////////////////////////////////////
        // 👷 EMPLEADO
        //////////////////////////////////////////////////
        $sql = "INSERT INTO Empleado
        (id_persona, id_puesto, id_sueldo, id_contacto, fecha_creacion, usuario_creacion, estado)
        VALUES (?, ?, ?, ?, NOW(), ?, 'activo')";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iiiii",
            $id_persona, $puesto, $sueldo, $id_contacto, $usuario_creacion
        );
        $stmt->execute();
        $id_empleado = $conexion->insert_id;

        //////////////////////////////////////////////////
        // 🔗 RELACIÓN TELEFONO
        //////////////////////////////////////////////////
        $sql = "INSERT INTO Empleado_Telefono
        (id_empleado, id_telefono, fecha_creacion, estado)
        VALUES (?, ?, NOW(), 'activo')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $id_empleado, $id_telefono);
        $stmt->execute();

        //////////////////////////////////////////////////
        // ✅ CONFIRMAR
        //////////////////////////////////////////////////
        $conexion->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Empleado guardado correctamente'
        ]);

    } catch (Exception $e) {

        $conexion->rollback();

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function actualizar($conexion) {

    try {
        $conexion->begin_transaction();

        // 🔹 ID PRINCIPAL
        $id_empleado = $_POST['id_empleado'];

        //////////////////////////////////////////////////
        // 🔎 OBTENER IDS RELACIONADOS
        //////////////////////////////////////////////////
        $sql = "SELECT e.id_persona, p.id_direccion, e.id_contacto
                FROM Empleado e
                JOIN Persona p ON e.id_persona = p.id_persona
                WHERE e.id_empleado=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_empleado);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        $id_persona   = $data['id_persona'];
        $id_direccion = $data['id_direccion'];
        $id_contacto  = $data['id_contacto'];

        //////////////////////////////////////////////////
        // 🔹 DATOS DEL FORMULARIO
        //////////////////////////////////////////////////
        $nombre1 = $_POST['nombre1'];
        $nombre2 = $_POST['nombre2'];
        $apellido_p = $_POST['apellido_p'];
        $apellido_m = $_POST['apellido_m'];
        $sexo = $_POST['sexo'];
        $cedula = $_POST['cedula'];
        $correo = $_POST['correo'];
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $nacionalidad = $_POST['nacionalidad'];

        $direccion = $_POST['direccion'];
        $id_ciudad = $_POST['ciudad'];

        $telefono = $_POST['telefono'];       // empleado
        $telefono_e = $_POST['telefono_e'];   // contacto
        $nombre_e = $_POST['nombre_e'];

        $puesto = $_POST['puesto'];
        $sueldo = $_POST['sueldo'];
        $estado = $_POST['estado'] ?? 'activo';

        //////////////////////////////////////////////////
        // 🏠 DIRECCION
        //////////////////////////////////////////////////
        $sql = "UPDATE Direccion 
                SET id_ciudad=?, Descripcion=? 
                WHERE id_direccion=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isi", $id_ciudad, $direccion, $id_direccion);
        $stmt->execute();

        //////////////////////////////////////////////////
        // 👤 PERSONA
        //////////////////////////////////////////////////
        $sql = "UPDATE Persona SET
                nombre=?, nombre_dos=?, apellido_p=?, apellido_m=?, 
                sexo=?, cedula=?, email=?, fecha_nacimiento=?, nacionalidad=?
                WHERE id_persona=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssssssii",
            $nombre1, $nombre2, $apellido_p, $apellido_m,
            $sexo, $cedula, $correo, $fecha_nacimiento,
            $nacionalidad, $id_persona
        );
        $stmt->execute();

        //////////////////////////////////////////////////
        // 📞 TELEFONO EMPLEADO
        //////////////////////////////////////////////////
        $sql = "UPDATE Telefono t
                JOIN Empleado_Telefono et ON t.id_telefono = et.id_telefono
                SET t.numero=?
                WHERE et.id_empleado=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $telefono, $id_empleado);
        $stmt->execute();

        //////////////////////////////////////////////////
        // 🚨 TELEFONO CONTACTO
        //////////////////////////////////////////////////
        // Obtener ID del teléfono del contacto
        $sql = "SELECT id_telefono FROM Contacto WHERE id_contacto=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_contacto);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        $id_telefono_contacto = $res['id_telefono'];

        // Actualizar teléfono del contacto
        $sql = "UPDATE Telefono SET numero=? WHERE id_telefono=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $telefono_e, $id_telefono_contacto);
        $stmt->execute();

        //////////////////////////////////////////////////
        // 🧑‍🤝‍🧑 CONTACTO (solo nombre)
        //////////////////////////////////////////////////
        $sql = "UPDATE Contacto SET nombre=? WHERE id_contacto=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $nombre_e, $id_contacto);
        $stmt->execute();

        //////////////////////////////////////////////////
        // 👷 EMPLEADO
        //////////////////////////////////////////////////
        $sql = "UPDATE Empleado 
                SET id_puesto=?, id_sueldo=?, estado=? 
                WHERE id_empleado=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iisi", $puesto, $sueldo, $estado, $id_empleado);
        $stmt->execute();

        //////////////////////////////////////////////////
        // ✅ CONFIRMAR
        //////////////////////////////////////////////////
        $conexion->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Empleado actualizado correctamente'
        ]);

    } catch (Exception $e) {

        $conexion->rollback();

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}