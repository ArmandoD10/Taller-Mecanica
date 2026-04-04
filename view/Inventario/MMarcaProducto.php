<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
    /* Forzamos al modal a estar por encima de la barra lateral */
    .modal { 
        z-index: 105000 !important; 
    }
    .modal-backdrop { 
        z-index: 104900 !important; 
    }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-tags me-2 text-primary"></i>Catálogo de Marcas</h2>
            <button class="btn btn-primary" onclick="nuevaMarca()">
                <i class="fas fa-plus me-2"></i>Nueva Marca
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre de la Marca</th>
                                <th>País de Origen</th>
                                <th>Correo de Contacto</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMarca" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModal">Formulario de Marca</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formMarca">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_marca_producto" name="id_marca_producto">

                        <div class="mb-3">
                            <label class="fw-bold">Nombre de la Marca <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-dark" name="nombre" id="nombre_marca" required placeholder="Ej: Toyota, Bosch, Castrol..." maxlength="25">
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">País de Origen <span class="text-danger">*</span></label>
                            <select class="form-select border-dark" name="id_pais" id="id_pais" required>
                                <option value="">Seleccione país...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Correo de Contacto <span class="text-muted fw-normal">(Opcional)</span></label>
                            <input type="email" class="form-control border-dark" name="correo" id="correo" placeholder="contacto@marca.com" maxlength="75">
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Estado <span class="text-danger">*</span></label>
                            <select class="form-select border-dark" name="estado" id="estado" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalUI('modalMarca')">Cerrar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Guardar Marca</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_MarcaProducto.js"></script>
</body>
</html>