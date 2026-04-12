<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-history me-2 text-danger"></i>Historial de Devoluciones</h2>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-light">
                <form id="form_filtros_devoluciones" class="row align-items-end">
                    <div class="col-md-3">
    <label class="fw-bold small mb-1">Desde</label>
    <input type="date" class="form-control shadow-sm" id="fecha_inicio" name="fecha_inicio">
</div>
<div class="col-md-3">
    <label class="fw-bold small mb-1">Hasta</label>
    <input type="date" class="form-control shadow-sm" id="fecha_fin" name="fecha_fin">
</div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-danger shadow-sm fw-bold w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small mb-1">Búsqueda Rápida</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="buscador_dinamico" class="form-control" placeholder="Buscar por cliente, motivo o FAC..." onkeyup="filtrarTablaDevoluciones()">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white fw-bold">
                <i class="fas fa-list-alt me-1"></i> Registros de Retorno
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>ID Dev.</th>
                                <th>Factura</th>
                                <th>Fecha Devolución</th>
                                <th class="text-start">Cliente</th>
                                <th>Estado Producto</th>
                                <th class="text-end">Monto Reintegrado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaDevoluciones">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalleDevolucion" tabindex="-1">
        <div class="modal-dialog modal-lg border-danger border-2">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2"></i>Detalle del Reingreso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row mb-3 p-3 bg-white rounded border mx-0 shadow-sm">
                        <div class="col-md-6 border-end">
                            <h6 class="text-muted small fw-bold">MOTIVO REGISTRADO:</h6>
                            <p id="det_dev_motivo" class="text-dark italic"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small fw-bold">ADMINISTRADOR QUE AUTORIZÓ:</h6>
                            <p id="det_dev_admin" class="fw-bold text-primary mb-0"></p>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">ARTÍCULOS QUE FUERON DEVUELTOS:</h6>
                    <div class="table-responsive bg-white border rounded">
                        <table class="table table-sm mb-0">
                            <thead class="table-secondary small">
                                <tr>
                                    <th>Descripción</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="det_dev_items"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../modules/Facturacion/Scripts_HistorialDevolucion.js"></script>