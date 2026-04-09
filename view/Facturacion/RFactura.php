<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-chart-line me-2 text-primary"></i>Reporte de Ventas (NCF)</h2>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-light">
                <form id="form_filtros" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="fw-bold small mb-1">Fecha Inicio</label>
                        <input type="date" class="form-control shadow-sm" id="fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-01') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold small mb-1">Fecha Fin</label>
                        <input type="date" class="form-control shadow-sm" id="fecha_fin" name="fecha_fin" value="<?= date('Y-m-t') ?>" required>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="submit" class="btn btn-primary shadow-sm fw-bold me-2">
                            <i class="fas fa-search me-1"></i> Generar Reporte
                        </button>
                        <button type="button" class="btn btn-outline-dark shadow-sm fw-bold" onclick="imprimirReporte()">
                            <i class="fas fa-print me-1"></i> Imprimir / PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-success text-white shadow border-0 h-100">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase fw-bold mb-1 opacity-75">Ventas Totales (Período)</h6>
                            <h2 class="fw-bold mb-0" id="lbl_total_ventas">RD$ 0.00</h2>
                        </div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-primary text-white shadow border-0 h-100">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="text-uppercase fw-bold mb-1 opacity-75">Facturas Válidas Emitidas</h6>
                            <h2 class="fw-bold mb-0" id="lbl_cantidad_facturas">0</h2>
                        </div>
                        <i class="fas fa-file-invoice fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fas fa-list me-1"></i> Desglose de Facturas
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover table-striped align-middle mb-0 text-center" id="tabla_reporte">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>N° Factura</th>
                                <th>NCF</th>
                                <th>Fecha Emisión</th>
                                <th class="text-start">Cliente</th>
                                <th>RNC / Cédula</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Monto Total</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoReporte">
                            <tr>
                                <td colspan="7" class="py-5 text-muted text-center">Seleccione un rango de fechas y haga clic en "Generar Reporte"</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Facturacion/Scripts_ReporteVentas.js"></script>
</body>
</html>