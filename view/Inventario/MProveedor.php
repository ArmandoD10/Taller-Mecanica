<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
    /* Forzamos al modal a estar por encima de la barra lateral */
    .modal { 
        z-index: 105000 !important; 
    }
    .modal-backdrop { 
        z-index: 104900 !important; 
    }
    
    /* Forzamos scroll interno para que los botones de guardar NUNCA se escondan */
    #modalProveedor .modal-body {
        max-height: 65vh; 
        overflow-y: auto;
    }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
    <h2><i class="fas fa-truck-loading me-2 text-primary"></i>Catálogo de Proveedores</h2>
    <div>
        <button class="btn btn-danger me-2" onclick="generarReporteProveedoresPDF()">
            <i class="fas fa-file-pdf me-2"></i>Imprimir Reporte
        </button>
        <button class="btn btn-primary" onclick="nuevoProveedor()">
            <i class="fas fa-plus me-2"></i>Nuevo Proveedor
        </button>
    </div>
</div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Tipo</th>
                                <th>Nombre Comercial / Empresa</th>
                                <th>Representante</th>
                                <th>RNC / Cédula</th>
                                <th>Teléfonos</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProveedor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable mt-4">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModal">Formulario de Proveedor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formProveedor">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_proveedor" name="id_proveedor">
                        <input type="hidden" id="id_persona" name="id_persona">
                        <input type="hidden" id="id_direccion" name="id_direccion">

                        <div class="row align-items-end mb-3">
                            <div class="col-md-3">
                                <label class="fw-bold text-primary fs-5 mb-2 border-bottom w-100">Clasificación Legal</label>
                                <label class="fw-bold">Tipo de Entidad <span class="text-danger">*</span></label>
                                <select class="form-select border-primary shadow-sm" name="tipo_persona" id="tipo_persona" required>
                                    <option value="Fisica">Persona Física (Independiente)</option>
                                    <option value="Juridica">Persona Jurídica (Empresa)</option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Datos Comerciales</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="fw-bold" id="lbl_nombre_comercial">Nombre Comercial <span class="text-muted fw-normal">(Opcional)</span></label>
                                        <input type="text" class="form-control border-dark" name="nombre_comercial" id="nombre_comercial" placeholder="Ej: Taller Juan">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="fw-bold" id="lbl_rnc">RNC / Cédula <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control border-dark" name="RNC" id="RNC" required placeholder="000-0000000-0" maxlength="13">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="fw-bold">Correo Electrónico</label>
                                        <input type="email" class="form-control border-dark" name="correo" id="correo" placeholder="ventas@empresa.com">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold text-primary border-bottom pb-2 mt-2 mb-3" id="titulo_seccion_persona">2. Datos del Proveedor (Persona Física)</h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="fw-bold">Nombres <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-dark" name="nombre" id="nombre_persona" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="fw-bold">Apellidos</label>
                                <input type="text" class="form-control border-dark" name="apellido_p" id="apellido_p">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="fw-bold" id="lbl_cedula_rep">Cédula Identidad</label>
                                <input type="text" class="form-control border-dark" name="cedula" id="cedula" placeholder="000-0000000-0" maxlength="13">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="fw-bold">Nacionalidad <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="nacionalidad" id="nacionalidad" required>
                                    <option value="">Seleccione país...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="fw-bold" id="lbl_fecha_nac">Fecha Nacimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control border-dark" name="fecha_nacimiento" id="fecha_nacimiento" required>
                            </div>
                        </div>

                        <h6 class="fw-bold text-primary border-bottom pb-2 mt-2 mb-3">3. Ubicación y Estado</h6>
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="fw-bold">País <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" id="id_pais_dir" required>
                                    <option value="">Seleccione país...</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="fw-bold">Provincia <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" id="id_provincia" required disabled>
                                    <option value="">Primero seleccione país...</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="fw-bold">Ciudad <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="id_ciudad" id="id_ciudad" required disabled>
                                    <option value="">Primero seleccione provincia...</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="fw-bold">Estado <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="estado" id="estado" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label class="fw-bold">Dirección Completa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-dark" name="descripcion_dir" id="descripcion_dir" required placeholder="Calle, Número, Sector">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalUI('modalProveedor')">Cerrar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Guardar Proveedor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTelefonos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-phone-alt me-2"></i>Contactos de: <span id="tituloProveedorTel" class="text-warning"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <form id="formTelefono" class="row g-3 align-items-end p-3 border border-2 border-secondary rounded mb-4 bg-white">
                        <input type="hidden" id="prov_tel_id" name="prov_tel_id">
                        <input type="hidden" id="id_telefono_edit" name="id_telefono_edit">
                        
                        <div class="col-md-5">
                            <label class="fw-bold text-dark">Número Telefónico <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-dark fw-bold" id="numero_telefono" name="numero_telefono" placeholder="(000)-000-0000" required maxlength="14">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold text-dark">Estado</label>
                            <select class="form-select border-dark" name="estado_telefono" id="estado_telefono" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-1">
                            <button type="submit" class="btn btn-primary w-100" id="btnGuardarTelefono">
                                <i class="fas fa-plus me-1"></i>Guardar
                            </button>
                            <button type="button" class="btn btn-secondary d-none" id="btnCancelarEdicionTel" onclick="cancelarEdicionTelefono()" title="Cancelar Edición">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </form>

                    <h6 class="fw-bold border-bottom pb-2">Directorio del Proveedor</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover bg-white text-center align-middle">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>Número</th>
                                    <th>Estado Relación</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaTelefonos"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalUI('modalTelefonos')">Cerrar Directorio</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.min.js"></script>
<script src="/Taller/Taller-Mecanica/Pdf/jspdf.plugin.autotable.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_Proveedor.js"></script>
</body>
</html>