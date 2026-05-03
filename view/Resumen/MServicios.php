<?php require("../../layout.php"); require("../../header.php"); ?>

<main class="contenido">
    <div class="container-fluid px-4 mt-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-tools me-2"></i>Resumen Operativo: Servicios</h2>
        <hr>

        <!-- Fila de Gráficos -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Servicios Más Realizados</div>
                    <div class="card-body"><canvas id="chartMasRealizados"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Daños más Frecuentes</div>
                    <div class="card-body"><canvas id="chartDanosFrecuentes"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Productividad por Sucursal</div>
                    <div class="card-body"><canvas id="chartSucursales"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Tabla Dinámica -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between">
                <span>Historial Reciente de Trabajos</span>
                <i class="fas fa-list"></i>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th># Orden</th>
                                <th>Servicio</th>
                                <th>Mecánico Asignado</th>
                                <th>Fecha</th>
                                <th>Sucursal</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyServicios">
                            <!-- Inyectado por JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Resumen/Scripts_MServicio.js"></script>