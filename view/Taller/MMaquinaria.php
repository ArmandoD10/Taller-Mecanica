<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-cogs me-2 text-primary"></i>Gestión de Recursos y Maquinaria</h2>
            <button class="btn btn-primary" onclick="nuevoRecurso()">
                <i class="fas fa-plus me-2"></i>Nuevo Recurso
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Sucursal</th>
                                <th>Fecha Ingreso</th>
                                <th>Condición</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMaquinaria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModal">Formulario de Recurso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formMaquinaria">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_maquinaria" name="id_maquinaria">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Nombre del Recurso <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-dark" name="nombre" id="nombre" required placeholder="Escriba el nombre del recurso...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="id_categoria" id="id_categoria" required>
                                    <option value="">Cargando categorías...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3 position-relative">
                                <label class="fw-bold">Buscar Sucursal <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-dark" id="txt_buscar_suc" placeholder="Escriba la sucursal..." autocomplete="off">
                                <input type="hidden" name="id_sucursal" id="id_sucursal" required>
                                <ul class="list-group position-absolute w-100 d-none shadow" id="lista_suc" style="z-index:1000; max-height: 150px; overflow-y: auto;"></ul>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Condición Física <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="estado_maquina" id="estado_maquina" required>
                                    <option value="Nuevo">Nuevo</option>
                                    <option value="Usado">Usado</option>
                                    <option value="Desgastado">Desgastado</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Fecha de Ingreso <span class="text-danger">*</span></label>
                                <input type="date" class="form-control border-dark" name="fecha_ingreso" id="fecha_ingreso" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Estado del Registro <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="estado" id="estado" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Descripción de Funcionamiento / Notas</label>
                            <textarea class="form-control border-dark" name="funcionamiento" id="funcionamiento" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalUI()">Cerrar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Guardar Recurso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_Maquinaria.js"></script>
</body>
</html>