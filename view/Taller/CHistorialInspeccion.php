<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
 /* ESTILOS CORPORATIVOS REPLICADOS PARA EL MODAL */
    :root { 
        --primary-dark: #123270; 
        --bg-light-blue: #e8eff9; 
        --border-color: #123270;
    }

    .modal { z-index: 105000 !important; }
    .modal-backdrop { z-index: 104900 !important; }
    
    .hoja-inspeccion-modal { 
        background: #fff; 
        border: 2px solid var(--border-color); 
        padding: 25px; 
        font-family: 'Segoe UI', Arial, sans-serif; 
        font-size: 11px; 
        color: #000; 
    }

    .section-title { 
        background-color: var(--primary-dark); 
        color: white; 
        padding: 6px 12px; 
        font-weight: bold; 
        font-size: 12px; 
        margin-top: 15px;
        margin-bottom: 0; 
        text-transform: uppercase;
    }

    .section-content {
        border: 1px solid var(--border-color);
        border-top: none;
        padding: 10px;
        background-color: #fdfdfd;
    }

    .table-inspeccion { width: 100%; border-collapse: collapse; }
    .table-inspeccion th, .table-inspeccion td { border: 1px solid #ccc; padding: 4px; vertical-align: middle; background: #fff;}
    .table-inspeccion th { background-color: var(--bg-light-blue); color: var(--primary-dark); font-weight: bold; text-align: center; }
    
    .print-checkbox { width: 13px; height: 13px; cursor: not-allowed; opacity: 0.6; }
    .img-referencia { max-width: 100%; height: auto; object-fit: contain; }

    .form-control-plaintext {
        border: 1px solid #e0e0e0;
        background-color: #fff;
        padding: 2px 5px;
        border-radius: 3px;
        font-weight: bold;
        color: var(--primary-dark);
        width: 100%;
        display: inline-block;
        min-height: 22px;
    }

    /* 🔥 SOLUCIÓN DEFINITIVA A LA PÁGINA EN BLANCO 🔥 */
    @page { size: letter; margin: 0; }
    
    @media print {
        /* 1. Desaparecemos TODO el sistema (layout, menus, tablas) excepto el Modal */
        body > *:not(#modalInspeccion) {
            display: none !important;
        }

        /* 2. Forzamos al body a ser blanco y normal */
        body {
            background-color: white !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* 3. Desactivamos el fondo negro translúcido */
        .modal-backdrop {
            display: none !important;
        }

        /* 4. Rescatamos el Modal y le quitamos sus propiedades flotantes */
        #modalInspeccion {
            position: relative !important;
            display: block !important;
            opacity: 1 !important;
            overflow: visible !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* 5. Estiramos la caja interior para que ocupe todo el papel */
        .modal-dialog {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            transform: none !important; /* <--- Esto es lo que causaba la hoja en blanco */
        }

        .modal-content {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }

        /* 6. Escondemos la cabecera (X) y el pie de página del Modal */
        .modal-header, .modal-footer {
            display: none !important;
        }

        /* 7. Ajustamos el margen para que la impresora no corte los bordes */
        .modal-body {
            padding: 10mm !important; 
        }

        /* Forzar impresión a color */
        * { 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
        }
    }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mb-4">Historial de Inspecciones</h2>

        <div class="card shadow-sm border-0 p-4 mb-5">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No. Insp.</th>
                            <th>Fecha y Hora</th>
                            <th>Cliente</th>
                            <th>Vehículo (Placa/VIN)</th>
                            <th>Asesor Asignado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-historial">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Cargando historial...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalInspeccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i>Inspección <span id="mod_id_titulo"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body bg-light">
                <div id="zona_impresion_modal" class="hoja-inspeccion-modal">
                    
                    <div class="row align-items-center mb-3">
                        <div class="col-5 text-center">
                            <i class="fas fa-tools fs-2" style="color: var(--primary-dark);"></i>
                            <h6 class="fw-bold mt-1 mb-0" style="color: var(--primary-dark); font-size: 14px;">TALLER MECÁNICO</h6>
                        </div>
                        <div class="col-3 text-center">
                            <h5 class="fw-bold mb-0" style="font-size: 16px;">HOJA DE INSPECCIÓN</h5>
                            <span class="badge bg-danger mt-1">Nº <span id="mod_id"></span></span>
                        </div>
                        <div class="col-4">
                            <table class="table table-bordered table-sm mb-0" style="font-size: 11px;">
                                <tr>
                                    <th class="bg-light p-2" style="width: 30%;">FECHA</th>
                                    <td class="p-1"><span class="form-control-plaintext text-dark text-center" id="mod_fecha"></span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light p-2">HORA</th>
                                    <td class="p-1"><span class="form-control-plaintext text-dark text-center" id="mod_hora"></span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light p-2">ASESOR</th>
                                    <td class="p-1"><span class="form-control-plaintext" id="mod_asesor"></span></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="section-title">INFORMACIÓN DEL CLIENTE Y VEHÍCULO</div>
                    <div class="section-content">
                        <div class="row px-2">
                            <div class="col-8 mb-2">
                                <label class="fw-bold mb-1">Cliente / Propietario:</label>
                                <span class="form-control-plaintext" id="mod_cliente"></span>
                            </div>
                            <div class="col-4 mb-2">
                                <label class="fw-bold mb-1">Documento / RNC:</label>
                                <span class="form-control-plaintext" id="mod_documento"></span>
                            </div>
                            
                            <div class="col-4 mb-2">
                                <label class="fw-bold mb-1">Vehículo:</label>
                                <span class="form-control-plaintext" id="mod_vehiculo"></span>
                            </div>
                            <div class="col-4 mb-2">
                                <label class="fw-bold mb-1">Color:</label>
                                <span class="form-control-plaintext" id="mod_color"></span>
                            </div>
                            <div class="col-4 mb-2">
                                <label class="fw-bold mb-1">Placa:</label>
                                <span class="form-control-plaintext" id="mod_placa"></span>
                            </div>

                            <div class="col-12 mt-1 mb-2 border-top pt-2"></div>

                            <div class="col-6 d-flex align-items-center">
                                <label class="fw-bold me-2 mb-0" style="width: 40px;">KM</label>
                                <span class="form-control-plaintext text-center me-2" id="mod_km" style="width: 100px;"></span>
                            </div>
                            <div class="col-6 d-flex align-items-center">
                                <label class="fw-bold me-2 mb-0" style="width: 110px;">Combustible</label>
                                <span class="form-control-plaintext text-center" id="mod_combustible" style="width: 150px;"></span>
                            </div>
                        </div>
                    </div>

                    <div class="section-title">ESTADO GENERAL DEL VEHÍCULO <span class="text-lowercase fw-normal">(Ver documento físico firmado para detalles)</span></div>
                    <div class="section-content opacity-75">
                        <div class="row px-2">
                            <div class="col-4">
                                <table class="table-inspeccion">
                                    <thead><tr><th colspan="4">Interior</th></tr><tr><th>Elemento</th><th>B</th><th>F</th><th>D</th></tr></thead>
                                    <tbody>
                                        <?php
                                        $items = ['Beeper', 'Pito/Bocina', 'Luces int.', 'Aire Cond.', 'Radio', 'Cristales'];
                                        foreach ($items as $item) { echo "<tr><td class='col-item'>$item</td><td class='col-check'><input type='radio' disabled class='print-checkbox'></td><td class='col-check'><input type='radio' disabled class='print-checkbox'></td><td class='col-check'><input type='radio' disabled class='print-checkbox'></td></tr>"; }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-4">
                                <table class="table-inspeccion">
                                    <thead><tr><th colspan="4">Exterior</th></tr><tr><th>Elemento</th><th>B</th><th>F</th><th>D</th></tr></thead>
                                    <tbody>
                                        <?php
                                        $items_ext = ['Goma Rep.', 'Gato', 'Herram.', 'Llave Rueda', 'Luces Tras.', 'Tapa Comb.'];
                                        foreach ($items_ext as $item) { echo "<tr><td class='col-item'>$item</td><td class='col-check'><input type='radio' disabled class='print-checkbox'></td><td class='col-check'><input type='radio' disabled class='print-checkbox'></td><td class='col-check'><input type='radio' disabled class='print-checkbox'></td></tr>"; }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-4">
                                <table class="table-inspeccion">
                                    <thead><tr><th colspan="4">Motor</th></tr><tr><th>Elemento</th><th>B</th><th>F</th><th>D</th></tr></thead>
                                    <tbody>
                                        <?php
                                        $items_mot = ['Varilla Aceite', 'Tapón Aceite', 'Radiador', 'Batería', 'Agua L/V', 'Filtro Aire'];
                                        foreach ($items_mot as $item) { echo "<tr><td class='col-item'>$item</td><td class='col-check'><input type='radio' disabled class='print-checkbox'></td><td class='col-check'><input type='radio' disabled class='print-checkbox'></td><td class='col-check'><input type='radio' disabled class='print-checkbox'></td></tr>"; }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="section-title">ESTADO FÍSICO Y TESTIGOS</div>
                    <div class="section-content opacity-75">
                        <div class="row px-2">
                            <div class="col-3">
                                <div class="text-center">
                                    <img src="../../img/iconos_motor.jpeg" alt="Testigos" class="img-referencia border p-1 bg-white mb-2">
                                </div>
                            </div>
                            <div class="col-9">
                                <div class="row h-100">
                                    <div class="col-6 d-flex justify-content-center align-items-center border-end">
                                        <img src="../../img/vehicle-diagram-Converted.jpg" alt="Exterior" class="img-referencia" style="max-height: 200px;">
                                    </div>
                                    <div class="col-6 d-flex justify-content-center align-items-center">
                                        <img src="../../img/interior.png" alt="Interior" class="img-referencia" style="max-height: 200px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-title">TRABAJO SOLICITADO</div>
                    <div class="section-content">
                        <div class="form-control-plaintext bg-white" id="mod_motivo" style="min-height: 60px; font-weight: normal; border: none;"></div>
                    </div>

                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-2"></i>Imprimir Copia</button>
            </div>
        </div>
    </div>
</div>

<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_HistorialInspeccion.js"></script>
</body>
</html>