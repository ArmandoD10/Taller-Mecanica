<?php require("../../layout.php"); require("../../header.php"); ?>

<main class="contenido">
    <div class="container-fluid px-4 mt-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 text-center">
                <img src="../../img/logo.png" alt="Logo Taller" style="max-width: 180px;" class="mb-2">
                <h3 class="fw-bold text-dark"><i class="fas fa-address-book me-2"></i>Directorio de Empleados</h3>
            </div>
            
            <div class="card-body px-4">
                <!-- Buscador Triple con Botón -->
                <div class="row g-2 mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">CÓDIGO / USUARIO</label>
                        <input type="text" id="filtroCodigo" class="form-control" placeholder="Ej: AD001">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">NOMBRE COMPLETO</label>
                        <input type="text" id="filtroNombre" class="form-control" placeholder="Ej: Armando Diaz">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">DEPARTAMENTO</label>
                        <input type="text" id="filtroDepto" class="form-control" list="listaDeptos" placeholder="Escribir departamento...">
                        <datalist id="listaDeptos">
                            <!-- Se llena dinámicamente -->
                        </datalist>
                    </div>
                    <div class="col-md-3">
    <div class="d-flex gap-2">
        <!-- Botón Buscar -->
        <button type="button" class="btn btn-primary w-100 fw-bold" onclick="ejecutarBusqueda()">
            <i class="fas fa-search me-1"></i> BUSCAR
        </button>
        <!-- Botón Limpiar/Refrescar -->
        <button type="button" class="btn btn-outline-secondary" onclick="limpiarTodo()" title="Limpiar campos">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th>Empleado</th>
                                <th>Puesto</th>
                                <th>Departamento</th>
                                <th>Sucursal Actual</th>
                            </tr>
                        </thead>
                        <tbody id="tablaEmpleados">
                            <!-- Inicia vacía por seguridad -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ficha de Empleado -->
<div class="modal fade" id="modalEmpleado" tabindex="-1" aria-labelledby="modalEmpleadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold" id="modalEmpleadoLabel">
                    <i class="fas fa-user-circle me-2"></i>Información Básica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detalleEmpleado">
                <!-- El JavaScript inyectará el contenido aquí -->
            </div>
        </div>
    </div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Submodulos/Scripts_Maestro.js"></script>