<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-key me-2 text-success"></i>Entrega de Vehículos y Servicios</h2>
            <button class="btn btn-secondary" type="button" onclick="listar()">
                <i class="fas fa-sync-alt me-2"></i>Actualizar Lista
            </button>
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
                                <th>Monto Total</th>
                                <th>Estado Pago</th>
                                <th>Estado Orden</th>
                                <th class="text-center" style="min-width: 100px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaEntregas"></tbody>
                    </table>
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
                            <select class="form-select border-info border-2" name="decision_calidad" id="decision_calidad" required>
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
                                    <input type="text" class="form-control" id="admin_user_calidad" name="admin_username" placeholder="Usuario" required>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="admin_pass_calidad" name="admin_password" placeholder="Contraseña" required>
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
                        
                        <div class="alert alert-warning d-none" id="alerta_pago">
                            <i class="fas fa-exclamation-triangle me-2"></i><strong>Atención:</strong> Esta orden figura como <span id="txt_alerta_pago" class="fw-bold"></span>. Confirme con facturación antes de entregar las llaves.
                        </div>

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
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                    <span class="text-muted fw-bold">Monto:</span>
                                    <span class="fw-bold text-success fs-5" id="lbl_monto"></span>
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
                            <h4 class="fw-bold mb-1">Monto Total del Servicio: <span id="acta_monto" class="text-success"></span></h4>
                            <p class="text-muted small mb-0">Verifique su factura fiscal para el detalle de impuestos y retenciones.</p>
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