<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container">
        <h2 class="mb-4">Registro de Vehículos</h2>
        
        <form method="POST" action="/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Vehiculo.php?action=guardar" id="formulario">
            <input type="hidden" id="id_oculto" name="sec_vehiculo">
            <div class="row">
            
                <div class="col-md-6 d-flex flex-column">
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-bold" style="color: var(--primary-blue)">Propietario (Buscar por ID o Cédula)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="buscar_cliente" placeholder="Escriba para buscar...">
                        </div>
                        <ul id="lista_clientes_res" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1000; max-height: 200px; overflow-y: auto;"></ul>
                        
                        <div id="info_cliente_seleccionado" class="mt-2 p-3 border rounded bg-light d-none">
                            <input type="hidden" id="id_cliente" name="id_cliente">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-0 small text-muted">Cliente Seleccionado:</p>
                                    <h6 class="mb-0"><span id="lbl_cli_id"></span> - <span id="lbl_cli_nombre"></span></h6>
                                    <small class="text-secondary">Documento: <span id="lbl_cli_doc"></span></small>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="deseleccionarCliente()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
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
                        <label class="form-label fw-bold" style="color: var(--primary-blue)">Modelo</label>
                        <select class="form-select" id="id_modelo_rel" name="modelo" required disabled>
                            <option value="" disabled selected>Seleccione una marca primero</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="color: var(--primary-blue)">Placa</label>
                        <input type="text" 
                            class="form-control" 
                            id="placa" 
                            name="placa" 
                            placeholder="Ej. A123456" 
                            maxlength="7" 
                            minlength="7" 
                            pattern="[A-Z0-9]{7}" 
                            title="La placa debe tener exactamente 7 caracteres (Letras mayúsculas y números)" 
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--primary-blue)">Color</label>
                        <select class="form-select" id="id_color" name="id_color" required>
                            <option value="" disabled selected>Cargando colores...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="color: var(--primary-blue)">Año</label>
                        <input type="number" 
                            class="form-control" 
                            id="anio" 
                            name="anio" 
                            placeholder="Ej. 2024" 
                            min="1950" 
                            max="2027" 
                            oninput="if(this.value.length > 4) this.value = this.value.slice(0, 4);" 
                            required>
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

                    <button type="button" class="btn btn-primary" onclick="generarReporteVehiculosPDF()">
    <i class="fas fa-file-pdf"></i> Imprimir Reporte
</button>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.min.js"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.plugin.autotable.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Vehiculo/Scripts_Vehiculo.js"></script>
</body>
</html>