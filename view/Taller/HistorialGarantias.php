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
            <div class="col-md-6">
                <div class="card bg-success text-white shadow-sm border-0">
                    <div class="card-body text-center">
                        <h6 class="fw-bold mb-1">Certificados Activos</h6>
                        <h3 class="mb-0 fw-bold" id="lbl_activas">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-danger text-white shadow-sm border-0">
                    <div class="card-body text-center">
                        <h6 class="fw-bold mb-1">Certificados Anulados</h6>
                        <h3 class="mb-0 fw-bold" id="lbl_anuladas">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted mb-1">Búsqueda General</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                            <input type="text" id="filtroGeneral" class="form-control" placeholder="N° Certificado, Placa o Cliente..." oninput="filtrarTabla()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Desde (Fecha Emisión):</label>
                        <input type="date" id="fechaDesde" class="form-control form-control-sm" onchange="filtrarTabla()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Hasta (Fecha Emisión):</label>
                        <input type="date" id="fechaHasta" class="form-control form-control-sm" onchange="filtrarTabla()">
                    </div>
                    <div class="col-md-1">
                        <label class="small d-block mb-1">&nbsp;</label>
                        <button class="btn btn-sm btn-outline-secondary w-100" onclick="limpiarFiltros()" title="Limpiar Filtros"><i class="fas fa-eraser"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center py-3">
                <span><i class="fas fa-list me-2"></i>Catálogo de Certificados Emitidos</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Código Certificado</th>
                                <th class="text-start">Cliente</th>
                                <th class="text-start">Vehículo</th>
                                <th>Emisión</th>
                                <th>Ítems Cubiertos</th>
                                <th>Estado Documento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaGarantias">
                            <tr><td colspan="7" class="py-4 text-muted">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalDetalleGarantia" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-list-check me-2 text-info"></i>Detalle de Coberturas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="text-center mb-3">
                    <h5 class="fw-bold text-dark mb-0" id="det_codigo_garantia"></h5>
                    <p class="text-muted small">Mostrando todas las líneas amparadas bajo este certificado.</p>
                </div>
                <div class="table-responsive bg-white border rounded">
                    <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 13px;">
                        <thead class="table-secondary text-muted">
                            <tr>
                                <th class="text-start ps-3">Descripción</th>
                                <th>Política</th>
                                <th>Vence (Fecha)</th>
                                <th>Vence (KM)</th>
                                <th>Estado Actual</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoDetallesGarantia">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAnular" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger border-2 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-shield-lock me-2"></i>Autorización de Cancelación</h5>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarModalAnular()"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" id="anular_id_garantia">
                <input type="hidden" id="anular_id_orden">
                <div class="text-center mb-3">
                    <p class="text-muted small mb-1">CÓDIGO DE CERTIFICADO</p>
                    <h4 class="text-danger fw-bold" id="lbl_codigo_anular"></h4>
                </div>

                <div class="bg-white p-3 border rounded mb-3">
                    <h6 class="fw-bold mb-3 border-bottom pb-2 text-secondary"><i class="fas fa-user-shield me-2"></i>Validación Admin</h6>
                    <div class="mb-2">
                        <label class="fw-bold small mb-1">Usuario</label>
                        <input type="text" id="admin_user" class="form-control border-danger" required autocomplete="off">
                    </div>
                    <div class="mb-0">
                        <label class="fw-bold small mb-1">Contraseña</label>
                        <input type="password" id="admin_pass" class="form-control border-danger" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold small mb-1">Motivo de Anulación</label>
                    <textarea id="anular_motivo" class="form-control" rows="2" placeholder="Especifique el motivo..." required></textarea>
                </div>

                <div class="alert alert-warning small p-2 mb-0 border-warning">
                    <i class="fas fa-info-circle me-1"></i> Esta acción anulará el certificado y <b>vencerá de inmediato</b> todas las líneas de servicios y repuestos amparados.
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary fw-bold" onclick="cerrarModalAnular()">Cancelar</button>
                <button type="button" class="btn btn-danger fw-bold px-4" onclick="confirmarAnulacion()">Validar y Anular</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_HistorialGarantias.js"></script>
</body>
</html>