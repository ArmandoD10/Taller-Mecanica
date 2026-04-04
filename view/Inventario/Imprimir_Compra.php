<?php
include("../../controller/conexion.php");

$id_compra = (int)($_GET['id'] ?? 0);

if ($id_compra === 0) {
    die("Error: No se especificó el número de orden.");
}

// 1. OBTENER CABECERA DE LA COMPRA
$sql_cabecera = "SELECT c.*, 
                        p.nombre_comercial, p.RNC, p.correo, 
                        m.nombre as metodo_pago, 
                        mo.codigo_ISO as moneda, mo.nombre as moneda_nombre
                 FROM compra c
                 JOIN proveedor p ON c.id_proveedor = p.id_proveedor
                 JOIN metodo_pago m ON c.id_metodo = m.id_metodo
                 JOIN moneda mo ON c.id_moneda = mo.id_moneda
                 WHERE c.id_compra = ?";
                 
$stmt = $conexion->prepare($sql_cabecera);
$stmt->bind_param("i", $id_compra);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();

if (!$orden) {
    die("Error: Orden de compra no encontrada o eliminada.");
}

// 2. OBTENER DETALLES DE ARTÍCULOS
$sql_detalles = "SELECT d.cantidad_pedida as cantidad, d.precio, d.subtotal, 
                        a.nombre, a.num_serie
                 FROM detalle_compra d
                 JOIN repuesto_articulo a ON d.id_articulo = a.id_articulo
                 WHERE d.id_compra = ?";
                 
$stmt_det = $conexion->prepare($sql_detalles);
$stmt_det->bind_param("i", $id_compra);
$stmt_det->execute();
$detalles = $stmt_det->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Compra OC-<?= str_pad($orden['id_compra'], 4, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #525659; /* Fondo gris oscuro tipo visor PDF */
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .hoja-impresion {
            background-color: white;
            width: 21cm;
            min-height: 29.7cm; /* Tamaño A4 */
            margin: 2cm auto;
            padding: 2cm;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        
        /* --- ESTILOS CORREGIDOS PARA EL LOGO --- */
        .logo-img {
            max-height: 80px; /* Ajusta este número si tu logo es muy grande o muy pequeño */
            width: auto;
            object-fit: contain;
            display: block;
        }

        .tabla-detalles th { background-color: #f8f9fa !important; }
        .firmas { margin-top: 100px; }
        .linea-firma {
            border-top: 1px solid #000;
            width: 250px;
            text-align: center;
            margin: 0 auto;
            padding-top: 5px;
            font-weight: bold;
        }
        
        /* --- MAGIA DE IMPRESIÓN LIMPIA --- */
        @page {
            /* Esto le dice al navegador que no imprima las URL ni la fecha en los bordes */
            margin: 0; 
        }

        @media print {
            body { 
                background-color: white; 
                /* Como pusimos el margin en 0, le damos padding a la hoja para que el texto no se pegue al borde físico del papel */
                padding: 1.5cm; 
                /* Esto fuerza a que los fondos grises de la tabla se impriman */
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .hoja-impresion {
                width: 100%; margin: 0; padding: 0; box-shadow: none; border: none;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="text-center my-3 no-print">
        <button class="btn btn-light me-2" onclick="window.close()">Cerrar Ventana</button>
        <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-2"></i>Imprimir / Guardar PDF</button>
    </div>

    <div class="hoja-impresion">
        
        <div class="row border-bottom pb-3 mb-4 align-items-center">
            <div class="col-6">
                <img src="/Taller/Taller-Mecanica/img/logo.png" class="logo-img mb-3" alt="Logo">
                <h4 class="fw-bold mb-0">Mecánica Automotriz Díaz Pantaleón</h4>
                <p class="mb-0 text-muted">Santiago de los Caballeros, Rep. Dom.</p>
                <p class="mb-0 text-muted">Tel: 809-545-6872</p>
            </div>
            <div class="col-6 text-end">
                <h2 class="text-uppercase fw-bold text-secondary">Orden de Compra</h2>
                <h4 class="fw-bold text-danger">OC-<?= str_pad($orden['id_compra'], 4, '0', STR_PAD_LEFT) ?></h4>
                <p class="mb-0"><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($orden['fecha_creacion'])) ?></p>
                <p class="mb-0"><strong>Estado:</strong> <?= strtoupper($orden['estado']) ?></p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-7">
                <h6 class="fw-bold text-uppercase bg-light p-2 border-start border-4 border-primary">Datos del Proveedor</h6>
                <p class="mb-1"><strong>Proveedor:</strong> <?= htmlspecialchars($orden['nombre_comercial']) ?></p>
                <p class="mb-1"><strong>RNC:</strong> <?= htmlspecialchars($orden['RNC'] ?? 'N/A') ?></p>
                <p class="mb-1"><strong>Correo:</strong> <?= htmlspecialchars($orden['correo'] ?? 'N/A') ?></p>
            </div>
            <div class="col-5">
                <h6 class="fw-bold text-uppercase bg-light p-2 border-start border-4 border-secondary">Términos Comerciales</h6>
                <p class="mb-1"><strong>Moneda:</strong> <?= htmlspecialchars($orden['moneda']) ?> - <?= htmlspecialchars($orden['moneda_nombre']) ?></p>
                <p class="mb-1"><strong>Método de Pago:</strong> <?= htmlspecialchars($orden['metodo_pago']) ?></p>
            </div>
        </div>

        <?php if (!empty($orden['detalle'])): ?>
        <div class="mb-4">
            <p><strong>Notas de la Orden:</strong> <?= htmlspecialchars($orden['detalle']) ?></p>
        </div>
        <?php endif; ?>

        <table class="table table-bordered tabla-detalles align-middle text-center mb-4">
            <thead>
                <tr>
                    <th>Cód/Serie</th>
                    <th class="text-start">Descripción del Artículo</th>
                    <th>Cant.</th>
                    <th>Precio U.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $det): ?>
                <tr>
                    <td><?= htmlspecialchars($det['num_serie']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($det['nombre']) ?></td>
                    <td class="fw-bold"><?= $det['cantidad'] ?></td>
                    <td><?= $orden['moneda'] ?> <?= number_format($det['precio'], 2) ?></td>
                    <td class="fw-bold"><?= $orden['moneda'] ?> <?= number_format($det['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end fw-bold fs-5">TOTAL ORDEN:</td>
                    <td class="fw-bold fs-5 text-primary"><?= $orden['moneda'] ?> <?= number_format($orden['monto'], 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="row firmas">
            <div class="col-6">
                <div class="linea-firma">Preparado por / Compras</div>
            </div>
            <div class="col-6">
                <div class="linea-firma">Autorizado por / Gerencia</div>
            </div>
        </div>

    </div>

    <script>
        window.onload = function() {
            // Un pequeño retraso de medio segundo asegura que la imagen del logo cargue antes de lanzar la ventana de impresión
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>