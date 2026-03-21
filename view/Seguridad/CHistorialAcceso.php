<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2>Historial de Accesos</h2>
        <!-- <h3 class="mb-4">_______________________________________________________________________________________________</h3> -->

            <div class="mt-5">
                <h2>Consulta de Registros</h2>
                <div class="mb-3 d-flex align-items-center gap-2">
                    <input type="text" class="form-control" id="filtro" placeholder="Escribe para filtrar...">
                    <img src="/Restaurante AD/Imagenes/lupa.png" alt="Icono de filtro" class="icono-filtro">
                    <!-- SWITCH -->
                    <div class="switch-container">
                        <span id="label-left">ID</span>

                        <label class="switch">
                            <input type="checkbox" id="tipoFiltro">
                            <span class="slider"></span>
                        </label>

                        <span id="label-right">Username</span>
                    </div>
                    
                    <button class="btn btn-primary" id="btnBuscar">
                        Imprimir Reporte
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-4" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th class="p-2">#</th>
                                <th class="p-2">Id Usuario</th>
                                <th>Username</th>
                                <th>Ip Equipo</th>
                                <th>Fecha</th>
                                <th>Accion</th>
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

<script src="/Taller/Taller-Mecanica/modules/Seguridad/Scripts_Hitorial_Acceso.js"></script>


</body>
</html>