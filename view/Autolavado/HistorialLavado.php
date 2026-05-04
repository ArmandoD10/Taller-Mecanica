<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <style>
        /* CSS para que el reporte de la tabla salga limpio en la impresora */
        @media print {
            body { background-color: white !important; }
            .no-print, nav, .sidebar { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .card-body { padding: 0 !important; }
            table { width: 100% !important; border-collapse: collapse; }
            th, td { border: 1px solid #ddd !important; padding: 8px !important; }
        }
    </style>

    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4 no-print">
            <div>
                <h2 class="mb-0 fw-bold text-dark"><i class="fas fa-history me-2 text-info"></i>Historial de Lavados</h2>
                <p class="text-muted mb-0">Auditoría y reporte de servicios realizados</p>
            </div>
            <button class="btn btn-success shadow-sm fw-bold" type="button" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Imprimir Reporte
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body bg-light">
                <form id="formFiltrosHistorial" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="fw-bold small text-dark mb-1">Desde</label>
                        <input type="date" class="form-control shadow-sm" id="fecha_inicio" name="fecha_inicio" value="<?php echo date('Y-01-01'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold small text-dark mb-1">Hasta</label>
                        <input type="date" class="form-control shadow-sm" id="fecha_fin" name="fecha_fin" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-dark mb-1">Buscador Rápido</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="buscador_tabla" placeholder="Cliente, vehículo o ticket...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark fw-bold w-100 shadow-sm">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaHistorial">
                        <thead class="table-dark text-center small text-uppercase">
                            <tr>
                                <th>Fecha</th>
                                <th>Origen</th>
                                <th class="text-start">Cliente y Vehículo</th>
                                <th>Servicio</th>
                                <th class="text-end">Monto (RD$)</th>
                                <th>Estado</th>
                                <th class="no-print">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaHistorial" class="text-center">
                            <tr><td colspan="7" class="text-muted py-4">Seleccione fechas y aplique el filtro...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade no-print" id="modalTicketLavado" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-dark">
                <div class="modal-body p-4" id="areaImpresionTicket">
                    <div class="text-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark">Mecánica Automotriz Díaz Pantaleón (SIG)</h5>
                        <small class="text-muted fw-bold">División: Autolavado Express</small><br>
                        <small>RNC: 131-XXXXX-X</small><br>
                        <small>*** REIMPRESIÓN ***</small>
                    </div>
                    <div class="border-bottom border-dark border-2 mb-2"></div>
                    <div class="small mb-3 text-dark">
                        <div><strong>Factura N°:</strong> <span id="tk_num"></span></div>
                        <div><strong>Fecha:</strong> <span id="tk_fecha"></span></div>
                        <div><strong>NCF:</strong> <span id="tk_ncf"></span></div>
                        <div><strong>Cliente:</strong> <span id="tk_cliente"></span></div>
                        <div><strong>Vehículo (Placa):</strong> <span id="tk_placa"></span></div>
                    </div>
                    <div class="border-bottom border-dark border-1 mb-2"></div>
                    <table class="table table-sm table-borderless small mb-2 text-dark" style="width: 100%;">
                        <thead><tr style="border-bottom: 1px solid black;"><th>Cant.</th><th>Descripción</th><th style="text-align: right;">Valor</th></tr></thead>
                        <tbody><tr><td>1</td><td id="tk_servicio"></td><td style="text-align: right;" id="tk_subtotal"></td></tr></tbody>
                    </table>
                    <div class="border-bottom border-dark border-1 mb-2"></div>
                    <div class="d-flex justify-content-between small text-dark" style="display:flex; justify-content: space-between;"><span>ITBIS (18%):</span><span id="tk_itbis"></span></div>
                    <div class="d-flex justify-content-between fw-bold text-dark mt-1" style="display:flex; justify-content: space-between; font-size: 1.1rem; font-weight: bold;"><span>TOTAL:</span><span id="tk_total"></span></div>
                </div>
                <div class="modal-footer p-2 d-flex justify-content-between bg-light">
                    <button type="button" class="btn btn-sm btn-secondary fw-bold" onclick="cerrarModalUI('modalTicketLavado')">Cerrar</button>
                    <button type="button" class="btn btn-sm btn-dark fw-bold shadow-sm" onclick="imprimirTicketLavado()">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Autolavado/Scripts_HistorialLavado.js"></script>
</body>
</html>