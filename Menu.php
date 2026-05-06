<?php
session_start();
// Asegúrate de que la ruta apunte a tu archivo de conexión real
include("controller/conexion.php"); 

// Validación de sesión activa
if (!isset($_SESSION['id_usuario'])) {
    // header("Location: index.php"); exit;
}
$nombreTaller = "Mecánica Automotriz Díaz & Pantaleón";
$usuarioActivo = $_SESSION['user'] ?? "Administrador";
$id_usuario_sesion = $_SESSION['id_usuario'] ?? 1;
$id_rol_usuario = $_SESSION['id_rol'] ?? 1; // Usado para el botón de personalizar
$id_sucursal_sesion = $_SESSION['id_sucursal'] ?? 0;
$modulos = $_SESSION['modulos'] ?? [];

// Arreglo de fecha en español
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_actual = $meses[date('n') - 1];
$fecha_actual = date('d') . ' de ' . $mes_actual . ' de ' . date('Y');

// ==========================================
// CONSULTAS REALES A LA BASE DE DATOS
// ==========================================

// 1. Vehículos en Bahía
$sqlBahia = "SELECT COUNT(*) as total FROM orden o 
             WHERE o.estado = 'activo' 
             AND (SELECT e.nombre FROM orden_estado oe JOIN estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = o.id_orden ORDER BY oe.sec_orden_estado DESC LIMIT 1) LIKE '%Reparaci%'";
$resBahia = $conexion->query($sqlBahia);
$vehiculos_bahia = $resBahia ? $resBahia->fetch_assoc()['total'] : 0;

// 2. Presupuestos Pendientes
if ($id_rol_usuario == 1) {
    $sqlPres = "SELECT COUNT(*) as total FROM cotizacion WHERE estado IN ('Pendiente', 'pendiente')";
} else {
    $sqlPres = "SELECT COUNT(*) as total FROM cotizacion 
                WHERE estado IN ('Pendiente', 'pendiente') 
                AND id_sucursal = '$id_sucursal_sesion'";
}
$resPres = $conexion->query($sqlPres);
$presupuestos_pendientes = $resPres ? $resPres->fetch_assoc()['total'] : 0;

// 3. Listos para Entrega
$sqlListos = "SELECT COUNT(*) as total FROM orden o 
              WHERE o.estado = 'activo' 
              AND (SELECT e.nombre FROM orden_estado oe JOIN estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = o.id_orden ORDER BY oe.sec_orden_estado DESC LIMIT 1) = 'Listo'";
$resListos = $conexion->query($sqlListos);
$listos_entrega = $resListos ? $resListos->fetch_assoc()['total'] : 0;

$stats = [
    "vehiculos_bahia" => $vehiculos_bahia,
    "presupuestos_pendientes" => $presupuestos_pendientes,
    "listos_entrega" => $listos_entrega
];

// 4. Tabla de Vehículos en Taller
$sqlTabla = "SELECT o.id_orden, 
                    CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) AS cliente,
                    CONCAT(m.nombre, ' ', v.modelo) AS marca_modelo, 
                    v.placa,
                    IFNULL((SELECT e.nombre FROM orden_estado oe JOIN estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = o.id_orden ORDER BY oe.sec_orden_estado DESC LIMIT 1), 'Ingresado') AS estado_actual,
                    IFNULL((SELECT p_emp.nombre FROM asignacion_orden ao 
                            JOIN asignacion_personal ap ON ao.id_asignacion = ap.id_asignacion 
                            JOIN empleado e ON ap.id_empleado = e.id_empleado 
                            JOIN persona p_emp ON e.id_persona = p_emp.id_persona 
                            WHERE ao.id_orden = o.id_orden LIMIT 1), 'Sin asignar') AS mecanico
             FROM orden o
             JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
             JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
             JOIN marca m ON v.id_marca = m.id_marca
             JOIN cliente c ON v.id_cliente = c.id_cliente
             JOIN persona p ON c.id_persona = p.id_persona
             WHERE o.estado = 'activo'
             ORDER BY o.id_orden DESC LIMIT 6";
$resTabla = $conexion->query($sqlTabla);
$vehiculosTabla = $resTabla ? $resTabla->fetch_all(MYSQLI_ASSOC) : [];

// 5. Gráfica de Ingresos 
$mostrarIngresos = in_array("Ingresos", $modulos);
$dias_grafica = [];
$ingresos_grafica = [];

if ($mostrarIngresos) {
    for($i = 6; $i >= 0; $i--) {
        $fecha_db = date('Y-m-d', strtotime("-$i days"));
        $fecha_label = date('d/m', strtotime("-$i days")); 
        $dias_grafica[] = $fecha_label;

        $qTaller = $conexion->query("SELECT IFNULL(SUM(monto_total), 0) as t FROM factura_central WHERE DATE(fecha_emision) = '$fecha_db' AND estado != 'eliminado' AND estado_pago != 'Cancelado'");
        $valTaller = $qTaller ? (float)$qTaller->fetch_assoc()['t'] : 0;
        
        $qLavado = $conexion->query("SELECT IFNULL(SUM(monto_total), 0) as t FROM factura_lavado WHERE DATE(fecha_creacion) = '$fecha_db' AND estado != 'eliminado'");
        $valLavado = $qLavado ? (float)$qLavado->fetch_assoc()['t'] : 0;

        $ingresos_grafica[] = $valTaller + $valLavado;
    }
}

// 6. Alerta de Stock Crítico
$mostrarInventario = in_array("Inventario", $modulos);
$stock_critico = [];

if ($mostrarInventario) {
    $sqlStock = "SELECT ra.nombre, ra.num_serie, SUM(i.cantidad) as stock_actual, i.stock_minimo 
                 FROM inventario i 
                 JOIN repuesto_articulo ra ON i.id_articulo = ra.id_articulo 
                 WHERE i.estado = 'activo' AND ra.estado = 'activo'
                 GROUP BY ra.id_articulo, ra.nombre, ra.num_serie, i.stock_minimo 
                 HAVING stock_actual <= IFNULL(i.stock_minimo, 5)
                 ORDER BY stock_actual ASC LIMIT 20";
    $resStock = $conexion->query($sqlStock);
    $stock_critico = $resStock ? $resStock->fetch_all(MYSQLI_ASSOC) : [];
}

// 7. Alertas de Cobros Pendientes
$mostrarFacturacion = in_array("Facturacion", $modulos);
$cobros_pendientes = [];

if ($mostrarFacturacion) {
    $sqlCobros = "SELECT apc.numero_cuota, apc.monto_cuota, apc.fecha_programada, 
                         fc.id_factura, CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente
                  FROM acuerdo_pago_cuotas apc
                  JOIN factura_central fc ON apc.id_factura = fc.id_factura
                  JOIN cliente c ON fc.id_cliente = c.id_cliente
                  JOIN persona p ON c.id_persona = p.id_persona
                  WHERE apc.estado_cuota = 'Pendiente' 
                    AND apc.fecha_programada <= CURDATE()
                    AND apc.estado = 'activo'
                  ORDER BY apc.fecha_programada ASC LIMIT 20";
    $resCobros = $conexion->query($sqlCobros);
    $cobros_pendientes = $resCobros ? $resCobros->fetch_all(MYSQLI_ASSOC) : [];
}

// 8. Mis Trabajos Asignados
$mostrarMisTrabajos = in_array("Taller", $modulos);
$mis_trabajos = [];

if ($mostrarMisTrabajos) {
    $sqlMisTrabajos = "SELECT ao.id_orden, ts.nombre as servicio, ap.estado_asignacion, 
                              DATE_FORMAT(ap.hora_asignacion, '%h:%i %p') as hora,
                              CONCAT(m.nombre, ' ', v.modelo) AS vehiculo
                       FROM detalle_asignacion_p dap
                       JOIN asignacion_personal ap ON dap.id_asignacion = ap.id_asignacion
                       JOIN asignacion_orden ao ON ap.id_asignacion = ao.id_asignacion
                       JOIN tipo_servicio ts ON ap.id_tipo_servicio = ts.id_tipo_servicio
                       JOIN empleado_usuario eu ON dap.id_empleado = eu.id_empleado
                       JOIN orden o ON ao.id_orden = o.id_orden
                       JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                       JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                       JOIN marca m ON v.id_marca = m.id_marca
                       WHERE eu.id_usuario = '$id_usuario_sesion'
                         AND ap.estado_asignacion IN ('Pendiente', 'En Curso')
                         AND ap.estado = 'activo'
                       ORDER BY ap.fecha_asignacion ASC, ap.hora_asignacion ASC LIMIT 20";
    $resMisTrabajos = $conexion->query($sqlMisTrabajos);
    $mis_trabajos = $resMisTrabajos ? $resMisTrabajos->fetch_all(MYSQLI_ASSOC) : [];
}

// 9. Seguimiento de Cotizaciones por Aprobar
$mostrarCotizaciones = in_array("Facturacion", $modulos) || in_array("Resumen", $modulos);
$cotizaciones_pendientes = [];

if ($mostrarCotizaciones) {
    $sqlCot = "SELECT id_cotizacion, nombre_cliente, vehiculo_desc, monto_total, telefono_cliente, fecha_creacion
               FROM cotizacion 
               WHERE estado IN ('Pendiente', 'pendiente') ";
    if ($id_rol_usuario != 1) {
        $sqlCot .= " AND id_sucursal = '$id_sucursal_sesion' ";
    }
    $sqlCot .= " ORDER BY fecha_creacion ASC LIMIT 20"; 
    $resCot = $conexion->query($sqlCot);
    $cotizaciones_pendientes = $resCot ? $resCot->fetch_all(MYSQLI_ASSOC) : [];
}

// 10. Vehículos Listos para Avisar al Cliente
$mostrarAvisos = in_array("Taller", $modulos) || in_array("Facturacion", $modulos) || in_array("Resumen", $modulos);
$vehiculos_listos_aviso = [];

if ($mostrarAvisos) {
    $sqlAvisos = "SELECT o.id_orden, 
                         CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) AS cliente,
                         CONCAT(m.nombre, ' ', v.modelo) AS vehiculo,
                         (SELECT t.numero FROM cliente_telefono ct JOIN telefono t ON ct.id_telefono = t.id_telefono WHERE ct.id_cliente = c.id_cliente AND ct.estado='activo' LIMIT 1) AS telefono
                  FROM orden o
                  JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
                  JOIN vehiculo v ON i.id_vehiculo = v.sec_vehiculo
                  JOIN marca m ON v.id_marca = m.id_marca
                  JOIN cliente c ON v.id_cliente = c.id_cliente
                  JOIN persona p ON c.id_persona = p.id_persona
                  WHERE o.estado = 'activo'
                  AND (SELECT e.nombre FROM orden_estado oe JOIN estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = o.id_orden ORDER BY oe.sec_orden_estado DESC LIMIT 1) = 'Listo'
                  ORDER BY o.id_orden ASC LIMIT 20";
    $resAvisos = $conexion->query($sqlAvisos);
    $vehiculos_listos_aviso = $resAvisos ? $resAvisos->fetch_all(MYSQLI_ASSOC) : [];
}

// 11. Top 5 Servicios más Vendidos
$mostrarTopServicios = in_array("Resumen", $modulos) || in_array("Facturacion", $modulos);
$top_servicios = [];

if ($mostrarTopServicios) {
    $sqlTop = "SELECT ts.nombre, SUM(os.cantidad) as total_vendido, SUM(os.precio_estimado * os.cantidad) as ingresos_generados
               FROM orden_servicio os
               JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio
               WHERE os.estado = 'activo'
               GROUP BY ts.id_tipo_servicio, ts.nombre
               ORDER BY total_vendido DESC LIMIT 5";
    $resTop = $conexion->query($sqlTop);
    $top_servicios = $resTop ? $resTop->fetch_all(MYSQLI_ASSOC) : [];
}

// 12. Estado de mi Caja (Turno Actual)
$estado_caja = null;

if ($mostrarFacturacion) {
    $sqlCaja = "SELECT id_sesion, fecha_apertura, monto_inicial, estado 
                FROM caja_sesion 
                WHERE id_sucursal = '$id_sucursal_sesion' 
                AND estado = 'Abierta' 
                ORDER BY id_sesion DESC LIMIT 1";
    $resCaja = $conexion->query($sqlCaja);
    if ($resCaja && $resCaja->num_rows > 0) {
        $estado_caja = $resCaja->fetch_assoc();
    }
}

// 13. Productividad por Mecánico
$mostrarProductividad = in_array("Resumen", $modulos) || in_array("RRHH", $modulos);
$productividad_mecanicos = [];

if ($mostrarProductividad) {
    $sqlProd = "SELECT CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as mecanico, 
                       COUNT(ap.id_asignacion) as trabajos_completados
                FROM detalle_asignacion_p dap
                JOIN asignacion_personal ap ON dap.id_asignacion = ap.id_asignacion
                JOIN empleado e ON dap.id_empleado = e.id_empleado
                JOIN persona p ON e.id_persona = p.id_persona
                WHERE ap.estado_asignacion = 'Completado' 
                  AND MONTH(ap.fecha_asignacion) = MONTH(CURDATE()) 
                  AND YEAR(ap.fecha_asignacion) = YEAR(CURDATE())
                GROUP BY e.id_empleado, p.nombre, p.apellido_p
                ORDER BY trabajos_completados DESC LIMIT 5";
    $resProd = $conexion->query($sqlProd);
    $productividad_mecanicos = $resProd ? $resProd->fetch_all(MYSQLI_ASSOC) : [];
    $max_trabajos = !empty($productividad_mecanicos) ? max(array_column($productividad_mecanicos, 'trabajos_completados')) : 1;
}

// 14. Recepciones Esperadas (Módulo Inventario / Compras) - CORREGIDO CON GROUP BY
$mostrarRecepciones = in_array("Inventario", $modulos);
$recepciones_esperadas = [];

if ($mostrarRecepciones) {
    try {
        $sqlRec = "SELECT c.id_compra as id_orden_compra, c.fecha_creacion as fecha_emision, c.monto as monto_total, p.nombre_comercial 
                   FROM compra c
                   JOIN proveedor p ON c.id_proveedor = p.id_proveedor
                   JOIN detalle_compra dc ON c.id_compra = dc.id_compra
                   WHERE c.estado = 'activo' AND dc.estado = 'espera'
                   GROUP BY c.id_compra, c.fecha_creacion, c.monto, p.nombre_comercial
                   ORDER BY c.fecha_creacion ASC LIMIT 20";
        $resRec = $conexion->query($sqlRec);
        if ($resRec) {
            $recepciones_esperadas = $resRec->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        $recepciones_esperadas = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nombreTaller; ?> - Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Archivo_Menu.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <?php if ($mostrarIngresos): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
        }

        .contenido {
            padding-top: 20px;
            padding-bottom: 40px;
            overflow-x: hidden;
        }

        .metric-card {
            border-radius: 12px;
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            background: #ffffff;
            height: 100%;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
            color: inherit;
        }

        .dashboard-panel {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border: 1px solid #f0f0f0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            padding: 15px 25px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #fff;
            border-radius: 12px 12px 0 0;
        }

        .panel-header h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .table-custom-wrapper {
            overflow-x: auto;
            flex-grow: 1;
        }

        .table-custom {
            margin-bottom: 0;
            width: 100%;
        }

        .table-custom th {
            font-size: 0.75rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            border-bottom-width: 1px;
            padding: 12px 15px;
            letter-spacing: 0.5px;
        }

        .table-custom td {
            font-size: 0.85rem;
            vertical-align: middle;
            padding: 12px 15px;
            color: #495057;
            border-bottom: 1px solid #f0f2f5;
        }

        .widget-scroll {
            max-height: 250px; 
            overflow-y: auto;
        }
        
        .widget-scroll::-webkit-scrollbar { width: 6px; }
        .widget-scroll::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .widget-scroll::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        .widget-scroll::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

        .notification-btn {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }

        .notification-btn:hover { background: #f8f9fa; }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 0.25em 0.4em;
            font-size: 0.65rem;
            font-weight: bold;
            border: 2px solid white;
        }

        /* Clase para ocultar widgets controlados por el Administrador */
        .widget-hidden {
            display: none !important;
        }

        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.25em;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="Menu.php"><img src="img/logo.png" class="logo-img" alt="Logo"></a>
        </div>
        
        <nav class="menu-container">
            <?php if (in_array("Seguridad", $modulos)) : ?>
            <div class="modulo">
                <button class="modulo-btn">
                    <img src="img/seguridad.png" class="icono-modulo"> <span>Seguridad</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Seguridad/MUsuario.php">Usuarios</a>
                    <a href="/Taller/Taller-Mecanica/view/Seguridad/MRoles.php">Roles y Permisos</a>
                    <a href="/Taller/Taller-Mecanica/view/Seguridad/CHistorialAcceso.php">Historial de Accesos</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array("Resumen", $modulos)) : ?>
            <div class="modulo">
                <button class="modulo-btn">
                    <img src="img/resumen.png" class="icono-modulo"> <span>Resumen Operativo</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Resumen/MStock.php">M. Stock</a>
                    <a href="/Taller/Taller-Mecanica/view/Resumen/MServicios.php">M. Servicios</a>
                    <a href="/Taller/Taller-Mecanica/view/Resumen/MCotizacion.php">M. Cotizacion</a>
                    <a href="/Taller/Taller-Mecanica/view/Resumen/MFactura.php">M. Factura</a>
                    <a href="/Taller/Taller-Mecanica/view/Resumen/MCliente.php">M. Cliente</a>
                    <a href="/Taller/Taller-Mecanica/view/Resumen/MSucursal.php">M. Sucursal</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("RRHH", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/rrhh.png" class="icono-modulo"> <span>Recursos Humanos</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/RRHH/MEmpleado.php">Empleados</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/MSucursal.php">Sucursales</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/MDepartamento.php">Departamentos</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/MPuesto.php">Puestos</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/MEspecialidad.php">Especialidades</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/GestionUsuario.php">Gestion de Usuarios</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/GestionPermisos.php">Gestion de Permisos</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/GestionEspecialidades.php">Gestion Especialidades</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/GestionDepartamento.php">Departamento a Sucursal</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/GestEmpleado_Sucursal.php">Empleado por Sucursal</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Cliente", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/cliente.png" class="icono-modulo"> <span>Cliente</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Cliente/MCliente.php">Gestión de Clientes</a>
                    <a href="/Taller/Taller-Mecanica/view/Cliente/MCredito.php">Créditos y Cobranzas</a>
                    <a href="/Taller/Taller-Mecanica/view/Cliente/CHistorialCredito.php">Historial de Crédito</a>
                    <a href="/Taller/Taller-Mecanica/view/Cliente/CHistorialCredito.php">Consulta de Deuda</a>
                    <a href="/Taller/Taller-Mecanica/view/Cliente/CDatacredito.php">Maestro DataCredito</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Vehiculo", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/coche.png" class="icono-modulo"> <span>Vehículo</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Vehiculo/MVehiculo.php">Registro de Vehículos</a>
                    <?php if (in_array("Seguridad", $modulos)) : ?>
                    <a href="/Taller/Taller-Mecanica/view/Vehiculo/MMarcaModelo.php">Marcas</a>
                    <a href="/Taller/Taller-Mecanica/view/Vehiculo/MModelo.php">Modelos</a>
                    <?php endif; ?>
                    <a href="/Taller/Taller-Mecanica/view/Vehiculo/RHistorialVehiculo.php">Historial Vehiculo</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Inventario", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/inventario.png" class="icono-modulo"> <span>Inventario</span>
                </button>
                <div class="modulo-content">
                    <?php if (in_array("Seguridad", $modulos)) : ?>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MArticulo.php">Artículos y Repuestos</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MMarcaProducto.php">Marcas Asociadas</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MAlmacen.php">Almacenes</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MProveedor.php">Proveedores</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MCompra.php">Orden de Compra</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MPagoCompra.php">Pago de Compra</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MovimientoStock.php">Movimientos de Stock</a>
                    <?php endif; ?>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/Transferencia.php">Transferencia de Stock</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/RecepcionCompra.php">Recepción de Compra</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MHistorialCompra.php">Historial de Compra</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MHistorialPago.php">Historial de Pagos</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MHistorialMovimientoStock.php">Historial de Mov. de Stock</a>
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MHistorialRecepcion.php">Historial de Recepción</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Taller", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/taller.png" class="icono-modulo"> <span>Taller</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Taller/MInspeccion.php">Inspecciones Técnicas</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/Servicio.php">Órdenes de Servicio</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MTipoServicio.php">Registro de Servicios</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MTrabajosSolicitados.php">Trabajos Solicitados</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MBahia.php">Gestión de Bahías</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MMaquinaria.php">Gestión de Recursos</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/RegistroTiempo.php">Tiempos y Asignacion</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/EntregaServicio.php">Entrega de Servicios</a>
                     <?php if (in_array("Seguridad", $modulos)) : ?>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MPoliticaGarantia.php">Politicas de Garantia</a>
                    <?php endif; ?>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MReclamoGarantia.php">Reclamos de Garantia</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/HistorialServicio.php">Historial de Servicios</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/HistorialEntrega.php">Historial de Entregas</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/CHistorialInspeccion.php">Historial de Inspecciones</a>
                </div>
            </div>
            <?php endif; ?>
  
            <div class="modulo">
                <?php if (in_array("Facturacion", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/facturacion.png" class="icono-modulo"> <span>Facturacion</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/MCaja.php">Apertura y Cierre de Caja</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/Factura.php">Nueva Factura (POS)</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/Cotizacion.php">Gestión de Cotizaciones</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/Devolucion.php">Gestión de Devolucion</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/CuentasPorCobrar.php">Cuentas Por Cobrar</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/RFactura.php">Reportes de Ventas (NCF)</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/HistorialFactura.php">Historial Factura</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/HistorialCotizacion.php">Historial Cotizacion</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/HistorialPagos.php">Historial de Pagos</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/HistorialDevolucion.php">Historial de Devoluciones</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Autolavado", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/lavado.png" class="icono-modulo"> <span>Autolavado</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Autolavado/OLavado.php">Control de Lavados</a>
                    <a href="/Taller/Taller-Mecanica/view/Autolavado/MTipoLavado.php">Tipos de Lavado</a>
                    <a href="/Taller/Taller-Mecanica/view/Autolavado/MPlanMembresia.php">Planes y Membresías</a>
                    <a href="/Taller/Taller-Mecanica/view/Autolavado/HistorialLavado.php">Historial Lavado</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Autoadorno", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/carreras.png" class="icono-modulo"> <span>Autoadorno</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Autoadorno/AServicio.php">Servicios de Detailing</a>
                    <a href="/Taller/Taller-Mecanica/view/Autoadorno/MPaquete.php">Paquetes de Servicio</a>
                    <a href="/Taller/Taller-Mecanica/view/Autoadorno/HistorialServicio.php">Historial de Servicio</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <a href="logout.php" class="modulo-btn" id="logoutBtn" style="text-decoration: none;">
                    <img src="img/salida.png" class="icono-modulo">
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="contenido">
        <div class="container-fluid px-4 py-3">
            <!-- Header del Dashboard -->
            <header class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-3">
                        <h2 class="fw-bold text-dark mb-0">Resumen Operativo</h2>
                        <!-- Botón de configuración solo para Administradores -->
                        <?php if ($id_rol_usuario == 1): ?>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalConfigWidgets" title="Personalizar Dashboard">
                                <i class="fas fa-sliders-h me-1"></i> Personalizar
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-0 mt-1"><i class="far fa-calendar-alt me-1"></i> <?php echo $fecha_actual; ?></p>
                </div>
                
                <div class="d-flex align-items-center gap-4">
                    <div class="dropdown">
                        <button class="notification-btn" type="button" id="dropdownNotificaciones" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell text-secondary"></i>
                            <span class="notification-badge" id="contador-notificaciones">0</span>
                        </button>
                        
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2" aria-labelledby="dropdownNotificaciones" style="width: 320px; padding: 0;">
                            <div class="bg-light px-3 py-2 border-bottom fw-bold text-dark">
                                <i class="fas fa-truck-moving text-primary me-2"></i> Solicitudes de Stock
                            </div>
                            <div id="contenedor-items-notificacion" style="max-height: 250px; overflow-y: auto;">
                                <div class="text-center p-4 text-muted small">Cargando alertas...</div>
                            </div>
                            <div class="border-top p-0 text-center">
                                <a href="/Taller/Taller-Mecanica/view/Inventario/Transferencia.php" class="d-block py-2 small fw-bold text-decoration-none">Ver todo el panel</a>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 shadow-sm" style="width: 40px; height: 40px; font-weight: bold;">
                            <?php echo strtoupper(substr($usuarioActivo, 0, 1)); ?>
                        </div>
                        <div>
                            <span class="d-block fw-bold text-dark small lh-1"><?php echo $usuarioActivo; ?></span>
                            <span class="text-muted" style="font-size: 0.75rem;">Sesión Activa</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Tarjetas de Métricas Fijas -->
            <div class="row g-4 mb-4" id="fila-metricas">
                <?php if (in_array("Taller", $modulos) || in_array("Resumen", $modulos)) : ?>
                <div class="col-xl-3 col-md-6 widget-panel" id="widget-metricas-bahia">
                    <a href="/Taller/Taller-Mecanica/view/Taller/RegistroTiempo.php" class="card metric-card shadow-sm border-start border-primary border-4 p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold mb-2">Vehículos en Bahía</h6>
                                <h2 class="mb-0 fw-bolder text-dark"><?php echo $stats['vehiculos_bahia']; ?></h2>
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-3 fs-4">
                                <i class="fas fa-tools"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="text-primary small fw-bold">Ver taller <i class="fas fa-arrow-right ms-1"></i></span>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (in_array("Facturacion", $modulos) || in_array("Resumen", $modulos)) : ?>
                <div class="col-xl-3 col-md-6 widget-panel" id="widget-metricas-presupuestos">
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/Cotizacion.php" class="card metric-card shadow-sm border-start border-warning border-4 p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold mb-2">Presupuestos Ptes.</h6>
                                <h2 class="mb-0 fw-bolder text-dark"><?php echo $stats['presupuestos_pendientes']; ?></h2>
                            </div>
                            <div class="bg-warning bg-opacity-10 text-warning rounded p-3 fs-4">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="text-dark small fw-bold">Revisar cotizaciones <i class="fas fa-arrow-right ms-1"></i></span>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (in_array("Taller", $modulos) || in_array("Facturacion", $modulos) || in_array("Resumen", $modulos)) : ?>
                <div class="col-xl-3 col-md-6 widget-panel" id="widget-metricas-entregas">
                    <a href="/Taller/Taller-Mecanica/view/Taller/EntregaServicio.php" class="card metric-card shadow-sm border-start border-success border-4 p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold mb-2">Listos para Entrega</h6>
                                <h2 class="mb-0 fw-bolder text-dark"><?php echo $stats['listos_entrega']; ?></h2>
                            </div>
                            <div class="bg-success bg-opacity-10 text-success rounded p-3 fs-4">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="text-success small fw-bold">Ir a entregas <i class="fas fa-arrow-right ms-1"></i></span>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (in_array("Inventario", $modulos)) : ?>
                <div class="col-xl-3 col-md-6 widget-panel" id="widget-metricas-stock">
                    <a href="/Taller/Taller-Mecanica/view/Inventario/MArticulo.php?mode=readonly" class="card metric-card shadow-sm border-start border-info border-4 p-4" style="background-color: #f8fcfd;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="text-info fw-bold mb-1">Consulta de Stock</h6>
                                <span class="text-muted small fw-bold">Disponibilidad Global</span>
                            </div>
                            <div class="bg-white text-info rounded-circle shadow-sm p-3 fs-5 d-flex align-items-center justify-content-center">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-info text-white w-100 fw-bold shadow-sm">Buscar Repuesto</button>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- ROW EXTRA: Widgets dinámicos -->
            <div class="row g-4 mb-4">
                
                <!-- 1. ALERTA DE STOCK CRÍTICO (Solo Inventario) -->
                <?php if ($mostrarInventario): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-stock-critico">
                    <div class="dashboard-panel border-start border-danger border-4">
                        <div class="panel-header bg-white">
                            <h5 class="text-danger mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Stock Crítico</h5>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Requiere atención</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-scroll">
                                <table class="table table-hover table-borderless align-middle mb-0 small">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th class="ps-4">Repuesto</th>
                                            <th class="text-center pe-4">Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($stock_critico)): ?>
                                            <tr><td colspan="2" class="text-center py-5 text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Todo el stock está en niveles óptimos.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($stock_critico as $item): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($item['nombre']); ?>">
                                                        <?php echo $item['nombre']; ?>
                                                    </div>
                                                    <div class="text-muted" style="font-size: 0.75rem;">Cod: <?php echo $item['num_serie']; ?></div>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <span class="badge bg-danger fs-6 shadow-sm"><?php echo $item['stock_actual']; ?> / <?php echo $item['stock_minimo'] ?? 5; ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center border-top py-2 rounded-bottom">
                            <a href="/Taller/Taller-Mecanica/view/Inventario/MArticulo.php" class="text-decoration-none small fw-bold text-danger">Ver todo el inventario <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 2. COBROS PENDIENTES DE HOY / VENCIDOS (Solo Facturación) -->
                <?php if ($mostrarFacturacion): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-cobros-ptes">
                    <div class="dashboard-panel border-start border-warning border-4">
                        <div class="panel-header bg-white">
                            <h5 class="text-warning text-dark mb-0"><i class="fas fa-clock me-2"></i> Cobros Pendientes</h5>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Hoy o Vencidos</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-scroll">
                                <table class="table table-hover table-borderless align-middle mb-0 small">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th class="ps-4">Cliente / Factura</th>
                                            <th class="text-center pe-4">Monto / Cuota</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($cobros_pendientes)): ?>
                                            <tr><td colspan="2" class="text-center py-5 text-success fw-bold"><i class="fas fa-check-circle me-1"></i> No hay cobros atrasados.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($cobros_pendientes as $cobro): 
                                                $fecha_prog = strtotime($cobro['fecha_programada']);
                                                $es_vencido = $fecha_prog < strtotime(date('Y-m-d'));
                                                $color_fecha = $es_vencido ? 'text-danger fw-bold' : 'text-muted';
                                                $texto_fecha = $es_vencido ? 'Vencido: '.date('d/m', $fecha_prog) : 'Vence Hoy';
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($cobro['cliente']); ?>">
                                                        <?php echo $cobro['cliente']; ?>
                                                    </div>
                                                    <div class="<?php echo $color_fecha; ?>" style="font-size: 0.75rem;">Fac. #<?php echo $cobro['id_factura']; ?> | <?php echo $texto_fecha; ?></div>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <div class="fw-bold text-dark">RD$ <?php echo number_format($cobro['monto_cuota'], 2); ?></div>
                                                    <span class="badge bg-light text-dark border shadow-sm" style="font-size: 0.70rem;">Cuota <?php echo $cobro['numero_cuota']; ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center border-top py-2 rounded-bottom">
                            <a href="/Taller/Taller-Mecanica/view/Facturacion/CuentasPorCobrar.php" class="text-decoration-none small fw-bold text-warning text-dark">Ver Cuentas por Cobrar <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 3. MIS TRABAJOS ASIGNADOS (Solo Taller / Mecánico) -->
                <?php if ($mostrarMisTrabajos): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-mis-trabajos">
                    <div class="dashboard-panel border-start border-primary border-4">
                        <div class="panel-header bg-white">
                            <h5 class="text-primary mb-0"><i class="fas fa-wrench me-2"></i> Mis Asignaciones</h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Pendientes de Hoy</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-scroll">
                                <table class="table table-hover table-borderless align-middle mb-0 small">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th class="ps-4">Orden / Vehículo</th>
                                            <th class="text-center pe-4">Servicio / Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($mis_trabajos)): ?>
                                            <tr><td colspan="2" class="text-center py-5 text-muted"><i class="fas fa-coffee fs-4 d-block mb-2 text-success"></i> No tienes trabajos pendientes asignados.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($mis_trabajos as $trabajo): 
                                                $badgeCls = $trabajo['estado_asignacion'] == 'En Curso' ? 'bg-warning text-dark' : 'bg-secondary';
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 150px;">
                                                        #<?php echo $trabajo['id_orden']; ?> - <?php echo $trabajo['vehiculo']; ?>
                                                    </div>
                                                    <div class="text-muted" style="font-size: 0.75rem;"><i class="far fa-clock"></i> Prog: <?php echo $trabajo['hora']; ?></div>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 130px;" title="<?php echo $trabajo['servicio']; ?>">
                                                        <?php echo $trabajo['servicio']; ?>
                                                    </div>
                                                    <span class="badge <?php echo $badgeCls; ?> shadow-sm" style="font-size: 0.70rem;"><?php echo $trabajo['estado_asignacion']; ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center border-top py-2 rounded-bottom">
                            <a href="/Taller/Taller-Mecanica/view/Taller/RegistroTiempo.php" class="text-decoration-none small fw-bold text-primary">Ir a Control de Tiempos <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 4. SEGUIMIENTO DE COTIZACIONES PENDIENTES (Para Facturación/Recepción) -->
                <?php if ($mostrarCotizaciones): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-cotizaciones">
                    <div class="dashboard-panel border-start border-info border-4">
                        <div class="panel-header bg-white">
                            <h5 class="text-info mb-0"><i class="fas fa-file-signature me-2"></i> Cotizaciones Ptes.</h5>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">Requieren Seguimiento</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-scroll">
                                <table class="table table-hover table-borderless align-middle mb-0 small">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th class="ps-4">Cliente / Vehículo</th>
                                            <th class="text-center pe-4">Monto / Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($cotizaciones_pendientes)): ?>
                                            <tr><td colspan="2" class="text-center py-5 text-success fw-bold"><i class="fas fa-check-circle me-1"></i> No hay cotizaciones pendientes.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($cotizaciones_pendientes as $cot): 
                                                $dias = floor((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($cot['fecha_creacion'])))) / (60 * 60 * 24));
                                                $color_dias = $dias > 3 ? 'text-danger fw-bold' : 'text-muted';
                                                $telefono_ws = preg_replace('/[^0-9]/', '', $cot['telefono_cliente']);
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 160px;" title="<?php echo htmlspecialchars($cot['nombre_cliente']); ?>">
                                                        <?php echo $cot['nombre_cliente']; ?>
                                                    </div>
                                                    <div class="text-muted text-truncate" style="max-width: 160px; font-size: 0.75rem;"><i class="fas fa-car-side"></i> <?php echo $cot['vehiculo_desc']; ?></div>
                                                    <div class="<?php echo $color_dias; ?>" style="font-size: 0.70rem;"><i class="far fa-clock"></i> Hace <?php echo $dias; ?> días</div>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <div class="fw-bold text-dark mb-1">RD$ <?php echo number_format($cot['monto_total'], 2); ?></div>
                                                    <?php if(!empty($telefono_ws)): ?>
                                                        <a href="https://api.whatsapp.com/send?phone=1<?php echo $telefono_ws; ?>&text=Hola%20<?php echo urlencode($cot['nombre_cliente']); ?>,%20le%20escribimos%20de%20Mecánica%20Díaz%20Pantaleón%20para%20darle%20seguimiento%20a%20su%20cotización..." target="_blank" class="btn btn-sm btn-success py-0 px-2 shadow-sm" style="font-size: 0.75rem;"><i class="fab fa-whatsapp"></i> Chat</a>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary" style="font-size: 0.70rem;">Sin Teléfono</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center border-top py-2 rounded-bottom">
                            <a href="/Taller/Taller-Mecanica/view/Facturacion/Cotizacion.php" class="text-decoration-none small fw-bold text-info text-dark">Ver Cotizaciones <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 5. VEHÍCULOS LISTOS PARA AVISAR (Recepción / Facturación) -->
                <?php if ($mostrarAvisos): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-avisar-clientes">
                    <div class="dashboard-panel border-start border-success border-4">
                        <div class="panel-header bg-white">
                            <h5 class="text-success mb-0"><i class="fas fa-bullhorn me-2"></i> Avisar a Clientes</h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Vehículos Listos</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-scroll">
                                <table class="table table-hover table-borderless align-middle mb-0 small">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th class="ps-4">Cliente / Orden</th>
                                            <th class="text-center pe-4">Aviso</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($vehiculos_listos_aviso)): ?>
                                            <tr><td colspan="2" class="text-center py-5 text-muted"><i class="fas fa-check-circle fs-4 d-block mb-2 text-success"></i> No hay vehículos esperando cliente.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($vehiculos_listos_aviso as $listo): 
                                                $telefono_ws = preg_replace('/[^0-9]/', '', $listo['telefono']);
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($listo['cliente']); ?>">
                                                        <?php echo $listo['cliente']; ?>
                                                    </div>
                                                    <div class="text-muted" style="font-size: 0.75rem;">Ord. #<?php echo $listo['id_orden']; ?> | <?php echo $listo['vehiculo']; ?></div>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <?php if(!empty($telefono_ws)): ?>
                                                        <a href="https://api.whatsapp.com/send?phone=1<?php echo $telefono_ws; ?>&text=Hola%20<?php echo urlencode($listo['cliente']); ?>,%20le%20informamos%20que%20su%20vehículo%20<?php echo urlencode($listo['vehiculo']); ?>%20ya%20está%20listo%20para%20ser%20retirado%20en%20Mecánica%20Díaz%20Pantaleón." target="_blank" class="btn btn-sm btn-success py-1 px-3 shadow-sm" style="font-size: 0.75rem; border-radius: 20px;"><i class="fab fa-whatsapp me-1"></i> Avisar</a>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary" style="font-size: 0.70rem;">Sin Teléfono</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center border-top py-2 rounded-bottom">
                            <a href="/Taller/Taller-Mecanica/view/Taller/EntregaServicio.php" class="text-decoration-none small fw-bold text-success text-dark">Ir a Entregas <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 6. TOP 5 SERVICIOS MÁS VENDIDOS (Gerencial) -->
                <?php if ($mostrarTopServicios): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-top-servicios">
                    <div class="dashboard-panel border-start border-dark border-4">
                        <div class="panel-header bg-white">
                            <h5 class="text-dark mb-0"><i class="fas fa-trophy me-2 text-warning"></i> Top 5 Servicios</h5>
                            <span class="badge bg-dark-subtle text-dark border border-dark-subtle">Más Vendidos</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-scroll">
                                <table class="table table-hover table-borderless align-middle mb-0 small">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th class="ps-4">Servicio</th>
                                            <th class="text-center pe-4">Volumen / Ingresos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($top_servicios)): ?>
                                            <tr><td colspan="2" class="text-center py-5 text-muted">Aún no hay datos suficientes.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($top_servicios as $index => $top): 
                                                $icono_rank = '';
                                                if ($index == 0) $icono_rank = '<i class="fas fa-crown text-warning me-1"></i>';
                                                else if ($index == 1) $icono_rank = '<span class="text-muted fw-bold me-1">2.</span>';
                                                else if ($index == 2) $icono_rank = '<span class="text-muted fw-bold me-1" style="color: #cd7f32 !important;">3.</span>';
                                                else $icono_rank = '<span class="text-muted small me-1">'.($index+1).'.</span>';
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 170px;" title="<?php echo htmlspecialchars($top['nombre']); ?>">
                                                        <?php echo $icono_rank . $top['nombre']; ?>
                                                    </div>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <div class="badge bg-primary rounded-pill mb-1"><?php echo $top['total_vendido']; ?> realizados</div>
                                                    <div class="text-success fw-bold" style="font-size: 0.75rem;">RD$ <?php echo number_format($top['ingresos_generados'], 2); ?></div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center border-top py-2 rounded-bottom">
                            <a href="/Taller/Taller-Mecanica/view/Resumen/MServicios.php" class="text-decoration-none small fw-bold text-dark">Ver Reporte Completo <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 7. ESTADO DE MI CAJA (Solo Facturación) -->
                <?php if ($mostrarFacturacion): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-estado-caja">
                    <?php if($estado_caja): ?>
                        <div class="dashboard-panel border-start border-success border-4 h-100">
                            <div class="panel-header bg-white">
                                <h5 class="text-success mb-0"><i class="fas fa-cash-register me-2"></i> Mi Turno de Caja</h5>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Abierta</span>
                            </div>
                            <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                                <div class="display-4 text-success mb-2"><i class="fas fa-box-open"></i></div>
                                <h4 class="fw-bold text-dark mb-1">Turno Activo</h4>
                                <p class="text-muted small mb-3">Apertura: <?php echo date('d/m/Y h:i A', strtotime($estado_caja['fecha_apertura'])); ?></p>
                                <div class="bg-light p-3 rounded border w-100 mb-3 mx-auto">
                                    <span class="d-block small text-muted fw-bold text-uppercase mb-1">Fondo Inicial (Menuda)</span>
                                    <span class="fs-4 fw-bold text-dark">RD$ <?php echo number_format($estado_caja['monto_inicial'], 2); ?></span>
                                </div>
                            </div>
                            <div class="card-footer bg-white text-center border-top py-2 rounded-bottom mt-auto">
                                <a href="/Taller/Taller-Mecanica/view/Facturacion/MCaja.php" class="text-decoration-none small fw-bold text-success text-dark">Ir a Gestión de Caja <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="dashboard-panel border-start border-danger border-4 h-100">
                            <div class="panel-header bg-white">
                                <h5 class="text-danger mb-0"><i class="fas fa-cash-register me-2"></i> Mi Turno de Caja</h5>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Cerrada</span>
                            </div>
                            <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                                <div class="display-4 text-danger mb-2"><i class="fas fa-lock"></i></div>
                                <h4 class="fw-bold text-dark mb-2">Turno Cerrado</h4>
                                <p class="text-muted small mb-4">No puedes facturar ni recibir pagos de clientes hasta aperturar formalmente tu turno.</p>
                                <a href="/Taller/Taller-Mecanica/view/Facturacion/MCaja.php" class="btn btn-danger py-2 fw-bold w-100 shadow-sm mx-auto"><i class="fas fa-key me-2"></i> Aperturar Caja Ahora</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- 8. RANKING DE PRODUCTIVIDAD (Gerencial / RRHH) -->
                <?php if ($mostrarProductividad): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-productividad">
                    <div class="dashboard-panel border-start border-secondary border-4">
                        <div class="panel-header bg-white">
                            <h5 class="text-secondary mb-0"><i class="fas fa-users-cog me-2"></i> Productividad</h5>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Mes Actual</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-scroll">
                                <table class="table table-borderless align-middle mb-0 small">
                                    <tbody>
                                        <?php if(empty($productividad_mecanicos)): ?>
                                            <tr><td class="text-center py-5 text-muted">Aún no hay trabajos completados este mes.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($productividad_mecanicos as $index => $prod): 
                                                $porcentaje = ($prod['trabajos_completados'] / $max_trabajos) * 100;
                                                $color_barra = $index == 0 ? 'bg-success' : ($index == 1 ? 'bg-primary' : 'bg-info');
                                            ?>
                                            <tr class="border-bottom">
                                                <td class="ps-4 py-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-bold text-dark"><i class="fas fa-user-circle me-1 text-muted"></i> <?php echo htmlspecialchars($prod['mecanico']); ?></span>
                                                        <span class="badge bg-light text-dark border"><?php echo $prod['trabajos_completados']; ?> completados</span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar <?php echo $color_barra; ?>" role="progressbar" style="width: <?php echo $porcentaje; ?>%;" aria-valuenow="<?php echo $porcentaje; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center border-top py-2 rounded-bottom">
                            <a href="/Taller/Taller-Mecanica/view/Taller/HistorialServicio.php" class="text-decoration-none small fw-bold text-secondary text-dark">Ver Historial de Servicios <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 9. RECEPCIONES ESPERADAS (Inventario / Compras) -->
                <?php if ($mostrarRecepciones): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-recepciones">
                    <div class="dashboard-panel border-start border-primary border-4" style="--bs-border-opacity: .5;">
                        <div class="panel-header bg-white">
                            <h5 class="text-primary mb-0"><i class="fas fa-truck-loading me-2"></i> Entregas de Proveedor</h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Mercancía Esperada</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive widget-scroll">
                                <table class="table table-hover table-borderless align-middle mb-0 small">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th class="ps-4">Orden / Fecha</th>
                                            <th class="text-center pe-4">Monto / Proveedor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($recepciones_esperadas)): ?>
                                            <tr><td colspan="2" class="text-center py-5 text-muted"><i class="fas fa-box-open fs-4 d-block mb-2 text-success"></i> No hay recepciones pendientes.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($recepciones_esperadas as $rec): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 150px;">
                                                        Orden #<?php echo $rec['id_orden_compra']; ?>
                                                    </div>
                                                    <div class="text-muted" style="font-size: 0.75rem;"><i class="far fa-calendar-alt"></i> Emi: <?php echo date('d/m/Y', strtotime($rec['fecha_emision'])); ?></div>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <div class="fw-bold text-dark mb-1">RD$ <?php echo number_format($rec['monto_total'], 2); ?></div>
                                                    <span class="badge bg-light text-dark border shadow-sm text-truncate" style="font-size: 0.70rem; max-width: 120px;" title="<?php echo htmlspecialchars($rec['nombre_comercial']); ?>">
                                                        <i class="fas fa-building text-muted me-1"></i> <?php echo $rec['nombre_comercial']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center border-top py-2 rounded-bottom">
                            <a href="/Taller/Taller-Mecanica/view/Inventario/RecepcionCompra.php" class="text-decoration-none small fw-bold text-primary text-dark">Ir a Recepción de Compra <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- DEFINIMOS ANCHOTABLA ANTES DE IMPRIMIRLA -->
            <?php 
            $anchoTabla = $mostrarIngresos ? "col-xl-8 col-lg-12" : "col-12"; 
            ?>
            
            <div class="row g-4 mb-5 pb-4">
                <!-- Tabla de Vehículos -->
                <div class="<?php echo $anchoTabla; ?> widget-panel" id="widget-tabla-vehiculos">
                    <div class="dashboard-panel">
                        <div class="panel-header">
                            <h5><i class="fas fa-car-side text-secondary me-2"></i> Vehículos Activos en Taller</h5>
                            <button class="btn btn-sm btn-light border"><i class="fas fa-filter text-muted"></i></button>
                        </div>
                        <div class="card-body p-0 d-flex flex-column h-100">
                            <div class="table-custom-wrapper">
                                <table class="table table-hover table-borderless align-middle table-custom">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Orden</th>
                                            <th>Cliente</th>
                                            <th>Vehículo</th>
                                            <th>Placa</th>
                                            <th>Estado</th>
                                            <th class="pe-4">Mecánico</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($vehiculosTabla)): ?>
                                            <tr><td colspan="6" class="text-center py-5 text-muted">No hay vehículos ingresados actualmente.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($vehiculosTabla as $veh): 
                                                $estado = strtolower($veh['estado_actual']);
                                                $badgeClass = 'bg-secondary';
                                                
                                                if(strpos($estado, 'reparacion') !== false || strpos($estado, 'progreso') !== false) {
                                                    $badgeClass = 'bg-primary';
                                                } elseif (strpos($estado, 'diagnostico') !== false || strpos($estado, 'ingresado') !== false) {
                                                    $badgeClass = 'bg-warning text-dark';
                                                } elseif (strpos($estado, 'lavado') !== false) {
                                                    $badgeClass = 'bg-info text-dark';
                                                } elseif (strpos($estado, 'listo') !== false) {
                                                    $badgeClass = 'bg-success'; 
                                                }
                                            ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-primary">#<?php echo $veh['id_orden']; ?></td>
                                                <td class="fw-bold text-dark"><?php echo $veh['cliente']; ?></td>
                                                <td><span class="text-muted"><i class="fas fa-car-alt me-1"></i> <?php echo $veh['marca_modelo']; ?></span></td>
                                                <td><span class="badge border border-secondary text-secondary bg-light px-2 py-1"><?php echo $veh['placa']; ?></span></td>
                                                <td><span class="badge <?php echo $badgeClass; ?> shadow-sm px-3 py-2"><?php echo $veh['estado_actual']; ?></span></td>
                                                <td class="text-muted pe-4"><i class="fas fa-user-cog me-1"></i> <?php echo $veh['mecanico']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Ingresos -->
                <?php if ($mostrarIngresos): ?>
                <div class="col-xl-4 col-lg-12 widget-panel" id="widget-grafico-ingresos">
                    <div class="dashboard-panel">
                        <div class="panel-header">
                            <h5><i class="fas fa-chart-line text-success me-2"></i> Tendencia de Ingresos</h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Últimos 7 días</span>
                        </div>
                        <div class="card-body p-4 d-flex align-items-center justify-content-center" style="min-height: 350px;">
                            <div style="position: relative; height: 100%; width: 100%;">
                                <canvas id="graficoIngresos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal de Personalización de Widgets (Solo Admin) -->
    <?php if ($id_rol_usuario == 1): ?>
    <div class="modal fade" id="modalConfigWidgets" tabindex="-1" aria-labelledby="modalConfigWidgetsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-bottom-0 pb-0 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="modalConfigWidgetsLabel"><i class="fas fa-desktop me-2 text-primary"></i> Personalizar Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pt-3 pb-4">
                    <p class="text-muted small mb-4">Enciende o apaga los paneles que deseas visualizar en tu inicio. Tus preferencias se guardarán en este navegador.</p>
                    
                    <div class="list-group list-group-flush border rounded-3 overflow-hidden shadow-sm" id="lista-switches-widgets">
                        <!-- Switch Tarjetas Superiores -->
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 bg-light">
                            <div class="fw-bold text-dark"><i class="fas fa-layer-group me-2 text-secondary"></i> Tarjetas de Métricas Rápidas</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="fila-metricas" checked>
                            </div>
                        </div>
                        <?php if($mostrarInventario): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div><div class="fw-bold text-dark"><i class="fas fa-exclamation-triangle me-2 text-danger"></i> Stock Crítico</div></div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-stock-critico" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($mostrarFacturacion): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="fw-bold text-dark"><i class="fas fa-clock me-2 text-warning"></i> Cobros Pendientes</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-cobros-ptes" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($mostrarMisTrabajos): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="fw-bold text-dark"><i class="fas fa-wrench me-2 text-primary"></i> Mis Asignaciones</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-mis-trabajos" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($mostrarCotizaciones): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="fw-bold text-dark"><i class="fas fa-file-signature me-2 text-info"></i> Cotizaciones por Aprobar</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-cotizaciones" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($mostrarAvisos): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="fw-bold text-dark"><i class="fas fa-bullhorn me-2 text-success"></i> Avisar a Clientes</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-avisar-clientes" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($mostrarTopServicios): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="fw-bold text-dark"><i class="fas fa-trophy me-2 text-warning"></i> Top 5 Servicios</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-top-servicios" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($mostrarFacturacion): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="fw-bold text-dark"><i class="fas fa-cash-register me-2 text-success"></i> Estado de mi Caja</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-estado-caja" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($mostrarProductividad): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="fw-bold text-dark"><i class="fas fa-users-cog me-2 text-secondary"></i> Productividad por Mecánico</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-productividad" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($mostrarRecepciones): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="fw-bold text-dark"><i class="fas fa-truck-loading me-2 text-primary"></i> Recepciones Esperadas</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-recepciones" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 bg-light">
                            <div class="fw-bold text-dark"><i class="fas fa-car-side me-2 text-secondary"></i> Tabla de Vehículos Activos</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-tabla-vehiculos" checked>
                            </div>
                        </div>
                        <?php if($mostrarIngresos): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 bg-light">
                            <div class="fw-bold text-dark"><i class="fas fa-chart-line me-2 text-success"></i> Gráfica de Ingresos</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-widget" type="checkbox" role="switch" data-target="widget-grafico-ingresos" checked>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Menú Flotante de Configuración -->
    <div class="config-flotante-container">
        <button class="btn-config-flotante" id="btnConfig">
            <img src="img/configuracion.png" alt="Config" class="icono-rueda">
        </button>
        <div class="menu-config-desplegable" id="menuConfig">
            <?php if (in_array("Perfil", $modulos)) : ?>
            <a href="/Taller/Taller-Mecanica/view/Submodulos/carnet.php"><i class="fas fa-user-circle"></i> Carnet Digital</a>
            <?php endif; ?>
            <?php if (in_array("Whatsapp", $modulos)) : ?>
            <a href="/Taller/Taller-Mecanica/view/Submodulos/apiw.php"><i class="fas fa-comments"></i>API Whatsapp</a>
            <?php endif; ?>
            <?php if (in_array("Contrasena", $modulos)) : ?>
            <a href="/Taller/Taller-Mecanica/view/Submodulos/seguridad.php"><i class="fas fa-shield-alt"></i> Seguridad</a>
            <?php endif; ?>
            <?php if (in_array("Evaluacion", $modulos)) : ?>
            <a href="/Taller/Taller-Mecanica/view/Submodulos/evaluacion.php"><i class="fas fa-poll-h"></i> Evaluación</a>
            <?php endif; ?>
            <?php if (in_array("Ofertas", $modulos)) : ?>
            <a href="/Taller/Taller-Mecanica/view/Submodulos/ofertas.php"><i class="fas fa-percentage"></i> Ofertas</a>
            <?php endif; ?>
            <?php if (in_array("Impuestos", $modulos)) : ?>
            <a href="/Taller/Taller-Mecanica/view/Submodulos/impuestos.php"><i class="fas fa-file-invoice-dollar"></i> Impuestos</a>
            <?php endif; ?>
            <?php if (in_array("Maestro Empleado", $modulos)) : ?>
            <a href="/Taller/Taller-Mecanica/view/Submodulos/maestro.php"><i class="fas fa-user-friends"></i> M. Empleado</a>
            <?php endif; ?>
            <?php if (in_array("Directorio DP", $modulos)) : ?>
            <a href="/Taller/Taller-Mecanica/view/Submodulos/directorio.php"><i class="fas fa-address-book"></i> Directorio DP</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts de Bootstrap y el Menú Original -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="Scripts_Menu.js"></script>
    
    <!-- Lógica Moderna para Chart.js -->
    <?php if ($mostrarIngresos): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartCanvas = document.getElementById('graficoIngresos');
            if(!chartCanvas) return;

            const dias_js = <?php echo json_encode(array_reverse($dias_grafica)); ?>;
            const ingresos_js = <?php echo json_encode(array_reverse($ingresos_grafica)); ?>;

            const ctx = chartCanvas.getContext('2d');
            
            let gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(40, 167, 69, 0.4)'); 
            gradient.addColorStop(1, 'rgba(40, 167, 69, 0.0)'); 

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dias_js,
                    datasets: [{
                        label: 'Facturación',
                        data: ingresos_js,
                        borderColor: '#28a745',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#28a745',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#2c3e50',
                            padding: 12,
                            titleFont: { size: 13, family: "'Poppins', sans-serif" },
                            bodyFont: { size: 14, weight: 'bold', family: "'Poppins', sans-serif" },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'RD$ ' + context.parsed.y.toLocaleString('es-DO', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + (value >= 1000 ? (value/1000) + 'k' : value);
                                },
                                font: { size: 11, family: "'Poppins', sans-serif" },
                                color: '#95a5a6'
                            },
                            grid: { color: '#f8f9fa', borderDash: [5, 5] },
                            border: { display: false }
                        },
                        x: {
                            ticks: { font: { size: 11, family: "'Poppins', sans-serif" }, color: '#95a5a6' },
                            grid: { display: false },
                            border: { display: false }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>