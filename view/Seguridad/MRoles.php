<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">

        <h2 class="mb-4">Configuración de Roles y Permisos</h2>

        <!-- 🧾 FORMULARIO -->
        <form id="formulario">

            <input type="hidden" id="id_nivel">

            <div class="card shadow p-4 mb-4">

                <div class="row">

                    <!-- 🧩 NOMBRE DEL NIVEL -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold" style="color: var(--primary-blue)">
                            Nombre del Nivel
                        </label>
                        <input type="text" class="form-control" id="nombre" placeholder="Ej: Administrador">
                    </div>

                </div>

                <!-- 📦 MÓDULOS -->
                <div class="mt-4">
                    <h5 class="fw-bold">Módulos Disponibles</h5>

                    <div id="contenedorModulos" class="row mt-3"
                        style="max-height: 250px; overflow-y: auto; border:1px solid #ddd; border-radius:10px; padding:15px; background:#f9f9f9;">

                        <!-- CHECKBOX DINÁMICOS -->
                    </div>
                </div>

                <!-- 🔘 BOTONES -->
                <div class="mt-4 d-flex gap-2">
                    <button type="button" class="btn btn-success" id="btnGuardar" onclick="guardarNivel()">
                        Guardar
                    </button>

                    <button type="button" class="btn btn-secondary" onclick="limpiar()">
                        Limpiar
                    </button>
                </div>

            </div>

        </form>

        <!-- 📊 TABLA -->
        <div class="card shadow p-4">

            <h4 class="mb-3">Listado de Niveles</h4>

            <!-- 🔍 FILTRO -->
            <div class="mb-3 d-flex align-items-center gap-2">
                <input type="text" class="form-control" id="filtro" placeholder="Filtrar nivel...">
            </div>

            <!-- 📋 TABLA -->
            <div class="table-responsive">
                <table class="table table-hover table-striped" id="tablaNiveles">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="cuerpo-niveles">
                        <!-- DINÁMICO -->
                    </tbody>
                </table>
            </div>

            <!-- 📄 PAGINACIÓN -->
            <nav>
                <ul class="pagination justify-content-center" id="pagination-container"></ul>
            </nav>

        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/modules/Seguridad/Scripts_Permiso.js"></script>

</body>
</html>