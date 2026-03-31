<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mb-4">Reporte de Cuentas por Cobrar (Deudas)</h2>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm p-4 d-flex flex-row align-items-center justify-content-between" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border-radius: 15px;">
                    <div>
                        <h5 class="mb-1 text-uppercase fw-bold opacity-75">Total de Dinero en la Calle</h5>
                        <h1 class="mb-0 fw-bold display-5" id="txt_gran_total">$0.00</h1>
                    </div>
                    <div>
                        <i class="fas fa-file-invoice-dollar opacity-50" style="font-size: 4rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2" style="border-bottom: 2px solid #eee;">
                <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-users me-2"></i>Cartera de Clientes Deudores</h5>
                <button class="btn btn-success" onclick="imprimirReporte()">
                    <i class="fas fa-print me-2"></i>Imprimir Reporte
                </button>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="filtro" placeholder="Buscar por cliente o cédula/RNC...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Cliente</th>
                            <th>Cliente / Empresa</th>
                            <th>Cédula/RNC</th>
                            <th>Teléfono</th>
                            <th>Créditos Activos</th>
                            <th>Vencimiento más antiguo</th>
                            <th class="text-end">Total Adeudado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla">
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Cargando deudores...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Cliente/Scripts_ConsultaDeuda.js"></script>
</body>
</html>