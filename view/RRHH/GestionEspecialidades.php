<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="fw-bold mt-4 mb-0"><i class="fas fa-user-tag me-2 text-primary"></i>Asignación de Especialidades</h2>
        <p class="text-muted small mb-4">Busca un mecánico y gestiónale sus habilidades técnicas.</p>

        <div class="row">
            <div class="col-md-5">
                <div class="card shadow-sm border-0 p-4 mb-4">
                    <label class="form-label fw-bold text-dark">Buscar Mecánico (Nombre, Cédula o Código)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="buscar_mecanico" placeholder="Escriba para buscar...">
                    </div>
                    <ul id="lista_mecanicos_res" class="list-group position-absolute shadow-sm d-none" style="z-index: 1000; width: 90%; top: 85px; max-height: 200px; overflow-y: auto;"></ul>
                    
                    <div id="card_mecanico" class="mt-4 p-3 border rounded bg-light d-none">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="fas fa-user-cog fa-lg"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <input type="hidden" id="id_empleado_hidden">
                                <h6 class="mb-0 fw-bold" id="txt_nombre_mecanico">Nombre Completo</h6>
                                <small class="text-muted" id="txt_cedula_mecanico">000-0000000-0</small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger ms-auto" onclick="deseleccionarMecanico()"><i class="fas fa-times"></i></button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-1">Agregar Especialidad</label>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" id="select_especialidad">
                                    <option value="">Cargando especialidades...</option>
                                </select>
                                <button class="btn btn-success btn-sm px-3" onclick="guardarAsignacion()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>Especialidades Asignadas</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-4">Especialidad</th>
                                        <th>Fecha Asignación</th>
                                        <th class="text-end pe-4">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_asignaciones">
                                    <tr><td colspan="3" class="text-center py-5 text-muted">Seleccione un mecánico para ver sus especialidades</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/RRHH/Scripts_GestionEspecialidad.js"></script>