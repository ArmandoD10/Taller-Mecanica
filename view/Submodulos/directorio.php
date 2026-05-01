<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">

<!-- Botón de acceso en la vista principal -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><i class="fas fa-folder-open me-2 text-primary"></i>Directorio de Documentos</h2>
    <button class="btn btn-outline-secondary" onclick="abrirModalTipos()">
        <i class="fas fa-cog me-2"></i>Configurar Tipos
    </button>
</div>

<!-- Modal para Gestionar Tipos de Documentos -->
<div class="modal fade" id="modalGestionTipos" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Gestionar Tipos de Documentos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formTipoDoc" class="mb-4">
                    <input type="hidden" id="id_tipo_doc" name="id_tipo_doc">
                    <div class="row g-2">
                        <div class="col-md-7">
                            <input type="text" id="nombre_tipo" name="nombre_tipo" class="form-control form-control-sm" placeholder="Ej: Certificación Médica" required>
                        </div>
                        <div class="col-md-5">
                            <select id="cat_tipo" name="cat_tipo" class="form-select form-select-sm" required>
                                <option value="Generico">Genérico</option>
                                <option value="Personal">Personal</option>
                            </select>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-success btn-sm w-100">Guardar Tipo</button>
                        </div>
                    </div>
                </form>
                <hr>
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_tipos">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- Vista de Directorio -->
<div class="row" id="contenedor_directorio">
    <!-- Aquí se cargan las cards estilo A4 -->
    <div class="col-md-3 mb-4">
        <div class="card card-a4-preview shadow-sm" onclick="abrirModalDocumento()">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <i class="fas fa-plus-circle fa-3x text-primary mb-2"></i>
                <h6 class="fw-bold">Nuevo Documento</h6>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Creación (El Editor) -->
<div class="modal fade" id="modalEditor" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom-0">
        <h5 class="modal-title fw-bold text-primary"><i class="fas fa-file-alt me-2"></i>Editor de Documentos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
            <div class="modal-body bg-light">
                <div class="row">
                    <!-- Controles -->
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3">
                            <label class="fw-bold small">Tipo de Documento</label>
                            <select id="sel_tipo" class="form-select mb-3" onchange="verificarTipo()"></select>
                            
                            <div id="panel_empleado" class="d-none">
                                <label class="fw-bold small">Buscar Empleado</label>
                                <input type="text" id="busc_emp" class="form-control mb-2" placeholder="Nombre...">
                                <div id="res_emp" class="list-group small"></div>
                            </div>

                            <label class="fw-bold small">Contenido del Documento</label>
                            <textarea id="editor_texto" class="form-control" rows="12" oninput="dibujarDocumento()"></textarea>
                            
                            <button class="btn btn-primary w-100 mt-3" onclick="guardarDocumento()">
                                <i class="fas fa-save me-2"></i>GUARDAR DOCUMENTO
                            </button>
                        </div>
                    </div>
                    <!-- El Canvas A4 -->
                    <div class="col-md-8 d-flex justify-content-center">
                        <div id="hoja_a4" class="hoja-blanca shadow">
                            <div class="header-doc d-flex align-items-center mb-4">
                                <img src="/Taller/Taller-Mecanica/img/logo.png" style="width: 80px;">
                                <div class="ms-3">
                                    <h5 class="mb-0 fw-bold">MECÁNICA DÍAZ & PANTALEÓN</h5>
                                    <small>Servicio Automotriz Profesional</small>
                                </div>
                            </div>

                            <!-- TÍTULO DINÁMICO DEL DOCUMENTO -->
                                <div class="text-center mb-5">
                                    <h4 id="titulo_dinamico_hoja" class="fw-bold text-uppercase" style="font-size: 16pt; text-decoration: underline;">
                                        <!-- Aquí se pintará el tipo seleccionado -->
                                    </h4>
                                </div>
                                                        <div id="preview_contenido" class="cuerpo-texto">
                                <!-- Aquí se pinta el texto en tiempo real -->
                            </div>
                            <div class="footer-doc mt-5 pt-5 text-center">
                                <div style="border-top: 1px solid #000; width: 250px; margin: 0 auto;"></div>
                                <small class="fw-bold">Taller Mecánica Díaz & Pantaleón</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar sin guardar</button>
    </div>
        </div>
    </div>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.min.js"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Submodulos/Scripts_Directorio.js"></script>
