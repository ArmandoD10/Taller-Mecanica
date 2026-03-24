<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2 class="mb-4">Registro de Usuarios</h2>
        <!-- <h3 class="mb-4">_______________________________________________________________________________________________</h3> -->
        
        <form method="POST" action="/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Usuario.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="id_usuario">
            <div class="row">
            
            <div class="col-md-6 d-flex flex-column">
                <div class="mb-3">
                    <label for="nombre" class="form-label" style="color: var(--primary-blue)">Nombre de Usuario</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingrese el nombre">
                </div>
                <div class="mb-3">
                    <label for="contrasena" class="form-label" style="color: var(--primary-blue)">Contrasena</label>
                    <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="xxxxxxxx">
                </div> 

                <div class="mb-3">
                    <label for="telefono" class="form-label" style="color: var(--primary-blue)">Repetir Contrasena</label>
                    <input type="password" class="form-control" id="contra2" name="contra2" placeholder="xxxxxxxxx">
                </div> 
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="emailempleado" class="form-label" style="color: var(--primary-blue)">Correo Organizativo</label>
                    <input type="email" class="form-control" id="correo" name="correo" placeholder="Ingrese el correo">
                </div>  
                <div class="mb-3">
                    <label for="nivel" class="form-label" style="color: var(--primary-blue)">Nivel de Acceso</label>
                    <select class="form-select" id="nivel" name="nivel">
                        <option value="" selected disabled>Selecciona una opción</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="interfaz" class="form-label" style="color: var(--primary-blue)">Interfaz de Usuario</label>
                    <select class="form-select" id="interfaz" name="interfaz">
                        <option value="" selected disabled>Selecciona una opción</option>
                        <option value="Movil">Movil</option>
                        <option value="Escritorio">Desktop</option>
                    </select>
                </div>
            </div>
            
        </div>
            <div class="d-flex gap-4 mb-4 mt-0">
                <button type="submit" class="btn btn-success" style="width: 150px" id="btnMostrar">Registrar</button>
                <button type="button" class="btn btn-secondary boton-separado" style="width: 150px" onclick="limpiarFormulario()">Limpiar</button>
            </div>

            <div class="mt-5">
                <h2>Consulta de Registros</h2>
               <div class="mb-3 d-flex align-items-center gap-3">
    
                    <div class="input-group" style="width: 50%; min-width: 400px;">
                        <span class="input-group-text bg-white border-end-0">
                            <img src="/Restaurante AD/Imagenes/lupa.png" alt="Lupa" class="icono-filtro">
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="filtro" placeholder="Escribe para filtrar...">
                    </div>

                    <div class="switch-container d-flex align-items-center gap-2 ms-2">
                        <span class="fw-bold small">Username</span>
                        <label class="switch">
                            <input type="checkbox" id="tipoFiltro">
                            <span class="slider"></span>
                        </label>
                        <span class="fw-bold small">Nivel</span>
                    </div>

                    <button class="btn btn-primary" id="btnBuscar">
                        Imprimir Reporte
                    </button>

                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-4" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th class="p-2">Id</th>
                                <th class="p-2">username</th>
                                <th>nivel</th>
                                <th>interfaz</th>
                                <th>correo</th>
                                <th>fecha_ingreso</th>
                                <th>Estado</th>
                                <th>Acciones</th> 
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

<script src="/Taller/Taller-Mecanica/modules/Seguridad/Scripts_Usuario.js"></script>

</body>
</html>