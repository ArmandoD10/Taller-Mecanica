<?php
session_start();
// Validación de sesión activa
if (!isset($_SESSION['id_usuario'])) {
    // header("Location: index.php"); exit;
}
$nombreTaller = "Mecánica Automotriz Díaz & Pantaleón";
$usuarioActivo = $_SESSION['user'] ?? "Administrador";
// Supongamos que en el login guardaste el rol en $_SESSION['nivel']
$modulos = $_SESSION['modulos'] ?? [];

// Estadísticas operativas extraídas de la base de datos 'taller'
$stats = [
    "vehiculos_bahia" => 12,
    "presupuestos_pendientes" => 5,
    "listos_entrega" => 3
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nombreTaller; ?></title>
    <link rel="stylesheet" href="Archivo_Menu.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <a href="/Taller/Taller-Mecanica/view/RRHH/GestionUsuario.php">Gestion de Usuarios</a>
                    <a href="/Taller/Taller-Mecanica/view/RRHH/GestionPermisos.php">Gestion de Permisos</a>
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
                    <a href="/Taller/Taller-Mecanica/view/Taller/Servicio.php">Órdenes de Servicio</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MTipoServicio.php">Registro de Servicios</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MInspeccion.php">Inspecciones Técnicas</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MBahia.php">Gestión de Bahías</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/MMaquinaria.php">Gestión de Recursos</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/RegistroTiempo.php">Tiempos y Asignacion</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/EntregaServicio.php">Entrega de Servicios</a>
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
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/Factura.php">Nueva Factura (POS)</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/ACaja.php">Apertura de Caja</a>
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
        <header style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Resumen Operativo - <?php echo date("d/m/Y"); ?></h1>
    
    <div class="user-area" style="display: flex; align-items: center; gap: 20px;">
        
        <div class="notification-dropdown" style="position: relative;">
            <button type="button" id="btnNotificaciones" style="background: none; border: none; cursor: pointer; position: relative;">
                <i class="fas fa-bell" style="font-size: 1.5rem; color: #333;"></i>
                <span id="contador-notificaciones" class="d-none" style="position: absolute; top: -5px; right: -5px; background: #e74c3c; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.7rem; font-weight: bold;">
                    0
                </span>
            </button>
            
            <div id="menu-notificaciones" class="d-none" style="position: absolute; top: 40px; right: 0; width: 300px; background: white; border-radius: 8px; shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000; border: 1px solid #ddd; overflow: hidden;">
                <div style="padding: 10px; background: #f8f9fa; border-bottom: 1px solid #eee; font-weight: bold; font-size: 0.9rem;">
                    <i class="fas fa-truck-moving me-2"></i> Solicitudes de Stock
                </div>
                <div id="contenedor-items-notificacion" style="max-height: 300px; overflow-y: auto;">
                    <p style="padding: 20px; text-align: center; color: #888; font-size: 0.8rem; margin: 0;">Cargando pedidos...</p>
                </div>
                <a href="/Taller/Taller-Mecanica/view/Inventario/Transferencia.php" style="display: block; text-align: center; padding: 10px; font-size: 0.8rem; color: #3498db; text-decoration: none; border-top: 1px solid #eee; background: #fff;">Ver todo el panel</a>
            </div>
        </div>
        <div class="user-display">
            Bienvenido, <strong><?php echo $usuarioActivo; ?></strong>
        </div>
    </div>
</header>
        <section class="stats-container">
    <div class="card"><h3>Vehículos en Bahía</h3><p><?php echo $stats['vehiculos_bahia']; ?></p></div>
    <div class="card"><h3>Presupuestos Pendientes</h3><p><?php echo $stats['presupuestos_pendientes']; ?></p></div>
    <div class="card"><h3>Listos para Entrega</h3><p><?php echo $stats['listos_entrega']; ?></p></div>
    
    <?php if (in_array("Inventario", $modulos)) : ?>
    <div class="card-acceso-rapido">
    <a href="/Taller/Taller-Mecanica/view/Inventario/MArticulo.php?mode=readonly" class="acceso-link">
        <div class="acceso-icon">
            <i class="fas fa-search-location"></i>
        </div>
        <div class="acceso-text">
            <h3>Consulta de Stock</h3>
            <p>Disponibilidad Global</p>
        </div>
        <div class="acceso-arrow">
            <i class="fas fa-chevron-right"></i>
        </div>
    </a>
</div>
    <?php endif; ?>
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
                <a href="#"><i class="fas fa-comments"></i>API Whatsapp</a>
                <?php endif; ?>
                <?php if (in_array("Contrasena", $modulos)) : ?>
                <a href="/Taller/Taller-Mecanica/view/Submodulos/seguridad.php"><i class="fas fa-shield-alt"></i> Seguridad</a>
                <?php endif; ?>
                <?php if (in_array("Evaluacion", $modulos)) : ?>
                <a href="#"><i class="fas fa-poll-h"></i> Evaluación</a>
                <?php endif; ?>
                <?php if (in_array("Ofertas", $modulos)) : ?>
                <a href="/Taller/Taller-Mecanica/view/Submodulos/ofertas.php"><i class="fas fa-percentage"></i> Ofertas</a>
                <?php endif; ?>
                <?php if (in_array("Impuestos", $modulos)) : ?>
                <a href="/Taller/Taller-Mecanica/view/Submodulos/impuestos.php"><i class="fas fa-file-invoice-dollar"></i> Impuestos</a>
                <?php endif; ?>
            </div>
        </div>
    <script src="Scripts_Menu.js"></script>
</body>
</html>