<?php
session_start();
// Validación de sesión activa
if (!isset($_SESSION['id_usuario'])) {
    // header("Location: index.php"); exit;
}
$nombreTaller = "Mecánica Automotriz Díaz & Pantaleón";
$usuarioActivo = $_SESSION['user'] ?? "Administrador";

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
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="Menu.php"><img src="img/logo.png" class="logo-img" alt="Logo"></a>
        </div>
        <nav class="menu-container">
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

            <div class="modulo">
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

            <div class="modulo">
                <button class="modulo-btn">
                    <img src="img/cliente.png" class="icono-modulo"> <span>Cliente</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Cliente/MCliente.php">Gestión de Clientes</a>
                    <a href="view/Cliente/MCredito.php">Créditos y Cobranzas</a>
                    <a href="view/Cliente/CHistorialCredito.php">Historial de Crédito</a>
                </div>
            </div>

            <div class="modulo">
                <button class="modulo-btn">
                    <img src="img/coche.png" class="icono-modulo"> <span>Vehículo</span>
                </button>
                <div class="modulo-content">
                    <a href="controller/VehiculoController.php">Registro de Vehículos</a>
                    <a href="view/Vehiculo/MMarca.php">Marcas</a>
                    <a href="view/Vehiculo/MColor.php">Colores</a>
                </div>
            </div>

            <div class="modulo">
                <button class="modulo-btn">
                    <img src="img/inventario.png" class="icono-modulo"> <span>Inventario</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Inventario/MArticulo.php">Artículos y Repuestos</a>
                    <a href="view/Inventario/MAlmacen.php">Almacenes</a>
                    <a href="view/Inventario/MMovimiento.php">Movimientos de Stock</a>
                    <a href="view/Inventario/MProveedor.php">Proveedores</a>
                </div>
            </div>

            <div class="modulo">
                <button class="modulo-btn">
                    <img src="img/taller.png" class="icono-modulo"> <span>Taller</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Taller/Servicio.php">Órdenes de Trabajo</a>
                    <a href="view/Taller/Inspeccion.php">Inspecciones Técnicas</a>
                    <a href="view/Taller/MBahia.php">Gestión de Bahías</a>
                    <a href="view/Taller/RegistroTiempos.php">Tiempos de Reparación</a>
                </div>
            </div>

            <div class="modulo">
                <button class="modulo-btn">
                    <img src="img/facturacion.png" class="icono-modulo"> <span>Facturacion</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Facturacion/Factura.php">Nueva Factura</a>
                    <a href="view/Facturacion/Cotizacion.php">Gestión de Cotizaciones</a>
                    <a href="view/Facturacion/RFactura.php">Reportes de Ventas (NCF)</a>
                </div>
            </div>

            <div class="modulo">
                <button class="modulo-btn">
                    <img src="img/lavado.png" class="icono-modulo"> <span>Autolavado</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Autolavado/OLavado.php">Control de Lavados</a>
                    <a href="view/Autolavado/MTipoLavado.php">Tipos de Lavado</a>
                    <a href="view/Autolavado/MPlanMembresia.php">Planes y Membresías</a>
                </div>
            </div>

            <div class="modulo">
                <button class="modulo-btn">
                    <img src="img/carreras.png" class="icono-modulo"> <span>Autoadorno</span>
                </button>
                <div class="modulo-content">
                    <a href="view/Autoadorno/AServicio.php">Servicios de Detailing</a>
                    <a href="view/Autoadorno/MPaquete.php">Paquetes de Servicio</a>
                    <a href="view/Autoadorno/MGarantia.php">Garantías de Instalación</a>
                </div>
            </div>

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
    <script src="Scripts_Menu.js"></script>
</body>
</html>