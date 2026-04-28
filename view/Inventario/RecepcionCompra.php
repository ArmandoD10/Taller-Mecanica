<?php
require("../../layout.php");
require("../../header.php");
?>


<main class="contenido">
    <div class="container-fluid px-4">
        <div class="mt-4 mb-4">
            <h2 class="mb-0"><i class="fas fa-truck-loading me-2 text-primary"></i>Panel de Recepción</h2>
            <p class="text-muted">Órdenes de compra pendientes de entrada a almacén.</p>
        </div>

        <div class="card card-recepcion shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>Compras por Recibir</h5>
                <input type="text" id="filtroTabla" class="form-control form-control-sm" placeholder="Filtrar por proveedor o ID..." style="width: 250px;">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Fecha Orden</th>
                                <th>Proveedor</th>
                                <th class="text-center">Items</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_compras_pendientes">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="area_recepcion" class="card card-recepcion shadow-lg border-0 d-none mb-5">
    <div class="card-header bg-white py-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h5 class="mb-0 fw-bold text-success" id="lbl_detalle_compra">Procesando Recepción</h5>
                <small class="text-muted fw-bold" id="lbl_proveedor_recep"></small>
            </div>

            <div class="col-md-8 mt-2 mt-md-0">
                <div class="d-flex gap-2 justify-content-end align-items-end">
                    
                    <div class="text-start position-relative" style="width: 250px;">
                        <label class="small fw-bold text-primary">Empleado Responsable *</label>
                        <input type="text" id="buscar_empleado" class="form-control form-control-sm" placeholder="Nombre o Código...">
                        <input type="hidden" id="id_empleado_seleccionado">
                        <div id="lista_empleados" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                    </div>

                    <div class="text-start" style="width: 180px;">
                        <label class="small fw-bold text-danger">No. Conduce *</label>
                        <input type="text" id="num_conduze_recep" class="form-control form-control-sm border-danger" placeholder="Obligatorio">
                    </div>
                    
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="cerrarRecepcion()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <table class="table table-striped align-middle mb-0">
            <thead class="table-light text-secondary small">
                <tr>
                    <th class="ps-4">Artículo / Repuesto</th>
                    <th class="text-center">Pedido</th>
                    <th class="text-center">En Stock</th>
                    <th class="text-center">Faltante</th>
                    <th width="150" class="text-center">Recibir Ahora</th>
                    <th width="250">Almacén Destino</th>
                </tr>
            </thead>
            <tbody id="tbody_detalle_recepcion">
                </tbody>
        </table>
    </div>
    <div class="card-footer bg-white p-4 text-end">
        <button class="btn btn-success btn-lg px-5 shadow fw-bold" onclick="confirmarRecepcion()">
            <i class="fas fa-save me-2"></i> Confirmar Entrada de Mercancía
        </button>
    </div>
</div>
    </div>

    <div class="modal fade" id="modalDetalleCompra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-search-plus me-2"></i>Vista Previa de la Orden</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <div>
                            <h4 id="md_proveedor" class="fw-bold text-primary mb-0"></h4>
                            <small class="text-muted" id="md_id_compra"></small>
                        </div>
                        <div class="text-end">
                            <small class="d-block text-muted">Total Estimado:</small>
                            <h4 id="md_total" class="fw-bold text-success"></h4>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr class="text-muted small">
                                    <th>Imagen / Item</th>
                                    <th class="text-center">Pedida</th>
                                    <th class="text-end">Precio Un.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_modal_detalle">
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary fw-bold" id="btn_iniciar_desde_modal">
                        <i class="fas fa-check me-1"></i> Iniciar Recepción
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_Recepcion.js"></script>