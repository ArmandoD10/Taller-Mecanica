<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2><i class="fas fa-credit-card me-2 text-primary"></i>Registro de Créditos</h2>
        
        <form method="POST" id="formulario">
            <input type="hidden" id="id_oculto" name="id_credito">
            
            <div class="row align-items-stretch mb-4">
                
                <div class="col-lg-6 mb-3">
                    <div class="card p-4 shadow-sm h-100 border-0">
                        <h5 class="mb-4" style="color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            <i class="fas fa-hand-holding-usd me-2"></i>Asignación de Crédito
                        </h5>
                        
                        <div class="row">
                            <div class="col-12 mb-3 position-relative">
                                <label class="form-label fw-bold">Buscar Cliente Beneficiario</label>
                                <div class="d-flex justify-content-between">
                                    <input type="text" class="form-control" id="buscador_cliente" placeholder="Escriba el nombre o cédula..." autocomplete="off" required>
                                </div>
                                <small id="lbl_tipo_cliente" class="text-muted d-block mt-1"></small>
                                <input type="hidden" id="id_cliente" name="id_cliente" required>
                                <ul class="list-group position-absolute w-100 d-none shadow-sm" id="lista_clientes" style="z-index: 1000; max-height: 200px; overflow-y: auto; top: 75%;"></ul>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Monto Aprobado (RD$)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control bg-light" id="monto_credito" name="monto_credito" placeholder="0.00" readonly required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ref. DataCrédito</label>
                                <input type="text" class="form-control bg-light" id="referencia_datacredito" name="referencia_datacredito" placeholder="Ej. DC-99823" readonly>
                            </div>

                            <div class="col-12 mt-2 d-none" id="sec_bypass">
                                <div class="form-check form-switch border p-2 rounded bg-white border-danger shadow-sm">
                                    <input class="form-check-input ms-1" type="checkbox" id="chk_bypass">
                                    <label class="form-check-label fw-bold text-danger ms-2" for="chk_bypass">Aprobación Manual (Bypass DataCrédito)</label>
                                </div>
                            </div>

                            <div class="col-12 mt-3 d-none" id="div_autorizacion_admin">
                                <label class="form-label fw-bold text-danger" id="lbl_motivo_autorizacion"><i class="fas fa-shield-alt me-1"></i> Autorización de Administrador</label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-danger text-white border-danger"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control border-danger" id="admin_password" name="admin_password" placeholder="Ingrese su Clave de Acceso para autorizar">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3">
                    <div class="card p-4 shadow-sm h-100 border-0">
                        <h5 class="mb-4" style="color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            <i class="fas fa-calendar-check me-2"></i>Condiciones y Estado
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                            </div>
                            
                            <div class="col-md-6 mb-3 d-none" id="contenedor_estado_credito">
                                <label class="form-label fw-bold">Estado del Crédito</label>
                                <select class="form-select" id="estado_credito" name="estado_credito" readonly required>
                                    <option value="Activo">Activo</option>
                                    <option value="Pagado">Pagado</option>
                                    <option value="Vencido">Vencido</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                            
                            <div class="col-12 text-muted small mt-2">
                                <i class="fas fa-info-circle me-1"></i> Si el cliente ya posee un crédito ACTIVO, el sistema unificará y actualizará el límite existente en lugar de crear una cuenta nueva. Los aumentos de límite requieren clave de Administrador.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="contenedor_consultas_api" class="row mb-4 d-none">
                <div class="col-12">
                    <div class="card shadow-sm border-0" style="background-color: #f8fff9; border-left: 5px solid #198754 !important;">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h5 class="text-success fw-bold mb-0">
                                <i class="fas fa-history me-2"></i>Historial de Consultas DataCrédito para este Cliente
                            </h5>
                            <p class="text-muted small mb-0">Seleccione una consulta válida para cargar automáticamente el monto y la referencia.</p>
                        </div>
                        <div class="card-body">
                            <div id="lista_consultas_api" class="row g-3"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-4 mb-4 mt-2">
                <button type="submit" class="btn btn-success py-2 px-3 fw-bold" style="width: 220px" id="btnGuardar">
                    <i class="fas fa-check-circle me-2"></i>Aprobar Crédito
                </button>
                <button type="button" class="btn btn-secondary py-2 px-3" style="width: 150px" onclick="limpiarFormulario()">
                    <i class="fas fa-eraser me-2"></i>Limpiar
                </button>
            </div>

            <hr class="mt-5 mb-5">

            <div class="mt-5">
                <h3 class="mb-4">Historial de Líneas de Crédito</h3>
                <div class="mb-4 p-3 bg-light rounded d-flex align-items-center gap-3">
                    <div class="input-group" style="width: 40%;">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="filtro" placeholder="Buscar por cliente o cédula...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-2" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th>Id</th>
                                <th>Cliente</th>
                                <th>Cédula/RNC</th>
                                <th>Monto Aprobado</th>
                                <th>Saldo Deudor</th>
                                <th>Vencimiento</th>
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

<div id="custom_modal_error" class="modal-overlay d-none" onclick="cerrarModalError()">
    <div class="modal-content-error" onclick="event.stopPropagation()">
        <div class="modal-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <h4 class="fw-bold mt-3">Acción Denegada</h4>
        <p id="modal_error_mensaje" class="text-muted px-3"></p>
        <button class="btn btn-danger rounded-pill px-4" onclick="cerrarModalError()">Entendido</button>
    </div>
</div>

<script src="/Taller/Taller-Mecanica/modules/Cliente/Scripts_Credito.js"></script>
</body>
</html>