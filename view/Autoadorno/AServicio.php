<?php 
require("../../layout.php"); 
require("../../header.php"); 
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2 class="fw-bold"><i class="fas fa-warehouse me-2 text-primary"></i>Centro de Autoadorno e Inventario</h2>
            <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevaOrden">
                <i class="fas fa-plus-circle me-2"></i>Nueva Orden de Combo
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 fw-bold"><i class="fas fa-history me-2"></i>Órdenes de Sucursal (Recientes)</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Orden</th>
                            <th>Descripción</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="tabla_ordenes_recientes"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNuevaOrden" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Nueva Orden de Autoadorno</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formNuevaInstalacion">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">PLACA DEL VEHÍCULO</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" id="ins_placa" name="placa" class="form-control text-uppercase" placeholder="Ej: A123456" required>
                            </div>
                            <div id="msg_busqueda" class="mt-1"></div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">SELECCIONAR INSPECCIÓN ACTIVA</label>
                            <select id="ins_id_inspeccion" name="id_inspeccion" class="form-select" required>
                                <option value="">Ingrese placa para buscar...</option>
                            </select>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="small fw-bold">SELECCIONAR COMBO DE INSTALACIÓN</label>
                            <select name="id_paquete" id="ins_paquete" class="form-select" onchange="actualizarVistaCombo()" required>
                                <option value="">-- Seleccione un combo --</option>
                            </select>
                        </div>

                        <label class="small fw-bold text-primary">Detalle de Insumos:</label>
                        <div id="resumen_combo" class="p-3 border rounded bg-light" style="max-height: 200px; overflow-y: auto;">
                            <small class="text-muted italic">Seleccione un combo para ver los artículos...</small>
                        </div>

                        <input type="hidden" id="total_oculto" name="total_oculto">
                    </form>
                </div>
                <div class="modal-footer flex-column align-items-stretch bg-white">
                    <div class="d-flex justify-content-between mb-3 px-2">
                        <span class="fw-bold">TOTAL A FACTURAR:</span>
                        <span id="lbl_total_orden" class="h4 text-primary fw-bold">RD$ 0.00</span>
                    </div>
                    <button type="button" class="btn btn-primary fw-bold py-2" onclick="guardarOrdenAutoadorno()">
                        <i class="fas fa-save me-2"></i>CREAR ORDEN Y REBAJAR STOCK
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Autoadorno/Scripts_Servicio.js"></script>