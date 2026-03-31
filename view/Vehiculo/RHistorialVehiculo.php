<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mb-4">Historial de Vehículo</h2>
        
        <div class="card p-4 shadow-sm border-0 mb-4 bg-light">
            <div class="row align-items-end">
                <div class="col-md-8 position-relative">
                    <label class="form-label fw-bold text-primary"><i class="fas fa-search me-2"></i>Buscar Vehículo</label>
                    <input type="text" class="form-control form-control-lg" id="buscador_vehiculo" placeholder="Escriba la placa o el número de chasis (VIN)..." autocomplete="off" style="text-transform: uppercase;">
                    <input type="hidden" id="id_vehiculo">
                    <ul class="list-group position-absolute w-100 d-none shadow-sm" id="lista_vehiculos" style="z-index: 1000; max-height: 250px; overflow-y: auto; top: 100%;"></ul>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-secondary btn-lg w-100" onclick="limpiarConsulta()">
                        <i class="fas fa-eraser me-2"></i>Limpiar Búsqueda
                    </button>
                </div>
            </div>
        </div>

        <div id="panel_resumen" class="card shadow-sm border-0 mb-4 d-none" style="border-left: 5px solid var(--primary-blue) !important;">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h5 class="text-primary fw-bold mb-3"><i class="fas fa-car me-2"></i>Datos del Vehículo</h5>
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr><th width="35%" class="text-muted">Marca y Modelo:</th><td id="txt_vehiculo" class="fw-bold fs-5"></td></tr>
                                <tr><th class="text-muted">Año:</th><td id="txt_anio"></td></tr>
                                <tr><th class="text-muted">Color:</th><td id="txt_color"></td></tr>
                                <tr><th class="text-muted">Placa:</th><td id="txt_placa" class="fw-bold text-primary"></td></tr>
                                <tr><th class="text-muted">Chasis (VIN):</th><td id="txt_chasis" class="fw-bold"></td></tr>
                                <tr><th class="text-muted">Kilometraje:</th><td id="txt_km"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6" style="border-left: 1px solid #eee;">
                        <h5 class="text-primary fw-bold mb-3 ps-3"><i class="fas fa-user-tie me-2"></i>Datos del Propietario</h5>
                        <table class="table table-borderless table-sm mb-0 ps-3">
                            <tbody>
                                <tr><th width="35%" class="text-muted ps-3">Nombre/Razón:</th><td id="txt_propietario" class="fw-bold"></td></tr>
                                <tr><th class="text-muted ps-3">Documento:</th><td id="txt_documento"></td></tr>
                                <tr><th class="text-muted ps-3">Teléfono:</th><td id="txt_telefono"></td></tr>
                            </tbody>
                        </table>
                        
                        <div class="mt-4 ps-3">
                            <button class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-plus-circle me-1"></i>Crear Orden de Servicio</button>
                            <button class="btn btn-outline-success btn-sm"><i class="fas fa-edit me-1"></i>Actualizar Kilometraje</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 p-4">
            <h5 class="mb-4" style="color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                <i class="fas fa-tools me-2"></i>Historial de Servicios y Reparaciones
            </h5>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No. Orden</th>
                            <th>Fecha de Ingreso</th>
                            <th>Descripción del Servicio</th>
                            <th>Técnico / Mecánico</th>
                            <th>Monto Facturado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Seleccione un vehículo para ver su historial.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Vehiculo/Scripts_HistorialVehiculo.js"></script>
</body>
</html>