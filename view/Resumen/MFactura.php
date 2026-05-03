<?php require("../../layout.php"); require("../../header.php"); ?>

<main class="contenido">
    <div class="container-fluid px-4 mt-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-file-invoice me-2 text-success"></i>Resumen Operativo: Facturación</h2>
        <hr>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold text-success">Top Facturas (Monto Alto)</div>
                    <div class="card-body"><canvas id="chartFacturasAltas"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold text-success">Flujo de Caja Mensual (RD$)</div>
                    <div class="card-body"><canvas id="chartCrecimientoIngresos"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold text-success">Ingresos por Sucursal</div>
                    <div class="card-body"><canvas id="chartSucursalesFact"></canvas></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white fw-bold">Historial de Ventas Recientes</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th># Factura</th>
                                <th>Origen</th>
                                <th>Usuario</th>
                                <th>Fecha</th>
                                <th>Sucursal</th>
                                <th>Total Bruto</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyFacturas" class="text-center"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Resumen/Scripts_MFactura.js"></script>