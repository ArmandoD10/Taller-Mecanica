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
                    <a href="view/Seguridad/MUsuario.php">Usuarios</a>
                    <a href="view/Seguridad/MRoles.php">Roles y Permisos</a>
                    <a href="view/Seguridad/CHistorialAcceso.php">Historial de Accesos</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("RRHH", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/rrhh.png" class="icono-modulo"> <span>Recursos Humanos</span>
                </button>
                <div class="modulo-content">
                    <a href="view/RRHH/MEmpleado.php">Empleados</a>
                    <a href="view/RRHH/MSucursal.php">Sucursales</a>
                    <a href="view/RRHH/MDepartamento.php">Departamentos</a>
                    <a href="view/RRHH/MSueldoSeguro.php">Sueldos y Seguros</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Cliente", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/cliente.png" class="icono-modulo"> <span>Cliente</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Cliente/MCliente.php">Gestión de Clientes</a>
                    <a href="view/Cliente/MCredito.php">Créditos y Cobranzas</a>
                    <a href="view/Cliente/CHistorialCredito.php">Historial de Crédito</a>
                    <a href="view/Cliente/CHistorialCredito.php">Consulta de Deuda</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Vehiculo", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/coche.png" class="icono-modulo"> <span>Vehículo</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Vehiculo/MVehiculo.php">Registro de Vehículos</a>
                    <a href="view/Vehiculo/MMarcaModelo.php">Marcas y Modelos</a>
                    <a href="view/Vehiculo/RHistorialVehiculo.php">Historial Vehiculo</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Inventario", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/inventario.png" class="icono-modulo"> <span>Inventario</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Inventario/MArticulo.php">Artículos y Repuestos</a>
                    <a href="view/Inventario/MAlmacen.php">Almacenes</a>
                    <a href="view/Inventario/MProveedor.php">Proveedores</a>
                    <?php if (in_array("Seguridad", $modulos)) : ?>
                    <a href="view/Inventario/Compra.php">Orden de Compra</a>
                    <a href="view/Inventario/PagoCompra.php">Pago de Compra</a>
                    <?php endif; ?>
                    <a href="view/Inventario/RecepcionCompra.php">Recepción de Compra</a>
                    <a href="view/Inventario/HistorialCompra.php">Historial de Compra</a>
                    <a href="view/Inventario/HistorialPago.php">Historial de Pagos</a>
                    <a href="view/Inventario/HistorialRecepcion.php">Historial de Recepción</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Taller", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img.png" class="icono-modulo"> <span>Taller</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Servicio.php">Órdenes de Servicio</a>
                    <a href="view/Inspeccion.php">Inspecciones Técnicas</a>
                    <a href="view/MBahia.php">Gestión de Bahías</a>
                    <a href="view/MRecursos.php">Gestión de Recursos</a>
                    <a href="view/RegistroTiempos.php">Tiempos y Asignacion</a>
                    <a href="view/Entrega.php">Entrega de Servicios</a>
                    <a href="view/HistorialServicio.php">Historial de Servicios</a>
                    <a href="view/HistorialEntrega.php">Historial de Entregas</a>
                    <a href="view/HistorialInspeccion.php">Historial de Inspecciones</a>
                </div>
            </div>
            <?php endif; ?>
  
            <div class="modulo">
                <?php if (in_array("Facturacion", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/facturacion.png" class="icono-modulo"> <span>Facturacion</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Facturacion/Factura.php">Nueva Factura</a>
                    <a href="view/Facturacion/Cotizacion.php">Gestión de Cotizaciones</a>
                    <a href="view/Facturacion/Devolucion.php">Gestión de Devolucion</a>
                    <a href="view/Facturacion/CobroFactura.php">Gestion de pago credito</a>
                    <a href="view/Facturacion/RFactura.php">Reportes de Ventas (NCF)</a>
                    <a href="view/Facturacion/HistorialFactura.php">Historial Factura</a>
                    <a href="view/Facturacion/HistorialCotizacion.php">Historial Cotizacion</a>
                    <a href="view/Facturacion/HistorialPagoCredito.php">Historial de Pagos</a>
                    <a href="view/Facturacion/HistorialDevolucion.php">Historial de Devoluciones</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Autolavado", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/lavado.png" class="icono-modulo"> <span>Autolavado</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Autolavado/OLavado.php">Control de Lavados</a>
                    <a href="view/Autolavado/MTipoLavado.php">Tipos de Lavado</a>
                    <a href="view/Autolavado/MPlanMembresia.php">Planes y Membresías</a>
                    <a href="view/Autolavado/HistorialLavado.php">Historial Lavado</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Autoadorno", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="img/carreras.png" class="icono-modulo"> <span>Autoadorno</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Autoadorno/AServicio.php">Servicios de Detailing</a>
                    <a href="view/Autoadorno/MPaquete.php">Paquetes de Servicio</a>
                    <a href="view/Autoadorno/MGarantia.php">Garantías de Instalación</a>
                    <a href="view/Autoadorno/HistorialServicio.php">Historial de Servicio</a>
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
        <header>
            <h1>Resumen Operativo - <?php echo date("d/m/Y"); ?></h1>
            <div class="user-display">Bienvenido, <strong><?php echo $usuarioActivo; ?></strong></div>
        </header>
        <section class="stats-container">
            <div class="card"><h3>Vehículos en Bahía</h3><p><?php echo $stats['vehiculos_bahia']; ?></p></div>
            <div class="card"><h3>Presupuestos Pendientes</h3><p><?php echo $stats['presupuestos_pendientes']; ?></p></div>
            <div class="card"><h3>Listos para Entrega</h3><p><?php echo $stats['listos_entrega']; ?></p></div>
        </section>
    </main>
        <div class="config-flotante-container">
            <button class="btn-config-flotante" id="btnConfig">
                <img src="img/configuracion.png" alt="Config" class="icono-rueda">
            </button>
            <div class="menu-config-desplegable" id="menuConfig">
                <?php if (in_array("Perfil", $modulos)) : ?>
                <a href="#"><i class="fas fa-user-circle"></i> Perfil</a>
                <?php endif; ?>
                <?php if (in_array("Contrasena", $modulos)) : ?>
                <a href="#"><i class="fas fa-shield-alt"></i> Seguridad</a>
                <?php endif; ?>
                <?php if (in_array("Membresia", $modulos)) : ?>
                <a href="#"><i class="fas fa-credit-card"></i> Membresías</a>
                <?php endif; ?>
                <?php if (in_array("Ofertas", $modulos)) : ?>
                <a href="#"><i class="fas fa-percentage"></i> Ofertas</a>
                <?php endif; ?>
                <?php if (in_array("Impuestos", $modulos)) : ?>
                <a href="#"><i class="fas fa-file-invoice-dollar"></i> Impuestos</a>
                <?php endif; ?>
            </div>
        </div>
    <script src="Scripts_Menu.js"></script>
</body>
</html>