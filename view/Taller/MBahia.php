<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-warehouse me-2 text-primary"></i>Mantenimiento de Bahías</h2>
            <button class="btn btn-primary" onclick="abrirModalNuevo()">
                <i class="fas fa-plus me-2"></i>Nueva Bahía
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="tablaBahias">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Sucursal</th>
                                <th>Descripción</th>
                                <th>Ocupación</th>
                                <th>Estado Registro</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaBahias">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBahia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitulo"><i class="fas fa-edit me-2"></i>Formulario de Bahía</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formBahia">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_bahia" name="id_bahia">
                        
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold">Buscar Sucursal <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-dark" id="txt_buscar_sucursal" placeholder="Escriba la sucursal..." autocomplete="off" required>
                            <input type="hidden" id="id_sucursal" name="id_sucursal" required>
                            <ul class="list-group position-absolute w-100 d-none shadow" id="lista_sucursales_busqueda" style="z-index: 1060; max-height: 150px; overflow-y: auto;"></ul>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Descripción / Nombre de la Bahía <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-dark" id="descripcion" name="descripcion" placeholder="Ej: Bahía de Alineación 1" required maxlength="100">
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Estado de Ocupación</label>
                                <select class="form-select border-dark" id="estado_bahia" name="estado_bahia" required>
                                    <option value="Disponible">Disponible</option>
                                    <option value="Ocupada">Ocupada</option>
                                    <option value="Mantenimiento">En Mantenimiento</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Estado Registro</label>
                                <select class="form-select border-dark" id="estado" name="estado" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_Bahia.js"></script>
</body>
</html>