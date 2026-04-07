<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-history me-2 text-primary"></i>Historial General de Servicios</h2>
            <button class="btn btn-secondary" type="button" onclick="listar()">
                <i class="fas fa-sync-alt me-2"></i>Actualizar
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <p class="m-0 text-muted small"><i class="fas fa-info-circle me-1"></i> Utiliza el buscador nativo de la tabla para filtrar por Placa, Cliente o N° de Orden.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fas fa-list me-1"></i> Registro de Órdenes
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="tablaHistorial">
                        <thead class="table-dark">
                            <tr>
                                <th>N° Orden</th>
                                <th>Fecha Creación</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Placa</th>
                                <th>Estado</th>
                                <th>Monto Total</th>
                                <th class="text-center" style="min-width: 80px;">Detalles</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaHistorial"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalleOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Reporte Detallado de Orden <span id="lbl_id_orden_modal" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalDetalle()"></button>
                </div>
                <div class="modal-body bg-light" id="areaImpresion">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h4 class="text-primary mb-0">Mecánica Automotriz Díaz Pantaleón (SIG)</h4>
                            <p class="text-muted small mb-0">Reporte Operativo de Servicio</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-dark fs-6 mb-1" id="lbl_estado_orden_modal">ESTADO</span><br>
                            <small class="text-muted fw-bold">Fecha: <span id="lbl_fecha_orden_modal"></span></small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-primary h-100">
                                <div class="card-header bg-primary text-white fw-bold py-1"><i class="fas fa-user me-2"></i>Datos del Cliente</div>
                                <div class="card-body py-2">
                                    <p class="mb-1"><strong>Nombre:</strong> <span id="det_cliente"></span></p>
                                    <p class="mb-1"><strong>Cédula/RNC:</strong> <span id="det_cedula"></span></p>
                                    <p class="mb-1"><strong>Teléfono:</strong> <span id="det_telefono"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-danger h-100">
                                <div class="card-header bg-danger text-white fw-bold py-1"><i class="fas fa-car me-2"></i>Datos del Vehículo</div>
                                <div class="card-body py-2">
                                    <p class="mb-1"><strong>Marca/Modelo:</strong> <span id="det_vehiculo"></span></p>
                                    <p class="mb-1"><strong>Placa:</strong> <span id="det_placa" class="badge bg-secondary"></span></p>
                                    <p class="mb-1"><strong>VIN/Chasis:</strong> <span id="det_vin"></span></p>
                                    <p class="mb-1"><strong>Kilometraje Ingreso:</strong> <span id="det_km"></span> km</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-dark mb-3">
                        <div class="card-header bg-dark text-white fw-bold py-1"><i class="fas fa-tools me-2"></i>Servicios Realizados y Hallazgos Técnicos</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped m-0">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Servicio</th>
                                            <th>Mecánicos</th>
                                            <th>Inicio</th>
                                            <th>Fin</th>
                                            <th>Notas / Hallazgos</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoServiciosDetalle">
                                        </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 text-end">
                            <h5 class="fw-bold">Total Facturado/Cotizado: <span class="text-success" id="det_total">RD$ 0.00</span></h5>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalDetalle()">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="imprimirReporte()"><i class="fas fa-print me-2"></i>Imprimir Reporte</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Taller/Scripts_Historial.js"></script>
</body>
</html>