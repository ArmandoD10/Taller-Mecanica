<?php require("../../layout.php"); require("../../header.php"); ?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="fw-bold mt-4 mb-3"><i class="fas fa-history me-2 text-primary"></i>Historial Detailing</h2>

        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="small fw-bold">ID ORDEN</label>
                        <input type="number" id="f_id_orden" class="form-control" placeholder="Ej: 10">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">FECHA INICIO</label>
                        <input type="date" id="f_fecha_inicio" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">FECHA FIN</label>
                        <input type="date" id="f_fecha_fin" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 fw-bold" onclick="aplicarFiltrosHistorial()">
                            <i class="fas fa-filter me-2"></i>APLICAR FILTRO
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-danger w-100 fw-bold" onclick="generarReporteDetailingPDF()">
                            <i class="fas fa-file-pdf me-2"></i>IMPRIMIR 
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 fw-bold"><i class="fas fa-history me-2"></i>Órdenes de Sucursal (Recientes)</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Orden</th>
                            <th>Descripción</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="text-center">Acción</th> </tr>
                        </tr>
                    </thead>
                    <tbody id="tabla_ordenes_recientes"></tbody>

<tbody id="tabla_historial_detailing"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalDetalleServicio" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title">Detalle de Insumos</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div id="body_detalle_servicio" class="modal-body"></div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.min.js"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.plugin.autotable.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Autoadorno/Scripts_HistorialServicio.js"></script>