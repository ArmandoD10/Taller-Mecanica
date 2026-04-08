<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-file-invoice-dollar me-2 text-success"></i>Historial de Pagos y Recibos</h2>
            <div>
                <button class="btn btn-secondary shadow-sm me-2" type="button" onclick="listarHistorial()">
                    <i class="fas fa-sync-alt me-2"></i>Actualizar
                </button>
                <button class="btn btn-primary shadow-sm" type="button" onclick="imprimirReportePagos()">
                    <i class="fas fa-print me-2"></i>Imprimir Reporte
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-light">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1 text-uppercase">Buscador Inteligente</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="inputBusqueda" class="form-control border-start-0 py-2" 
                                   placeholder="Buscar cliente, factura o N° recibo..." onkeyup="filtrarTabla()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1 text-uppercase">Fecha Desde</label>
                        <input type="date" id="fechaDesde" class="form-control py-2" onchange="filtrarTabla()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1 text-uppercase">Fecha Hasta</label>
                        <input type="date" id="fechaHasta" class="form-control py-2" onchange="filtrarTabla()">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div id="statusBusqueda" class="form-text small"></div>
                    <h5 class="mb-0 fw-bold text-success d-none" id="totalFiltrado">Total: RD$ 0.00</h5>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fas fa-list-ol me-2"></i> Registro de Ingresos
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaHistorial">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th>N° Recibo</th>
                                <th>Fecha / Hora</th>
                                <th>Cliente</th>
                                <th>Factura / Orden</th>
                                <th class="text-end">Monto Pagado</th>
                                <th class="text-center">Método</th>
                                <th>Cajero</th>
                                <th class="text-center no-print">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaPagos">
                            <tr><td colspan="8" class="text-center py-5 text-muted">Cargando historial...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Facturacion/Scripts_HistorialPagos.js"></script>
</body>
</html>