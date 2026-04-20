<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <div>
                <h2 class="fw-bold mb-0"><i class="fas fa-tools me-2 text-primary"></i>Catálogo de Especialidades</h2>
                <p class="text-muted small">Define las habilidades técnicas para el personal del taller.</p>
            </div>
            <button type="button" class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEspecialidad" onclick="prepararModalNuevo()">
                <i class="fas fa-plus me-1"></i> Nueva Especialidad
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark text-uppercase small">
                            <tr>
                                <th class="ps-4">Nombre Especialidad</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_especialidades">
                            <tr><td colspan="4" class="text-center py-4 text-muted">Cargando catálogo...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalEspecialidad" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" id="formEspecialidad">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-certificate me-2"></i>Gestión de Especialidad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <input type="hidden" id="id_especialidad" name="id_especialidad" value="0">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">Nombre de la Especialidad</label>
                    <input type="text" class="form-control fw-bold border-primary" name="nombre" id="nombre" placeholder="Ej: Mecánica General" required>
                </div>
                
                <div class="mb-0">
                    <label class="small fw-bold text-muted mb-1">Descripción / Alcance Técnico</label>
                    <textarea class="form-control" name="descripcion" id="descripcion" rows="3" placeholder="Define brevemente qué abarca esta especialidad..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-3">
                <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm"><i class="fas fa-save me-2"></i>Guardar Especialidad</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/RRHH/Scripts_Especialidad.js"></script>