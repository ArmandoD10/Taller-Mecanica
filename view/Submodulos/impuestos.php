<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="mt-4 mb-4">
            <h2 class="mb-0"><i class="fas fa-percentage me-2 text-primary"></i>Configuración de Impuestos</h2>
            <p class="text-muted">Administre los impuestos que se aplicarán a las facturas del sistema.</p>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold" id="tituloForm">
            <i class="fas fa-percentage me-2 text-primary"></i>Configurar Impuesto
        </h5>
    </div>
    <div class="card-body">
        <form id="formImpuesto">
            <input type="hidden" id="id_config_impuesto" name="id_config_impuesto">

            <div class="mb-3">
                <label class="form-label small fw-bold">Nombre del Impuesto</label>
                <input type="text" id="nombre_impuesto" name="nombre_impuesto" 
                       class="form-control" placeholder="Ej: ITBIS (18%)" required
                       oninput="validarNombreImpuesto(this)">
                <div id="error_nombre" class="text-danger small d-none">Solo letras, números y paréntesis ()</div>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-bold">Porcentaje (%)</label>
                <div class="input-group">
                    <input type="number" id="porcentaje_impuesto" name="porcentaje_impuesto" 
                           class="form-control fw-bold" placeholder="0.00" step="0.01" required>
                    <span class="input-group-text bg-light">%</span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold d-block">Estado del Impuesto</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="estado_impuesto" id="estado_activo" value="activo" checked autocomplete="off">
                    <label class="btn btn-outline-success py-2" for="estado_activo">
                        <i class="fas fa-check-circle me-1"></i> Activo
                    </label>

                    <input type="radio" class="btn-check" name="estado_impuesto" id="estado_inactivo" value="inactivo" autocomplete="off">
                    <label class="btn btn-outline-danger py-2" for="estado_inactivo">
                        <i class="fas fa-times-circle me-1"></i> Inactivo
                    </label>
                </div>
                <small class="text-muted d-block mt-2">Los impuestos inactivos no aparecerán en facturación.</small>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                    <i class="fas fa-save me-2"></i>GUARDAR IMPUESTO
                </button>
                <button type="button" class="btn btn-light btn-sm text-muted" onclick="cancelarEdicion()">
                    Limpiar Formulario
                </button>
            </div>
        </form>
    </div>
</div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Impuestos Registrados</h5>
                        <div class="input-group input-group-sm w-25">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="busquedaImpuesto" class="form-control border-start-0" placeholder="Buscar...">
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Nombre</th>
                                        <th class="text-center">Porcentaje</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_impuestos">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                            Cargando impuestos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Submodulos/Scripts_Impuesto.js"></script>
