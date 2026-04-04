<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
    .modal { z-index: 105000 !important; }
    .modal-backdrop { z-index: 104900 !important; }
    .balance-box { background-color: #e9ecef; border-radius: 8px; padding: 15px; border-left: 5px solid #0d6efd; }
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
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModal"><i class="fas fa-money-bill-wave me-2"></i>Registrar Pago de Compra</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formPago">
                    <div class="modal-body bg-light p-4">
                        
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Localizar Orden Pendiente</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Proveedor <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" id="id_proveedor" required>
                                    <option value="">Seleccione proveedor...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Ordenes Pendientes <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="id_compra" id="id_compra" required disabled>
                                    <option value="">Primero seleccione proveedor...</option>
                                </select>
                            </div>
                        </div>

                        <div class="balance-box mb-4 d-none" id="cajaBalance">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <span class="text-muted d-block fw-bold">Total Orden</span>
                                    <span class="fs-5 fw-bold text-dark" id="lbl_total_orden">$ 0.00</span>
                                </div>
                                <div class="col-md-4 border-start border-end">
                                    <span class="text-muted d-block fw-bold">Pagado Anteriormente</span>
                                    <span class="fs-5 fw-bold text-success" id="lbl_total_pagado">$ 0.00</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-danger d-block fw-bold">Balance Pendiente</span>
                                    <span class="fs-4 fw-bold text-danger" id="lbl_balance_pendiente">$ 0.00</span>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">2. Detalles del Pago / Abono</h6>
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
                            <div class="col-md-12 mb-2">
                                <label class="fw-bold">Referencia de Pago (Cheque/Transf.) <span class="text-muted fw-normal">(Opcional)</span></label>
                                <input type="text" class="form-control border-dark" name="referencia_pago" id="referencia_pago" placeholder="Ej: No. Transacción, Bauche Azul, Cheque #">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalUI('modalPago')">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="btnGuardar"><i class="fas fa-check-circle me-2"></i>Registrar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_PagoCompra.js"></script>
</body>
</html>