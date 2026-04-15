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

        <div class="row mb-4 text-center">
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
                        <thead class="table-dark text-center">
                            <tr>
                                <th>N° Orden</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Monto Base</th>
                                <th>Estado Pago</th>
                                <th>Estado Orden</th>
                                <th style="min-width: 140px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaEntregas" class="text-center"></tbody>
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

                                    <div class="d-flex justify-content-between align-items-center mb-2 mt-3 border-bottom pb-2">
                                        <h6 class="fw-bold text-secondary m-0">Detalle y Repuestos Extras</h6>
                                        <button class="btn btn-sm btn-outline-danger border-0 fw-bold" onclick="abrirAuthOferta()" title="Aplicar Ofertas y Descuentos">
                                            <i class="fas fa-tags me-1"></i> APLICAR OFERTA
                                        </button>
                                    </div>
                                    
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
                                                    <th class="text-center"></th> 
                                                </tr>
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
                                        <label class="small fw-bold">NCF Comprobante</label>
                                        <input type="text" id="fac_ncf" class="form-control fw-bold border-primary" placeholder="B0200000001 (Consumidor Final)">
                                    </div>

                                    <div class="bg-light p-3 rounded mb-3 border">
                                        <div class="d-flex justify-content-between mb-1 small text-muted">
                                            <span>Sub-Total Bruto:</span>
                                            <span id="fac_subtotal" class="fw-bold text-dark">RD$ 0.00</span>
                                        </div>
                                        
                                        <div id="fila_ofertas" class="d-flex justify-content-between mb-2 small d-none border-top pt-2">
                                            <span class="text-danger fw-bold">Descuento Ofertas:</span>
                                            <span id="ofertas_valor" class="text-danger fw-bold">- RD$ 0.00</span>
                                        </div>

                                        <div id="desglose_impuestos_dinamico" class="border-top pt-2 border-bottom pb-2">
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

    <div class="modal fade" id="modalAuthAdmin" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered" style="z-index: 1060;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title fw-bold small"><i class="fas fa-shield-alt me-2"></i>AUTORIZACIÓN REQUERIDA</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted text-center mb-3">Ingrese credenciales de administrador para aplicar ofertas.</p>
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
        <div class="modal-dialog modal-dialog-centered" style="z-index: 1060;">
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
                        APLICAR BENEFICIOS A LA ORDEN
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAzulTaller" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="bg-primary p-4 text-center text-white">
                    <img src="../../img/lazul.jpg" alt="Azul" style="height: 50px; border-radius: 5px;">
                    <h5 class="fw-bold mb-0">PASARELA AZUL</h5>
                    <small>BANCO POPULAR</small>
                </div>
                <div class="p-4" id="azul_formulario_taller">
                    <h2 class="text-center fw-bold mb-4" id="azul_monto_display">RD$ 0.00</h2>
                    <input type="text" class="form-control form-control-lg text-center mb-4 border-primary" id="azul_tarjeta_taller" placeholder="Número de Tarjeta" maxlength="16">
                    <button class="btn btn-primary w-100 py-3 fw-bold shadow" onclick="procesarAzulTaller()">AUTORIZAR PAGO</button>
                    <button class="btn btn-link text-muted w-100 mt-2" onclick="cerrarModalAzul()">Cancelar</button>
                </div>
                <div id="azul_cargando_taller" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-2 text-muted">Comunicando con el Banco...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCalidad" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-info border-2">
                <div class="modal-header bg-info">
                    <h5 class="modal-title fw-bold">Control de Calidad</h5>
                    <button type="button" class="btn-close" onclick="cerrarModalCalidad()"></button>
                </div>
                <form id="formCalidad">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_orden_calidad" name="id_orden_calidad">
                        
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-primary mb-0" id="lbl_calidad_orden"></h4>
                            <p class="text-muted small fw-bold mb-0" id="lbl_calidad_vehiculo"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="fw-bold small">Veredicto de Revisión</label>
                            <select class="form-select border-info border-2" name="decision_calidad" id="decision_calidad" onchange="toggleServiciosCalidad()" required>
                                <option value="">Seleccione...</option>
                                <option value="Aprobado">✅ Aprobado (Avanzar a Entrega)</option>
                                <option value="Rechazado">❌ Rechazado (Devolver a Taller)</option>
                            </select>
                        </div>

                        <div id="contenedor_servicios_calidad" class="mb-3 d-none">
                            <label class="fw-bold small text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Seleccione qué servicios deben repetirse:</label>
                            <div id="lista_servicios_calidad" class="list-group mt-1 shadow-sm" style="max-height: 150px; overflow-y: auto;">
                                </div>
                        </div>

                        <div class="bg-white p-3 rounded border shadow-sm mt-3">
                            <input type="text" class="form-control mb-2" name="admin_username" placeholder="Usuario Supervisor" required>
                            <input type="password" class="form-control" name="admin_password" placeholder="Contraseña" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info w-100 fw-bold shadow text-dark">Guardar Veredicto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEntrega" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-success border-2">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Entrega de Vehículo y Certificado de Garantía</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalEntrega()"></button>
                </div>
                <form id="formEntrega">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_orden_entrega" name="id_orden_entrega">
                        
                        <div class="text-center mb-4 border-bottom pb-3">
                            <h3 class="text-primary fw-bold mb-1" id="lbl_orden"></h3>
                            <h5 class="text-dark fw-bold" id="lbl_cliente"></h5>
                            <p class="mb-0 text-muted" id="lbl_vehiculo"></p>
                        </div>

                        <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-shield-check me-2"></i>Configuración de Garantía</h6>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="fw-bold small mb-1">Km Inspección (Ingreso) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control shadow-sm bg-white border-success" id="km_actual" name="km_actual" placeholder="Ej. 120000" required>
                            </div>
                            <div class="col-md-8">
                                <label class="fw-bold small mb-1">Tipo de Garantía a Aplicar <span class="text-danger">*</span></label>
                                <select class="form-select shadow-sm border-success" id="tipo_garantia" name="tipo_garantia" onchange="calcularGarantia()" required>
                                    <option value="" selected disabled>Seleccione cobertura...</option>
                                    <option value="A">Categoría A - Mantenimiento Preventivo (30 días / 1,500 km)</option>
                                    <option value="B">Categoría B - Mecánica Menor y Suspensión (3 meses / 5,000 km)</option>
                                    <option value="C">Categoría C - Mecánica Mayor (6 meses / 10,000 km)</option>
                                    <option value="D">Categoría D - SIN GARANTÍA</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="fw-bold small mb-1 text-muted">Vence el (Fecha):</label>
                                <input type="date" class="form-control bg-light text-danger fw-bold" id="fecha_vencimiento" name="fecha_vencimiento" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold small mb-1 text-muted">Vence a los (Kilómetros):</label>
                                <input type="number" class="form-control bg-light text-danger fw-bold" id="km_limite" name="km_limite" readonly>
                            </div>
                            <div class="col-md-12">
                                <label class="fw-bold small mb-1 text-muted">Términos que se imprimirán y guardarán:</label>
                                <textarea class="form-control bg-light text-muted" id="terminos_resumen" name="terminos_resumen" rows="2" readonly></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm py-2" style="font-size: 1.1rem;">
                            <i class="fas fa-check-circle me-2"></i> Confirmar Entrega de Llaves y Generar Certificado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalComprobante" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-success border-2">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Acta de Entrega de Vehículo</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalComprobante()"></button>
                </div>
                <div class="modal-body bg-white p-4" id="areaImpresionEntrega">
                    <div class="text-center mb-4 border-bottom pb-3">
                        <h4 class="fw-bold mb-0">MECÁNICA DÍAZ PANTALEÓN (SIG)</h4>
                        <small class="text-muted">Santiago de los Caballeros, R.D.</small>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6 small">
                            <b>N° Orden:</b> <span id="acta_orden" class="text-primary fw-bold"></span><br>
                            <b>Fecha Ingreso:</b> <span id="acta_ingreso"></span><br>
                            <b>Fecha Salida:</b> <span id="acta_salida" class="text-success fw-bold"></span><br>
                            <b>Entregado Por:</b> <span id="acta_usuario"></span>
                        </div>
                        <div class="col-6 small">
                            <b>Cliente:</b> <span id="acta_cliente"></span><br>
                            <b>Vehículo:</b> <span id="acta_vehiculo"></span><br>
                            <b>Placa:</b> <span id="acta_placa"></span><br>
                            <b>VIN/Chasis:</b> <span id="acta_vin"></span>
                        </div>
                    </div>
                    <div class="bg-light p-3 text-center mb-4 rounded border">
                        <h6 class="text-muted small mb-1">Monto Total Liquidado</h6>
                        <h3 id="acta_monto" class="text-success fw-bold mb-0"></h3>
                    </div>
                    <div class="row mt-5 text-center small">
                        <div class="col-5 border-top pt-1">Firma del Cliente</div>
                        <div class="col-2"></div>
                        <div class="col-5 border-top pt-1">Firma del Taller</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success px-4 fw-bold" onclick="imprimirComprobante()"><i class="fas fa-print me-2"></i>Imprimir Acta</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Taller/Scripts_Entrega.js"></script>
</body>
</html>