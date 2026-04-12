<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <div>
                <h2 class="mb-0 fw-bold text-dark"><i class="fas fa-tint me-2 text-info"></i>Centro de Autolavado</h2>
                <p class="text-muted mb-0">Gestión de colas y servicios de limpieza</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary shadow-sm" type="button" onclick="listarLavados()">
                    <i class="fas fa-sync-alt me-2"></i>Actualizar
                </button>
                <button class="btn btn-info shadow-sm fw-bold text-dark" type="button" onclick="abrirModalNuevoLavado()">
                    <i class="fas fa-plus-circle me-2"></i>Registrar Lavado
                </button>
            </div>
        </div>

        <div class="row mb-4 text-center">
            <div class="col-md-4">
                <div class="card bg-white border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">EN ESPERA</h6>
                        <h2 class="text-warning fw-bold mb-0" id="count_espera">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-dark border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">LAVANDO AHORA</h6>
                        <h2 class="fw-bold mb-0" id="count_lavando">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">LISTOS PARA ENTREGA</h6>
                        <h2 class="fw-bold mb-0" id="count_listos">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fas fa-water me-1 text-info"></i> Monitor de Pista
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center small text-uppercase">
                            <tr>
                                <th>N° Orden</th>
                                <th>Vehículo y Cliente</th>
                                <th>Tipo de Lavado</th>
                                <th>Nivel Suciedad</th>
                                <th>Estado Actual</th>
                                <th style="width: 150px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaLavados" class="text-center">
                            <tr><td colspan="6" class="text-muted py-4">Cargando pista de lavado...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNuevoLavado" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-info border-2 shadow-lg">
                <div class="modal-header bg-info text-dark py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-car-wash me-2"></i>Ingreso a Autolavado</h5>
                    <button type="button" class="btn-close" onclick="cerrarModalLavado()"></button>
                </div>
                <form id="formNuevoLavado">
                    <div class="modal-body bg-light">
                        <div class="mb-3">
                            <label class="fw-bold small text-dark mb-1">Vincular a Orden de Taller (Opcional)</label>
                            <select class="form-select shadow-sm" id="id_orden_taller" name="id_orden_taller">
                                <option value="">-- Cliente de calle (Lavado Directo) --</option>
                            </select>
                        </div>

                        <div id="panel_nuevo_lavado_directo" class="border rounded p-3 bg-white mb-3 shadow-sm">
                            <div class="mb-3 text-center border-bottom pb-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_cliente_lav" id="lav_reg" value="registrado" checked onchange="toggleTipoClienteLav()">
                                    <label class="form-check-label fw-bold text-dark" for="lav_reg">Cliente Registrado</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_cliente_lav" id="lav_oca" value="ocasional" onchange="toggleTipoClienteLav()">
                                    <label class="form-check-label fw-bold text-dark" for="lav_oca">Cliente Ocasional</label>
                                </div>
                            </div>

                            <div id="seccion_express">
                                <label class="fw-bold small text-dark mb-1">Buscar Cliente / Vehículo</label>
                                <div class="input-group shadow-sm mb-2 position-relative">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-info"></i></span>
                                    <input type="text" id="buscador_vehiculo_lavado" class="form-control" placeholder="Escriba nombre o placa..." oninput="buscarVehiculosLavado(this)">
                                    <ul class="list-group position-absolute w-100 d-none shadow" id="res_vehiculos_lavado" style="z-index: 1060; top: 100%; max-height: 200px; overflow-y: auto;"></ul>
                                </div>
                                <input type="hidden" id="id_vehiculo_express" name="id_vehiculo_express">
                                
                                <div class="bg-light p-2 rounded border d-none text-center mb-2" id="info_vehiculo_seleccionado">
                                    <span class="d-block fw-bold text-primary" id="lbl_lav_vehiculo"></span>
                                    <small class="text-muted" id="lbl_lav_cliente"></small>
                                </div>

                                <div id="alerta_membresia" class="alert alert-success d-none p-2 mb-3 shadow-sm border-success">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-star text-warning me-1"></i> <strong id="lbl_mem_nombre"></strong><br>
                                            <small id="lbl_mem_restantes" class="fw-bold text-dark"></small>
                                        </div>
                                        <div class="form-check form-switch fs-4 mb-0">
                                            <input class="form-check-input" type="checkbox" id="usar_membresia" name="usar_membresia" value="1" onchange="toggleMembresiaLavado()" style="cursor: pointer;" title="Usar membresía para este lavado">
                                        </div>
                                    </div>
                                    <input type="hidden" id="id_membresia_activa" name="id_membresia_activa">
                                </div>
                            </div>

                            <div id="seccion_ocasional" class="d-none">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="fw-bold small text-dark mb-1">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm border-dark" id="occ_nombre_lav" name="occ_nombre_lav">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-bold small text-dark mb-1">Vehículo / Placa <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm border-dark" id="occ_vehiculo_lav" name="occ_vehiculo_lav">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label class="fw-bold small text-dark mb-1">Tipo de Lavado <span class="text-danger">*</span></label>
                                <select class="form-select shadow-sm border-info" id="id_tipo_lavado" name="id_tipo_lavado" required>
                                    <option value="" disabled selected>Seleccione...</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="fw-bold small text-dark mb-1">Precio <span class="text-danger">*</span></label>
                                <select class="form-select shadow-sm border-info" id="id_precio" name="id_precio" required>
                                    <option value="" disabled selected>Seleccione...</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="fw-bold small text-dark mb-1">Nivel de Suciedad</label>
                            <select class="form-select shadow-sm" id="nivel_suciedad" name="nivel_suciedad" required>
                                <option value="Bajo">Bajo (Mantenimiento)</option>
                                <option value="Medio" selected>Medio (Normal)</option>
                                <option value="Alto">Alto (Fango/Barro intenso)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-secondary fw-bold" onclick="cerrarModalLavado()">Cancelar</button>
                        <button type="submit" class="btn btn-info fw-bold shadow-sm text-dark">
                            <i class="fas fa-check-circle me-1"></i> Enviar a Pista
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTicketLavado" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-dark">
                <div class="modal-body p-4" id="areaImpresionTicket">
                    <style>
                        @media print {
                            body * { visibility: hidden; }
                            #areaImpresionTicket, #areaImpresionTicket * { visibility: visible; }
                            #areaImpresionTicket { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; }
                            .no-print { display: none !important; }
                            .modal-content { border: none !important; }
                        }
                    </style>
                    <div class="text-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark">Mecánica Automotriz Díaz Pantaleón</h5>
                        <small class="text-muted fw-bold">División: Autolavado Express</small><br>
                        <small>RNC: 131-XXXXX-X</small>
                    </div>
                    <div class="border-bottom border-dark border-2 mb-2"></div>
                    <div class="small mb-3 text-dark">
                        <div><strong>Factura N°:</strong> <span id="tk_num"></span></div>
                        <div><strong>Fecha:</strong> <span id="tk_fecha"></span></div>
                        <div><strong>NCF:</strong> <span id="tk_ncf"></span></div>
                        <div><strong>Cliente:</strong> <span id="tk_cliente"></span></div>
                        <div><strong>Vehículo:</strong> <span id="tk_placa"></span></div>
                    </div>
                    <div class="border-bottom border-dark border-1 mb-2"></div>
                    <table class="table table-sm table-borderless small mb-2 text-dark">
                        <thead><tr class="border-bottom border-dark"><th>Cant.</th><th>Descripción</th><th class="text-end">Valor</th></tr></thead>
                        <tbody><tr><td>1</td><td id="tk_servicio"></td><td class="text-end" id="tk_subtotal"></td></tr></tbody>
                    </table>
                    <div class="border-bottom border-dark border-1 mb-2"></div>
                    <div class="d-flex justify-content-between small text-dark"><span>ITBIS (18%):</span><span id="tk_itbis"></span></div>
                    <div class="d-flex justify-content-between fw-bold text-dark mt-1" style="font-size: 1.1rem;"><span>TOTAL:</span><span id="tk_total"></span></div>
                    <div class="text-center mt-4 small fw-bold text-dark">¡Gracias por preferirnos!</div>
                </div>
                <div class="modal-footer p-2 d-flex justify-content-between bg-light no-print">
                    <button type="button" class="btn btn-sm btn-secondary fw-bold" onclick="cerrarModalUI('modalTicketLavado')">Cerrar</button>
                    <button type="button" class="btn btn-sm btn-dark fw-bold shadow-sm" onclick="window.print()"><i class="fas fa-print me-1"></i> Imprimir</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Autolavado/Scripts_Lavado.js"></script>
</body>
</html>