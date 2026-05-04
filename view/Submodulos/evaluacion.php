<?php
require("../../layout.php");
require("../../header.php");

// Validamos el nivel de usuario para la ruedita de configuración
$id_nivel_usuario = $_SESSION['id_nivel'] ?? 0;
?>

<main class="contenido">
    <div class="container-fluid px-4 mt-4">
        <!-- Encabezado con Ruedita de Configuración -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="fw-bold"><i class="fas fa-star text-warning me-2"></i>Evaluación de Satisfacción</h2>
            
            <!-- Cambiamos la validación al ID numérico (Ejemplo: 1 para Admin)[cite: 2, 5] -->
            <?php if($id_nivel_usuario == 1): ?>
                <button class="btn btn-outline-secondary border-0" onclick="abrirModalConfiguracion()" title="Configurar Preguntas">
                    <i class="fas fa-cog fa-lg"></i>
                </button>
            <?php endif; ?>
        </div>
        <hr>

        <!-- Formulario de Búsqueda de Orden -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form id="formBuscarOrden">
                    <label class="fw-bold mb-2 small text-muted text-uppercase">Búsqueda de Expediente</label>
                    <div class="d-flex gap-2" style="max-width: 600px;">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white fw-bold">ORD-</span>
                            <input type="number" name="id_orden_buscar" id="input_orden" class="form-control" placeholder="Ej: 17" required>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-search me-2"></i>BUSCAR
                            </button>
                        </div>
                        <!-- Botón de Refrescar -->
                        <button type="button" class="btn btn-outline-danger" onclick="location.reload()" title="Limpiar pantalla">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel de Encuesta (Se activa al encontrar la orden)[cite: 1] -->
        <div id="panel_encuesta" class="d-none animate__animated animate__fadeIn">
            <div class="alert alert-white border shadow-sm d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-0 fw-bold text-primary" id="txt_cliente_nombre">---</h5>
                    <small class="text-muted"><i class="fas fa-car me-1"></i> <span id="txt_vehiculo_detalle">---</span></small>
                </div>
                <div class="text-end">
                    <span class="badge bg-success p-2 fs-6" id="txt_orden_id">ORD-00</span>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-clipboard-check me-2 text-primary"></i>Formulario de Evaluación</h6>
                </div>
                <div class="card-body">
                    <form id="formSatisfaccion">
                        <!-- Campos ocultos para el guardado[cite: 2] -->
                        <input type="hidden" id="hidden_id_orden" name="id_orden">
                        <input type="hidden" id="hidden_id_cliente" name="id_cliente">
                        
                        <!-- Contenedor dinámico de preguntas[cite: 3] -->
                        <div id="contenedor_preguntas" class="mb-4">
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2">Cargando preguntas...</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold small mb-2 text-uppercase text-muted">Observaciones adicionales</label>
                            <textarea name="comentario_general" class="form-control bg-light" rows="3" placeholder="¿Desea agregar algo más sobre su experiencia en el taller?"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold shadow-sm">
                            <i class="fas fa-save me-2"></i>REGISTRAR EVALUACIÓN FINAL
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Mantenimiento de Preguntas (Solo Admin)[cite: 1] -->
    <div class="modal fade" id="modalConfiguracion" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-cogs me-2"></i>Gestión de Preguntas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formPregunta" class="row g-3 mb-4 p-3 border rounded bg-light shadow-sm">
                        <input type="hidden" id="id_pregunta" name="id_pregunta">
                        <div class="col-md-7">
                            <label class="fw-bold small">Texto de la Pregunta</label>
                            <input type="text" name="pregunta" id="txt_pregunta" class="form-control" placeholder="Ej: ¿El trato fue cordial?" required>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold small">Tipo de Respuesta</label>
                            <select name="tipo_respuesta" id="sel_tipo_res" class="form-select">
                                <option value="Escala">Escala (1-5 ★)</option>
                                <option value="Si/No">Sí / No</option>
                                <option value="Texto">Texto Libre</option>
                            </select>
                        </div>
                        <!-- Dentro de la fila del formulario en el modalConfiguracion[cite: 1] -->
<div class="col-md-2 d-flex align-items-end gap-1">
    <button type="submit" class="btn btn-success btn-sm w-100 fw-bold" id="btnGuardarPregunta">
        Guardar
    </button>
    <!-- Botón para limpiar el formulario[cite: 1] -->
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFormPregunta()" title="Nueva Pregunta">
        <i class="fas fa-eraser"></i>
    </button>
</div>
                    </form>

                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th width="50%">Pregunta</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_preguntas_config">
                                <!-- Se llena dinámicamente vía AJAX[cite: 3] -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Submodulos/Scripts_Evaluacion.js"></script>


