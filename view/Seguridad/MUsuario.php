<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
    /* Centrado y visibilidad de la tabla */
    .contenido { 
        padding: 20px; 
        display: flex; 
        justify-content: center; 
    }
    .container { 
        max-width: 98% !important; /* Aprovecha casi todo el ancho para visibilidad total */
        width: 1300px; 
    }
    h2 { 
        text-align: center; 
        margin-bottom: 30px; 
        color: var(--primary-blue); 
        font-weight: 600; 
    }
    
    .table-responsive {
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        background-color: white;
        overflow-x: auto; /* Scroll horizontal solo si es estrictamente necesario */
    }

    #tabladatos th {
        background-color: #212529;
        color: white;
        text-transform: uppercase;
        font-size: 0.85rem;
        white-space: nowrap;
        text-align: center;
    }

    #tabladatos td {
        text-align: center;
        vertical-align: middle;
    }

    .icono-filtro {
        width: 20px;
        height: 20px;
    }
</style>

<main class="contenido">
    <div class="container">
        <h2>Registro de Usuarios</h2>
        
        <form method="POST" action="/modules/Seguridad/Archivo_Usuario.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="id_usuario">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nombre" class="form-label" style="color: var(--primary-blue)">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingrese el nombre">
                    </div>
                    <div class="mb-3">
                        <label for="contrasena" class="form-label" style="color: var(--primary-blue)">Contraseña</label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="xxxxxxxx">
                    </div> 
                    <div class="mb-3">
                        <label for="contra2" class="form-label" style="color: var(--primary-blue)">Repetir Contraseña</label>
                        <input type="password" class="form-control" id="contra2" name="contra2" placeholder="xxxxxxxxx">
                    </div> 
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="correo" class="form-label" style="color: var(--primary-blue)">Correo Organizativo</label>
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
                            <option value="Movil">Móvil</option>
                            <option value="Escritorio">Desktop</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3 mb-5 mt-3">
                <button type="submit" class="btn btn-success" style="width: 150px" id="btnMostrar">Registrar</button>
                <button type="button" class="btn btn-secondary" style="width: 150px" onclick="limpiarFormulario()">Limpiar</button>
            </div>

            <hr>

            <div class="mt-5">
                <h2 style="text-align: left;">Consulta de Registros</h2>
                <div class="mb-4 d-flex align-items-center gap-3">
                    <div class="position-relative" style="width: 400px;">
                        <input type="text" class="form-control pe-5" id="filtro" placeholder="Escribe para filtrar...">
                        <img src="/img/lupa.png" alt="Lupa" class="icono-filtro" style="position: absolute; right: 15px; top: 10px;">
                    </div>
                    
                    <div class="switch-container d-flex align-items-center gap-2">
                        <span>ID</span>
                        <label class="switch">
                            <input type="checkbox" id="tipoFiltro">
                            <span class="slider"></span>
                        </label>
                        <span>Username</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tabladatos">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Username</th>
                                <th>Nivel</th>
                                <th>Interfaz</th>
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
                
                <nav aria-label="Navegación" class="mt-4">
                    <ul class="pagination justify-content-center" id="pagination-container"></ul>
                </nav>
            </div>
        </form>
    </div>
</main>

<script src="../../modules/Seguridad/Scripts_Usuario.js"></script>
</body>
</html>