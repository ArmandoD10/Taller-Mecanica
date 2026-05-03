<?php require("../../layout.php"); require("../../header.php"); ?>

<main class="contenido">
    <div class="container-fluid px-4 mt-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-store-alt me-2 text-primary"></i>Resumen Operativo: Sucursales</h2>
        <hr>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Volumen de Ventas (RD$)</div>
                    <div class="card-body"><canvas id="chartVentasSedes"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Órdenes Realizadas (Cant.)</div>
                    <div class="card-body"><canvas id="chartOrdenesSedes"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Ticket Promedio por Cliente</div>
                    <div class="card-body"><canvas id="chartTicketPromedio"></canvas></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white fw-bold">Ranking de Productividad por Sede</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Sede / Ubicación</th>
                                <th>Total Facturas</th>
                                <th>Total Órdenes</th>
                                <th>Ingresos Acumulados</th>
                                <th>Rendimiento</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRanking" class="text-center"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Resumen/Scripts_MSucursal.js"></script>