<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <div>
                <h2 class="fw-bold mb-0"><i class="fas fa-file-contract me-2 text-danger"></i>Reclamos de Garantía</h2>
                <p class="text-muted small">Gestión y evaluación de retornos por fallas de piezas o servicios.</p>
            </div>
            <button class="btn btn-danger fw-bold shadow-sm" onclick="abrirModalNuevo()">
                <i class="fas fa-plus me-1"></i> Aperturar Reclamo
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark text-center small">
                            <tr>
                                <th>EXPediente</th>
                                <th>Fecha</th>
                                <th>Orden Orig.</th>
                                <th>Cliente / Vehículo</th>
                                <th>Ítem Afectado</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_reclamos" class="text-center">
                            <tr><td colspan="7" class="py-4 text-muted">Cargando expedientes...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalNuevoReclamo" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content border-danger border-2 shadow-lg" id="formNuevoReclamo">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-search me-2"></i>Búsqueda y Apertura de Reclamo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted">N° Orden Original (Ej: 45)</label>
                        <input type="number" id="buscar_id_orden" class="form-control fw-bold border-danger" required>
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted">Kilometraje Actual del Vehículo</label>
                        <input type="number" id="buscar_km" class="form-control fw-bold border-danger" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger w-100 fw-bold" onclick="buscarOrdenGarantia()">Buscar</button>
                    </div>
                </div>

                <div id="area_resultados" class="d-none">
                    <div class="alert alert-secondary p-2 mb-3 shadow-sm border-secondary text-center">
                        <h6 class="fw-bold mb-0 text-dark" id="rg_lbl_cliente"></h6>
                        <small class="text-muted" id="rg_lbl_vehiculo"></small>
                    </div>

                    <input type="hidden" id="rg_id_orden" name="rg_id_orden">
                    <input type="hidden" id="rg_id_cliente" name="rg_id_cliente">
                    <input type="hidden" id="rg_id_vehiculo" name="rg_id_vehiculo">
                    <input type="hidden" id="rg_id_sucursal" name="rg_id_sucursal">
                    <input type="hidden" id="rg_km_actual" name="km_actual">

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="fas fa-list-check me-2 text-danger"></i>Ítems de la orden con cobertura</h6>
                    <p class="small text-muted mb-2">Seleccione el servicio o repuesto que presenta la falla reportada. (Las piezas en rojo ya vencieron su garantía).</p>
                    
                    <div class="list-group mb-3 shadow-sm" id="lista_items_garantia" style="max-height: 200px; overflow-y: auto;"></div>

                    <div class="mb-0">
                        <label class="small fw-bold text-muted">Descripción detallada de la falla reportada por el cliente</label>
                        <textarea class="form-control border-danger" name="falla_reportada" rows="3" required placeholder="Ej: El cliente indica que al frenar el vehículo vibra fuertemente..."></textarea>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger fw-bold px-4" id="btn_guardar_reclamo" disabled>Generar Expediente</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEvaluar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-dark border-2 shadow-lg" id="formEvaluar">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold">Evaluación Técnica de Reclamo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ev_id_reclamo" name="ev_id_reclamo">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Veredicto Final</label>
                    <select class="form-select border-dark fw-bold" name="ev_decision" required>
                        <option value="">Seleccione...</option>
                        <option value="Aprobado">✅ APROBADO (Cubre Garantía)</option>
                        <option value="Rechazado">❌ RECHAZADO (No procede)</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="small fw-bold text-muted">Resolución / Motivo Técnico</label>
                    <textarea class="form-control" name="ev_resolucion" rows="3" required placeholder="Explique por qué se aprueba o se rechaza..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="submit" class="btn btn-dark w-100 fw-bold">Guardar Resolución</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_ReclamoGarantia.js"></script>