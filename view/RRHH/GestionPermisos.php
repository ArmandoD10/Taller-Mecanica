<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4 py-3">

        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-kpi border-info">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Empleados Totales</div>
                            <div class="h3 mb-0 fw-bold" id="kpi_totales">0</div>
                        </div>
                        <div class="icon-circle bg-info-light"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-kpi border-danger">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Permisos Activos</div>
                            <div class="h3 mb-0 fw-bold" id="kpi_activos">0</div>
                        </div>
                        <div class="icon-circle bg-danger-light"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-kpi border-success">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">De Vacaciones</div>
                            <div class="h3 mb-0 fw-bold" id="kpi_vacaciones">0</div>
                        </div>
                        <div class="icon-circle bg-success-light"><i class="fas fa-sun"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-kpi border-warning">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">Otros..</div>
                            <div class="h3 mb-0 fw-bold" id="kpi_otros">0</div>
                        </div>
                        <div class="icon-circle bg-warning-light"><i class="fas fas fa-clipboard-list"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">Control de Permisos y Vacaciones</h5>
                <button class="btn btn-primary shadow-sm" id="btnNuevaSolicitud" onclick="toggleForm()">
                    <i class="fas fa-plus me-2"></i>Nueva Solicitud
                </button>
            </div>
            
            <div class="card-body">
                <div id="formRegistroPermiso" class="d-none bg-light p-4 rounded border mb-4">
                    <div class="row mb-3">
                        <div class="col-md-9">
                            <label class="form-label fw-bold small">Buscar Empleado (Nombre, ID o Cedula)</label>
                            <div class="input-group">
                                <input type="text" id="busquedaEmp" class="form-control" placeholder="Ej: 001, Amauris o ###-#######-#...">
                                <button class="btn btn-dark" type="button" onclick="buscarEmpleadoReal()">Buscar</button>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-danger px-5" onclick="toggleForm()">Cancelar</button>
                        </div>
                    </div>

                    <div id="resultadoBusqueda" class="d-none card border-primary mb-3 shadow-sm">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <div class="col-auto"><i class="fas fa-id-card fa-2x text-primary"></i></div>
                                <div class="col">
                                    <span class="d-block fw-bold" id="res_nombre">---</span>
                                    <span class="text-muted small">ID: <b id="res_id">0</b> | Usuario: <b id="res_user">---</b> | Puesto: <b id="res_puesto">---</b></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="camposSolicitud" class="d-none row g-3 pt-2 border-top">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tipo de Permiso</label>
                            <select class="form-select" id="tipoPermiso">
                                </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Desde</label>
                            <input type="date" id="fecha_inicio" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Hasta</label>
                            <input type="date" id="fecha_fin" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Motivo Detallado</label>
                            <textarea id="motivo_texto" class="form-control" rows="2" placeholder="Describa el motivo..."></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-success px-5" onclick="guardarPermisoReal()">Guardar Permiso</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tablaPermisos">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Empleado</th>
                                <th>Tipo</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPermisos">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/RRHH/Scripts_Gestion_Permiso.js"></script>

</body>
</html>