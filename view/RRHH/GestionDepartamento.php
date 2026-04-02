<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2 class="mb-4">Gestión de Sucursales y Departamentos</h2>

        <form id="formSucursal">
            <input type="hidden" id="id_sucursal">

            <div class="card shadow p-4 mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nombre de la Sucursal</label>
                        <input type="text" class="form-control" id="nombre_sucursal" placeholder="Ej: Sucursal Central">
                    </div>
                </div>

                <div class="mt-4">
                    <h5 class="fw-bold">Asignar Departamentos</h5>
                    <div id="contenedorDeptos" class="row mt-3" 
                         style="max-height: 250px; overflow-y: auto; border:1px solid #ddd; border-radius:10px; padding:15px; background:#f9f9f9;">
                        </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="button" class="btn btn-primary" id="btnGuardar" onclick="guardarSucursal()">
                        Guardar Sucursal
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="limpiar()">
                        Limpiar
                    </button>
                </div>
            </div>
        </form>

        <div class="card shadow p-4">
            <h4 class="mb-3">Listado de Sucursales</h4>
            <div class="mb-3">
                <input type="text" class="form-control" id="filtroSucursal" placeholder="Filtrar sucursal...">
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre Sucursal</th>
                            <th style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-sucursales">
                        </tbody>
                </table>
            </div>
            <nav><ul class="pagination justify-content-center" id="pagination-sucursal"></ul></nav>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/RRHH/Scripts_Gestion_Sucursal.js"></script>