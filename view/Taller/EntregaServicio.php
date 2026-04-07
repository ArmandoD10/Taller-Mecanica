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
                        <input type="hidden" id="estado_anterior" name="estado_anterior">
                        
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
</main>

<script src="../../modules/Taller/Scripts_Entrega.js"></script>
</body>
</html>