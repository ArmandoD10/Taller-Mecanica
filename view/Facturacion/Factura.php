<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="mt-4 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-0"><i class="fas fa-cash-register me-2 text-primary"></i>Facturación Profesional</h2>
                <p class="text-muted">Sucursal: <span class="fw-bold text-dark"><?php echo $_SESSION['nombre_sucursal'] ?? 'General'; ?></span></p>
            </div>
            <div class="text-end">
                <h4 class="text-primary fw-bold mb-0" id="total_general_display">RD$ 0.00</h4>
                <span class="badge bg-info-subtle text-info border border-info-subtle">Listo para procesar</span>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold small-caps text-dark">
            <i class="fas fa-file-invoice me-2 text-primary"></i>Órdenes Listas para Facturar
        </h5>
        <div class="input-group input-group-sm w-25">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search"></i></span>
            <input type="text" id="filtro_ordenes" class="form-control border-start-0" placeholder="Filtrar por placa o cliente...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 300px;">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr class="text-uppercase small fw-bold text-muted">
                        <th class="ps-4" style="width: 50px;"></th>
                        <th>Orden #</th>
                        <th>Vehículo / Cliente</th>
                        <th>Fecha</th>
                        <th class="text-end pe-4">Monto Base</th>
                    </tr>
                </thead>
                <tbody id="tbody_ordenes">
                    </tbody>
            </table>
        </div>
    </div>
</div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 border-top border-primary border-4 mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-uppercase">Detalle de la Factura</h6>
                            <div class="d-flex gap-2 align-items-end mb-3">
    <div class="flex-grow-1 position-relative">
        <label class="small fw-bold text-muted">Añadir Producto Extra</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
            <input type="text" id="buscar_producto" class="form-control" placeholder="Nombre o Código..." oninput="buscarProductoAdicional(this)">
        </div>
        <div id="lista_busqueda_productos" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1050; max-height: 200px; overflow-y: auto;"></div>
    </div>
    <div style="width: 80px;">
        <label class="small fw-bold text-muted">Cant.</label>
        <input type="number" id="cant_extra" class="form-control form-control-sm text-center" value="1" min="1">
    </div>
</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Descripción</th>
                                        <th>Tipo</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody id="detalle_factura_items">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex flex-column">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Resumen y Pago</h6>
                        
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">NCF / Comprobante Fiscal</label>
                            <input type="text" id="ncf_factura" class="form-control form-control-sm" placeholder="Ej: B0100000001">
                        </div>

                        <div class="form-check form-switch mb-3 p-3 bg-light rounded border border-info-subtle">
                            <input class="form-check-input ms-0 me-2" type="checkbox" id="es_credito" onchange="toggleCredito(this.checked)">
                            <label class="form-check-label fw-bold text-primary" for="es_credito">
                                <i class="fas fa-hand-holding-usd me-1"></i> ¿Venta a Crédito?
                            </label>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Sub-Total:</span>
                            <span class="fw-bold" id="subtotal_valor">RD$ 0.00</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2 align-items-center">
                            <span class="text-muted">
                                Impuestos 
                                <button class="btn btn-link btn-sm p-0 ms-1 text-primary" data-bs-toggle="modal" data-bs-target="#modalConfigImpuestos">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </span>
                            <span class="fw-bold text-danger" id="impuestos_valor">RD$ 0.00</span>
                        </div>

                        <div id="desglose_impuestos" class="mb-3">
                         </div>

                        <div class="p-3 bg-primary text-white rounded mb-4 mt-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h4 mb-0">TOTAL:</span>
                                <span class="h3 mb-0 fw-bold" id="total_final_valor">RD$ 0.00</span>
                            </div>
                        </div>

                        <label class="small fw-bold mb-2">Método de Pago</label>
                        <select id="metodo_pago" class="form-select mb-4">
                            <option value="1">Efectivo</option>
                            <option value="2">Tarjeta (Azul API)</option>
                            <option value="3">Transferencia</option>
                        </select>

                        <button class="btn btn-dark w-100 py-3 fw-bold shadow" onclick="ejecutarAccionPago()">
                            <i class="fas fa-file-invoice me-2"></i> PROCESAR FACTURA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalConfigImpuestos" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold small-caps">Impuestos Aplicables</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush" id="lista_impuestos_check">
                        <div class="p-3 text-center text-muted small">Cargando impuestos...</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button class="btn btn-primary btn-sm w-100" data-bs-dismiss="modal">Cerrar y Calcular</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAzul" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content overflow-hidden border-0 shadow-lg">
            <div class="modal-body p-0">
                <div class="bg-primary p-4 text-center">
                    <img src="https://www.azul.com.do/PublishingImages/logos/logo_azul_main.png" alt="Azul" style="filter: brightness(0) invert(1); height: 45px;">
                </div>

                <div class="p-4">
                    <div id="azul_formulario">
                        <div class="text-center mb-4">
                            <h6 class="fw-bold text-muted mb-1 small-caps">Monto a Retener</h6>
                            <h2 class="fw-bold text-dark">RD$ <span id="monto_azul_display">0.00</span></h2>
                        </div>
                        
                        <div class="mb-3">
                            <label class="small fw-bold">Número de Tarjeta</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-credit-card text-muted"></i></span>
                                <input type="text" class="form-control form-control-lg text-center" placeholder="xxxx xxxx xxxx xxxx" id="tarjeta_numero">
                            </div>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="small fw-bold">Expiración</label>
                                <input type="text" class="form-control" placeholder="MM/AA">
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold">CVV</label>
                                <input type="password" class="form-control" placeholder="***">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary py-3 fw-bold shadow" onclick="simularProcesandoAzul()">
                                <i class="fas fa-lock me-2"></i>CONFIRMAR PAGO
                            </button>
                            <button type="button" class="btn btn-link text-muted btn-sm mt-1" onclick="cancelarPagoAzul()">
                                Cancelar Transacción
                            </button>
                        </div>
                    </div>

                    <div id="azul_cargando" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                        <h5 class="fw-bold">Comunicando con Azul...</h5>
                        <p class="text-muted small">Por favor, no cierre el navegador.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Facturacion/Scripts_Factura.js"></script>