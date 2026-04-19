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
            <div class="card-body py-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted mb-1">Búsqueda General</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                            <input type="text" id="filtroGeneral" class="form-control" placeholder="Placa, Cliente o N° de Orden..." oninput="filtrarTabla()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Desde:</label>
                        <input type="date" id="fechaDesde" class="form-control form-control-sm" onchange="filtrarTabla()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Hasta:</label>
                        <input type="date" id="fechaHasta" class="form-control form-control-sm" onchange="filtrarTabla()">
                    </div>
                    <div class="col-md-1">
                        <label class="small d-block mb-1">&nbsp;</label>
                        <button class="btn btn-sm btn-outline-secondary w-100" onclick="limpiarFiltros()" title="Limpiar Filtros"><i class="fas fa-eraser"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fas fa-list me-1"></i> Registro de Órdenes
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0" id="tablaHistorial">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3">N° Orden</th>
                                <th>Fecha Creación</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Placa</th>
                                <th>Estado</th>
                                <th>Monto Total</th>
                                <th class="text-center" style="min-width: 80px;">Detalles</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaHistorial">
                            <tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Cargando historial...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalleOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-alt me-2"></i>Reporte Detallado de Orden <span id="lbl_id_orden_modal" class="text-info ms-1"></span></h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalDetalle()"></button>
                </div>
                <div class="modal-body bg-light" id="areaImpresion">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h4 class="text-primary mb-0 fw-bold">Mecánica Automotriz Díaz Pantaleón</h4>
                            <p class="text-muted small mb-0">Reporte Operativo de Servicio</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-dark fs-6 mb-1" id="lbl_estado_orden_modal">ESTADO</span><br>
                            <small class="text-muted fw-bold">Fecha: <span id="lbl_fecha_orden_modal"></span></small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="card border-primary h-100 shadow-sm">
                                <div class="card-header bg-primary text-white fw-bold py-2"><i class="fas fa-user me-2"></i>Datos del Cliente</div>
                                <div class="card-body py-2 small">
                                    <p class="mb-1"><strong>Nombre:</strong> <span id="det_cliente"></span></p>
                                    <p class="mb-1"><strong>Cédula/RNC:</strong> <span id="det_cedula"></span></p>
                                    <p class="mb-1"><strong>Teléfono:</strong> <span id="det_telefono"></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-danger h-100 shadow-sm">
                                <div class="card-header bg-danger text-white fw-bold py-2"><i class="fas fa-car me-2"></i>Datos del Vehículo</div>
                                <div class="card-body py-2 small">
                                    <p class="mb-1"><strong>Marca/Modelo:</strong> <span id="det_vehiculo"></span></p>
                                    <p class="mb-1"><strong>Placa:</strong> <span id="det_placa" class="badge bg-secondary"></span></p>
                                    <p class="mb-1"><strong>VIN/Chasis:</strong> <span id="det_vin"></span></p>
                                    <p class="mb-1"><strong>Kilometraje Ingreso:</strong> <span id="det_km"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-info mb-3 shadow-sm">
                        <div class="card-header bg-info text-dark fw-bold py-2"><i class="fas fa-comment-dots me-2"></i>Motivo de Visita (Recepción)</div>
                        <div class="card-body py-2">
                            <div id="det_trabajos" class="d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                    <div class="card border-dark mb-3 shadow-sm">
                        <div class="card-header bg-dark text-white fw-bold py-2"><i class="fas fa-tools me-2"></i>Mano de Obra y Hallazgos Técnicos</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle m-0" style="font-size: 13px;">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th class="ps-2">Servicio</th>
                                            <th>Mecánicos</th>
                                            <th>Inicio</th>
                                            <th>Fin</th>
                                            <th>Notas / Hallazgos</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoServiciosDetalle"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card border-secondary mb-3 shadow-sm">
                        <div class="card-header bg-secondary text-white fw-bold py-2"><i class="fas fa-box-open me-2"></i>Repuestos e Insumos</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle m-0" style="font-size: 13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-2">Descripción</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">P. Unitario</th>
                                            <th class="text-end pe-2">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoRepuestosDetalle"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12 text-end">
                            <h4 class="fw-bold">Total Facturado/Cotizado: <span class="text-success" id="det_total">RD$ 0.00</span></h4>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary fw-bold" onclick="cerrarModalDetalle()">Cerrar</button>
                    <button type="button" class="btn btn-primary fw-bold" onclick="imprimirReporte()"><i class="fas fa-print me-2"></i>Imprimir Reporte</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Taller/Scripts_Historial.js"></script>
</body>
</html>