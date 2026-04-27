<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2><i class="fas fa-user me-2 text-primary"></i>Registro de Clientes</h2>
        
        <form method="POST" id="formulario">
            <input type="hidden" id="id_oculto" name="id_cliente">
            
            <div class="row align-items-stretch mb-4">
                
                <div class="col-lg-6 mb-3">
                    <div class="card p-4 shadow-sm h-100 border-0">
                        <h5 class="mb-4" style="color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            <i class="fas fa-user-tie me-2"></i>Identidad del Cliente
                        </h5>
                        <input type="hidden" id="id_persona_capturado" name="id_persona_capturado">
                        <div class="mb-4 bg-light p-3 rounded">
                            <label class="form-label fw-bold me-3 mb-0">Tipo de Cliente:</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_persona" id="fisica" value="fisica" checked>
                                <label class="form-check-label" for="fisica">Persona Física</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_persona" id="juridica" value="juridica">
                                <label class="form-check-label" for="juridica">Empresa (Jurídica)</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold" id="lbl_nombre">Primer Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required disabled>
                            </div>
                            <div class="col-md-6 mb-3" id="col_apellido_p">
                                <label class="form-label fw-bold">Apellido Paterno</label>
                                <input type="text" class="form-control" id="apellido_p" name="apellido_p" disabled>
                            </div>
                            <div class="col-md-6 mb-3" id="col_apellido_m">
                                <label class="form-label fw-bold">Apellido Materno</label>
                                <input type="text" class="form-control" id="apellido_m" name="apellido_m" disabled>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold" id="lbl_cedula">Cédula</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="cedula" name="cedula" placeholder="000-0000000-0" required>
                                    <button class="btn btn-primary" type="button" id="btnBuscarCedula">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold" id="lbl_fecha">Fecha Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required disabled>
                            </div>
                            <div class="col-md-4 mb-3" id="col_sexo">
                                <label class="form-label fw-bold">Sexo</label>
                                <select class="form-select" id="sexo" name="sexo" disabled>
                                    <option value="" disabled selected>Seleccione</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3">
                    <div class="card p-4 shadow-sm h-100 border-0">
                        <h5 class="mb-4" style="color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            <i class="fas fa-map-marker-alt me-2"></i>Contacto y Ubicación
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Teléfono Principal</label>
                                <input type="text" class="form-control f-telefono" id="telefono" name="telefono" placeholder="(000) 000-0000" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@correo.com" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">País</label>
                                <select class="form-select" id="pais" name="pais" required disabled>
                                    <option value="" disabled selected>Cargando...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nacionalidad</label>
                                <select class="form-select" id="nacionalidad" name="nacionalidad" required disabled>
                                    <option value="" disabled selected>Cargando...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Provincia</label>
                                <select class="form-select" id="provincia" name="provincia" required disabled>
                                    <option value="" disabled selected>Seleccione País Primero</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ciudad</label>
                                <select class="form-select" id="ciudad" name="ciudad" required disabled>
                                    <option value="" disabled selected>Seleccione Prov. Primero</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Dirección Detallada</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" required disabled>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div> <div id="contenedor-estado" class="row card p-3 shadow-sm mx-0 mb-4 border-0 d-none">
                <div class="col-12">
                    <label class="form-label fw-bold me-4">Estado del Registro:</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="estado" id="activo" value="activo">
                        <label class="form-check-label text-success fw-bold" for="activo">Activo</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="estado" id="inactivo" value="inactivo">
                        <label class="form-check-label text-danger fw-bold" for="inactivo">Inactivo</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-4 mb-4 mt-0">
                <button type="submit" class="btn btn-success" style="width: 150px" id="btnGuardar">Registrar</button>
                <button type="button" class="btn btn-secondary" style="width: 150px" onclick="limpiarFormulario()">Limpiar</button>
            </div>

            <hr class="mt-5 mb-5">

            <div class="mt-5">
                <h2>Consulta de Clientes</h2>
                <div class="mb-4 p-3 bg-light rounded d-flex align-items-center gap-3">
                    <div class="input-group" style="width: 40%;">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="filtro" placeholder="Escribe para filtrar...">
                    </div>
                    
                    <div class="d-flex gap-3 align-items-center ms-3">
                        <span class="fw-bold small text-muted">Buscar por:</span>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="f_nombre" value="nombre" checked>
                            <label class="form-check-label small" for="f_nombre">Nombre/Razón Social</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="criterioFiltro" id="f_cedula" value="cedula">
                            <label class="form-check-label small" for="f_cedula">Cédula/RNC</label>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary fw-bold shadow-sm" onclick="generarReporteClientesPDF()">
        <i class="fas fa-print me-3"></i> Imprimir Reporte
    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-2" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th class="p-2">Id</th>
                                <th>Cliente / Empresa</th>
                                <th>Cédula/RNC</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Acciones</th> 
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla"></tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.min.js"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.plugin.autotable.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Cliente/Scripts_Cliente.js"></script>
</body>
</html>