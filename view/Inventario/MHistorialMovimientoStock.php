<?php require("../../layout.php"); require("../../header.php"); ?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="fw-bold mt-4 mb-3"><i class="fas fa-history me-2"></i>Historial de Movimientos</h2>

        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small fw-bold">DESDE</label>
                        <input type="date" id="f_inicio" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">HASTA</label>
                        <input type="date" id="f_fin" class="form-control">
                    </div>
                    <div class="col-md-4">
    <div class="d-flex gap-2">
        <button class="btn btn-primary flex-grow-1 fw-bold" onclick="cargarHistorialStock()">
            <i class="fas fa-search me-2"></i>APLICAR FILTRO
        </button>
        <button class="btn btn-outline-secondary fw-bold" onclick="location.reload()" title="Recargar página">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>
                </div>
            </div>
        </div>

        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr><th>ID</th><th>Tipo</th><th>Almacén</th><th>Sucursal</th><th>Fecha</th><th>Acción</th></tr>
                </thead>
                <tbody id="tabla_historial_stock"></tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal fade" id="modalDetalleStock" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Detalle del Movimiento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div id="body_detalle_stock" class="modal-body p-0"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_HistorialMovimiento.js"></script>