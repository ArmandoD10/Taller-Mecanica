<?php require("../../layout.php"); require("../../header.php"); ?>

<main class="contenido">
    <div class="container-fluid px-4 mt-4">
        <h2 class="fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-warning"></i>Resumen Operativo: Cotizaciones</h2>
        <hr>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Cotizaciones de Mayor Monto</div>
                    <div class="card-body"><canvas id="chartMontoAlto"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Crecimiento Mensual (Cant.)</div>
                    <div class="card-body"><canvas id="chartCrecimiento"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Cotizaciones por Sucursal</div>
                    <div class="card-body"><canvas id="chartSucursalesCot"></canvas></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark fw-bold">Historial de Cotizaciones Generales</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th># Cotización</th>
                                <th>Cliente</th>
                                <th>Usuario</th>
                                <th>Fecha</th>
                                <th>Sucursal</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCotizaciones"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Resumen/Scripts_MCotizacion.js"></script>