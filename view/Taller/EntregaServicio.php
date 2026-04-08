<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-key me-2 text-success"></i>Entrega de Vehículos y Servicios</h2>
            <div class="d-flex gap-2">
                <div class="input-group shadow-sm" style="width: 300px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="buscar_entrega" class="form-control border-start-0" placeholder="Buscar orden o cliente..." onkeyup="filtrarEntregas()">
                </div>
                <button class="btn btn-secondary shadow-sm" type="button" onclick="listar()">
                    <i class="fas fa-sync-alt me-2"></i>Actualizar Lista
                </button>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card bg-primary text-white mb-4 shadow-sm">
                    <div class="card-body fw-bold fs-5"><i class="fas fa-check-double me-2"></i>Vehículos Listos</div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <span class="small text-white stretched-link" id="count_listos">0</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-info text-white mb-4 shadow-sm">
                    <div class="card-body fw-bold fs-5 text-dark"><i class="fas fa-search me-2"></i>En Control de Calidad</div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <span class="small text-dark stretched-link fw-bold" id="count_calidad">0</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card bg-success text-white mb-4 shadow-sm">
                    <div class="card-body fw-bold fs-5"><i class="fas fa-flag-checkered me-2"></i>Entregados Hoy</div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <span class="small text-white stretched-link" id="count_entregados">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fas fa-list me-1"></i> Órdenes en Proceso de Salida
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>N° Orden</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Monto Base</th>
                                <th>Estado Pago</th>
                                <th>Estado Orden</th>
                                <th class="text-center" style="min-width: 140px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaEntregas"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFacturacion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-primary border-2">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Facturación Fiscal de Orden</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalFacturacion()"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="hidden" id="fac_id_orden">
                    <input type="hidden" id="fac_id_cliente">

                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold border-bottom pb-2 text-secondary">Datos del Cliente</h6>
                                    <div class="row mb-3 small">
                                        <div class="col-6">
                                            <p class="mb-1"><strong>Orden:</strong> <span id="fac_lbl_orden" class="text-primary fw-bold"></span></p>
                                            <p class="mb-1"><strong>Cliente:</strong> <span id="fac_lbl_cliente"></span></p>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-1"><strong>Vehículo:</strong> <span id="fac_lbl_vehiculo"></span></p>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold border-bottom pb-2 text-secondary mt-3">Detalle y Repuestos Extras</h6>
                                    <div class="input-group mb-2 shadow-sm position-relative">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-barcode text-muted"></i></span>
                                        <input type="text" id="buscar_prod_entrega" class="form-control border-start-0" placeholder="Buscar repuestos extras..." oninput="buscarProductoEntrega(this)">
                                        <input type="number" id="cant_prod_entrega" class="form-control text-center bg-light" style="max-width: 80px;" value="1" min="1">
                                    </div>
                                    <div id="res_prod_entrega" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1050; max-height: 200px; overflow-y: auto;"></div>

                                    <div class="table-responsive bg-white border rounded mt-2" style="max-height: 220px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-secondary text-muted small sticky-top">
                                                <tr>
                                                    <th>Descripción</th>
                                                    <th class="text-center">Cant.</th>
                                                    <th class="text-end">Precio</th>
                                                    <th class="text-end">Total</th>
                                                    <th class="text-center"></th> </tr>
                                            </thead>
                                            <tbody id="fac_tabla_detalles">
                                                <tr><td colspan="5" class="text-center text-muted py-3">Cargando detalles...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card border-0 shadow-sm h-100 bg-white">
                                <div class="card-body">
                                    <h6 class="fw-bold border-bottom pb-2 text-secondary">Resumen Financiero</h6>
                                    
                                    <div class="mb-3">
                                        <label class="small fw-bold">NCF (Comprobante Fiscal)</label>
                                        <input type="text" id="fac_ncf" class="form-control fw-bold border-primary" placeholder="B0200000001 (Consumidor Final)">
                                    </div>

                                    <div class="bg-light p-3 rounded mb-3 border">
                                        <div class="d-flex justify-content-between mb-1 small text-muted">
                                            <span>Sub-Total Gravado:</span>
                                            <span id="fac_subtotal" class="fw-bold text-dark">RD$ 0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small text-muted border-bottom pb-2">
                                            <span>ITBIS (18%):</span>
                                            <span id="fac_itbis" class="fw-bold text-danger">RD$ 0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span class="fw-bold text-uppercase">Total a Cobrar:</span>
                                            <h4 class="fw-bold text-success mb-0" id="fac_total_final">RD$ 0.00</h4>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="small fw-bold text-dark">Método de Pago</label>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" id="fac_switch_credito" onchange="toggleCreditoTaller(this.checked)">
                                                <label class="form-check-label fw-bold text-danger small" for="fac_switch_credito">Facturar a Crédito</label>
                                            </div>
                                        </div>
                                        <select id="fac_metodo_pago" class="form-select fw-bold">
                                            <option value="1">💵 Efectivo</option>
                                            <option value="2">💳 Tarjeta (Pasarela AZUL)</option>
                                            <option value="3">🏦 Transferencia Bancaria</option>
                                        </select>
                                    </div>
                                    
                                    <div id="fac_info_credito" class="alert alert-info d-none p-2 small mb-0 shadow-sm border-info">
                                        <div class="row text-center">
                                            <div class="col-6 border-end">
                                                <span class="text-muted d-block" style="font-size: 10px;">LÍMITE TOTAL</span>
                                                <b id="fac_credito_limite" class="text-dark">RD$ 0.00</b>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 10px;">DISPONIBLE</span>
                                                <b id="fac_credito_disponible" class="text-success">RD$ 0.00</b>
                                            </div>
                                        </div>
                                        <input type="hidden" id="fac_id_credito">
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalFacturacion()">Cancelar</button>
                    <button type="button" class="btn btn-primary fw-bold px-4" onclick="iniciarCobroOrden()"><i class="fas fa-print me-2"></i>Facturar e Imprimir</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAzulTaller" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 overflow-hidden shadow-lg">
                <div class="bg-primary p-4 text-center">
                    <img src="/Taller/Taller-Mecanica/img/azul.webp" alt="Azul" style="filter: brightness(0) invert(1); height: 45px;">
                    <div class="mt-2 text-white small fw-bold">PASARELA BANCO POPULAR</div>
                </div>
                <div class="p-4" id="azul_formulario_taller">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-dark" id="azul_monto_display">RD$ 0.00</h2>
                    </div>
                    <label class="small fw-bold text-muted mb-1">Número de Tarjeta del Cliente</label>
                    <input type="text" class="form-control form-control-lg text-center mb-4 border-primary" id="azul_tarjeta_taller" placeholder="•••• •••• •••• ••••" maxlength="16">
                    <button class="btn btn-primary w-100 py-3 fw-bold" onclick="procesarAzulTaller()"><i class="fas fa-lock me-2"></i>AUTORIZAR PAGO</button>
                    <button class="btn btn-link text-muted w-100 mt-2" onclick="cerrarModalAzul()">Cancelar</button>
                </div>
                <div id="azul_cargando_taller" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
                    <h5 class="fw-bold text-dark">Procesando Transacción...</h5>
                    <p class="text-muted small">Comunicando con Banco Popular</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCalidad" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-info border-2">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold"><i class="fas fa-clipboard-check me-2"></i>Auditoría de Control de Calidad</h5>
                    <button type="button" class="btn-close" onclick="cerrarModalCalidad()"></button>
                </div>
                <form id="formCalidad">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_orden_calidad" name="id_orden_calidad">
                        <div class="mb-3 text-center">
                            <h4 class="text-primary fw-bold" id="lbl_calidad_orden"></h4>
                            <p class="text-muted mb-0" id="lbl_calidad_vehiculo"></p>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Veredicto de la Revisión <span class="text-danger">*</span></label>
                            <select class="form-select border-info border-2" name="decision_calidad" required>
                                <option value="">Seleccione el resultado...</option>
                                <option value="Aprobado" class="text-success fw-bold">✅ APROBADO: Vehículo Listo para Entrega</option>
                                <option value="Rechazado" class="text-danger fw-bold">❌ RECHAZADO: Devolver a los Mecánicos (Reparación)</option>
                            </select>
                        </div>
                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-body bg-white rounded">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-shield-alt text-info me-1"></i> Firma del Supervisor / Admin <span class="text-danger">*</span></label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="admin_username" placeholder="Usuario" required>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" name="admin_password" placeholder="Contraseña" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalCalidad()">Cancelar</button>
                        <button type="submit" class="btn btn-info text-dark fw-bold"><i class="fas fa-save me-2"></i>Guardar Veredicto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEntrega" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-success border-2">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-car-side me-2"></i>Confirmar Entrega de Vehículo</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalEntrega()"></button>
                </div>
                <form id="formEntrega">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_orden_entrega" name="id_orden_entrega">
                        <div class="mb-3">
                            <ul class="list-group list-group-flush shadow-sm">
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                    <span class="text-muted fw-bold">Orden:</span>
                                    <span class="fw-bold fs-5 text-primary" id="lbl_orden"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                    <span class="text-muted fw-bold">Cliente:</span>
                                    <span class="fw-bold" id="lbl_cliente"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                    <span class="text-muted fw-bold">Vehículo:</span>
                                    <span class="fw-bold" id="lbl_vehiculo"></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalEntrega()">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-check-circle me-2"></i>Entregar Vehículo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalComprobante" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-success border-2">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-certificate me-2"></i>Acta de Entrega de Vehículo</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalComprobante()"></button>
                </div>
                <div class="modal-body bg-white" id="areaImpresionEntrega">
                    
                    <div class="text-center mb-4 border-bottom pb-3">
                        <h3 class="text-dark fw-bold mb-0">Mecánica Automotriz Díaz Pantaleón (SIG)</h3>
                        <p class="text-muted mb-0">Comprobante de Salida y Entrega Conforme</p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <h6 class="fw-bold text-secondary border-bottom pb-1">DATOS DE LA ORDEN</h6>
                            <p class="mb-1"><strong>N° de Orden:</strong> <span id="acta_orden" class="text-primary fw-bold"></span></p>
                            <p class="mb-1"><strong>Fecha de Ingreso:</strong> <span id="acta_ingreso"></span></p>
                            <p class="mb-1"><strong>Fecha de Entrega:</strong> <span id="acta_salida" class="text-success fw-bold"></span></p>
                            <p class="mb-1"><strong>Responsable Entrega:</strong> <span id="acta_usuario"></span></p>
                        </div>
                        <div class="col-6">
                            <h6 class="fw-bold text-secondary border-bottom pb-1">DATOS DEL VEHÍCULO Y CLIENTE</h6>
                            <p class="mb-1"><strong>Propietario:</strong> <span id="acta_cliente"></span></p>
                            <p class="mb-1"><strong>Vehículo:</strong> <span id="acta_vehiculo"></span></p>
                            <p class="mb-1"><strong>Placa:</strong> <span id="acta_placa"></span></p>
                            <p class="mb-1"><strong>VIN/Chasis:</strong> <span id="acta_vin"></span></p>
                        </div>
                    </div>

                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body text-center">
                            <h4 class="fw-bold mb-1">Monto Total Facturado: <span id="acta_monto" class="text-success"></span></h4>
                            <p class="text-muted small mb-0">La factura fiscal y detalles impositivos fueron entregados por caja.</p>
                        </div>
                    </div>

                    <div class="row mt-5 pt-4 text-center">
                        <div class="col-6">
                            <div class="border-top border-dark mx-4 pt-2">
                                <p class="fw-bold mb-0">Firma del Cliente</p>
                                <small class="text-muted">Recibe conforme a satisfacción</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border-top border-dark mx-4 pt-2">
                                <p class="fw-bold mb-0">Firma Taller</p>
                                <small class="text-muted">Autoriza Salida</small>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalComprobante()">Cerrar</button>
                    <button type="button" class="btn btn-success" onclick="imprimirComprobante()"><i class="fas fa-print me-2"></i>Imprimir Acta</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Taller/Scripts_Entrega.js"></script>
</body>
</html>