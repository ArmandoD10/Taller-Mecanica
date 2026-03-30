<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2 class="mb-4">Gestion de Departamentos</h2>
        <!-- <h3 class="mb-4">_______________________________________________________________________________________________</h3> -->
        
        <form method="POST" action="/Taller/Taller-Mecanica/modules/RRHH/Archivo_DepartamentoP.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="id_departamento">
            <div class="row">
            
            <h3 class="titulo-seccion">Datos Departamento</h3>
            <div class="col-md-6 d-flex flex-column">
                <div class="mb-3 mt-2">
                    <label for="primer_nombre" class="form-label" style="color: var(--primary-blue)">Nombre otorgado al Departamento</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingrese el nombre del departamento">
                </div>
                <div class="mb-3">
                    <label for="cantidad_dias" class="form-label" style="color: var(--primary-blue)">Cantidad de Días laborables</label>
                    <input type="number" 
                        class="form-control" 
                        id="cantidad_dias" 
                        name="cantidad_dias" 
                        value="1" 
                        min="1" 
                        max="7" 
                        placeholder="0">
                </div>
            </div> 

            <div class="col-md-6">
                
                <div class="col-md-6 mb-3">
                    <label for="hora_entrada" class="form-label" style="color: var(--primary-blue)">Hora de Entrada</label>
                    <input type="time" class="form-control" id="hora_entrada" name="hora_entrada" value="08:00">
                </div> 

                <div class="col-md-6 mb-3">
                    <label for="hora_salida" class="form-label" style="color: var(--primary-blue)">Hora de Salida</label>
                    <input type="time" class="form-control" id="hora_salida" name="hora_salida" value="17:30">
                </div>

                <div class="mb-3 d-none" id="contenedor-estado">
                            <label class="form-label" style="color: var(--primary-blue); display: block;">Estado</label>
                            
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="estado" id="activo" value="activo" checked>
                                <label class="form-check-label" for="activo">Activo</label>
                            </div>
                            
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="estado" id="inactivo" value="inactivo">
                                <label class="form-check-label" for="inactivo">Inactivo</label>
                            </div>
                        </div>
    
            </div>

            
            <div class="d-flex gap-4 mb-4 mt-4">
                <button type="submit" class="btn btn-success" style="width: 150px" id="btnGuardar">Registrar</button>
                <button type="button" class="btn btn-secondary boton-separado" style="width: 150px" onclick="limpiarFormulario()">Limpiar</button>
            </div>

            <div class="mt-5">
                <h2>Consulta de Registros</h2>
               <div class="mb-3 d-flex align-items-center gap-3">
                    <div class="input-group" style="width: 40%; min-width: 300px;">
                        <span class="input-group-text bg-white border-end-0">
                            <img src="/Restaurante AD/Imagenes/lupa.png" alt="Lupa" class="icono-filtro" style="width: 20px;">
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="filtro" placeholder="Escribe para filtrar...">
                    </div>

                    <div class="d-flex align-items-center gap-3 ms-2">
                        <span class="fw-bold small">Filtrar por:</span>
                        
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="radioId" value="id_departamento" checked>
                            <label class="form-check-label small" for="radioId">Codigo</label>
                        </div>
                        
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="radioNombre" value="nombre">
                            <label class="form-check-label small" for="radioNombre">Nombre</label>
                        </div>

                        <button class="btn btn-primary" id="btnBuscar">
                                Imprimir Reporte
                            </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-4" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th>Codigo</th>
                                <th class="p-2">Nombre Departamento</th>
                                <th>Dias Labor</th>
                                <th>Hora Entrada</th>
                                <th>Hora Salida</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla">
                            </tbody>
                    </table>
                </div>
                <nav aria-label="Navegación de páginas">
                    <ul class="pagination justify-content-center" id="pagination-container">
                        </ul>
                </nav>
            </div>
        </form>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/RRHH/Scripts_DepartamentoP.js"></script>

</body>
</html>