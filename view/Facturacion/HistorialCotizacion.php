<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-history me-2 text-secondary"></i>Historial de Cotizaciones</h2>
            <div class="d-flex gap-2">
                <select id="filtro_estado" class="form-select shadow-sm" style="width: 200px;" onchange="listarHistorial()">
                    <option value="TODOS">Todos los Estados</option>
                    <option value="Pendiente">Pendientes</option>
                    <option value="Aprobada">Aprobadas</option>
                    <option value="Rechazada">Rechazadas</option>
                </select>
                <button class="btn btn-primary shadow-sm" onclick="listarHistorial()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                <div class="row align-items-center">
                    <div class="col">Registros de Presupuestos</div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="busqueda_historial" class="form-control" placeholder="Buscar cliente, vehículo o COT..." onkeyup="filtrarHistorial()">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>N° Cotización</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Monto Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoHistorial"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalleHistorial" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-primary border-2">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2"></i>Detalle de Cotización</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalUI('modalDetalleHistorial')"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted small fw-bold">CLIENTE</h6>
                            <p id="det_cliente" class="fw-bold text-dark mb-0"></p>
                            <p id="det_fecha" class="small text-muted"></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted small fw-bold">VEHÍCULO</h6>
                            <p id="det_vehiculo" class="fw-bold text-dark"></p>
                        </div>
                    </div>
                    <table class="table table-sm table-bordered bg-white shadow-sm">
                        <thead class="table-secondary small">
                            <tr>
                                <th>Descripción</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="det_tabla_items"></tbody>
                    </table>
                    <div class="d-flex justify-content-end">
                        <div class="text-end p-2 border rounded bg-white shadow-sm" style="min-width: 200px;">
                            <span class="small text-muted">TOTAL ESTIMADO:</span><br>
                            <h4 id="det_total" class="fw-bold text-primary mb-0"></h4>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button class="btn btn-secondary fw-bold" onclick="cerrarModalUI('modalDetalleHistorial')">Cerrar</button>
                    <button class="btn btn-info fw-bold text-dark" id="btn_reimprimir"><i class="fas fa-print me-1"></i> Reimprimir</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Facturacion/Scripts_Historial_Cot.js"></script>
</body>
</html>