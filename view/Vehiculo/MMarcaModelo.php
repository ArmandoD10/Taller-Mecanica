<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2 class="mb-4">Gestión de Marcas de Vehículos</h2>
        
        <form method="POST" action="/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Marca.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="id_marca">
            <div class="card p-4 shadow-sm mb-4">
                <div class="row mx-0">
                    
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold" style="color: var(--primary-blue)">Nombre de la Marca</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. Toyota, Honda, Ford" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold" style="color: var(--primary-blue)">País de Origen</label>
                                <select class="form-select" id="id_pais" name="id_pais" required>
                                    <option value="" disabled selected>Cargando países...</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold" style="color: var(--primary-blue)">Correo Corporativo (Opcional)</label>
                                <input type="email" class="form-control" id="correo" name="correo" placeholder="contacto@marca.com">
                            </div>
                            
                            <div class="col-12 d-flex gap-3 mt-2">
                                <button type="submit" class="btn btn-success" style="width: 150px" id="btnMostrar">Registrar</button>
                                <button type="button" class="btn btn-secondary" style="width: 150px" onclick="limpiarFormulario()">Limpiar</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 d-none d-md-flex align-items-center justify-content-center border-start">
                        <img src="/Taller/Taller-Mecanica/img/honda.webp" alt="Logo de Marca" class="img-fluid" style="max-height: 180px; width: auto; opacity: 0.9;">
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
                                <th>Marca</th>
                                <th>País de Origen</th>
                                <th>Correo de Contacto</th>
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

<script src="/Taller/Taller-Mecanica/modules/Vehiculo/Scripts_Marca.js"></script>
</body>
</html>