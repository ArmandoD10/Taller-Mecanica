<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-clipboard-check me-2 text-success"></i>Historial de Vehículos Entregados</h2>
            <button class="btn btn-secondary" type="button" onclick="listar()">
                <i class="fas fa-sync-alt me-2"></i>Actualizar
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <p class="m-0 text-muted small"><i class="fas fa-info-circle me-1"></i> Este módulo registra la auditoría exacta del momento en que los vehículos salieron del taller y fueron entregados a sus propietarios.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-success text-white fw-bold">
                <i class="fas fa-car-side me-1"></i> Registro Oficial de Entregas
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="tablaEntregas">
                        <thead class="table-dark">
                            <tr>
                                <th>N° Orden</th>
                                <th>Fecha de Entrega</th>
                                <th>Cliente</th>
                                <th>Vehículo (Placa)</th>
                                <th>Monto Total</th>
                                <th>Entregado Por</th>
                                <th class="text-center" style="min-width: 100px;">Comprobante</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaEntregas"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalComprobante" tabindex="-1" aria-hidden="true">
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

<script src="../../modules/Taller/Scripts_HistorialEntrega.js"></script>
</body>
</html>