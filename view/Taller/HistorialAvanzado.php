<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mt-4 mb-4"><i class="fas fa-history me-2 text-dark"></i>Panel de Auditoría Operativa</h2>

        <ul class="nav nav-tabs mb-4" id="tabHistoriales" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active fw-bold text-dark" id="maq-tab" data-toggle="tab" href="#tab-maquinaria" role="tab" aria-controls="tab-maquinaria" aria-selected="true" style="cursor: pointer;">
                    <i class="fas fa-tools me-2"></i>Uso de Maquinaria
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link fw-bold text-primary" id="mec-tab" data-toggle="tab" href="#tab-mecanicos" role="tab" aria-controls="tab-mecanicos" aria-selected="false" style="cursor: pointer;">
                    <i class="fas fa-user-cog me-2"></i>Productividad de Mecánicos
                </a>
            </li>
        </ul>

        <div class="tab-content" id="tabHistorialesContent">
            <div class="tab-pane fade show active" id="tab-maquinaria" role="tabpanel" aria-labelledby="maq-tab">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tablaMaquinaria">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Maquinaria</th>
                                        <th>Orden</th>
                                        <th>Mecánico</th>
                                        <th>Servicio</th>
                                        <th>Uso (Min)</th>
                                        <th>Fecha/Hora</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoMaquinaria"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-mecanicos" role="tabpanel" aria-labelledby="mec-tab">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tablaMecanicos">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Mecánico</th>
                                        <th>N° Orden</th>
                                        <th>Servicio Realizado</th>
                                        <th>Estado</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoMecanicos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Taller/Scripts_HistorialAvanzado.js"></script>
</body>
</html>