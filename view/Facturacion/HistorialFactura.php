<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Historial de Facturas</h2>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-light">
                <form id="form_filtros_facturas" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="fw-bold small mb-1">Fecha Inicio</label>
                        <input type="date" class="form-control shadow-sm" id="fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-01') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold small mb-1">Fecha Fin</label>
                        <input type="date" class="form-control shadow-sm" id="fecha_fin" name="fecha_fin" value="<?= date('Y-m-t') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary shadow-sm fw-bold w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar Rango
                        </button>
                    </div>
                    <div class="col-md-4 position-relative">
                        <label class="fw-bold small mb-1">Búsqueda Rápida</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="buscador_dinamico" class="form-control" placeholder="Buscar cliente, vehículo o N° FAC..." onkeyup="filtrarTablaFacturas()">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fas fa-list me-1"></i> Registro de Facturación
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>N° Factura</th>
                                <th>Fecha</th>
                                <th class="text-start">Cliente</th>
                                <th class="text-start">Vehículo</th>
                                <th>NCF</th>
                                <th>Estado</th>
                                <th class="text-end">Monto Total</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaFacturas">
                            <tr>
                                <td colspan="8" class="py-5 text-muted fw-bold">Cargando facturas...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalleFactura" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-primary border-2 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-receipt me-2"></i>Detalle de Factura</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalUI('modalDetalleFactura')"></button>
                </div>
                <div class="modal-body bg-light p-4">
                    <div class="row mb-4 border-bottom pb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted small fw-bold mb-1">DATOS DEL CLIENTE</h6>
                            <p id="det_fac_cliente" class="fw-bold text-dark mb-0 fs-5"></p>
                            <p id="det_fac_vehiculo" class="small text-muted mb-0"></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted small fw-bold mb-1">FACTURA <span id="det_fac_numero" class="text-primary"></span></h6>
                            <p id="det_fac_fecha" class="small text-dark fw-bold mb-0"></p>
                            <p id="det_fac_ncf" class="small text-muted mb-0"></p>
                        </div>
                    </div>
                    
                    <div class="table-responsive bg-white border rounded shadow-sm">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-secondary small text-muted">
                                <tr>
                                    <th>Descripción del Artículo/Servicio</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="det_fac_items">
                                <tr><td colspan="4" class="text-center py-3">Cargando detalles...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <div class="text-end p-3 border rounded bg-white shadow-sm" style="min-width: 250px;">
                            <span class="small text-muted fw-bold">MONTO TOTAL COBRADO:</span><br>
                            <h3 id="det_fac_total" class="fw-bold text-success mb-0"></h3>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button class="btn btn-secondary fw-bold" onclick="cerrarModalUI('modalDetalleFactura')">Cerrar</button>
                    <button class="btn btn-info fw-bold text-dark shadow-sm" id="btn_reimprimir_factura">
                        <i class="fas fa-print me-1"></i> Reimprimir Comprobante
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Facturacion/Scripts_HistorialFacturas.js"></script>
</body>
</html>