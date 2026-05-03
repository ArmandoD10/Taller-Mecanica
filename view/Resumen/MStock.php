<?php 
require("../../layout.php"); 
require("../../header.php"); 
?>

<main class="contenido">
    <div class="container-fluid px-4 mt-4">
        <h2 class="fw-bold"><i class="fas fa-chart-line me-2"></i>Resumen Operativo: Gestión de Stock</h2>
        <hr>

        <!-- Fila de Gráficos Superiores -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Distribución General de Artículos</div>
                    <div class="card-body"><canvas id="chartPieStock"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Top 5 Más Vendidos (Histórico)</div>
                    <div class="card-body"><canvas id="chartBarrasVentas"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">Análisis de Precios (Extremos)</div>
                    <div class="card-body"><canvas id="chartPreciosExtremos"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Sección de Cards por Articulo y Sucursal -->
        <h4 class="fw-bold mb-3">Desglose de Stock por Sucursales</h4>
        <div class="row g-3" id="contenedorCardsSucursales">
            <!-- Se llena con JS -->
        </div>
    </div>




<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Resumen/Scripts_MStock.js"></script>