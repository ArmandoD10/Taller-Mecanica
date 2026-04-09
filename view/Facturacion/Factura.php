<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="mt-4 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0"><i class="fas fa-cash-register me-2 text-primary"></i>Punto de Venta POS</h2>
                <p class="text-muted small">Panel de Facturación Directa e Inventario</p>
            </div>
            <div class="text-end">
                <h3 class="text-primary fw-bold mb-0" id="total_general_display">RD$ 0.00</h3>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Conectado a Sucursal</span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-9 position-relative">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="small fw-bold text-muted mb-0">Buscador de Repuestos</label>
                                    <button class="btn btn-sm btn-outline-danger border-0 fw-bold" onclick="abrirAuthOferta()" title="Aplicar Ofertas">
                                        <i class="fas fa-cog fa-spin me-1"></i> GESTIONAR OFERTAS
                                    </button>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-barcode text-muted"></i></span>
                                    <input type="text" id="buscar_producto" class="form-control border-start-0" placeholder="Escriba nombre o código de barras..." oninput="buscarProducto(this)">
                                </div>
                                <div id="res_productos" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1050;"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Cantidad</label>
                                <input type="number" id="cant_extra" class="form-control text-center fw-bold" value="1" min="1">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Descripción</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody id="detalle_factura_items"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-user-tag me-2 text-primary"></i>CLIENTE / CRÉDITO</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="switch_credito" onchange="toggleModoCredito(this.checked)">
                                <label class="form-check-label fw-bold text-danger" for="switch_credito">Venta a Crédito</label>
                            </div>
                        </div>
                        <div id="contenedor_cliente" class="d-none">
                            <input type="text" id="buscar_cliente" class="form-control mb-2" placeholder="Nombre o Cédula del cliente..." oninput="buscarClienteCredito(this)">
                            <div id="res_clientes" class="list-group shadow-sm d-none mb-3"></div>
                            
                            <div id="info_credito_cliente" class="p-3 bg-light rounded border d-none">
                                <div class="row text-center small">
                                    <div class="col-4 border-end text-uppercase">Cliente: <br><b id="c_nombre" class="text-dark">---</b></div>
                                    <div class="col-4 border-end text-uppercase">Límite: <br><b id="c_limite" class="text-success">RD$ 0.00</b></div>
                                    <div class="col-4 text-uppercase">Disponible: <br><b id="c_disponible" class="text-primary">RD$ 0.00</b></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="mb-3">
                <label class="small fw-bold">NCF Comprobante</label>
                <input type="text" id="ncf_factura" class="form-control fw-bold border-primary" placeholder="B0200000001 (Consumidor Final)" style="text-transform: uppercase;">
            </div>
            
            <div class="bg-light p-3 rounded mb-3 border">
                <div class="d-flex justify-content-between mb-1 small text-muted">
                    <span>Sub-Total Bruto:</span>
                    <span id="subtotal_valor" class="text-dark fw-bold">RD$ 0.00</span>
                </div>
                
                <div id="fila_ofertas" class="d-flex justify-content-between mb-2 small d-none border-top pt-2">
                    <span class="text-danger fw-bold">Descuento Ofertas:</span>
                    <span id="ofertas_valor" class="text-danger fw-bold">- RD$ 0.00</span>
                </div>

                <div id="desglose_impuestos_dinamico" class="border-top pt-2">
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2 border-top pt-2">
                    <span class="fw-bold text-uppercase">Total Neto:</span>
                    <h4 class="fw-bold text-success mb-0" id="total_final_valor">RD$ 0.00</h4>
                </div>
            </div>

            <label class="small fw-bold mb-2">Método de Pago</label>
            <div class="input-group mb-4">
                <span class="input-group-text bg-white"><i class="fas fa-wallet text-muted"></i></span>
                <select id="metodo_pago" class="form-select fw-bold">
                    <option value="1">💵 Efectivo</option>
                    <option value="2">💳 Tarjeta (AZUL / POPULAR)</option>
                    <option value="3">🏦 Transferencia</option>
                </select>
            </div>
            
            <button class="btn btn-primary w-100 py-3 fw-bold shadow-sm" onclick="previsualizarVoucher()">
                <i class="fas fa-file-invoice-dollar me-2"></i> PROCESAR FACTURACIÓN
            </button>
        </div>
    </div>
</div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalAuthAdmin" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title fw-bold small"><i class="fas fa-shield-alt me-2"></i>AUTORIZACIÓN REQUERIDA</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted text-center mb-3">Ingrese credenciales de administrador para gestionar ofertas.</p>
                <div class="mb-2">
                    <input type="text" id="auth_user" class="form-control text-center" placeholder="Usuario">
                </div>
                <div class="mb-3">
                    <input type="password" id="auth_pass" class="form-control text-center" placeholder="Contraseña">
                </div>
                <button class="btn btn-danger w-100 fw-bold" onclick="validarAccesoOfertas()">VALIDAR ACCESO</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSeleccionOfertas" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-tags me-2 text-warning"></i>Ofertas Disponibles</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="lista_ofertas_disponibles" class="list-group list-group-flush">
                    <div class="p-5 text-center text-muted">
                        <div class="spinner-border spinner-border-sm me-2"></div> Buscando ofertas vigentes...
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button class="btn btn-dark w-100 fw-bold py-2 shadow" onclick="aplicarOfertasSeleccionadas()">
                    APLICAR BENEFICIOS A FACTURA
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVoucher" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body p-4 text-center" id="voucher_content" style="font-family: 'Courier New', Courier, monospace; font-size: 13px;"></div>
            <div class="modal-footer bg-light border-0 p-2">
                <button class="btn btn-success w-100 fw-bold py-2" onclick="finalizarTodo()">CONFIRMAR E IMPRIMIR</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAzul" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden shadow-lg">
            <div class="bg-primary p-4 text-center">
                <img src="/Taller/Taller-Mecanica/img/azul.webp" alt="Azul" style="filter: brightness(0) invert(1); height: 45px;">
                <div class="mt-2 text-white small fw-bold">PASARELA BANCO POPULAR</div>
            </div>
            <div class="p-4" id="azul_formulario">
                <div class="text-center mb-4">
                    <small class="text-muted d-block">Monto a Autorizar</small>
                    <h2 class="fw-bold">RD$ <span id="monto_azul_display">0.00</span></h2>
                </div>
                <input type="text" class="form-control form-control-lg text-center mb-4 border-primary fw-bold" id="tarjeta_numero" placeholder="•••• •••• •••• ••••" maxlength="16">
                <button class="btn btn-primary w-100 py-3 fw-bold shadow" onclick="simularAzul()">AUTORIZAR PAGO</button>
            </div>
            <div id="azul_cargando" class="text-center py-5 d-none">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                <h6 class="fw-bold text-muted">Comunicando con Banco Popular...</h6>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Facturacion/Scripts_Factura.js"></script>