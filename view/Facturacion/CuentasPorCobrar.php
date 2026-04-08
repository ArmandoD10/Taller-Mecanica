<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-hand-holding-usd me-2 text-primary"></i>Cuentas por Cobrar (Créditos)</h2>
            <button class="btn btn-secondary" type="button" onclick="listarCuentas()"><i class="fas fa-sync-alt me-2"></i>Actualizar</button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Factura / Orden</th>
                                <th class="text-start">Cliente</th>
                                <th>Fecha Emisión</th>
                                <th>Total Factura</th>
                                <th>Balance Pendiente</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaCxC">
                            <tr><td colspan="6" class="text-muted py-4">Cargando cuentas...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCobro" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-success border-2">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-money-bill-wave me-2"></i>Registrar Pago / Abono</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalCobro()"></button>
                </div>
                <form id="formCobro">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="cobro_id_factura" name="id_factura">
                        <input type="hidden" id="cobro_id_credito" name="id_credito">
                        <input type="hidden" id="cobro_maximo">

                        <div class="alert alert-info text-center shadow-sm">
                            <h6 class="mb-1 text-dark">Balance Pendiente</h6>
                            <h2 class="fw-bold text-primary mb-0" id="lbl_balance_pendiente">RD$ 0.00</h2>
                        </div>

                        <div class="mb-3">
                            <ul class="list-group small shadow-sm">
                                <li class="list-group-item d-flex justify-content-between"><span>Factura N°:</span><b id="lbl_cobro_factura"></b></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Cliente:</span><b id="lbl_cobro_cliente"></b></li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Monto a Pagar (RD$)</label>
                            <input type="number" step="0.01" class="form-control form-control-lg fw-bold text-success border-success" id="monto_pago" name="monto_pago" required>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="checkSaldar" onchange="saldarCompleto(this.checked)">
                                <label class="form-check-label small fw-bold text-muted" for="checkSaldar">Saldar cuenta completa</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="fw-bold">Método de Pago</label>
                                <select class="form-select border-dark" name="metodo_pago" id="metodo_pago" required onchange="verificarMetodo()">
                                    <option value="Efectivo">💵 Efectivo</option>
                                    <option value="Tarjeta">💳 Tarjeta</option>
                                    <option value="Transferencia">🏦 Transferencia</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="fw-bold">Referencia <small class="text-muted">(Opcional)</small></label>
                                <input type="text" class="form-control border-dark" name="referencia" id="referencia_pago" placeholder="Voucher o N° Trans.">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalCobro()">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-save me-2"></i>Procesar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Facturacion/Scripts_Cobros.js"></script>
</body>
</html>