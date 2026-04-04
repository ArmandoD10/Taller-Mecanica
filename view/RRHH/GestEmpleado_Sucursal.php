<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2 class="mb-4">Gestión de Personal por Sucursal</h2>

        <form id="formAsignacion">
            <div class="card shadow p-4 mb-4">
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Empleado (Nombre o Cédula)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="buscar_empleado" placeholder="Escriba para buscar empleado...">
                        </div>
                        <ul id="lista_empleados_res" class="list-group position-absolute shadow-sm d-none" style="z-index: 1000; width: 350px; max-height: 200px; overflow-y: auto;"></ul>
                        
                        <div id="info_empleado_seleccionado" class="mt-2 p-2 border rounded bg-light d-none">
                            <input type="hidden" id="id_empleado" name="id_empleado">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted d-block">Empleado seleccionado:</small>
                                    <h6 class="mb-0"><span id="lbl_emp_nombre"></span></h6>
                                    <small class="text-secondary">ID: <span id="lbl_emp_id"></span></small>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="deseleccionarEmpleado()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold">Asignar a Sucursal</label>
                        <select class="form-select" id="id_sucursal" required>
                            <option value="">Seleccione sucursal...</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" onclick="guardarAsignacion()">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="card shadow p-4">
            <h4 class="mb-3">Personal Activo en Sucursales</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Empleado</th>
                            <th>Sucursal</th>
                            <th>Desde</th>
                            <th>Estado</th>
                            <th style="width: 100px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-asignaciones">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/RRHH/Scripts_Gestion_Empleado.js"></script>