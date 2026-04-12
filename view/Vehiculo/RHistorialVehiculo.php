<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mt-4"><i class="fas fa-file-medical text-primary me-2"></i>Historial Clínico Vehicular</h2>
        
        <div class="card shadow-sm border-0 mb-4 bg-light p-3">
            <div class="row align-items-end">
                <div class="col-md-9 position-relative">
                    <label class="fw-bold small text-muted text-uppercase">Buscar por Placa, Chasis o Cliente</label>
                    <input type="text" id="buscador_vehiculo" class="form-control form-control-lg border-primary shadow-sm" placeholder="Escriba aquí..." autocomplete="off">
                    <ul id="lista_vehiculos" class="list-group position-absolute w-100 d-none shadow-lg" style="z-index: 1050; max-height: 300px; overflow-y: auto;"></ul>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-secondary btn-lg w-100 fw-bold" onclick="limpiarConsulta()">
                        <i class="fas fa-broom me-2"></i>LIMPIAR
                    </button>
                </div>
            </div>
        </div>

        <div id="panel_resumen" class="card shadow-sm border-0 mb-4 d-none border-start border-primary border-5">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <h5 class="text-primary fw-bold border-bottom pb-2">FICHA TÉCNICA</h5>
                        <p class="mb-1"><strong>Vehículo:</strong> <span id="txt_vehiculo" class="fs-5"></span></p>
                        <p class="mb-1"><strong>Placa:</strong> <span id="txt_placa" class="badge bg-dark fs-6"></span></p>
                        <p class="mb-1"><strong>VIN/Chasis:</strong> <span id="txt_chasis" class="text-muted"></span></p>
                        <p class="mb-0"><strong>Kilometraje:</strong> <span id="txt_km"></span></p>
                    </div>
                    <div class="col-md-6 ps-4">
                        <h5 class="text-primary fw-bold border-bottom pb-2">PROPIETARIO</h5>
                        <h4 id="txt_propietario" class="fw-bold mb-1"></h4>
                        <p class="mb-1"><strong>Cédula:</strong> <span id="txt_documento"></span></p>
                        <p class="mb-0"><strong>Teléfono:</strong> <span id="txt_telefono" class="text-success fw-bold"></span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-tools me-2"></i>INTERVENCIONES REALIZADAS</span>
                <button class="btn btn-sm btn-outline-light d-none" id="btn_imprimir_full" onclick="prepararImpresion()">
                    <i class="fas fa-print me-1"></i> IMPRIMIR REPORTE COMPLETO
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>Falla Reportada (Sínitoma)</th>
                            <th>Servicio / Solución</th>
                            <th>Mecánico Principal</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla">
                        <tr><td colspan="6" class="text-center py-5 text-muted">Realice una búsqueda para ver el historial clínico.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalImpresion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Reporte Clínico Vehicular</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="area_impresion">
                    </div>
                <div class="modal-footer">
                    <button class="btn btn-primary w-100 fw-bold" onclick="ejecutarImpresion()">
                        <i class="fas fa-print me-2"></i> CONFIRMAR IMPRESIÓN
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../modules/Vehiculo/Scripts_HistorialVehiculo.js"></script>