<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-file-invoice me-2 text-primary"></i>Módulo de Cotizaciones</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary shadow-sm" type="button" onclick="listarPendientes()">
                    <i class="fas fa-sync-alt me-2"></i>Actualizar
                </button>
                <button class="btn btn-success shadow-sm" type="button" onclick="abrirModalNuevaCotizacion()">
                    <i class="fas fa-plus me-2"></i>Nueva Cotización
                </button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list me-1"></i> Pendientes</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-2 bg-light border-bottom">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="buscar_cotizacion" class="form-control" placeholder="Buscar por Nombre, Vehículo o N° COT..." onkeyup="filtrarCotizaciones()">
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>N° Cot.</th>
                                        <th>Cliente / Vehículo</th>
                                        <th class="text-center">Tipo</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoTablaPendientes"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-edit me-1"></i> Constructor de Presupuesto</span>
                        <span id="lbl_orden_activa" class="badge bg-light text-primary fs-6">Seleccione una Cotización</span>
                    </div>
                    <div class="card-body p-4 position-relative" id="panel_cotizacion">
                        
                        <div id="capa_bloqueo" class="position-absolute top-0 start-0 w-100 h-100 bg-white opacity-75 d-flex justify-content-center align-items-center" style="z-index: 10;">
                            <h5 class="text-muted fw-bold"><i class="fas fa-hand-pointer me-2"></i>Seleccione o cree una cotización nueva</h5>
                        </div>

                        <input type="hidden" id="id_cotizacion_actual">
                        <input type="hidden" id="tipo_cotizacion_activa">
                        <input type="hidden" id="es_ocasional_activa">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-md-9 position-relative">
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="buscador_items" class="form-control" placeholder="Buscar Repuestos o Servicios..." oninput="buscarItems(this)">
                                </div>
                                <div id="res_items" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1050; max-height: 200px; overflow-y: auto;"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group shadow-sm">
                                    <input type="number" id="cantidad_item" class="form-control text-center fw-bold border-end-0" value="1" min="1">
                                    <span class="input-group-text bg-light text-muted" style="font-size: 11px;">CANT.</span>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded bg-light mb-3" style="min-height: 200px;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-secondary text-muted small">
                                    <tr>
                                        <th>Descripción</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoDetalleCotizacion">
                                    <tr><td colspan="6" class="text-center text-muted py-4">Buscando detalles...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-white p-3 rounded border shadow-sm mb-4">
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Sub-Total:</span>
                                <span id="cot_subtotal" class="fw-bold text-dark">RD$ 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-muted border-bottom pb-2">
                                <span>ITBIS (18%):</span>
                                <span id="cot_itbis" class="fw-bold text-dark">RD$ 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold text-uppercase text-dark">Total Presupuesto:</span>
                                <h3 class="fw-bold text-primary mb-0" id="cot_total">RD$ 0.00</h3>
                            </div>
                        </div>

                        <div class="row g-2 mb-2" id="panel_botones_accion"></div>
                        
                        <div class="mt-2">
                            <button class="btn btn-info text-dark w-100 fw-bold shadow-sm" onclick="imprimirCotizacion()"><i class="fas fa-print me-1"></i> Imprimir Presupuesto</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNuevaCotizacion" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-success border-2 shadow-lg">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-signature me-2"></i>Crear Nueva Cotización</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalNuevaCotizacion()"></button>
                </div>
                <div class="modal-body bg-light">
                    
                    <div class="mb-3 border-bottom pb-2 text-center">
                        <p class="fw-bold small text-muted mb-1">TIPO DE COTIZACIÓN</p>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rd_tipo_cot" id="tipo_taller" value="Taller" checked>
                            <label class="form-check-label text-dark fw-bold" for="tipo_taller"><i class="fas fa-tools text-primary me-1"></i>Servicios (Taller)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rd_tipo_cot" id="tipo_pos" value="POS">
                            <label class="form-check-label text-dark fw-bold" for="tipo_pos"><i class="fas fa-box text-success me-1"></i>Solo Repuestos (POS)</label>
                        </div>
                    </div>

                    <div class="mb-3 border-bottom pb-3 text-center">
                        <p class="fw-bold small text-muted mb-1">TIPO DE CLIENTE</p>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_cliente_express" id="tipo_reg" value="registrado" checked onchange="toggleTipoClienteExpress()">
                            <label class="form-check-label fw-bold text-dark" for="tipo_reg" style="cursor: pointer;">Registrado</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_cliente_express" id="tipo_oca" value="ocasional" onchange="toggleTipoClienteExpress()">
                            <label class="form-check-label fw-bold text-dark" for="tipo_oca" style="cursor: pointer;">Ocasional (Rápido)</label>
                        </div>
                    </div>

                    <div id="seccion_registrado" class="mb-3 position-relative">
                        <label class="fw-bold text-dark small mb-1">Buscar Cliente / Vehículo</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-success"></i></span>
                            <input type="text" id="buscador_vehiculo_express" class="form-control" placeholder="Escriba nombre o placa..." oninput="buscarVehiculosExpress(this)">
                        </div>
                        <ul class="list-group position-absolute w-100 d-none shadow" id="res_vehiculos_express" style="z-index: 1060; max-height: 200px; overflow-y: auto;"></ul>
                        
                        <div class="bg-white p-3 rounded border shadow-sm mt-2 d-none" id="info_vehiculo_express">
                            <input type="hidden" id="id_cliente_express">
                            <input type="hidden" id="id_vehiculo_express">
                            <input type="hidden" id="tel_cliente_express">
                            <small class="d-block text-dark mb-1"><i class="fas fa-user text-primary me-2"></i><span id="lbl_exp_cliente" class="fw-bold"></span></small>
                            <small class="d-block text-dark"><i class="fas fa-car text-primary me-2"></i><span id="lbl_exp_vehiculo" class="fw-bold"></span></small>
                        </div>
                    </div>

                    <div id="seccion_ocasional" class="d-none bg-white p-3 rounded border shadow-sm mb-3">
                        <div class="mb-2">
                            <label class="small fw-bold text-dark">Nombre del Cliente <span class="text-danger">*</span></label>
                            <input type="text" id="occ_nombre" class="form-control form-control-sm" placeholder="Ej: Cliente Mostrador">
                        </div>
                        <div class="mb-2">
                            <label class="small fw-bold text-dark">Teléfono</label>
                            <input type="text" id="occ_telefono" class="form-control form-control-sm" placeholder="(000) 000-0000">
                        </div>
                        <div class="mb-2">
                            <label class="small fw-bold text-dark">Vehículo (Opcional)</label>
                            <input type="text" id="occ_vehiculo" class="form-control form-control-sm" placeholder="Ej: No aplica">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button class="btn btn-secondary fw-bold" onclick="cerrarModalNuevaCotizacion()">Cancelar</button>
                    <button class="btn btn-success fw-bold shadow-sm" onclick="crearCotizacionExpress()">
                        <i class="fas fa-arrow-right me-1"></i> Iniciar Cotización
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Facturacion/Scripts_Cotizaciones.js"></script>
</body>
</html>