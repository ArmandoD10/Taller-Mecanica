<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2><i class="fas fa-store me-2 text-primary"></i>Registro de Sucursales</h2>
        <!-- <h3 class="mb-4">_______________________________________________________________________________________________</h3> -->
        
        <form method="POST" action="/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Sucursal.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="id_sucursal">
            <div class="row">
            
            <h3 class="titulo-seccion">Datos Sucursal</h3>
            <div class="col-md-6 d-flex flex-column">
                <div class="mb-3 mt-2">
                    <label for="primer_nombre" class="form-label" style="color: var(--primary-blue)">Nombre otorgado a la Sucursal</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingrese el nombre de la sucursal">
                </div>
                
            </div> 

            <div class="col-md-6">
                
                <div class="mb-3 mt-2">
                    <label for="emailempleado" class="form-label" style="color: var(--primary-blue)">Telefono extension de sucursal</label>
                    <input type="text" class="form-control f-telefono" id="telefono" name="telefono" placeholder="Ingrese el teléfono">
                </div>  
    
            </div>

            <!-- ================= DATOS Direccion ================= -->
                <h3 class="mt-4 titulo-seccion">Datos de Ubicacion</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3 mt-2">
                            <label for="puesto" class="form-label" style="color: var(--primary-blue)">Pais</label>
                            <select class="form-select" id="pais" name="pais">
                                <option value="" selected disabled>Selecciona una opción</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sueldo" class="form-label" style="color: var(--primary-blue)">Provincia</label>
                            <select class="form-select" id="provincia" name="provincia">
                                <option value="" selected disabled>Selecciona una opción</option>
                            </select>
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
                
                    <div class="col-md-6">
                        <div class="mb-3 mt-2">
                            <label for="sueldo" class="form-label" style="color: var(--primary-blue)">Ciudad</label>
                            <select class="form-select" id="ciudad" name="ciudad">
                                <option value="" selected disabled>Selecciona una opción</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="direccion" class="form-label" style="color: var(--primary-blue)">Direccion</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Ingrese la dirección">
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
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="radioId" value="id_sucursal" checked>
                            <label class="form-check-label small" for="radioId">Codigo</label>
                        </div>
                        
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="radioNombre" value="nombre">
                            <label class="form-check-label small" for="radioNombre">Nombre</label>
                        </div>
                        
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="radioCedula" value="ciudad">
                            <label class="form-check-label small" for="radioCiudad">Ciudad</label>
                        </div>

                       <button type="button" class="btn btn-primary shadow-sm" onclick="generarReporteSucursalesPDF()">
    <i class="fas fa-print me-1"></i> Imprimir Reporte
</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-4" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th>Codigo Sucursal</th>
                                <th class="p-2">Nombre Sucursal</th>
                                <th class="p-2">Telefono Ext</th>
                                <th>Direccion</th>
                                <th>Ciudad</th>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.min.js"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.plugin.autotable.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/RRHH/Scripts_Sucursal.js"></script>

</body>
</html>