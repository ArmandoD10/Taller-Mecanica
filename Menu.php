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
    <style>
        :root {
            --primary: #1e3a8a; --secondary: #64748b; --success: #10b981;
            --warning: #f59e0b; --danger: #ef4444; --bg: #f8fafc; --white: #ffffff;
        }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background-color: var(--bg); }
        
        /* Sidebar */
        aside { width: 250px; background-color: var(--primary); color: white; padding: 20px; display: flex; flex-direction: column; }
        aside h2 { font-size: 1.5rem; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        nav a { color: #cbd5e1; text-decoration: none; padding: 12px 0; display: block; transition: 0.3s; }
        nav a:hover { color: white; padding-left: 10px; }

        /* Contenido Principal */
        main { flex: 1; overflow-y: auto; padding: 30px; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        /* Tarjetas */
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: var(--white); padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card h3 { margin: 0; font-size: 0.9rem; color: var(--secondary); }
        .card p { margin: 10px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--primary); }

        /* Tabla */
        .table-container { background: var(--white); padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid var(--bg); color: var(--secondary); }
        td { padding: 12px; border-bottom: 1px solid var(--bg); }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .bg-warning { background: #fef3c7; color: #92400e; }
        .bg-success { background: #d1fae5; color: #065f46; }
        .bg-primary { background: #dbeafe; color: #1e40af; }

        .btn-action { background: var(--primary); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>

    <aside>
        <h2><?php echo $nombreTaller; ?></h2>
        <nav>
            <a href="#">📊 Dashboard</a>
            <a href="#">📋 Órdenes</a>
            <a href="#">📦 Inventario</a>
            <a href="#">💰 Facturación</a>
        </nav>
    </aside>

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

</body>
</html>