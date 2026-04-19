<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
    /* Estilos replicados de la Hoja de Inspección para el Modal */
    :root { --form-border-color: #0b2a70; --form-bg-header: #e6edff; }
    .hoja-preview { background: #fff; border: 1px solid var(--form-border-color); padding: 15px; font-family: Arial, sans-serif; font-size: 11px; color: #000; }
    .preview-title { background-color: var(--form-border-color); color: white; padding: 4px 10px; font-weight: bold; text-align: center; margin-top: 10px; margin-bottom: 5px; font-size: 12px; }
    .preview-sub-title { background-color: var(--form-bg-header); color: var(--form-border-color); border: 1px solid var(--form-border-color); padding: 3px; text-align: center; font-weight: bold; }
    .table-preview { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
    .table-preview th, .table-preview td { border: 1px solid #ccc; padding: 2px 4px; vertical-align: middle; }
    .car-box { border: 1px solid #ccc; text-align: center; padding: 5px; }
    .img-ref { max-width: 100%; height: auto; object-fit: contain; }
    .check-cell { width: 20px; text-align: center; font-weight: bold; }
    .active-check { background-color: #ffff99; color: #000; border: 1px solid #000; }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="mt-4 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0"><i class="fas fa-history me-2 text-primary"></i>Historial de Inspecciones</h2>
                <p class="text-muted small">Consulta de registros de entrada técnicos.</p>
            </div>
            <a href="MInspeccion.php" class="btn btn-primary fw-bold shadow-sm">
                <i class="fas fa-plus me-1"></i> Nueva Inspección
            </a>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1">Búsqueda General</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                            <input type="text" id="filtroGeneral" class="form-control" placeholder="Placa, Cliente o ID..." oninput="filtrarTabla()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Desde:</label>
                        <input type="date" id="fechaDesde" class="form-control form-control-sm" onchange="filtrarTabla()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Hasta:</label>
                        <input type="date" id="fechaHasta" class="form-control form-control-sm" onchange="filtrarTabla()">
                    </div>
                    <div class="col-md-2">
                        <label class="small d-block mb-1">&nbsp;</label>
                        <button class="btn btn-sm btn-outline-secondary w-100" onclick="limpiarFiltros()">Limpiar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Fecha</th>
                                <th>Vehículo / Placa</th>
                                <th>Cliente</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-4">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_historial">
                            <tr><td colspan="6" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Cargando historial...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalDetalleInspeccion" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fas fa-eye me-2"></i>Vista de Inspección Guardada</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                
                <div class="hoja-preview mx-auto" id="hoja_a_ver">
                    <div class="row align-items-center mb-2">
                        <div class="col-4 text-center">
                            <img src="../../img/logo.png" alt="Logo" style="max-width: 120px;">
                            <div class="fw-bold mt-1" style="font-size: 8px; color: var(--form-border-color);">MECÁNICA AUTOMOTRIZ</div>
                        </div>
                        <div class="col-4 text-center"><h6 class="fw-bold mb-0">HOJA DE INSPECCIÓN</h6></div>
                        <div class="col-4">
                            <table class="table-preview" style="font-size: 9px;">
                                <tr><th class="bg-light">FECHA</th><td id="det_fecha"></td></tr>
                                <tr><th class="bg-light">ASESOR</th><td id="det_asesor" class="fw-bold"></td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="preview-title text-start ps-2">INFORMACIÓN DEL CLIENTE Y VEHÍCULO</div>
                    <div class="row px-1">
                        <div class="col-6"><b>Cliente:</b> <span id="det_cliente"></span></div>
                        <div class="col-6 text-end"><b>Vehículo:</b> <span id="det_vehiculo"></span> &nbsp; <b>Placa:</b> <span id="det_placa" class="px-1 bg-dark text-white rounded"></span></div>
                    </div>
                    <div class="row px-1 mt-1">
                        <div class="col-6"><b>Kilometraje:</b> <span id="det_km" class="fw-bold"></span></div>
                        <div class="col-6 text-end"><b>Nivel Combustible:</b> <span id="det_comb"></span></div>
                    </div>

                    <div class="preview-title text-start ps-2 mt-2">ESTADO GENERAL DEL VEHÍCULO</div>
                    <div class="row gx-2">
                        <div class="col-4">
                            <div class="preview-sub-title">Interior</div>
                            <table class="table-preview text-center" id="table_int">
                                <thead><tr><th class="text-start">Elemento</th><th>B</th><th>F</th><th>D</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="col-4">
                            <div class="preview-sub-title">Exterior</div>
                            <table class="table-preview text-center" id="table_ext">
                                <thead><tr><th class="text-start">Elemento</th><th>B</th><th>F</th><th>D</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="col-4">
                            <div class="preview-sub-title">Motor</div>
                            <table class="table-preview text-center" id="table_mot">
                                <thead><tr><th class="text-start">Elemento</th><th>B</th><th>F</th><th>D</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="preview-title text-start ps-2 mt-2">TRABAJOS SOLICITADOS / MOTIVO DE VISITA</div>
                    <div class="row px-1">
                        <div class="col-12">
                            <div id="det_trabajos" class="d-flex flex-wrap gap-1 mb-2"></div>
                            <div class="p-2 border rounded bg-white small italic" id="det_observacion" style="min-height: 30px;"></div>
                        </div>
                    </div>

                    <div class="preview-title text-start ps-2 mt-2">DIAGRAMA DE ESTADO</div>
                    <div class="row gx-1">
                        <div class="col-3">
                            <div class="border p-1 bg-light mb-1" style="font-size: 8px;">
                                <b>Simbología:</b> X=Falta | O=Abolladura | —=Rayazo | ∆=Roto
                            </div>
                            <img src="../../img/iconos_motor.jpeg" class="img-ref">
                        </div>
                        <div class="col-9 car-box">
                             <div class="row g-0">
                                 <div class="col-6 border-end"><img src="../../img/vehicle-diagram-Converted.jpg" class="img-ref" style="max-height: 140px;"></div>
                                 <div class="col-6"><img src="../../img/interior.png" class="img-ref" style="max-height: 140px;"></div>
                             </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-primary btn-sm" onclick="imprimirHoja()"><i class="fas fa-print me-1"></i>Imprimir esta hoja</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_HistorialInspeccion.js"></script>