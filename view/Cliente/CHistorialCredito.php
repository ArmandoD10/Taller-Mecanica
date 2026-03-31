<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mb-4">Consulta e Historial de Crédito</h2>
        
        <div class="card p-4 shadow-sm border-0 mb-4 bg-light">
            <div class="row align-items-end">
                <div class="col-md-8 position-relative">
                    <label class="form-label fw-bold text-primary"><i class="fas fa-search me-2"></i>Buscar Cliente</label>
                    <input type="text" class="form-control form-control-lg" id="buscador_cliente" placeholder="Escriba el nombre o cédula/RNC del cliente..." autocomplete="off">
                    <input type="hidden" id="id_cliente">
                    <ul class="list-group position-absolute w-100 d-none shadow-sm" id="lista_clientes" style="z-index: 1000; max-height: 250px; overflow-y: auto; top: 100%;"></ul>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-secondary btn-lg w-100" onclick="limpiarConsulta()">
                        <i class="fas fa-eraser me-2"></i>Limpiar Búsqueda
                    </button>
                </div>
            </div>
        </div>

        <div id="panel_resumen" class="row mb-4 d-none">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-bottom: 5px solid var(--primary-blue) !important;">
                    <h6 class="text-muted fw-bold text-uppercase mb-2">Límite de Crédito</h6>
                    <h2 class="text-primary mb-0" id="txt_limite">$0.00</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-bottom: 5px solid #dc3545 !important;">
                    <h6 class="text-muted fw-bold text-uppercase mb-2">Deuda Actual</h6>
                    <h2 class="text-danger mb-0" id="txt_deuda">$0.00</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-bottom: 5px solid #198754 !important;">
                    <h6 class="text-muted fw-bold text-uppercase mb-2">Crédito Disponible</h6>
                    <h2 class="text-success mb-0" id="txt_disponible">$0.00</h2>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 p-4">
            <h5 class="mb-4" style="color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                <i class="fas fa-list-alt me-2"></i>Detalle de Líneas de Crédito
            </h5>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No. Crédito</th>
                            <th>Monto Aprobado</th>
                            <th>Saldo Pendiente</th>
                            <th>Fecha Aprobación</th>
                            <th>Vencimiento</th>
                            <th>Referencia</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Seleccione un cliente para ver su historial.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Cliente/Scripts_HistorialCredito.js"></script>
</body>
</html>