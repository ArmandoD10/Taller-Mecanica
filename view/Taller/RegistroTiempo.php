<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-stopwatch me-2 text-primary"></i>Gestión de Tiempos y Asignaciones</h2>
            <button class="btn btn-primary" type="button" onclick="nuevaAsignacion()">
                <i class="fas fa-plus me-2"></i>Nueva Asignación
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>N° Orden</th>
                                <th>Servicio</th>
                                <th>Mecánicos Asignados</th>
                                <th>Estado</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th class="text-center" style="min-width: 120px;">Control</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaAsignaciones"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAsignacion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-primary border-2">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModalAsignacion">Nueva Asignación Detallada</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalAsignacion()"></button>
                </div>
                <form id="formAsignacion">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_asignacion" name="id_asignacion">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3 position-relative">
                                <label class="fw-bold">1. Seleccionar Orden <span class="text-danger">*</span></label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-white border-dark"><i class="fas fa-search text-primary"></i></span>
                                    <input type="text" class="form-control border-dark" id="txt_buscar_orden" placeholder="Escriba ORD-X o descripción..." autocomplete="off" required>
                                </div>
                                
                                <div id="info_orden_seleccionada" class="mt-2 p-2 border border-info rounded bg-white shadow-sm d-none">
                                    <small class="d-block text-dark mb-1"><i class="fas fa-user text-primary me-2"></i><strong>Cliente:</strong> <span id="lbl_orden_cliente" class="text-muted"></span></small>
                                    <small class="d-block text-dark"><i class="fas fa-car text-primary me-2"></i><strong>Vehículo:</strong> <span id="lbl_orden_vehiculo" class="text-muted"></span></small>
                                </div>

                                <input type="hidden" name="id_orden" id="id_orden" required>
                                <ul class="list-group position-absolute w-100 d-none shadow" id="lista_ordenes" style="z-index:1000; max-height: 200px; overflow-y: auto; top: 100%;"></ul>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">2. Servicio de la Orden <span class="text-danger">*</span></label>
                                <select class="form-select border-dark shadow-sm" name="id_tipo_servicio" id="id_tipo_servicio" required disabled>
                                    <option value="">Seleccione primero una orden...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold">Bahía de Trabajo <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="id_bahia" id="id_bahia" required>
                                    <option value="">Cargando bahías...</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold">Tarifa a Aplicar <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" name="id_precio" id="id_precio" required>
                                    <option value="">Cargando tarifas...</option>
                                </select>
                                <small class="text-muted d-block mt-1" id="lbl_precio_sugerido">Sugerido por servicio: <span class="fw-bold text-muted">RD$ 0.00</span></small>
                            </div>
                            <div class="col-md-4 mb-3 position-relative">
                                <label class="fw-bold">Maquinaria <small class="text-muted">(Opcional)</small></label>
                                <div class="input-group">
                                    <input type="text" class="form-control border-dark" id="txt_buscar_maquinaria" placeholder="Buscar..." autocomplete="off">
                                    <button class="btn btn-outline-secondary border-dark" type="button" onclick="agregarMaquinariaLista()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <input type="hidden" id="id_maquinaria_temp">
                                <ul class="list-group position-absolute w-100 d-none shadow" id="lista_maquinaria" style="z-index:1000; max-height: 150px; overflow-y: auto; top: 100%;"></ul>
                                
                                <div class="mt-2 p-2 border border-dark rounded bg-white" id="contenedor_maquinaria" style="min-height: 50px;">
                                    <p class="text-muted small m-0" id="msg_sin_maquinaria">Ninguna asignada.</p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3 position-relative">
                                <label class="fw-bold">Asignar Mecánicos <small class="text-muted">(Se validará disponibilidad y horario)</small> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-dark"><i class="fas fa-user-cog"></i></span>
                                    <input type="text" class="form-control border-dark" id="txt_buscar_empleado" placeholder="Buscar mecánico por nombre..." autocomplete="off">
                                    <button class="btn btn-outline-primary border-dark fw-bold" type="button" onclick="agregarMecanicoLista()">
                                        <i class="fas fa-plus"></i> Añadir
                                    </button>
                                </div>
                                <input type="hidden" id="id_empleado_temp">
                                <ul class="list-group position-absolute w-100 d-none shadow" id="lista_empleado" style="z-index:1000; max-height: 150px; overflow-y: auto; top: 100%;"></ul>
                                
                                <div class="mt-2 p-2 border border-dark rounded bg-white" id="contenedor_mecanicos" style="min-height: 60px;">
                                    <p class="text-muted small m-0" id="msg_sin_mecanicos">No hay mecánicos seleccionados.</p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Fecha programada</label>
                                <input type="date" class="form-control border-dark bg-light" name="fecha_asignacion" id="fecha_asignacion" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Hora de inicio</label>
                                <input type="time" class="form-control border-dark bg-light" name="hora_asignacion" id="hora_asignacion" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalAsignacion()">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarAsig"><i class="fas fa-save me-2"></i>Guardar Asignación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTiempos" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger border-2">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-flag-checkered me-2"></i>Finalizar Trabajo</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalTiempos()"></button>
                </div>
                <form id="formTiempos">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_asignacion_tiempo" name="id_asignacion_tiempo">
                        
                        <div class="row mb-3 text-center">
                            <div class="col-6 border-end">
                                <span class="text-muted small fw-bold">HORA INICIO</span>
                                <h4 id="lbl_hora_inicio" class="text-primary">--:--</h4>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small fw-bold">HORA FIN (AHORA)</span>
                                <h4 id="lbl_hora_fin" class="text-danger">--:--</h4>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Notas y Hallazgos <span class="text-danger">*</span></label>
                            <textarea class="form-control border-dark" name="notas_hallazgos" id="notas_hallazgos" rows="4" placeholder="Indique los hallazgos técnicos..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalTiempos()">Cancelar</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-stop-circle me-2"></i>Confirmar y Finalizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="../../modules/Taller/Scripts_Tiempos.js"></script>
</body>
</html>