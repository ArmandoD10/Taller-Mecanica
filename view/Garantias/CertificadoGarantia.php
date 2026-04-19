<?php
include("../../controller/conexion.php");

$id_orden = (int)($_GET['id_orden'] ?? 0);

if ($id_orden == 0) {
    die("<h2 style='text-align:center; margin-top:50px; font-family:Arial;'>Error: No se especificó el número de orden.</h2>");
}

// 1. Consultar Cabecera de Garantía
$sqlCab = "SELECT 
            g.codigo_certificado, 
            DATE_FORMAT(g.fecha_creacion, '%d/%m/%Y') as fecha_emision,
            o.id_orden, 
            CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente_nombre,
            CONCAT(m.nombre, ' ', IFNULL(v.modelo, ''), ' (Placa: ', v.placa, ')') as vehiculo_info,
            IFNULL(i.kilometraje_recepcion, 0) as km_actual
        FROM garantia_servicio g
        JOIN orden o ON g.id_orden = o.id_orden
        LEFT JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
        JOIN vehiculo v ON g.id_vehiculo = v.sec_vehiculo
        JOIN marca m ON v.id_marca = m.id_marca
        JOIN cliente c ON v.id_cliente = c.id_cliente
        JOIN persona p ON c.id_persona = p.id_persona
        WHERE g.id_orden = ? 
        LIMIT 1";

$stmtC = $conexion->prepare($sqlCab);
$stmtC->bind_param("i", $id_orden);
$stmtC->execute();
$resC = $stmtC->get_result();

if ($resC->num_rows === 0) {
    die("<h2 style='text-align:center; margin-top:50px; font-family:Arial; color:red;'>No se generó certificado de garantía para la Orden ORD-$id_orden (Ningún ítem aplicó cobertura).</h2>");
}
$cabecera = $resC->fetch_assoc();

// 2. Consultar Detalles Garantizados (Unión de Servicios y Repuestos)
$sqlDetalles = "
    SELECT 
        'Servicio (Mano de Obra)' as tipo_item, 
        ts.nombre as descripcion, 
        pg.nombre as politica, 
        DATE_FORMAT(os.fecha_vencimiento, '%d/%m/%Y') as vence_fecha,
        IFNULL(os.kilometraje_vencimiento, 'Ilimitado') as vence_km,
        pg.descripcion as notas_politica
    FROM orden_servicio os
    JOIN tipo_servicio ts ON os.id_tipo_servicio = ts.id_tipo_servicio
    JOIN politica_garantia pg ON os.id_politica = pg.id_politica
    WHERE os.id_orden = $id_orden AND os.id_politica IS NOT NULL
    
    UNION ALL
    
    SELECT 
        'Repuesto (Pieza)' as tipo_item, 
        ra.nombre as descripcion, 
        pg.nombre as politica, 
        DATE_FORMAT(orp.fecha_vencimiento, '%d/%m/%Y') as vence_fecha,
        IFNULL(orp.kilometraje_vencimiento, 'Ilimitado') as vence_km,
        pg.descripcion as notas_politica
    FROM orden_repuesto orp
    JOIN repuesto_articulo ra ON orp.id_articulo = ra.id_articulo
    JOIN politica_garantia pg ON orp.id_politica = pg.id_politica
    WHERE orp.id_orden = $id_orden AND orp.id_politica IS NOT NULL
";
$detalles_garantia = $conexion->query($sqlDetalles)->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado <?= $cabecera['codigo_certificado'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #e9ecef; font-family: 'Arial', sans-serif; }
        .certificado-hoja {
            background: white; width: 210mm; min-height: 297mm;
            margin: 30px auto; padding: 50px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15); border: 1px solid #ccc;
        }
        .titulo-doc { font-size: 26px; font-weight: 900; letter-spacing: 1px; color: #1a1a1a; text-transform: uppercase; }
        .codigo-box { border: 2px solid #198754; padding: 6px 20px; font-weight: bold; color: #198754; border-radius: 8px; font-size: 1.1rem; background: #f8fff9;}
        
        .seccion-titulo { font-size: 14px; font-weight: bold; background-color: #f8f9fa; padding: 8px 12px; margin-top: 25px; border-left: 4px solid #198754; text-transform: uppercase;}
        .dato-label { font-size: 12px; color: #666; margin-bottom: 2px; }
        .dato-valor { font-size: 15px; font-weight: bold; border-bottom: 1px dashed #ccc; padding-bottom: 2px; color: #212529;}
        
        .tabla-items th { background-color: #f8f9fa !important; font-size: 12px; }
        .tabla-items td { font-size: 12px; vertical-align: middle;}
        
        .politicas-texto { font-size: 11.5px; color: #444; text-align: justify; line-height: 1.5; margin-top: 20px;}
        
        .firmas-box { margin-top: 60px; }
        .linea-firma { border-top: 1px solid #000; width: 85%; margin: 0 auto; margin-top: 60px; }
        .nombre-firma { text-align: center; font-size: 12px; font-weight: bold; margin-top: 8px;}

        @media print {
            body { background-color: white; margin: 0; padding: 0; }
            .certificado-hoja { margin: 0; padding: 0; box-shadow: none; border: none; width: 100%; min-height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="text-center my-4 no-print">
    <button onclick="window.print()" class="btn btn-success btn-lg shadow fw-bold px-4"><i class="fas fa-print me-2"></i> Imprimir Documento</button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-lg shadow ms-2 fw-bold">Cerrar Pestaña</button>
</div>

<div class="certificado-hoja">
    <div class="row align-items-center border-bottom pb-4">
        <div class="col-4">
            <img src="/Taller/Taller-Mecanica/img/logo.png" alt="Logo Taller" style="max-width: 100%; height: 70px; object-fit: contain;">
        </div>
        <div class="col-8 text-end">
            <h1 class="titulo-doc mb-1">Certificado de Garantía</h1>
            <p class="mb-0 fs-6 fw-bold text-secondary">MECÁNICA AUTOMOTRIZ DÍAZ PANTALEÓN (SIG)</p>
            <span class="codigo-box d-inline-block mt-2"><i class="fas fa-shield-check me-1"></i> N° <?= $cabecera['codigo_certificado'] ?></span>
        </div>
    </div>

    <div class="seccion-titulo">1. Información del Cliente y Vehículo</div>
    <div class="row mt-3 g-4">
        <div class="col-6">
            <p class="dato-label">Cliente Responsable</p>
            <p class="dato-valor"><?= $cabecera['cliente_nombre'] ?></p>
        </div>
        <div class="col-6 text-end">
            <p class="dato-label">Orden de Origen</p>
            <p class="dato-valor text-primary text-end">ORD-<?= $cabecera['id_orden'] ?></p>
        </div>
        <div class="col-8">
            <p class="dato-label">Vehículo</p>
            <p class="dato-valor"><?= $cabecera['vehiculo_info'] ?></p>
        </div>
        <div class="col-4 text-end">
            <p class="dato-label">Km Inicial (Recepción)</p>
            <p class="dato-valor text-end"><?= number_format($cabecera['km_actual'], 0) ?> Km</p>
        </div>
    </div>

    <div class="seccion-titulo">2. Desglose de Coberturas por Ítem</div>
    <p class="small text-muted mt-2 mb-2"><em>* La garantía expirará cuando se alcance la Fecha Límite o el Kilometraje Límite, lo que ocurra primero. Los ítems de la orden no mostrados en esta lista NO poseen garantía.</em></p>
    
    <table class="table table-bordered tabla-items mt-2">
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Descripción del Trabajo / Pieza</th>
                <th>Política Asignada</th>
                <th class="text-center text-danger">Fecha Vence</th>
                <th class="text-center text-danger">KM Vence</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles_garantia as $d): ?>
            <tr>
                <td class="text-muted"><b><?= $d['tipo_item'] ?></b></td>
                <td class="fw-bold"><?= $d['descripcion'] ?></td>
                <td>
                    <span class="badge bg-success mb-1"><?= $d['politica'] ?></span><br>
                    <small class="text-muted" style="font-size:10px;"><?= $d['notas_politica'] ?></small>
                </td>
                <td class="text-center fw-bold text-danger"><?= $d['vence_fecha'] ?></td>
                <td class="text-center fw-bold text-danger"><?= $d['vence_km'] != 'Ilimitado' ? number_format($d['vence_km'],0)." Km" : 'Ilimitado' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="seccion-titulo">3. Políticas Generales de Anulación</div>
    <div class="politicas-texto">
        Este documento es indispensable para procesar cualquier reclamación. La garantía ampara exclusivamente defectos en repuestos suministrados por el taller listados arriba y fallas directas de mano de obra. 
        <strong>La garantía quedará ANULADA AUTOMÁTICAMENTE sin responsabilidad para el taller en los siguientes casos:</strong>
        <ul class="mt-2 ps-3 mb-4">
            <li class="mb-1">Si el vehículo es revisado, desarmado o reparado por un tercero, otro taller o el cliente fuera de nuestras instalaciones.</li>
            <li class="mb-1">Daños causados por negligencia (ignorar luces del tablero, conducir sin fluidos), accidentes o uso inadecuado.</li>
            <li>Piezas eléctricas o componentes electrónicos, los cuales pueden fallar por fluctuaciones de voltaje ajenas al repuesto.</li>
        </ul>
        <div class="text-center p-2 border border-2 border-dark bg-light mt-3">
            <p class="mb-0 fw-bold text-uppercase" style="font-size: 11px;">AL FIRMAR, EL CLIENTE ACEPTA HABER RECIBIDO EL VEHÍCULO A SU ENTERA SATISFACCIÓN Y ACEPTA LAS POLÍTICAS AQUÍ DESCRITAS.</p>
        </div>
    </div>

    <div class="row firmas-box">
        <div class="col-6">
            <div class="linea-firma"></div>
            <p class="nombre-firma">Firma Autorizada Taller</p>
        </div>
        <div class="col-6">
            <div class="linea-firma"></div>
            <p class="nombre-firma">Firma de Conformidad Cliente</p>
        </div>
    </div>
</div>

</body>
</html>