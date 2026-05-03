<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-hand-holding-usd me-2 text-primary"></i>Cuentas por Cobrar (Créditos)</h2>
            <div class="d-flex gap-2">
                <div class="input-group shadow-sm" style="width: 300px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="buscador_cxc" class="form-control border-start-0" placeholder="Buscar por cliente o factura..." onkeyup="filtrarCuentas()">
                </div>
                <button class="btn btn-secondary shadow-sm" type="button" onclick="listarCuentas()">
                    <i class="fas fa-sync-alt me-2"></i>Actualizar
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>Factura / Orden</th>
                                <th>Cliente</th>
                                <th>Fecha Emisión</th>
                                <th>Total Factura</th>
                                <th>Balance Pendiente</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaCxC" class="text-center">
                            <tr><td colspan="6" class="text-center py-5 text-muted">Cargando cuentas...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCobro" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-success border-2 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-money-bill-wave me-2"></i>Procesar Pago / Abono</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalCobro()"></button>
                </div>
                <form id="formCobro">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="cobro_id_factura" name="id_factura">
                        <input type="hidden" id="cobro_id_credito" name="id_credito">
                        <input type="hidden" id="cobro_maximo">

                        <div class="bg-white p-3 rounded border shadow-sm mb-4">
                            <div class="row text-center mb-2">
                                <div class="col-6 border-end">
                                    <small class="text-muted d-block fw-bold">FACTURA</small>
                                    <span class="fw-bold text-primary fs-5" id="lbl_cobro_factura">---</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block fw-bold">DEUDA ACTUAL</small>
                                    <span class="fw-bold text-danger fs-5" id="lbl_balance_pendiente">RD$ 0.00</span>
                                </div>
                            </div>
                            <div class="text-center border-top pt-2 mt-2">
                                <small class="text-muted d-block">Cliente:</small>
                                <span class="fw-bold text-dark" id="lbl_cobro_cliente">---</span>
                            </div>
                        </div>
                        <!-- Insertar dentro del formCobro en el modal de MCobros.php -->
<div id="contenedor_cuotas_pendientes" class="mb-4 d-none">
    <label class="fw-bold small mb-2 text-primary">
        <i class="fas fa-calendar-alt me-1"></i> SELECCIONE LA CUOTA A PAGAR:
    </label>
    <div class="list-group shadow-sm border" id="lista_cuotas_pago" style="max-height: 180px; overflow-y: auto; border-radius: 8px;">
        <!-- Se llena con JS -->
    </div>
    <input type="hidden" id="id_cuota_seleccionada" name="id_cuota">
    <div class="form-text text-muted small mt-1">
        <i class="fas fa-info-circle me-1"></i> Al seleccionar una cuota, el monto se ajustará automáticamente.
    </div>
</div>

                        <div class="mb-4">
                            <label class="fw-bold small mb-1 text-dark">Monto a Pagar o Abonar</label>
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-dollar-sign text-success"></i></span>
                                <input type="number" class="form-control form-control-lg border-start-0 text-end fw-bold text-success" id="monto_pago" name="monto_pago" step="0.01" min="0.01" required>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="check_saldar" onchange="saldarCompleto(this.checked)">
                                <label class="form-check-label small fw-bold text-primary" for="check_saldar" style="cursor:pointer;">
                                    Saldar la deuda completa
                                </label>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fw-bold small mb-1 text-dark">Método de Pago</label>
                                <select class="form-select border-secondary" id="metodo_pago" name="metodo_pago" onchange="verificarMetodo()">
                                    <option value="Efectivo">💵 Efectivo</option>
                                    <option value="Tarjeta">💳 Tarjeta</option>
                                    <option value="Transferencia">🏦 Transferencia</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold small mb-1 text-dark">N° Referencia</label>
                                <input type="text" class="form-control border-secondary" id="referencia_pago" name="referencia" placeholder="N/A" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalCobro()">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm"><i class="fas fa-check-circle me-2"></i>Aplicar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-info border-2 shadow-lg">
                <div class="modal-header bg-info">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-list-alt me-2"></i>Detalle de Facturación</h5>
                    <button type="button" class="btn-close" onclick="cerrarModalDetalle()"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <div>
                            <span class="text-muted small fw-bold">FACTURA:</span>
                            <h5 class="fw-bold text-primary mb-0" id="lbl_detalle_factura">---</h5>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small fw-bold">CLIENTE:</span>
                            <h6 class="fw-bold text-dark mb-0" id="lbl_detalle_cliente">---</h6>
                        </div>
                    </div>
                    
                    <div class="table-responsive bg-white rounded border shadow-sm">
                        <table class="table table-sm table-striped table-hover align-middle mb-0">
                            <thead class="table-dark small text-center">
                                <tr>
                                    <th class="text-start">Descripción del Servicio / Repuesto</th>
                                    <th>Cant.</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaDetalle">
                                <tr><td colspan="4" class="text-center py-4">Cargando detalles...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end mt-3 border-top pt-2">
                        <span class="text-muted fw-bold me-2">TOTAL (Aprox Sin ITBIS):</span>
                        <h4 class="fw-bold text-success d-inline" id="lbl_detalle_total">RD$ 0.00</h4>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary fw-bold" onclick="cerrarModalDetalle()">Cerrar Detalles</button>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="/Taller/Taller-Mecanica/modules/Facturacion/Scripts_Cobros.js"></script>
</body>
</html>