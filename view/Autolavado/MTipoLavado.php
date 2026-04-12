<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <div>
                <h2 class="mb-0 fw-bold text-dark"><i class="fas fa-list-ul me-2 text-primary"></i>Tipos de Lavado</h2>
                <p class="text-muted mb-0">Gestión del catálogo de servicios de limpieza</p>
            </div>
            <button class="btn btn-primary shadow-sm fw-bold" type="button" onclick="abrirModalTipo()">
                <i class="fas fa-plus me-2"></i>Nuevo Tipo
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center small text-uppercase">
                            <tr>
                                <th>ID</th>
                                <th class="text-start">Nombre del Servicio</th>
                                <th>Fecha Registro</th>
                                <th>Estado</th>
                                <th style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaTipos" class="text-center">
                            <tr><td colspan="5" class="text-muted py-4">Cargando catálogo...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTipoLavado" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-primary border-2 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="tituloModalTipo"><i class="fas fa-edit me-2"></i>Registrar Tipo</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalUI('modalTipoLavado')"></button>
                </div>
                <form id="formTipoLavado">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_tipo" name="id_tipo">
                        
                        <div class="mb-3">
                            <label class="fw-bold small text-dark mb-1">Nombre del Lavado <span class="text-danger">*</span></label>
                            <input type="text" class="form-control shadow-sm border-primary" id="nombre_tipo" name="nombre_tipo" placeholder="Ej: Lavado Sencillo, VIP, Motor..." required>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-secondary fw-bold" onclick="cerrarModalUI('modalTipoLavado')">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Autolavado/Scripts_TipoLavado.js"></script>
</body>
</html>