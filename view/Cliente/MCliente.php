<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2 class="mb-4">Registro de Clientes</h2>
        
        <form method="POST" action="/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="id_cliente">
            <div class="row">
            
                <div class="col-md-6 d-flex flex-column">
                    <div class="mb-3">
                        <label for="nombre" class="form-label" style="color: var(--primary-blue)">Nombres</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingrese el nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="apellido" class="form-label" style="color: var(--primary-blue)">Apellidos</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Ingrese los apellidos">
                    </div>
                    <div class="mb-3">
                        <label for="cedula_rnc" class="form-label" style="color: var(--primary-blue)">Cédula o RNC</label>
                        <input type="text" class="form-control" id="cedula_rnc" name="cedula_rnc" placeholder="00100000000" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="telefono" class="form-label" style="color: var(--primary-blue)">Teléfono de Contacto</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" placeholder="(809) 000-0000">
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label" style="color: var(--primary-blue)">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@correo.com">
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label" style="color: var(--primary-blue)">Dirección Física</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Ej. Calle 1, Sector, Santiago de los Caballeros">
                    </div>
                </div>
                
            </div>

            <div class="d-flex gap-4 mb-4 mt-0">
                <button type="submit" class="btn btn-success" style="width: 150px" id="btnMostrar">Registrar</button>
                <button type="button" class="btn btn-secondary boton-separado" style="width: 150px" onclick="limpiarFormulario()">Limpiar</button>
            </div>

            <hr class="mt-5 mb-5">

            <div class="mt-5">
                <h2>Consulta de Clientes</h2>
                <div class="mb-3 d-flex align-items-center gap-3">
    
                    <div class="input-group" style="width: 50%; min-width: 400px;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="filtro" placeholder="Escribe para filtrar...">
                    </div>

                    <div class="switch-container d-flex align-items-center gap-2 ms-2">
                        <span class="fw-bold small">Nombre</span>
                        <label class="switch">
                            <input type="checkbox" id="tipoFiltro">
                            <span class="slider"></span>
                        </label>
                        <span class="fw-bold small">Cédula/RNC</span>
                    </div>

                    <button type="button" class="btn btn-primary" id="btnBuscar">
                        Imprimir Listado
                    </button>

                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-4" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th class="p-2">Id</th>
                                <th class="p-2">Nombre Completo</th>
                                <th>Cédula/RNC</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Fecha Ingreso</th>
                                <th>Estado</th>
                                <th>Acciones</th> 
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla">
                            </tbody>
                    </table>
                </div>
                
                <nav aria-label="Navegación de páginas">
                    <ul class="pagination justify-content-center" id="pagination-container"></ul>
                </nav>
            </div>
        </form>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Cliente/Scripts_Cliente.js"></script>
</body>
</html>