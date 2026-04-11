<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-shield-alt me-2 text-primary"></i>Historial de Garantías</h2>
            <button class="btn btn-secondary shadow-sm fw-bold" onclick="cargarGarantias()">
                <i class="fas fa-sync-alt me-2"></i>Actualizar
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1"><i class="fas fa-check-circle me-2"></i>Garantías Activas</h6>
                        <h3 class="mb-0 fw-bold" id="lbl_activas">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1"><i class="fas fa-clock me-2"></i>Garantías Vencidas</h6>
                        <h3 class="mb-0 fw-bold" id="lbl_vencidas">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1"><i class="fas fa-ban me-2"></i>Garantías Anuladas</h6>
                        <h3 class="mb-0 fw-bold" id="lbl_anuladas">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center py-3">
                <span><i class="fas fa-list me-2"></i>Certificados Emitidos</span>
                <div class="input-group input-group-sm w-25">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="buscador_garantia" class="form-control border-start-0" placeholder="Buscar placa, cliente o código..." onkeyup="filtrarTabla()">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 text-center text-nowrap">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Código</th>
                                <th class="text-start">Cliente</th>
                                <th class="text-start">Vehículo</th>
                                <th>Emisión</th>
                                <th>Vence (Fecha / Km)</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaGarantias">
                            <tr><td colspan="7" class="text-center py-4 text-muted">Cargando historial...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalAnular" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger border-2">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Anular Garantía</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" id="anular_id_garantia">
                <p class="text-center mb-3 text-dark">¿Está seguro que desea anular el certificado <br><strong class="fs-5 text-danger" id="lbl_codigo_anular"></strong>?</p>
                <div class="mb-3">
                    <label class="fw-bold small mb-1">Motivo de la Anulación <span class="text-danger">*</span></label>
                    <textarea id="anular_motivo" class="form-control shadow-sm" rows="3" placeholder="Ej. El cliente intervino el motor en otro taller..." required></textarea>
                </div>
                <div class="alert alert-warning small p-2 mb-0 border-warning">
                    <i class="fas fa-info-circle me-1"></i> Esta acción es irreversible y el certificado perderá toda validez legal.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger fw-bold px-4" onclick="confirmarAnulacion()">Proceder con Anulación</button>
            </div>
        </div>
    </div>
</div>

<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_HistorialGarantias.js"></script>
</body>
</html>