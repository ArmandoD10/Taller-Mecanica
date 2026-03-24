<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
<div class="container">

    <h2 class="mb-4">Asignar Usuarios a Empleado</h2>

    <!-- 🔍 BUSCAR EMPLEADO -->
    <div class="card mb-4">
    <div class="card-body">

        <div class="d-flex align-items-center gap-3">

            <input 
                type="text" 
                id="buscarEmpleado" 
                class="form-control w-75 fs-5" 
                placeholder="Buscar empleado por nombre o ID"
            >

            <button class="btn btn-primary px-4 fs-5" id="btnBuscarEmpleado">
                Buscar
            </button>

            <button class="btn btn-secondary px-4 fs-5" onclick="limpiarTodo()">
                Limpiar
            </button>

        </div>

    </div>
</div>

    <!-- 👤 DATOS EMPLEADO -->
    <div class="card mb-4 d-none" id="cardEmpleado">
        <div class="card-body">
            <h5>Empleado Seleccionado</h5>
            <p class="fw-bold fs-5">
                ID: <span class="text-success" id="emp_id"></span>
            </p>

            <p class="fw-bold fs-5">
                Nombre: <span class="text-success" id="emp_nombre"></span>
            </p>
        </div>
    </div>

    <!-- 📋 USUARIOS ASIGNADOS -->
    <div class="card mb-4 d-none" id="cardUsuarios">
        <div class="card-body">

            <h5>Usuarios Asignados</h5>

            <div class="table-responsive">
                <table class="table table-striped table-hover mt-3">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Nivel</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaUsuarios"></tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- ➕ ASIGNAR -->
    <div class="card d-none" id="cardAsignar">
        <div class="card-body">

            <h5>Asignar Usuario</h5>

            <div class="row">

                <div class="col-md-6">
                    <label class="form-label">Usuario</label>
                    <select class="form-select" id="comboUsuarios"></select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Estado</label>

                    <!-- SWITCH -->
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="estadoSwitch" checked>
                        <label class="form-check-label" id="labelEstado">Activo</label>
                    </div>
                </div>

            </div>

            <div class="mt-4">
                <button class="btn btn-success" id="btnAsignar">➕ Asignar</button>
            </div>

        </div>
    </div>

</div>
</main>

<script src="/Taller/Taller-Mecanica/modules/RRHH/Scripts_Gestion_Usuario.js"></script>

</body>
</html>