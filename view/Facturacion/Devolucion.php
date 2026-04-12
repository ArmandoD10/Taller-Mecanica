<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-undo-alt me-2 text-danger"></i>Gestión de Devoluciones</h2>
            <div class="badge bg-dark p-2 shadow-sm">Garantía Estándar: 30 Días</div>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body bg-light">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="txt_buscar_fac" class="form-label fw-bold small mb-1" style="color: var(--primary-blue)">NÚMERO DE FACTURA</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-file-invoice"></i></span>
                            <input type="number" id="txt_buscar_fac" class="form-control" placeholder="Ej: 21">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 fw-bold shadow-sm" onclick="buscarFacturaDevolucion()">
                            <i class="fas fa-search me-2"></i>VALIDAR
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary w-100 fw-bold shadow-sm" onclick="limpiarPantallaDevolucion()">
                            <i class="fas fa-eraser me-2"></i>LIMPIAR
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="resultado_busqueda" class="d-none">
            <div id="card_factura" class="card shadow-sm mb-4 border-2">
                <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white py-3">
                    <span class="fw-bold h5 mb-0" id="info_fac_nro"></span>
                    <span id="info_fac_fecha" class="badge bg-primary fs-6"></span>
                </div>
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <div class="mb-4">
                                <p class="mb-1 text-muted small text-uppercase fw-bold">Nombre del Cliente</p>
                                <h4 id="info_fac_cliente" class="fw-bold text-dark"></h4>
                            </div>
                            <div>
                                <p class="mb-1 text-muted small text-uppercase fw-bold">Monto Total Facturado</p>
                                <h3 id="info_fac_monto" class="text-success fw-bold"></h3>
                            </div>
                        </div>
                        
                        <div class="col-md-5 text-end">
                            <div id="alerta_expirada" class="alert alert-danger d-none mb-3 border-0 shadow-sm">
                                <i class="fas fa-times-circle me-2"></i><b>FACTURA NO APTA:</b> Plazo vencido o ya devuelta.
                            </div>
                            <div id="alerta_valida" class="alert alert-success d-none mb-3 border-0 shadow-sm">
                                <i class="fas fa-check-circle me-2"></i><b>FACTURA VIGENTE:</b> Apta para proceso de devolución.
                            </div>

                            <div class="d-flex gap-3 justify-content-end mt-4">
                                <button type="button" class="btn btn-outline-primary btn-lg px-4 fw-bold shadow-sm" id="btn_ver_detalle">
                                    <i class="fas fa-list-ul me-2"></i>VER DETALLE
                                </button>
                                <button type="button" class="btn btn-success btn-lg px-4 fw-bold shadow-sm" id="btn_procesar_dev">
                                    <i class="fas fa-box-open me-2"></i>PROCESAR DEVOLUCIÓN
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalDetalleFactura" tabindex="-1">
    <div class="modal-dialog modal-lg border-primary border-2">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-receipt me-2"></i>ÍTEMS DE LA FACTURA</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start ps-4">Descripción</th>
                                <th>Cant.</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end pe-4">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="det_fac_items"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDevolucion" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog border-danger border-2 shadow-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-shield-alt me-2"></i>AUTORIZACIÓN REQUERIDA</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded border mb-4">
                    <p class="fw-bold mb-2 small text-muted text-uppercase">Estado Físico de la Mercancía</p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="chk_buen_estado">
                        <label class="form-check-label fw-bold" for="chk_buen_estado">Producto en Buen Estado (Reingresa al Stock)</label>
                    </div>
                    <div class="form-check mb-2 text-danger">
                        <input class="form-check-input" type="checkbox" id="chk_destapado">
                        <label class="form-check-label small" for="chk_destapado">¿Está Destapado / Abierto?</label>
                    </div>
                    <div class="form-check text-danger">
                        <input class="form-check-input" type="checkbox" id="chk_uso">
                        <label class="form-check-label small" for="chk_uso">¿Tiene Signos de Uso / Instalación?</label>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="txt_motivo" class="form-label fw-bold small text-muted text-uppercase">Motivo de Devolución</label>
                    <textarea id="txt_motivo" class="form-control" rows="2" placeholder="Explique brevemente por qué se devuelve..."></textarea>
                </div>

                <div class="border-top pt-3">
                    <p class="text-primary fw-bold mb-3 small text-uppercase"><i class="fas fa-key me-1"></i>Validación de Administrador</p>
                    <div class="input-group mb-2">
                        <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                        <input type="text" id="admin_user" class="form-control" placeholder="Usuario Admin">
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" id="admin_pass" class="form-control" placeholder="Contraseña">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">CANCELAR</button>
                <button type="button" class="btn btn-danger fw-bold px-4 shadow-sm" onclick="confirmarDevolucion()">
                    CONFIRMAR Y ANULAR FACTURA
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Facturacion/Scripts_Devolucion.js"></script>

</body>
</html>