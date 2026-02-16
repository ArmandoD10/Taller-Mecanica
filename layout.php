<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Taller/Taller-Mecanica/estilos.css">

</head>
<body>

    <div class="sidebar">

    <div class="sidebar-header">
      <a href="/Taller/Taller-Mecanica/Menu.php">
        <img src="../../img/logo.png" class="logo-img">
    </a>
    </div>


  <!-- MODULO TALLER -->
  <div class="modulo">
    <button class="modulo-btn">
        <img src="../../img/taller.png" class="icono-modulo" alt="Taller">
        <span>Taller</span>
    </button>
    <div class="modulo-content">

      <button class="submenu-btn">Mantenimientos</button>
      <div class="submenu-content">
        <a href="/Taller/Taller-Mecanica/view/Mantenimiento/MUsuario.php">Usuarios</a>
        <a href="/Taller/Taller-Mecanica/view/Mantenimiento/MCliente.php">Clientes</a>
        <a href="/Taller/Taller-Mecanica/view/Mantenimiento/MEmpleado.php">Empleados</a>
        <a href="/Taller/Taller-Mecanica/view/Mantenimiento/MAlmacen.php">Almacen</a>
        <a href="/Taller/Taller-Mecanica/view/Mantenimiento/MRecurso.php">Recursos</a>
        <a href="/Taller/Taller-Mecanica/view/Mantenimiento/MVehiculo.php">Vehículos</a>
        <a href="/Taller/Taller-Mecanica/view/Mantenimiento/MProveedor.php">Proveedor</a>
        <a href="/Taller/Taller-Mecanica/view/Mantenimiento/MCXC.php">Cuentas Credito</a>
      </div>

      <button class="submenu-btn">Procesos</button>
      <div class="submenu-content">
         <a href="/Taller/Taller-Mecanica/view/Proceso/Inspeccion.php">Inspeccion</a>
        <a href="/Taller/Taller-Mecanica/view/Proceso/Cotizacion.php">Cotizacion</a>
        <a href="/Taller/Taller-Mecanica/view/Proceso/Factura.php">Facturacion</a>
        <a href="/Taller/Taller-Mecanica/view/Proceso/Compra.php">Orden de Compra</a>
        <a href="/Taller/Taller-Mecanica/view/Proceso/Servicio.php">Orden Servicio</a>
        <a href="/Taller/Taller-Mecanica/view/Proceso/Entrega.php">Entrega y Revision</a>
      </div>

      <button class="submenu-btn">Consultas</button>
      <div class="submenu-content">
        <a href="/Taller/Taller-Mecanica/view/Consulta/CAlmacen.php">Almacen</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CUsuario.php">Usuario</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CCotizacion.php">Cotizacion</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CFactura.php">Facturas</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CCxc.php">Cuentas por pagar</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CServicio.php">Orden de Servicio</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CVehiculo.php">Vehiculos</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CCliente.php">Clientes</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CEmpleado.php">Empleados</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CProveedor.php">Proveedores</a>
        <a href="/Taller/Taller-Mecanica/view/Consulta/CCompra.php">Historial de Compra</a>
      </div>

      <button class="submenu-btn">Reportes</button>
      <div class="submenu-content">
        <a href="/Taller/Taller-Mecanica/view/Reporte/RCotizacion.php">Reporte Cotizacion</a>
        <a href="/Taller/Taller-Mecanica/view/Reporte/RFactura.php">Reporte Factura</a>
        <a href="/Taller/Taller-Mecanica/view/Reporte/RDeuda.php">Reporte Deuda</a>
        <a href="/Taller/Taller-Mecanica/view/Reporte/RServicio.php">Reporte Servicio</a>
        <a href="/Taller/Taller-Mecanica/view/Reporte/RCompra.php">Reporte Compra</a>
        <a href="/Taller/Taller-Mecanica/view/Reporte/REmpleado.php">Reporte Empleado</a>
        <a href="/Taller/Taller-Mecanica/view/Reporte/RCliente.php">Reporte Cliente</a>
        <a href="/Taller/Taller-Mecanica/view/Reporte/RVehiculo.php">Reporte Vehiculo</a>
        <a href="/Taller/Taller-Mecanica/view/Reporte/RAlmacen.php">Reporte Almacen</a>
      </div>

    </div>
  </div>

  <!-- MODULO AUTOLAVADO -->
  <div class="modulo">
    <button class="modulo-btn">
        <img src="../../img/lavado.png" class="icono-modulo" alt="Autolavado">
        <span>Autolavado</span>
    </button>
    <div class="modulo-content">
      <!-- mismos submenus -->
      <button class="submenu-btn">Procesos</button>
      <div class="submenu-content">
        <a href="#">Orden Lavador</a>
        <a href="#">Entrega y Revision</a>
      </div>

      <button class="submenu-btn">Consultas</button>
      <div class="submenu-content">
        <a href="#">Historial de Lavado</a>
        <a href="#">Historial de Entrega</a>
      </div>

      <button class="submenu-btn">Reportes</button>
      <div class="submenu-content">
        <a href="#">Reporte de Lavados</a>
      </div>
    </div>
  </div>

  <!-- MODULO AUTOADORNO -->
  <div class="modulo">
    <button class="modulo-btn">
        <img src="../../img/carreras.png" class="icono-modulo" alt="Autoadorno">
        <span>Autoadorno</span>
    </button>
    <div class="modulo-content">
      <button class="submenu-btn">Procesos</button>
      <div class="submenu-content">
        <a href="#">Devolucion</a>
        <a href="#">Cotizacion</a>
        <a href="#">Apertura de caja</a>
      </div>

      <button class="submenu-btn">Consultas</button>
      <div class="submenu-content">
        <a href="#">Cotizaciones</a>
        <a href="#">Articulos</a>
        <a href="#">Devoluciones</a>
      </div>

      <button class="submenu-btn">Reportes</button>
      <div class="submenu-content">
        <a href="#">Devoluciones</a>
      </div>
    </div>
  </div>

   <div class="modulo">
    <a href="/Taller/Taller-Mecanica/logout.php" class="modulo-btn" id="logoutBtn">
        <img src="../../img/salida.png" class="icono-modulo">
        <span>Cerrar sesión</span>
    </a>
</div>

</div>

    <script>
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
    </script>