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

</head>
<body>

    <div class="sidebar">

    <div class="sidebar-header">
        <h2>🚗 AutoFlow Pro</h2>
    </div>


  <!-- MODULO TALLER -->
  <div class="modulo">
    <button class="modulo-btn">
        <img src="img/taller.png" class="icono-modulo" alt="Taller">
        <span>Taller</span>
    </button>
    <div class="modulo-content">

      <button class="submenu-btn">Mantenimientos</button>
      <div class="submenu-content">
        <a href="#">Clientes</a>
        <a href="#">Vehículos</a>
        <a href="#">Servicios</a>
      </div>

      <button class="submenu-btn">Procesos</button>
      <div class="submenu-content">
        <a href="#">Orden Taller</a>
        <a href="#">Facturación</a>
      </div>

      <button class="submenu-btn">Consultas</button>
      <div class="submenu-content">
        <a href="#">Historial</a>
      </div>

      <button class="submenu-btn">Reportes</button>
      <div class="submenu-content">
        <a href="#">Reporte General</a>
      </div>

    </div>
  </div>

  <!-- MODULO AUTOLAVADO -->
  <div class="modulo">
    <button class="modulo-btn">
        <img src="img/lavado.png" class="icono-modulo" alt="Autolavado">
        <span>Autolavado</span>
    </button>
    <div class="modulo-content">
      <!-- mismos submenus -->
      <button class="submenu-btn">Procesos</button>
      <div class="submenu-content">
        <a href="#">Orden Lavador</a>
        <a href="#">Facturación</a>
      </div>

      <button class="submenu-btn">Consultas</button>
      <div class="submenu-content">
        <a href="#">Historial</a>
      </div>

      <button class="submenu-btn">Reportes</button>
      <div class="submenu-content">
        <a href="#">Reporte General</a>
      </div>
    </div>
  </div>

  <!-- MODULO AUTOADORNO -->
  <div class="modulo">
    <button class="modulo-btn">
        <img src="img/carreras.png" class="icono-modulo" alt="Autoadorno">
        <span>Autoadorno</span>
    </button>
    <div class="modulo-content">
      <!-- mismos submenus -->
    </div>
  </div>

  <div class="modulo">
    <button class="modulo-btn">
        <img src="img/salida.png" class="icono-modulo" alt="Taller">
        <span>Cerrar seccion</span>
    </button>
    <div class="modulo-content">
      <!-- mismos submenus -->
    </div>
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