<?php
// Supongamos que en el login guardaste el rol en $_SESSION['nivel']
session_start();
$modulos = $_SESSION['modulos'] ?? [];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/Taller/Taller-Mecanica/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/Taller/Taller-Mecanica/estilos.css">

</head>
<body>

   <aside class="sidebar">
        <div class="sidebar-header">
            <a href="/Taller/Taller-Mecanica/Menu.php"><img src="/Taller/Taller-Mecanica/img/logo.png" class="logo-img" alt="Logo"></a>
        </div>
        <nav class="menu-container">
            <?php if (in_array("Seguridad", $modulos)) : ?>
            <div class="modulo">
                <button class="modulo-btn">
                    <img src="/Taller/Taller-Mecanica/img/seguridad.png" class="icono-modulo"> <span>Seguridad</span>
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
                    <img src="/Taller/Taller-Mecanica/img/rrhh.png" class="icono-modulo"> <span>Recursos Humanos</span>
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
                    <img src="/Taller/Taller-Mecanica/img/cliente.png" class="icono-modulo"> <span>Cliente</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Cliente/MCliente.php">Gestión de Clientes</a>
                    <a href="/Taller/Taller-Mecanica/view/Cliente/MCredito.php">Créditos y Cobranzas</a>
                    <a href="/Taller/Taller-Mecanica/view/Cliente/CHistorialCredito.php">Historial de Crédito</a>
                    <a href="/Taller/Taller-Mecanica/view/Cliente/CDeuda.php">Consulta de Deuda</a>
                    <a href="/Taller/Taller-Mecanica/view/Cliente/CDatacredito.php">Maestro DataCredito</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <?php if (in_array("Vehiculo", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="/Taller/Taller-Mecanica/img/coche.png" class="icono-modulo"> <span>Vehículo</span>
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
                    <img src="/Taller/Taller-Mecanica/img/inventario.png" class="icono-modulo"> <span>Inventario</span>
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
                    <img src="/Taller/Taller-Mecanica/img/taller.png" class="icono-modulo"> <span>Taller</span>
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
                    <a href="/Taller/Taller-Mecanica/view/Taller/HistorialGarantias.php">Historial de Garantías</a>
                    <a href="/Taller/Taller-Mecanica/view/Taller/HistorialAvanzado.php">Historial Avanzado</a>
                </div>
            </div>
            <?php endif; ?>
  
            <div class="modulo">
                <?php if (in_array("Facturacion", $modulos)) : ?>
                <button class="modulo-btn">
                    <img src="/Taller/Taller-Mecanica/img/facturacion.png" class="icono-modulo"> <span>Facturacion</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/Factura.php">Nueva Factura (POS)</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/ACaja.php">Apertura de Caja</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/Cotizacion.php">Gestión de Cotizaciones</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/Devolucion.php">Gestión de Devolucion</a>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/CuentasPorCobrar.php">Cuentas Por Cobrar</a>
                    <?php if (in_array("Seguridad", $modulos)) : ?>
                    <a href="/Taller/Taller-Mecanica/view/Facturacion/RFactura.php">Reportes de Ventas (NCF)</a>
                    <?php endif; ?>
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
                    <img src="/Taller/Taller-Mecanica/img/lavado.png" class="icono-modulo"> <span>Autolavado</span>
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
                    <img src="/Taller/Taller-Mecanica/img/carreras.png" class="icono-modulo"> <span>Autoadorno</span>
                </button>
                <div class="modulo-content">
                    <a href="/Taller/Taller-Mecanica/view/Autoadorno/AServicio.php">Servicios de Detailing</a>
                    <a href="/Taller/Taller-Mecanica/view/Autoadorno/MPaquete.php">Paquetes de Servicio</a>
                    <a href="/Taller/Taller-Mecanica/view/Autoadorno/MGarantia.php">Garantías de Instalación</a>
                    <a href="/Taller/Taller-Mecanica/view/Autoadorno/HistorialServicio.php">Historial de Servicio</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="modulo">
                <a href="/Taller/Taller-Mecanica/logout.php" class="modulo-btn" id="logoutBtn" style="text-decoration: none;">
                    <img src="/Taller/Taller-Mecanica/img/salida.png" class="icono-modulo">
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- <script>
        document.addEventListener("DOMContentLoaded", () => {

  const moduloBtns = document.querySelectorAll(".modulo-btn");
  const submenuBtns = document.querySelectorAll(".submenu-btn");

  // ===== MODULOS =====
  moduloBtns.forEach(btn => {
    btn.addEventListener("click", () => {

      const moduloContent = btn.nextElementSibling;

      // Cerrar otros módulos
      document.querySelectorAll(".modulo-content").forEach(m => {
        if (m !== moduloContent) {
          m.style.maxHeight = null;
          m.querySelectorAll(".submenu-content").forEach(s => {
            s.style.maxHeight = null;
          });
        }
      });

      // Toggle módulo actual
      if (moduloContent.style.maxHeight) {
        moduloContent.style.maxHeight = null;
      } else {
        moduloContent.style.maxHeight = moduloContent.scrollHeight + "px";
      }

    });
  });

  // ===== SUBMENUS =====
  submenuBtns.forEach(btn => {
    btn.addEventListener("click", () => {

      const submenuContent = btn.nextElementSibling;
      const moduloActual = btn.closest(".modulo-content");

      // Cerrar otros submenus
      moduloActual.querySelectorAll(".submenu-content").forEach(s => {
        if (s !== submenuContent) {
          s.style.maxHeight = null;
        }
      });

      // Toggle submenu actual
      if (submenuContent.style.maxHeight) {
        submenuContent.style.maxHeight = null;
      } else {
        submenuContent.style.maxHeight = submenuContent.scrollHeight + "px";
      }

      // 🔥 CLAVE: recalcular altura del módulo padre
      setTimeout(() => {
        moduloActual.style.maxHeight = moduloActual.scrollHeight + "px";
      }, 300); // coincide con el transition

    });
  });

});
    </script> -->

    <script src="/Taller/Taller-Mecanica/Scripts_Menu.js"></script>