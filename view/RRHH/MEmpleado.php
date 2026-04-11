<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2><i class="fas fa-id-badge me-2 text-primary"></i>Registro de Empleados</h2>
        <!-- <h3 class="mb-4">_______________________________________________________________________________________________</h3> -->
        
        <form method="POST" action="/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Empleado.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="id_empleado">
            <div class="row">
            
            <h3 class="titulo-seccion">Datos Personales</h3>
            <div class="col-md-6 d-flex flex-column">
                <div class="mb-3">
                    <label for="primer_nombre" class="form-label" style="color: var(--primary-blue)">Primer Nombre</label>
                    <input type="text" class="form-control" id="nombre1" name="nombre1" placeholder="Ingrese el primer nombre" disabled>
                </div>
                <div class="mb-3">
                    <label for="segundo_nombre" class="form-label" style="color: var(--primary-blue)">Segundo Nombre</label>
                    <input type="text" class="form-control" id="nombre2" name="nombre2" placeholder="Ingrese el segundo nombre" disabled>
                </div>
                <div class="mb-3">
                    <label for="apellido_p" class="form-label" style="color: var(--primary-blue)">Apellido Paterno</label>
                    <input type="text" class="form-control" id="apellido_p" name="apellido_p" placeholder="Ingrese el apellido paterno" disabled>
                </div>
                <div class="mb-3">
                    <label for="apellido_m" class="form-label" style="color: var(--primary-blue)">Apellido Materno</label>
                    <input type="text" class="form-control" id="apellido_m" name="apellido_m" placeholder="Ingrese el apellido materno" disabled>
                </div>
                <div class="mb-3">
                    <label for="sexo" class="form-label" style="color: var(--primary-blue)">Sexo</label>
                    <select class="form-select" id="sexo" name="sexo" disabled>
                        <option value="" selected disabled>Seleccione una opción</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                    </select>
                </div>
            
            </div> 

            <div class="col-md-6">
                <input type="hidden" id="id_persona_capturado" name="id_persona_capturado">

<label class="form-label" style="color: var(--primary-blue)">No de Documento</label>
<div class="input-group">
    <input type="text" class="form-control" id="cedula" name="cedula" placeholder="000-0000000-0">
    <button class="btn btn-primary" type="button" id="btnBuscarCedula">
        <i class="fas fa-search"></i>
    </button>
</div>
                <div class="mb-3">
                    <label for="emailempleado" class="form-label" style="color: var(--primary-blue)">Telefono Personal</label>
                    <input type="text" class="form-control f-telefono" id="telefono" name="telefono" placeholder="Ingrese el teléfono" disabled>
                </div>  
                <div class="mb-3">
                    <label for="nivel" class="form-label" style="color: var(--primary-blue)">fecha de Nacimiento</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" disabled>
                </div>
                <div class="mb-3">
                    <label for="nacionalidad" class="form-label" style="color: var(--primary-blue)">Nacionalidad</label>
                    <select class="form-select" id="nacionalidad" name="nacionalidad" disabled>
                        <option value="" selected disabled>Selecciona una opción</option>
                    </select>
                </div>
                 <div class="mb-3">
                    <label for="correo" class="form-label" style="color: var(--primary-blue)">Correo Electronico</label>
                    <input type="email" class="form-control" id="correo" name="correo" placeholder="Ingrese el correo electrónico" disabled>
                </div>
            </div>

            <!-- ================= DATOS Direccion ================= -->
                <h3 class="mt-4 titulo-seccion">Datos de Ubicacion</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="puesto" class="form-label" style="color: var(--primary-blue)">Pais</label>
                            <select class="form-select" id="pais" name="pais" disabled>
                                <option value="" selected disabled>Selecciona una opción</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sueldo" class="form-label" style="color: var(--primary-blue)">Provincia</label>
                            <select class="form-select" id="provincia" name="provincia" disabled>
                                <option value="" selected disabled>Selecciona una opción</option>
                            </select>
                        </div>
                    </div>
                
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="sueldo" class="form-label" style="color: var(--primary-blue)">Ciudad</label>
                            <select class="form-select" id="ciudad" name="ciudad" disabled>
                                <option value="" selected disabled>Selecciona una opción</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="direccion" class="form-label" style="color: var(--primary-blue)">Direccion</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Ingrese la dirección" disabled>
                        </div>
                    </div>

                </div>

            <!-- ================= DATOS LABORALES ================= -->
                <h3 class="mt-4 titulo-seccion">Datos del Empleado</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="puesto" class="form-label" style="color: var(--primary-blue)">Puesto</label>
                            <select class="form-select" id="puesto" name="puesto">
                                <option value="" selected disabled>Selecciona una opción</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sueldo" class="form-label" style="color: var(--primary-blue)">Sueldo Base</label>
                            <select class="form-select" id="sueldo" name="sueldo">
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
                        <h3 class="mt-3 titulo-seccion">Contacto de Emergencia</h3>
                        <div class="mb-3">
                            <label for="emailempleado" class="mt-2 form-label" style="color: var(--primary-blue)">Telefono a llamar</label>
                            <input type="text" class="form-control f-telefono" id="telefono_e" name="telefono_e" placeholder="Ingrese el teléfono">
                        </div>  

                        <div class="mb-3">
                            <label for="primer_nombre" class="mt-0 form-label" style="color: var(--primary-blue)">Nombre del contacto</label>
                            <input type="text" class="form-control" id="nombre_e" name="nombre_e" placeholder="Ingrese el primer nombre">
                        </div> 
                    </div>

                </div>
               

            
            <div class="d-flex gap-4 mb-4 mt-0">
               <button type="button" class="btn btn-success" style="width: 150px" id="btnGuardar" onclick="enviarFormulario()">Registrar</button>
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
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="radioId" value="id_empleado" checked>
                            <label class="form-check-label small" for="radioId">ID</label>
                        </div>
                        
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="radioNombre" value="nombre">
                            <label class="form-check-label small" for="radioNombre">Nombre</label>
                        </div>
                        
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="radioCedula" value="cedula">
                            <label class="form-check-label small" for="radioCedula">Cédula</label>
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
                                <th class="p-2">Id Empleado</th>
                                <th class="p-2">1 Nombre</th>
                                <th>Apellido P</th>
                                <th>Cedula</th>
                                <th>Puesto</th>
                                <th>Sueldo</th>
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

<script src="/Taller/Taller-Mecanica/modules/RRHH/Scripts_Empleado.js"></script>

</body>
</html>