<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-chart-line me-2 text-success"></i>Reporte Analítico de Ventas</h2>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-light">
                <form id="form_filtros_ventas" class="row align-items-end">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="fw-bold small mb-1">Desde</label>
                        <input type="date" class="form-control shadow-sm" id="fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-01') ?>" required>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="fw-bold small mb-1">Hasta</label>
                        <input type="date" class="form-control shadow-sm" id="fecha_fin" name="fecha_fin" value="<?= date('Y-m-t') ?>" required>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <button type="submit" class="btn btn-success shadow-sm fw-bold w-100">
                            <i class="fas fa-search me-1"></i> Generar
                        </button>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small mb-1">Buscar Cliente</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" id="buscador_cliente" class="form-control" placeholder="Escriba el nombre..." onkeyup="filtrarPorCliente()">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white border-0 shadow-sm h-100">
                    <div class="card-body py-4 text-center">
                        <h6 class="text-uppercase fw-bold mb-1 opacity-75">Total de Facturas</h6>
                        <h2 class="display-5 fw-bold mb-0" id="lbl_total_facturas">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white border-0 shadow-sm h-100">
                    <div class="card-body py-4 text-center">
                        <h6 class="text-uppercase fw-bold mb-1 opacity-75">Ingreso Total Estimado</h6>
                        <h2 class="display-5 fw-bold mb-0" id="lbl_monto_total">RD$ 0.00</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark p-0 border-bottom-0">
                <ul class="nav nav-tabs nav-fill bg-dark border-0" id="ventasTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-0 border-0 py-3 fw-bold text-white" id="tab-todas" data-bs-toggle="tab" data-bs-target="#panel-tabla" type="button" role="tab" onclick="cambiarTab('todas')">
                            <i class="fas fa-layer-group me-2"></i>Todas las Ventas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-0 border-0 py-3 fw-bold text-white" id="tab-pos" data-bs-toggle="tab" data-bs-target="#panel-tabla" type="button" role="tab" onclick="cambiarTab('pos')">
                            <i class="fas fa-store me-2 text-info"></i>Mostrador (POS)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-0 border-0 py-3 fw-bold text-white" id="tab-taller" data-bs-toggle="tab" data-bs-target="#panel-tabla" type="button" role="tab" onclick="cambiarTab('taller')">
                            <i class="fas fa-tools me-2 text-warning"></i>Taller Mecánico
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-0 border-0 py-3 fw-bold text-white" id="tab-credito" data-bs-toggle="tab" data-bs-target="#panel-tabla" type="button" role="tab" onclick="cambiarTab('credito')">
                            <i class="fas fa-hand-holding-usd me-2 text-danger"></i>Cuentas por Cobrar
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="tab-content" id="ventasTabContent">
                <div class="tab-pane fade show active" id="panel-tabla" role="tabpanel">
                    <div class="card-body p-0">
                        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-muted" id="lbl_titulo_tabla">Mostrando: Todas las Ventas</h6>
                            <button class="btn btn-sm btn-outline-secondary" onclick="imprimirTabla()"><i class="fas fa-print me-1"></i> Imprimir Vista</button>
                        </div>
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0 text-center" id="tabla_ventas_print">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>N° Factura</th>
                                        <th>Fecha</th>
                                        <th class="text-start">Cliente</th>
                                        <th>NCF</th>
                                        <th>Origen</th>
                                        <th>Estado</th>
                                        <th class="text-end pe-4">Monto Total</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoTablaVentas">
                                    <tr>
                                        <td colspan="7" class="py-5 text-muted fw-bold">Presione "Generar" para ver los datos.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    /* CAMBIO 3: PESTAÑA SELECCIONADA TOTALMENTE BLANCA Y TEXTO OSCURO */
    .nav-tabs .nav-link { opacity: 0.7; transition: all 0.3s; color: #fff; }
    .nav-tabs .nav-link:hover { opacity: 1; background-color: rgba(255,255,255,0.1); }
    .nav-tabs .nav-link.active { 
        opacity: 1; 
        background-color: #ffffff !important; 
        color: #212529 !important; 
        border-top: 4px solid #198754 !important; 
        box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
    }
</style>

<script src="/Taller/Taller-Mecanica/modules/Facturacion/Scripts_ReporteVentas.js"></script>

</body>
</html>