<?php
// CertificadoGarantia.php
include("../../controller/conexion.php");

$id_orden = $_GET['id_orden'] ?? 0;

if ($id_orden == 0) {
    die("<h2 style='text-align:center; margin-top:50px; font-family:Arial;'>Error: No se especificó el número de orden.</h2>");
}

// Consultar los datos reales de la garantía, cliente y vehículo (CORREGIDO kilometraje_recepcion)
$sql = "SELECT 
            g.codigo_certificado, 
            DATE_FORMAT(g.fecha_creacion, '%d/%m/%Y') as fecha_emision,
            o.id_orden, 
            CONCAT(p.nombre, ' ', IFNULL(p.apellido_p, '')) as cliente_nombre,
            CONCAT(m.nombre, ' ', IFNULL(v.modelo, ''), ' (Placa: ', v.placa, ')') as vehiculo_info,
            IFNULL(i.kilometraje_recepcion, 0) as km_actual,
            DATE_FORMAT(g.fecha_vencimiento, '%d/%m/%Y') as fecha_vencimiento, 
            g.kilometraje_limite as km_limite, 
            g.terminos_condiciones as terminos
        FROM garantia_servicio g
        JOIN Orden o ON g.id_orden = o.id_orden
        LEFT JOIN inspeccion i ON o.id_inspeccion = i.id_inspeccion
        JOIN Vehiculo v ON g.id_vehiculo = v.sec_vehiculo
        JOIN Marca m ON v.id_marca = m.id_marca
        JOIN Cliente c ON v.id_cliente = c.id_cliente
        JOIN Persona p ON c.id_persona = p.id_persona
        WHERE g.id_orden = ? 
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_orden);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("<h2 style='text-align:center; margin-top:50px; font-family:Arial; color:red;'>No se encontró un certificado de garantía emitido para la Orden ORD-$id_orden.</h2>");
}

$data = $res->fetch_assoc();

// Extraer el tipo de cobertura desde el texto para mostrarlo bonito
$tipo_garantia = "Categoría Estándar";
if (strpos($data['terminos'], 'CAT-A') !== false) $tipo_garantia = "Categoría A - Mantenimiento Preventivo";
if (strpos($data['terminos'], 'CAT-B') !== false) $tipo_garantia = "Categoría B - Mecánica Menor";
if (strpos($data['terminos'], 'CAT-C') !== false) $tipo_garantia = "Categoría C - Mecánica Mayor";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado <?= $data['codigo_certificado'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #e9ecef; font-family: 'Arial', sans-serif; }
        .certificado-hoja {
            background: white;
            width: 210mm; /* A4 */
            min-height: 297mm;
            margin: 30px auto;
            padding: 50px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border: 1px solid #ccc;
        }
        .titulo-doc { font-size: 26px; font-weight: 900; letter-spacing: 1px; color: #1a1a1a; text-transform: uppercase; }
        .codigo-box { border: 2px solid #198754; padding: 6px 20px; font-weight: bold; color: #198754; border-radius: 8px; font-size: 1.1rem; background: #f8fff9;}
        
        .seccion-titulo { font-size: 14px; font-weight: bold; background-color: #f8f9fa; padding: 8px 12px; margin-top: 25px; border-left: 4px solid #198754; text-transform: uppercase;}
        .dato-label { font-size: 12px; color: #666; margin-bottom: 2px; }
        .dato-valor { font-size: 15px; font-weight: bold; border-bottom: 1px dashed #ccc; padding-bottom: 2px; color: #212529;}
        
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
            <p class="mb-0 fs-6 fw-bold text-secondary">MECÁNICA AUTOMOTRIZ DÍAZ & PANTALEÓN</p>
            <p class="small text-muted mb-3">Santiago de los Caballeros, Rep. Dom.</p>
            <span class="codigo-box"><i class="fas fa-shield-check me-1"></i> N° <?= $data['codigo_certificado'] ?></span>
        </div>
    </div>

    <p class="text-end small mt-3 text-muted"><strong>Fecha de Emisión:</strong> <?= $data['fecha_emision'] ?></p>

    <div class="seccion-titulo">1. Datos del Vehículo y Servicio</div>
    <div class="row mt-3 g-4">
        <div class="col-6">
            <p class="dato-label">Cliente Responsable</p>
            <p class="dato-valor"><?= $data['cliente_nombre'] ?></p>
        </div>
        <div class="col-6">
            <p class="dato-label">Orden de Servicio Relacionada</p>
            <p class="dato-valor text-primary">ORD-<?= $data['id_orden'] ?></p>
        </div>
        <div class="col-8">
            <p class="dato-label">Vehículo Intervenido</p>
            <p class="dato-valor"><?= $data['vehiculo_info'] ?></p>
        </div>
        <div class="col-4">
            <p class="dato-label">Kilometraje de Ingreso</p>
            <p class="dato-valor"><?= number_format($data['km_actual'], 0) ?> Km</p>
        </div>
    </div>

    <div class="seccion-titulo">2. Condiciones de Cobertura Aplicadas</div>
    <div class="row mt-3 g-4">
        <div class="col-12">
            <p class="dato-label">Tipo de Cobertura Registrada</p>
            <p class="dato-valor fs-5 text-success"><i class="fas fa-check-circle me-1"></i> <?= $tipo_garantia ?></p>
        </div>
        <div class="col-6">
            <p class="dato-label">La garantía expira el (Fecha Límite):</p>
            <p class="dato-valor text-danger fs-5"><?= $data['fecha_vencimiento'] ?></p>
        </div>
        <div class="col-6">
            <p class="dato-label">O al alcanzar el kilometraje de:</p>
            <p class="dato-valor text-danger fs-5"><?= number_format($data['km_limite'], 0) ?> Km</p>
        </div>
        <div class="col-12 mt-2">
            <div class="p-3 bg-light border border-secondary rounded">
                <p class="dato-label fw-bold text-dark mb-1"><i class="fas fa-info-circle text-primary"></i> Cláusula Específica Guardada en el Sistema:</p>
                <p class="mb-0 small font-monospace text-muted"><?= $data['terminos'] ?></p>
            </div>
        </div>
    </div>

    <div class="seccion-titulo">3. Políticas Generales de Anulación</div>
    <div class="politicas-texto">
        Este documento es indispensable para procesar cualquier reclamación. La garantía ampara exclusivamente defectos en repuestos suministrados por el taller y fallas directas de mano de obra. 
        <strong>La garantía quedará ANULADA AUTOMÁTICAMENTE sin responsabilidad para el taller en los siguientes casos:</strong>
        <ol class="mt-2 ps-3 mb-4">
            <li class="mb-1">Si el vehículo es revisado, desarmado o reparado por un tercero, otro taller o el cliente fuera de nuestras instalaciones.</li>
            <li class="mb-1">Daños causados por negligencia (ignorar luces del tablero, conducir sin fluidos), sobrecarga, accidentes o uso en competencias deportivas.</li>
            <li class="mb-1">Fallas en repuestos, piezas o fluidos suministrados directamente por el cliente.</li>
            <li>Piezas eléctricas o componentes electrónicos, los cuales son ventas finales y no tienen garantía por fluctuaciones de voltaje externas.</li>
        </ol>
        <div class="text-center p-2 border border-2 border-dark bg-light mt-3">
            <p class="mb-0 fw-bold text-uppercase" style="font-size: 11px;">AL FIRMAR ESTE DOCUMENTO, EL CLIENTE ACEPTA HABER RECIBIDO EL VEHÍCULO A SU ENTERA SATISFACCIÓN Y ACEPTA TODAS LAS POLÍTICAS AQUÍ DESCRITAS.</p>
        </div>
    </div>

    <div class="row firmas-box">
        <div class="col-6">
            <div class="linea-firma"></div>
            <p class="nombre-firma">Firma Taller (Autorización)</p>
            <p class="text-center small text-muted">Mecánica Díaz & Pantaleón</p>
        </div>
        <div class="col-6">
            <div class="linea-firma"></div>
            <p class="nombre-firma">Firma Cliente (Conformidad)</p>
            <p class="text-center small text-muted">Cédula: ___________________</p>
        </div>
    </div>
</div>

</body>
</html>