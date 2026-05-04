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
$modulos = $_SESSION['modulos'] ?? [];

// ==========================================
// CONSULTAS REALES A LA BASE DE DATOS
// ==========================================

// 1. Vehículos en Bahía (Último estado = 'Reparacion' o similar)
$sqlBahia = "SELECT COUNT(*) as total FROM orden o 
             WHERE o.estado = 'activo' 
             AND (SELECT e.nombre FROM orden_estado oe JOIN estado e ON oe.id_estado = e.id_estado WHERE oe.id_orden = o.id_orden ORDER BY oe.sec_orden_estado DESC LIMIT 1) LIKE '%Reparaci%'";
$resBahia = $conexion->query($sqlBahia);
$vehiculos_bahia = $resBahia ? $resBahia->fetch_assoc()['total'] : 0;

// 2. Presupuestos Pendientes con filtro por Nivel de Usuario
$id_sucursal_sesion = $_SESSION['id_sucursal'] ?? 0;
$id_rol_usuario = $_SESSION['id_rol'] ?? 2; // Asumiendo que 1 es Admin y 2 es Sucursal

if ($id_rol_usuario == 1) {
    // Si es Administrador (Nivel 1), ve todo
    $sqlPres = "SELECT COUNT(*) as total FROM cotizacion WHERE estado IN ('Pendiente', 'pendiente')";
} else {
    // Si es Nivel 2, solo ve lo de su sucursal
    $sqlPres = "SELECT COUNT(*) as total FROM cotizacion 
                WHERE estado IN ('Pendiente', 'pendiente') 
                AND id_sucursal = '$id_sucursal_sesion'";
}

$resPres = $conexion->query($sqlPres);
$presupuestos_pendientes = $resPres ? $resPres->fetch_assoc()['total'] : 0;
// 3. Listos para Entrega (Último estado = 'Listo')
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

// 4. Tabla de Vehículos en Taller (Trae los 6 más recientes con datos reales)
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

// 5. Gráfica de Ingresos (Suma facturación de Taller y Lavado de los últimos 7 días)
$dias_grafica = [];
$ingresos_grafica = [];

for($i = 6; $i >= 0; $i--) {
    $fecha_db = date('Y-m-d', strtotime("-$i days"));
    $fecha_label = date('d/m', strtotime("-$i days")); 
    $dias_grafica[] = $fecha_label;

    // Taller usa fecha_emision en factura_central
    $qTaller = $conexion->query("SELECT SUM(monto_total) as t FROM factura_central WHERE DATE(fecha_emision) = '$fecha_db'");
    $valTaller = ($qTaller && $row = $qTaller->fetch_assoc()) ? (float)$row['t'] : 0;
    
    // Lavado usa fecha_creacion en factura_lavado
    $qLavado = $conexion->query("SELECT SUM(monto_total) as t FROM factura_lavado WHERE DATE(fecha_creacion) = '$fecha_db'");
    $valLavado = ($qLavado && $row = $qLavado->fetch_assoc()) ? (float)$row['t'] : 0;

    $ingresos_grafica[] = $valTaller + $valLavado;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nombreTaller; ?></title>
    <link rel="stylesheet" href="Archivo_Menu.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        main {
            padding: 20px 30px;
            background-color: #f4f7f6;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .card-link {
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
            padding: 20px 25px;
            border-radius: 10px;
            border-left: 6px solid #1a5276; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-link:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.08);
        }

        .card-link h3 {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
            margin: 0 0 10px 0;
        }

        .card-link p {
            font-size: 2.2rem;
            color: #1a5276;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .panel-dashboard {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }

        .panel-title {
            font-size: 1.1rem;
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-dashboard {
            width: 100%;
            border-collapse: collapse;
        }

        .table-dashboard th {
            text-align: left;
            padding: 12px 10px;
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 600;
            border-bottom: 2px solid #edf2f9;
        }

        .table-dashboard td {
            padding: 12px 10px;
            font-size: 0.9rem;
            color: #495057;
            border-bottom: 1px solid #edf2f9;
            vertical-align: middle;
        }

        .table-dashboard tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            display: inline-block;
        }
        .badge-progreso { background-color: #28a745; }
        .badge-diagnostico { background-color: #ffc107; color: #333; }
        .badge-lavado { background-color: #007bff; }
        .badge-default { background-color: #6c757d; }
        
        .card-acceso-rapido { border-left: 6px solid #28a745; }

        @media (max-width: 1100px) {
            .dashboard-grid { grid-template-columns: 1fr; }
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

    <main>
        <header class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="font-size: 1.8rem; color: #333; font-weight: 600; margin: 0;">Resumen Operativo - <?php echo date("d/m/Y"); ?></h1>
            
            <div class="user-area" style="display: flex; align-items: center; gap: 20px;">
                <div class="notification-dropdown" style="position: relative;">
                    <button type="button" id="btnNotificaciones" style="background: none; border: none; cursor: pointer; position: relative;">
                        <i class="fas fa-bell" style="font-size: 1.5rem; color: #555;"></i>
                        <span id="contador-notificaciones" class="d-none" style="position: absolute; top: -5px; right: -5px; background: #e74c3c; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.7rem; font-weight: bold;">0</span>
                    </button>
                    
                    <div id="menu-notificaciones" class="d-none" style="position: absolute; top: 40px; right: 0; width: 300px; background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000; border: 1px solid #ddd; overflow: hidden;">
                        <div style="padding: 10px; background: #f8f9fa; border-bottom: 1px solid #eee; font-weight: bold; font-size: 0.9rem;">
                            <i class="fas fa-truck-moving me-2"></i> Solicitudes de Stock
                        </div>
                        <div id="contenedor-items-notificacion" style="max-height: 300px; overflow-y: auto;">
                            <p style="padding: 20px; text-align: center; color: #888; font-size: 0.8rem; margin: 0;">Cargando pedidos...</p>
                        </div>
                        <a href="/Taller/Taller-Mecanica/view/Inventario/Transferencia.php" style="display: block; text-align: center; padding: 10px; font-size: 0.8rem; color: #3498db; text-decoration: none; border-top: 1px solid #eee; background: #fff;">Ver todo el panel</a>
                    </div>
                </div>
                <div class="user-display" style="color: #666; font-size: 0.95rem;">
                    Bienvenido, <strong style="color:#333;"><?php echo $usuarioActivo; ?></strong>
                </div>
            </div>
        </header>

        <section class="stats-container">
            <a href="/Taller/Taller-Mecanica/view/Taller/RegistroTiempo.php" class="card-link">
                <h3>Vehículos en Bahía</h3>
                <p><?php echo $stats['vehiculos_bahia']; ?></p>
            </a>
            
            <a href="/Taller/Taller-Mecanica/view/Facturacion/Cotizacion.php" class="card-link">
                <h3>Presupuestos Pendientes</h3>
                <p><?php echo $stats['presupuestos_pendientes']; ?></p>
            </a>
            
            <a href="/Taller/Taller-Mecanica/view/Taller/EntregaServicio.php" class="card-link">
                <h3>Listos para Entrega</h3>
                <p><?php echo $stats['listos_entrega']; ?></p>
            </a>

            <?php if (in_array("Inventario", $modulos)) : ?>
            <a href="/Taller/Taller-Mecanica/view/Inventario/MArticulo.php?mode=readonly" class="card-link card-acceso-rapido" style="flex-direction: row; justify-content: space-between; align-items: center;">
                <div>
                    <h3>Consulta de Stock</h3>
                    <span style="font-size: 0.85rem; color:#888;">Disponibilidad Global</span>
                </div>
                <i class="fas fa-search-location" style="font-size: 2rem; color: #28a745;"></i>
            </a>
            <?php endif; ?>
        </section>

        <section class="dashboard-grid">
            <div class="panel-dashboard">
                <div class="panel-title">
                    <span>Vehículos en Taller</span>
                    <i class="fas fa-filter text-muted" style="cursor: pointer;"></i>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table-dashboard">
                        <thead>
                            <tr>
                                <th>Orden #</th>
                                <th>Cliente</th>
                                <th>Marca/Modelo</th>
                                <th>Placa</th>
                                <th>Estado</th>
                                <th>Mecánico</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($vehiculosTabla)): ?>
                                <tr><td colspan="6" style="text-align:center; padding: 20px;">No hay vehículos en el taller.</td></tr>
                            <?php else: ?>
                                <?php foreach($vehiculosTabla as $veh): 
                                    // Color de la etiqueta dependiendo del estado real
                                    $estado = strtolower($veh['estado_actual']);
                                    $badgeClass = 'badge-default';
                                    
                                    if(strpos($estado, 'reparacion') !== false || strpos($estado, 'progreso') !== false) {
                                        $badgeClass = 'badge-progreso';
                                    } elseif (strpos($estado, 'diagnostico') !== false || strpos($estado, 'ingresado') !== false) {
                                        $badgeClass = 'badge-diagnostico';
                                    } elseif (strpos($estado, 'lavado') !== false) {
                                        $badgeClass = 'badge-lavado';
                                    } elseif (strpos($estado, 'listo') !== false) {
                                        $badgeClass = 'badge-progreso'; // Listo se muestra verde
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $veh['id_orden']; ?></td>
                                    <td><?php echo $veh['cliente']; ?></td>
                                    <td><?php echo $veh['marca_modelo']; ?></td>
                                    <td><?php echo $veh['placa']; ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $veh['estado_actual']; ?></span></td>
                                    <td><?php echo $veh['mecanico']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel-dashboard">
                <div class="panel-title">
                    <span>Ingresos de Facturación (Últimos 7 días)</span>
                </div>
                <div style="position: relative; height: 250px; width: 100%;">
                    <canvas id="graficoIngresos"></canvas>
                </div>
            </div>
        </section>
    </main>

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

    <script src="Scripts_Menu.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Pasamos los datos calculados en PHP directo a JavaScript
            const dias_js = <?php echo json_encode(array_reverse($dias_grafica)); ?>;
            const ingresos_js = <?php echo json_encode(array_reverse($ingresos_grafica)); ?>;

            const ctx = document.getElementById('graficoIngresos').getContext('2d');
            
            let gradient = ctx.createLinearGradient(0, 0, 0, 250);
            gradient.addColorStop(0, 'rgba(52, 152, 219, 0.5)'); 
            gradient.addColorStop(1, 'rgba(52, 152, 219, 0.0)'); 

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dias_js,
                    datasets: [{
                        label: 'Ingresos Totales (RD$)',
                        data: ingresos_js,
                        borderColor: '#3498db',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        pointBackgroundColor: '#3498db',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
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
                                    return '$' + value.toLocaleString();
                                },
                                font: { size: 10 }
                            },
                            grid: { color: '#f0f0f0' },
                            border: { display: false }
                        },
                        x: {
                            ticks: { font: { size: 10 } },
                            grid: { display: false },
                            border: { display: false }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>