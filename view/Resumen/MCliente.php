<?php require("../../layout.php"); require("../../header.php"); ?>

<main class="contenido">
    <div class="container-fluid px-4 mt-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-users me-2 text-info"></i>Resumen Operativo: Clientes & Vehículos</h2>
        <hr>

        <!-- Fila de Gráficos -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold text-info">Top 5 Clientes (Fidelidad)</div>
                    <div class="card-body"><canvas id="chartTopClientes"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold text-info">Vehículos con Mayor Gasto (RD$)</div>
                    <div class="card-body"><canvas id="chartGastoVehiculos"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold text-info">Distribución por Marcas</div>
                    <div class="card-body"><canvas id="chartMarcas"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Sección de Cards Dinámicas -->
        <h4 class="fw-bold mb-3"><i class="fas fa-car me-2"></i>Directorio de Clientes y Vehículos</h4>
        <div class="row g-3" id="contenedorCardsClientes">
            <!-- Se carga vía JS -->
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Resumen/Scripts_MCliente.js"></script>