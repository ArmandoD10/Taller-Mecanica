<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 mb-4 gap-3">
            <div>
                <h2 class="mb-0 fw-bold text-dark"><i class="fas fa-tools me-2 text-primary"></i>Gestión de Órdenes de Servicio</h2>
                <p class="text-muted mb-0">Conversión de inspecciones técnicas en expedientes de trabajo</p>
            </div>
            <div class="card border-0 shadow-sm px-3 py-2 bg-white">
                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Esperando Orden</small>
                <span class="fw-bold text-warning" id="cnt_pendientes">0 Inspecciones</span>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="filtro_inspeccion" class="form-control border-start-0 ps-0" 
                                   onkeyup="actualizarTablaInspecciones()"
                                   placeholder="Buscar por Placa, Cliente, VIN o ID de Inspección...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 fw-bold shadow-sm" onclick="actualizarTablaInspecciones()">
                            <i class="fas fa-sync-alt me-2"></i>Sincronizar Datos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-clipboard-check me-2 text-success"></i>Inspecciones Listas para Procesar</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Vehículo / Placa</th>
                                <th>Cliente</th>
                                <th>Tipo</th>
                                <th class="text-center">Hallazgos</th>
                                <th>Fecha</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_inspecciones">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                    Cargando inspecciones...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-microchip me-2 text-info"></i>Monitor de Órdenes Activas en Taller</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tabla_ordenes_activas">
                        <thead class="table-light text-uppercase" style="font-size: 0.75rem;">
                            <tr>
                                <th class="ps-4">Orden #</th>
                                <th>Vehículo / Cliente</th>
                                <th>Técnico Asignado</th>
                                <th>Estado / Progreso</th>
                                <th>Recursos (Srv/Rep)</th>
                                <th class="text-end pe-4">Detalle</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_ordenes_activas">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    No hay vehículos trabajando actualmente.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalCrearOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-signature me-2 text-success"></i>Nueva Orden de Servicio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-3">
                                <h6 class="fw-bold text-primary mb-3 small text-uppercase">Vehículo & Cliente</h6>
                                <div class="row g-2 small">
                                    <div class="col-12 mb-1"><b>Cliente:</b> <span id="modal_cliente_nombre" class="text-muted">---</span></div>
                                    <div class="col-6"><b>Placa:</b> <span id="modal_vehiculo_placa" class="badge bg-dark">---</span></div>
                                    <div class="col-6"><b>Color:</b> <span id="modal_vehiculo_color" class="text-muted">---</span></div>
                                    <div class="col-12 mt-2"><b>Modelo:</b> <span id="modal_vehiculo_modelo" class="fw-bold">---</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3 border-primary" style="border-left: 4px solid #0d6efd !important;">
                            <div class="card-header bg-white fw-bold small text-primary"><i class="fas fa-comment-dots me-1"></i>Trabajos Solicitados (Cliente)</div>
                            <ul class="list-group list-group-flush" id="lista_trabajos_solicitados"></ul>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white fw-bold small text-danger"><i class="fas fa-exclamation-circle me-1"></i>Hallazgos Críticos</div>
                            <ul class="list-group list-group-flush" id="lista_hallazgos_sugeridos"></ul>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <label class="small fw-bold mb-1 text-primary">Buscar y Asignar Servicio</label>
                                <div class="position-relative mb-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-primary"><i class="fas fa-search text-primary"></i></span>
                                        <input type="text" id="busqueda_servicio" class="form-control border-primary fw-bold" placeholder="Ej: Afinamiento, Aceite...">
                                    </div>
                                    <ul id="res_servicios" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 2000; max-height: 200px; overflow-y: auto;"></ul>
                                </div>

                                <label class="small fw-bold mb-1">Buscar Repuesto</label>
                                <div class="position-relative mb-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                        <input type="text" id="busqueda_repuesto" class="form-control" placeholder="Nombre o ID...">
                                    </div>
                                    <ul id="res_repuestos" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 2000; max-height: 200px; overflow-y: auto;"></ul>
                                </div>

                                <label class="small fw-bold mb-1 mt-2">Notas del Asesor</label>
                                <textarea id="obs_orden" class="form-control form-control-sm" rows="2" placeholder="Notas para el mecánico..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm h-100 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="mb-0 fw-bold">Planificación de Trabajo</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light text-uppercase" style="font-size: 0.65rem;">
                                            <tr>
                                                <th class="ps-3">Descripción</th>
                                                <th class="text-center" width="80">Cantidad</th>
                                                <th class="text-end" width="110">P. Unitario</th>
                                                <th class="text-end" width="110">Subtotal</th>
                                                <th width="50"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="lista_servicios_orden"></tbody>
                                        <tbody id="lista_repuestos_orden" class="border-top-0"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top p-3 text-end">
                                <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.7rem;">Total Estimado</small>
                                <h3 class="mb-0 fw-bold text-primary" id="txt_total_orden">$0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3">
                <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" onclick="guardarOrdenServicio()">
                    <i class="fas fa-check-circle me-2"></i>Confirmar y Abrir Orden
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVerDetalleOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice me-2"></i>Detalle de Orden <span id="det_id_orden" class="badge bg-white text-primary ms-2"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white fw-bold small text-uppercase">Mano de Obra</div>
                            <div id="det_lista_servicios" class="list-group list-group-flush"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white fw-bold small text-uppercase">Insumos y Repuestos</div>
                            <div id="det_lista_repuestos" class="list-group list-group-flush"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top p-3 d-flex justify-content-between">
                <div class="small text-muted italic">Precios sin ITBIS incluido</div>
                <h4 class="fw-bold text-dark mb-0">Monto Total: <span id="det_total_orden" class="text-primary"></span></h4>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAcuerdoPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-handshake me-2"></i> Definir Acuerdo de Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="small fw-bold">Monto Total Factura</label>
                        <input type="text" id="total_acuerdo" class="form-control fw-bold text-primary" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Cantidad de Cuotas</label>
                        <select id="cant_cuotas" class="form-select" onchange="generarCronograma()">
                            <option value="1">1 Pago (Completo)</option>
                            <option value="2">2 Pagos</option>
                            <option value="3">3 Pagos</option>
                            <option value="4">4 Pagos</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Frecuencia (Días)</label>
                        <input type="number" id="frecuencia_dias" class="form-control" value="15" onchange="generarCronograma()">
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 text-muted">Cronograma de Pagos Sugerido</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th># Cuota</th>
                                <th>Monto</th>
                                <th>Fecha de Pago</th>
                            </tr>
                        </thead>
                        <tbody id="lista_cuotas_acuerdo">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" onclick="confirmarAcuerdo()">Aceptar Acuerdo</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_Orden.js"></script>

