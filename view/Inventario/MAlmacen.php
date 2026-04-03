<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-boxes me-2 text-primary"></i>Gestión de Almacenes y Góndolas</h2>
            <button class="btn btn-primary" onclick="nuevoAlmacen()">
                <i class="fas fa-plus me-2"></i>Nuevo Almacén
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Almacén</th>
                                <th>Sucursal</th>
                                <th>Cant. Góndolas</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaAlmacen"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAlmacen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModalAlmacen">Formulario de Almacén</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formAlmacen">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_almacen" name="id_almacen">
                        
                        <div class="mb-3">
                            <label class="fw-bold">Nombre del Almacén <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-dark" name="nombre" id="nombre_almacen" required placeholder="Ej: Almacén Principal" maxlength="25">
                        </div>

                        <div class="mb-3 position-relative">
                            <label class="fw-bold">Buscar Sucursal <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-dark" id="txt_buscar_suc" placeholder="Escriba la sucursal..." autocomplete="off">
                            <input type="hidden" name="id_sucursal" id="id_sucursal" required>
                            <ul class="list-group position-absolute w-100 d-none shadow" id="lista_suc" style="z-index:1000; max-height: 150px; overflow-y: auto;"></ul>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Estado <span class="text-danger">*</span></label>
                            <select class="form-select border-dark" name="estado" id="estado_almacen" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalUI('modalAlmacen')">Cerrar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Guardar Almacén</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalGondolas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-layer-group me-2"></i>Góndolas de: <span id="tituloAlmacenGondola" class="text-warning"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    
                    <form id="formGondola" class="row g-3 align-items-end p-3 border border-2 border-secondary rounded mb-4 bg-white">
                        <input type="hidden" id="gondola_id_almacen" name="id_almacen">
                        
                        <div class="col-md-3">
                            <label class="fw-bold text-dark" style="font-size:13px;">Número Góndola <span class="text-danger">*</span></label>
                            <input type="number" class="form-control border-dark" id="numero_gondola" name="numero" placeholder="Ej: 1" required min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold text-dark" style="font-size:13px;">Cant. Niveles <span class="text-danger">*</span></label>
                            <input type="number" class="form-control border-dark" id="niveles_gondola" name="niveles" placeholder="Ej: 4" required min="1" value="1">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold text-dark" style="font-size:13px;">Estado</label>
                            <select class="form-select border-dark" name="estado" id="estado_gondola" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i>Agregar</button>
                        </div>
                    </form>

                    <h6 class="fw-bold border-bottom pb-2">Listado de Góndolas Existentes</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover bg-white text-center align-middle">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>ID</th>
                                    <th>Góndola Número</th>
                                    <th>Niveles</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaGondolas"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalUI('modalGondolas')">Cerrar Administrador</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_Almacen.js"></script>
</body>
</html>