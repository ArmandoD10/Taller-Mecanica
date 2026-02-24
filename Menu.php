<?php
// Simulación de datos que vendrían de la Base de Datos
$nombreTaller = "AutoFlow Pro";
$usuario = "Administrador";

// Datos de las tarjetas de resumen
$stats = [
    "vehiculos_bahia" => 12,
    "presupuestos_pendientes" => 5,
    "listos_entrega" => 3,
    "ingresos_mes" => 145200
];

// Listado de Órdenes de Servicio
$ordenes = [
    [
        "id" => "OS-1024",
        "vehiculo" => "Toyota Corolla 2022 (Gris)",
        "cliente" => "Juan Pérez",
        "estado" => "En Diagnóstico",
        "clase_estado" => "bg-warning"
    ],
    [
        "id" => "OS-1025",
        "vehiculo" => "Honda Civic 2019 (Blanco)",
        "cliente" => "María García",
        "estado" => "En Reparación",
        "clase_estado" => "bg-primary"
    ],
    [
        "id" => "OS-1026",
        "vehiculo" => "Hyundai Tucson 2021 (Azul)",
        "cliente" => "Carlos Rojas",
        "estado" => "Control Calidad",
        "clase_estado" => "bg-success"
    ]
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nombreTaller; ?> - Panel de Control</title>
    <link rel="stylesheet" href="Archivo_Menu.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">


</head>
<body>

    <div class="sidebar">

    <div class="sidebar-header">
      <a href="/Taller/Taller-Mecanica/Menu.php">
        <img src="img/logo.png" class="logo-img">
    </a>
    </div>



  <!-- MODULO Seguridad -->
<div class="modulo">
  <button class="modulo-btn">
      <img src="img/seguridad.png" class="icono-modulo" alt="Seguridad">
      <span>Seguridad</span>
  </button>

  <div class="modulo-content">
    <a href="/Taller/Taller-Mecanica/view/Seguridad/MUsuario.php">Usuarios</a>
    <a href="/Taller/Taller-Mecanica/view/Seguridad/MRoles.php">Roles</a>
  </div>
</div>

  <!-- MODULO RRHH -->
<div class="modulo">
  <button class="modulo-btn">
      <img src="img/rrhh.png" class="icono-modulo" alt="Cliente">
      <span>Recursos Humanos</span>
  </button>

  <div class="modulo-content">
    <a href="/Taller/Taller-Mecanica/view/RRHH/MEmpleado.php">Empleado</a>
    <a href="/Taller/Taller-Mecanica/view/RRHH/MSucursal.php">Sucursal</a>
    <a href="/Taller/Taller-Mecanica/view/RRHH/MDepartamento.php">Departamento</a>
    <a href="/Taller/Taller-Mecanica/view/RRHH/MBahia.php">Bahia</a>
    <a href="/Taller/Taller-Mecanica/view/RRHH/CEmpleado.php">Consulta de Empleado</a>
    <a href="/Taller/Taller-Mecanica/view/RRHH/REmpleado.php">Reporte de Empleado</a>
  </div>
</div>

  <!-- MODULO Cliente-->
<div class="modulo">
  <button class="modulo-btn">
      <img src="img/cliente.png" class="icono-modulo" alt="Cliente">
      <span>Cliente</span>
  </button>

  <div class="modulo-content">
    <a href="/Taller/Taller-Mecanica/view/Cliente/MCliente.php">Cliente</a>
    <a href="/Taller/Taller-Mecanica/view/Cliente/CHistorialVehiculo.php">Historial Vehiculo</a>
    <a href="/Taller/Taller-Mecanica/view/Cliente/CCliente.php">Consulta Cliente</a>
    <a href="/Taller/Taller-Mecanica/view/Cliente/RCliente.php">Reporte Cliente</a>
  </div>
</div>

  <!-- MODULO Vehiculo-->
<div class="modulo">
  <button class="modulo-btn">
      <img src="img/coche.png" class="icono-modulo" alt="Vehiculo">
      <span>Vehículo</span>
  </button>

  <div class="modulo-content">
    <a href="/Taller/Taller-Mecanica/view/Vehiculo/MVehiculo.php">Vehiculo</a>
    <a href="/Taller/Taller-Mecanica/view/Vehiculo/CVehiculo.php">Consulta Vehiculo</a>
    <a href="/Taller/Taller-Mecanica/view/Vehiculo/RVehiculo.php">Reporte Vehiculo</a>
    <a href="/Taller/Taller-Mecanica/view/Vehiculo/RHistorialVehiculo.php">Reporte Historial</a>
  </div>
</div>

  <!-- MODULO Inventario -->
<div class="modulo">
  <button class="modulo-btn">
      <img src="img/inventario.png" class="icono-modulo" alt="Inventario">
      <span>Inventario</span>
  </button>

  <div class="modulo-content">
    <a href="/Taller/Taller-Mecanica/view/Inventario/MAlmacen.php">Almacen</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/MProveedor.php">Proveedor</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/Compra.php">Orden Compra</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/PagoCompra.php">Pago de Compra</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/HistorialCompra.php">Historial Compra</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/HistorialPago.php">Historial Pago</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/CAlmacen.php">Consulta Inventario</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/CProveedor.php">Consulta Proveedor</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/RAlmacen.php">Reporte Inventario</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/RCompra.php">Reporte Compra</a>
  </div>
</div>

  <!-- MODULO Taller -->
<div class="modulo">
  <button class="modulo-btn">
      <img src="img/taller.png" class="icono-modulo" alt="Taller">
      <span>Taller</span>
  </button>

  <div class="modulo-content">
    <a href="/Taller/Taller-Mecanica/view/Taller/Inspeccion.php">Inspeccion de Vehiculo</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/Servicio.php">Orden de Servicio</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/MRecursos.php">Recursos y Herramientas</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/Entrega.php">Entrega y Revision</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/Cotizacion.php">Cotizacion</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/CCotizacion.php">Consulta Cotizacion</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/CInspeccion.php">Consulta Inspeccion</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/CServicio.php">Consulta Orden de Servicio</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/RServicio.php">Reporte Servicio</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/RInspeccion.php">Reporte Inspeccion</a>
    <a href="/Taller/Taller-Mecanica/view/Taller/RCotizacion.php">Reporte Cotizacion</a>
  </div>
</div>

  <!-- MODULO Facturacion Y Cobro -->
<div class="modulo">
  <button class="modulo-btn">
      <img src="img/facturacion.png" class="icono-modulo" alt="Facturacion">
      <span>Facturacion</span>
  </button>

  <div class="modulo-content">
    <a href="/Taller/Taller-Mecanica/view/Facturacion/Factura.php">Facturacion</a>
    <a href="/Taller/Taller-Mecanica/view/Facturacion/CobroFactura.php">Cobro de Facturas</a>
    <a href="/Taller/Taller-Mecanica/view/Facturacion/Devolucion.php">Devolucion</a>
    <a href="/Taller/Taller-Mecanica/view/Facturacion/CPagos.php">Historial de Pagos</a>
    <a href="/Taller/Taller-Mecanica/view/Facturacion/CFactura.php">Historial de Factura</a>
    <a href="/Taller/Taller-Mecanica/view/Facturacion/CCxc.php">Historial de Credito</a>
    <a href="/Taller/Taller-Mecanica/view/Facturacion/CDevolucion.php">Historial de devolucion</a>
    <a href="/Taller/Taller-Mecanica/view/Facturacion/RFactura.php">Reporte Facturacion</a>
    <a href="/Taller/Taller-Mecanica/view/Facturacion/RPagosFactura.php">Reporte Pagos</a>
    <a href="/Taller/Taller-Mecanica/view/Facturacion/RDeuda.php">Reporte Deuda</a>
  </div>
</div>

   <!-- MODULO Autolavado -->
<div class="modulo">
  <button class="modulo-btn">
      <img src="img/lavado.png" class="icono-modulo" alt="Autolavado">
      <span>Autolavado</span>
  </button>

  <div class="modulo-content">
    <a href="/Taller/Taller-Mecanica/view/Autolavado/OLavado.php">Orden de lavado</a>
    <a href="/Taller/Taller-Mecanica/view/Autolavado/EntregaLavado.php">Entrega de lavado</a>
    <a href="/Taller/Taller-Mecanica/view/Autolavado/CLavado.php">Historial de lavado</a>
    <a href="/Taller/Taller-Mecanica/view/Autolavado/RLavado.php">Reporte de lavado</a>
  </div>
</div>

  <!-- MODULO Autoadorno -->
<div class="modulo">
  <button class="modulo-btn">
      <img src="img/carreras.png" class="icono-modulo" alt="Autoadorno">
      <span>Autoadorno</span>
  </button>

  <div class="modulo-content">
    <a href="/Taller/Taller-Mecanica/view/Autoadorno/ACotizacion.php">Cotizacion</a>
    <a href="/Taller/Taller-Mecanica/view/Autoadorno/ADevolucion.php">Devolucion</a>
    <a href="/Taller/Taller-Mecanica/view/Autoadorno/AAperturaCaja.php">Apertura de caja</a>
    <a href="/Taller/Taller-Mecanica/view/Inventario/CAlmacen.php">Almacen</a>
    <a href="/Taller/Taller-Mecanica/view/Autoadorno/ACCotizacion.php">Historial de Cotizacion</a>
    <a href="/Taller/Taller-Mecanica/view/Autoadorno/ACAperturaCaja.php">Historial de Apertura</a>
    <a href="/Taller/Taller-Mecanica/view/Autoadorno/ACDevolucion.php">Historial de devolucion</a>
  </div>
</div>

<div class="modulo">
    <a href="/Taller/Taller-Mecanica/logout.php" class="modulo-btn" id="logoutBtn">
        <img src="img/salida.png" class="icono-modulo">
        <span>Cerrar sesión</span>
    </a>
</div>


</div>


    <main>
        <header>
            <h1>Resumen del Taller - <?php echo date("d/m/Y"); ?></h1>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span>Hola, <strong><?php echo $usuario; ?></strong></span>
            </div>
        </header>

        <section class="stats-container">
            <div class="card">
                <h3>Vehículos en Bahía</h3>
                <p><?php echo $stats['vehiculos_bahia']; ?></p>
            </div>
            <div class="card">
                <h3>Presupuestos</h3>
                <p><?php echo $stats['presupuestos_pendientes']; ?></p>
            </div>
            <div class="card">
                <h3>Listos</h3>
                <p><?php echo $stats['listos_entrega']; ?></p>
            </div>
            <div class="card">
                <h3>Ingresos del Mes</h3>
                <p>RD$ <?php echo number_format($stats['ingresos_mes'], 2); ?></p>
            </div>
        </section>

        <section class="table-container">
            <h2>Estatus de Reparaciones Activas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Orden #</th>
                        <th>Vehículo</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordenes as $orden): ?>
                    <tr>
                        <td>#<?php echo $orden['id']; ?></td>
                        <td><?php echo $orden['vehiculo']; ?></td>
                        <td><?php echo $orden['cliente']; ?></td>
                        <td>
                            <span class="badge <?php echo $orden['clase_estado']; ?>">
                                <?php echo $orden['estado']; ?>
                            </span>
                        </td>
                        <td><button class="btn-action">Gestionar</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

<script src="Scripts_Menu.js"></script>

</body>
</html>