<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2 class="mb-4">Registro de Vehículos</h2>
        
        <form method="POST" action="/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Vehiculo.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="id_vehiculo">
            <div class="row">
            
                <div class="col-md-6 d-flex flex-column">
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--primary-blue)">Propietario (Cliente)</label>
                        <select class="form-select" id="id_cliente" name="id_cliente" required>
                            <option value="" disabled selected>Cargando clientes...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--primary-blue)">Número de Chasis (VIN)</label>
                        <input type="text" class="form-control" id="vin_chasis" name="vin_chasis" placeholder="Ej. 1HGCM82633A..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--primary-blue)">Marca</label>
                        <select class="form-select" id="id_marca" name="id_marca" required>
                            <option value="" disabled selected>Cargando marcas...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--primary-blue)">Modelo</label>
                        <input type="text" class="form-control" id="modelo" name="modelo" placeholder="Ej. Civic, Corolla" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--primary-blue)">Placa</label>
                        <input type="text" class="form-control" id="placa" name="placa" placeholder="Ej. A123456">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--primary-blue)">Color</label>
                        <select class="form-select" id="id_color" name="id_color" required>
                            <option value="" disabled selected>Cargando colores...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--primary-blue)">Año</label>
                        <input type="number" class="form-control" id="anio" name="anio" placeholder="Ej. 2019" min="1950" max="2030">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--primary-blue)">Kilometraje Actual</label>
                        <input type="number" class="form-control" id="kilometraje_actual" name="kilometraje_actual" placeholder="0">
                    </div>
                </div>
                
            </div>

            <div class="d-flex gap-4 mb-4 mt-0">
                <button type="submit" class="btn btn-success" style="width: 150px" id="btnMostrar">Registrar</button>
                <button type="button" class="btn btn-secondary boton-separado" style="width: 150px" onclick="limpiarFormulario()">Limpiar</button>
            </div>

            <hr class="mt-5 mb-5">

            <div class="mt-5">
                <h2>Consulta de Vehículos</h2>
                <div class="mb-3 d-flex align-items-center gap-3">
                    <div class="input-group" style="width: 50%; min-width: 400px;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="filtro" placeholder="Escribe para filtrar...">
                    </div>

                    <div class="switch-container d-flex align-items-center gap-2 ms-2">
                        <span class="fw-bold small">Chasis/Placa</span>
                        <label class="switch">
                            <input type="checkbox" id="tipoFiltro">
                            <span class="slider"></span>
                        </label>
                        <span class="fw-bold small">Cliente</span>
                    </div>

                    <button type="button" class="btn btn-primary">Imprimir Listado</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-4" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th class="p-2">Id</th>
                                <th>Cliente</th>
                                <th>Chasis/VIN</th>
                                <th>Placa</th>
                                <th>Vehículo</th>
                                <th>Año/Km</th>
                                <th>Estado</th>
                                <th>Acciones</th> 
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla"></tbody>
                    </table>
                </div>
                
                <nav aria-label="Navegación de páginas">
                    <ul class="pagination justify-content-center" id="pagination-container"></ul>
                </nav>
            </div>
        </form>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Vehiculo/Scripts_Vehiculo.js"></script>
</body>
</html>