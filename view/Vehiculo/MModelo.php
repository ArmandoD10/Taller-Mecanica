<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2><i class="fas fa-list me-2 text-primary"></i>Gestión de Modelos de vehiculos</h2>
        
        <form method="POST" action="/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Modelo.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="id_modelo">
            <div class="card p-4 shadow-sm mb-4">
                <div class="row mx-0">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold" style="color: var(--primary-blue)">Nombre del Modelo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. Corolla, Civic, F-150" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold" style="color: var(--primary-blue)">Marca del Modelo</label>
                                <select class="form-select" id="id_marca" name="id_marca" required>
                                    <option value="" disabled selected>Cargando marcas...</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="fecha_lanzamiento" class="form-label fw-bold" style="color: var(--primary-blue)">Fecha de Lanzamiento</label>
                                <input type="date" class="form-control" id="fecha_lanzamiento" name="fecha_lanzamiento">
                            </div>
                            
                            <div class="col-12 d-flex gap-3 mt-2">
                                <button type="submit" class="btn btn-success" style="width: 150px" id="btnMostrar">Registrar</button>
                                <button type="button" class="btn btn-secondary" style="width: 150px" onclick="limpiarFormulario()">Limpiar</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 d-none d-md-flex align-items-center justify-content-center border-start">
                        <img src="/Taller/Taller-Mecanica/img/civic.webp" alt="Ilustración Modelo" class="img-fluid" style="max-height: 180px; opacity: 0.8;">
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <h2>Marcas Registradas</h2>
                <div class="mb-3 d-flex align-items-center gap-3">
                    <div class="input-group" style="width: 50%; min-width: 400px;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="filtro" placeholder="Escribe para buscar marca...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-4" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th class="p-2">Id</th>
                                <th>Nombre</th>
                                <th>Marca</th>
                                <th>Fecha Lanzamiento</th>
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

<script src="/Taller/Taller-Mecanica/modules/Vehiculo/Scripts_Modelo.js"></script>
</body>
</html>