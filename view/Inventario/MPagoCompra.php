<?php
require("../../layout.php");
require("../../header.php");
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    .modal { 
        z-index: 105000 !important; 
    }
    .modal-backdrop { 
        z-index: 104900 !important; 
    }
    /* Un z-index más alto para el sub-modal de detalles para que quede por encima del modal principal */
    #modalDetallesCompra { 
        z-index: 106000 !important; 
    } 
    .balance-box { 
        background-color: #e9ecef; 
        border-radius: 8px; 
        padding: 15px; 
        border-left: 5px solid #0d6efd; 
    }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-money-check-alt me-2 text-primary"></i>Pagos a Proveedores</h2>
            <button class="btn btn-primary" onclick="nuevoPago()">
                <i class="fas fa-plus me-2"></i>Registrar Nuevo Pago
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>ID Pago</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Orden Pagada</th>
                                <th>Método de Pago</th>
                                <th>Referencia</th>
                                <th>Monto Pagado</th>
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

    <div class="modal fade" id="modalPago" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-money-bill-wave me-2"></i>Registrar Pago de Compra</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body bg-light p-4">
                    
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Localizar Proveedor</h6>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="fw-bold mb-1">Buscar Proveedor (Nombre o RNC) <span class="text-danger">*</span></label>
                            <select class="form-select border-dark" id="id_proveedor" style="width: 100%;">
                                <option value="">Seleccione o escriba...</option>
                            </select>
                        </div>
                    </div>

                    <div id="seccion_ordenes" class="d-none">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mt-4 mb-3">2. Órdenes Pendientes de Pago</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle text-center bg-white shadow-sm">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>No. Orden</th>
                                        <th>Fecha</th>
                                        <th>Balance Pendiente</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoOrdenesPendientes"></tbody>
                            </table>
                        </div>
                    </div>

                    <form id="formPago" class="d-none mt-4 border border-2 border-primary rounded p-3 bg-white">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            3. Detalles del Pago / Abono para la <span id="lbl_orden_seleccionada" class="text-danger"></span>
                        </h6>
                        
                        <input type="hidden" name="id_compra" id="id_compra_pagar">

                        <div class="balance-box mb-4">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <span class="text-muted d-block fw-bold">Total Orden</span>
                                    <span class="fs-5 fw-bold text-dark" id="lbl_total_orden">$ 0.00</span>
                                </div>
                                <div class="col-md-4 border-start border-end">
                                    <span class="text-muted d-block fw-bold">Pagado Antes</span>
                                    <span class="fs-5 fw-bold text-success" id="lbl_total_pagado">$ 0.00</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-danger d-block fw-bold">Deuda Actual</span>
                                    <span class="fs-4 fw-bold text-danger" id="lbl_balance_pendiente">$ 0.00</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold">Monto a Pagar <span class="text-danger">*</span></label>
                                <input type="number" class="form-control border-dark fs-5 text-end text-success fw-bold" name="monto_pagado" id="monto_pagado" required placeholder="0.00" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold">Moneda <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="id_moneda" id="id_moneda" required>
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="id_metodo" id="id_metodo" required>
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <label class="fw-bold">Referencia (Cheque/Transf.) <span class="text-muted fw-normal">(Opcional)</span></label>
                                <input type="text" class="form-control border-dark" name="referencia_pago" id="referencia_pago" placeholder="Ej: Bauche Azul, Cheque #">
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success btn-lg" id="btnGuardar">
                                <i class="fas fa-check-circle me-2"></i>Procesar Pago
                            </button>
                        </div>
                    </form>

                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalUI('modalPago')">Cerrar Ventana</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetallesCompra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-primary">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-list-alt me-2"></i>Artículos de la Orden <span id="lbl_submodal_orden" class="text-warning"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalUI('modalDetallesCompra')"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped text-center align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>Artículo</th>
                                <th>Precio U.</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaArticulos"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="cerrarModalUI('modalDetallesCompra')">Entendido</button>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_PagoCompra.js"></script>
</body>
</html>