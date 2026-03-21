<?php
// Incluye la conexión a la base de datos una sola vez al inicio del script
include("../../controller/conexion.php");

// Establece el encabezado para indicar que la respuesta es JSON
header('Content-Type: application/json');

// Obtiene la acción solicitada desde el parámetro 'action' en la URL
$action = $_GET['action'] ?? '';

//La estructura 'switch' dirige la petición a la función correspondiente
switch ($action) {
    case 'cargar':
        cargar($conexion);
        break;
    case 'guardar':
        guardar($conexion);
        break;
    case 'actualizar':
        actualizar($conexion);
        break;
    case 'desactivar':
        desactivar($conexion);
        break;
    default:
        // Mensaje de error para acciones no válidas
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}

/**
 * Función para cargar los movimientos desde la base de datos.
 * Incluye la lógica de paginación.
 */
function cargar($conexion) {

    // Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
$offset = ($page - 1) * $limit;

// 1. Contar el total de registros de la tabla 'sala'
$count_sql = "SELECT COUNT(*) as total FROM Auditoria_Log";
$count_result = $conexion->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];

// 2. Obtener los registros de la página actual de la tabla 'sala'
$proveedor = [];
$sql = "SELECT 
    a.sec_log, 
    a.id_usuario,
    u.username,
    a.ip_equipo, 
    a.fecha, 
    a.tipo 
FROM Auditoria_Log a
INNER JOIN Usuario u ON a.id_usuario = u.id_usuario
LIMIT $limit OFFSET $offset";

$resultado = $conexion->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $proveedor[] = $fila;
    }
}

// Cierra la conexión a la base de datos
$conexion->close();

// Devuelve los datos paginados y el total de registros
echo json_encode([
    'data' => $proveedor,
    'total_records' => (int)$total_rows,
    'page' => $page,
    'limit' => $limit
]);
}

// /**
//  * Función para guardar un nuevo movimiento en la base de datos.
//  */
// function guardar($conexion) {
//     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//         $nombre = $conexion->real_escape_string($_POST['nombre'] ?? '');
//         $contrasena = $conexion->real_escape_string($_POST['contrasena'] ?? '');
//         $correo = $conexion->real_escape_string($_POST['correo'] ?? '');
//         $nivel = $conexion->real_escape_string($_POST['nivel'] ?? '');
//         $interfaz = $conexion->real_escape_string($_POST['interfaz'] ?? '');
    
    
//         if (empty($nombre) || empty($contrasena) || empty($correo)) {
//             echo json_encode(['success' => false, 'message' => 'Faltan datos para guardar.']);
//             exit;
//         }
    
//         $sql = "INSERT INTO usuario (username, password_hash, correo_org, nivel, interfaz_acceso, estado, fecha_creacion, usuario_creacion, id_organizacion)

//                 VALUES ('$nombre', '$contrasena', '$correo', '$nivel', '$interfaz', 'Activo', NOW(), 1, 1)";
              
    
//         if ($conexion->query($sql) === TRUE) {
//             echo json_encode(['success' => true, 'message' => 'Registro guardado con exito.']);
//             exit;
//         } else {
//             echo "Error al guardar: " . $conexion->error;
//         }
//         $conexion->close();
//     } else {
//         echo "Método no permitido.";
//     }
// }

// /**
//  * Función para actualizar un movimiento existente.
//  */
// function actualizar($conexion) {

// // Inicializa una respuesta estándar
// $response = array('success' => false, 'message' => '');

// // Verifica si la solicitud es de tipo POST
// if ($_SERVER["REQUEST_METHOD"] == "POST") {
//     // Recibe los datos del formulario.
//     // Usamos el Id_Usuario para saber qué registro actualizar.
//     $id_usuario = $_POST['id_usuario'] ?? null;
//     $nombre = $_POST['nombre'] ?? null;
//     $contrasena = $_POST['contrasena'] ?? null;
//     $correo = $_POST['correo'] ?? null;
//     $nivel = $_POST['nivel'] ?? null;
//     $interfaz = $_POST['interfaz'] ?? null;



//     // Se valida que el ID del usuario no esté vacío
//     if ($id_usuario === null || $id_usuario === '') {
//         $response['message'] = 'ID del usuario no proporcionado.';
//         echo json_encode($response);
//         exit;
//     }

//     // Prepara la consulta SQL para actualizar los datos.
//     $sql = "UPDATE usuario SET
//                 username = ?,
//                 correo_org = ?,
//                 nivel = ?,
//                 interfaz_acceso = ?
//             WHERE id_usuario = ?";

//     // Usa sentencias preparadas para mayor seguridad
//     if ($stmt = $conexion->prepare($sql)) {
//         // Asocia los parámetros a la consulta.
//         // 'ssssssssisi' son los tipos de datos: string, string, string, etc.
//         $stmt->bind_param("ssssi", $nombre, $correo, $nivel, $interfaz, $id_usuario);

//         // Ejecuta la consulta
//         if ($stmt->execute()) {
//             if ($stmt->affected_rows > 0) {
//                 $response['success'] = true;
//                 $response['message'] = 'Registro actualizado correctamente.';
//             } else {
//                 $response['message'] = 'No se realizaron cambios en el registro o no se encontró el ID.';
//             }
//         } else {
//             $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
//         }
//         $stmt->close();
//     } else {
//         $response['message'] = 'Error al preparar la consulta: ' . $conexion->error;
//     }
// } else {
//     $response['message'] = 'Método de solicitud no válido.';
// }

// // Cierra la conexión a la base de datos
// $conexion->close();

// // Devuelve la respuesta en formato JSON
// echo json_encode($response);
// }

// /**
//  * Función para desactivar (eliminar lógicamente) un movimiento.
//  */
// function desactivar($conexion) {
//     $response = array('success' => false, 'message' => '');

//     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//         // El JavaScript envía 'id_usuario', por lo que el PHP debe recibir 'id_usuario'
//         $id_usuario = $_POST['id_usuario'] ?? '';
    
//         if (empty($id_usuario)) {
//             $response['message'] = 'ID del usuario no proporcionado.';
//             echo json_encode($response);
//             exit;
//         }
    
//         // Prepara la consulta para actualizar el estado
//         $sql = "UPDATE usuario SET estado = 'Inactivo' WHERE id_usuario = ?";
        
//         if ($stmt = $conexion->prepare($sql)) {
//             $stmt->bind_param("i", $id_usuario);
    
//             if ($stmt->execute()) {
//                 if ($stmt->affected_rows > 0) {
//                     $response['success'] = true;
//                     $response['message'] = 'Registro desactivado correctamente.';
//                 } else {
//                     $response['message'] = 'No se realizó ningún cambio o no se encontró el ID.';
//                 }
//             } else {
//                 $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
//             }
//             $stmt->close();
//         } else {
//             $response['message'] = 'Error al preparar la consulta: ' . $conexion->error;
//         }
        
//         $conexion->close();
//     } else {
//         $response['message'] = 'Método de solicitud no válido.';
//     }
    
//     echo json_encode($response);
// }
// ?>