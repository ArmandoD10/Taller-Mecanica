<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <div>
                <h2 class="fw-bold mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i>Catálogo de Garantías</h2>
                <p class="text-muted small">Administra las políticas de cobertura para tus servicios y repuestos.</p>
            </div>
            <button type="button" class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPolitica" onclick="prepararModalNuevo()">
                <i class="fas fa-plus me-1"></i> Nueva Política
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaPoliticas">
                        <thead class="table-dark text-uppercase small">
                            <tr>
                                <th class="ps-4">Nombre de Política</th>
                                <th>Cobertura de Tiempo</th>
                                <th>Límite de Kilometraje</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_politicas">
                            <tr><td colspan="5" class="text-center py-4 text-muted">Cargando catálogo...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalPolitica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" id="formPolitica">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-cog me-2"></i>Configuración de Garantía</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <input type="hidden" id="id_politica" name="id_politica" value="0">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">Nombre de la Política</label>
                    <input type="text" class="form-control fw-bold border-primary" name="nombre" id="nombre" placeholder="Ej: Garantía Estándar Transmisión" required>
                </div>
                
                <div class="row mb-3 g-2">
                    <div class="col-6">
                        <label class="small fw-bold text-muted mb-1">Cantidad de Tiempo</label>
                        <input type="number" class="form-control" name="tiempo_cobertura" id="tiempo_cobertura" min="1" placeholder="Ej: 90" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted mb-1">Unidad de Medida</label>
                        <select class="form-select" name="unidad_tiempo" id="unidad_tiempo">
                            <option value="Dias">Días</option>
                            <option value="Meses">Meses</option>
                            <option value="Anios">Años</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">Límite de Kilometraje <span class="fw-normal italic">(Opcional)</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="kilometraje_cobertura" id="kilometraje_cobertura" placeholder="Ej: 5000">
                        <span class="input-group-text bg-white">KM</span>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Si se deja vacío, la garantía solo vencerá por tiempo.</small>
                </div>
                
                <div class="mb-0">
                    <label class="small fw-bold text-muted mb-1">Descripción / Condiciones</label>
                    <textarea class="form-control" name="descripcion" id="descripcion" rows="2" placeholder="Términos y condiciones específicos que anulan la garantía..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-3">
                <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm"><i class="fas fa-save me-2"></i>Guardar Política</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_PoliticaGarantia.js"></script>