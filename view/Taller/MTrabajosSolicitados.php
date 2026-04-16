<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="mt-4 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i>Catálogo de Trabajos</h2>
                <p class="text-muted small">Gestione las opciones de trabajos solicitados para las inspecciones.</p>
            </div>
            <div>
                <button class="btn btn-primary fw-bold shadow-sm" onclick="abrirModalNuevo()">
                    <i class="fas fa-plus me-1"></i> NUEVO TRABAJO
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>Listado de Trabajos / Fallos</h5>
                <input type="text" id="filtroTabla" class="form-control form-control-sm" placeholder="Buscar descripción..." style="width: 250px;">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Descripción del Trabajo</th>
                                <th>Fecha Registro</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaTrabajos">
                            <tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Cargando datos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalTrabajo" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="tituloModal"><i class="fas fa-edit me-2 text-warning"></i>Gestionar Trabajo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTrabajo">
                <div class="modal-body p-4">
                    <input type="hidden" id="id_trabajo" name="id_trabajo">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Descripción del Trabajo / Fallo *</label>
                        <input type="text" class="form-control fw-bold border-primary" id="descripcion" name="descripcion" required placeholder="Ej: Cambio de Aceite y Filtro" maxlength="150">
                    </div>
                    
                    <div class="mb-3" id="div_estado">
                        <label class="form-label fw-bold small text-muted">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="activo">Activo (Visible en Inspección)</option>
                            <option value="inactivo">Inactivo (Oculto)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold shadow-sm" id="btnGuardar"><i class="fas fa-save me-1"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body p-4 text-center">
                <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                <h5 class="fw-bold mb-2">¿Eliminar Registro?</h5>
                <p class="text-muted small mb-4">Esta acción ocultará este trabajo del catálogo permanentemente.</p>
                <input type="hidden" id="id_eliminar">
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger fw-bold shadow-sm" onclick="confirmarEliminar()">Sí, Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_TrabajoSolicitado.js"></script>