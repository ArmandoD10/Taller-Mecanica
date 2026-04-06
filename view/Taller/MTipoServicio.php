<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-wrench me-2 text-primary"></i>Catálogo de Servicios</h2>
            <button class="btn btn-primary" onclick="nuevoServicio()">
                <i class="fas fa-plus me-2"></i>Nuevo Servicio
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Servicio</th>
                                <th>Descripción</th>
                                <th>Tiempo Est.</th>
                                <th>Precio Estimado</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaServicios">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalServicio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModal"><i class="fas fa-plus me-2"></i>Nuevo Servicio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formServicio">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_tipo_servicio" name="id_tipo_servicio">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre del Servicio <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-dark" id="nombre" name="nombre" placeholder="Ej: Cambio de Aceite y Filtro" required maxlength="50">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Descripción / Detalles Adicionales</label>
                            <textarea class="form-control border-dark" id="descripcion" name="descripcion" rows="2" maxlength="75" placeholder="Breve descripción..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tiempo Estimado <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-dark text-center fw-bold" id="tiempo_estimado" name="tiempo_estimado" placeholder="HH:MM" maxlength="5" required autocomplete="off">
                                <small class="text-muted">Horas : Minutos (Ej: 01:30)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Precio Sugerido ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control border-dark fw-bold text-success" id="precio_estimado" name="precio_estimado" placeholder="0.00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" id="estado" name="estado" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalUI()">Cancelar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_TipoServicio.js"></script>
</body>
</html>