<?php
require("../../layout.php");
require("../../header.php");
// Usamos la sesión que ya tenemos para identificar la sucursal actual
$sucursal_actual = $_SESSION['nombre_sucursal'] ?? 'Cargando...';
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="mb-0"><i class="fas fa-exchange-alt me-2 text-primary"></i>Transferencia de Stock</h2>
            <span class="badge bg-white text-primary border border-primary-subtle rounded-pill px-3 py-2 shadow-sm" style="font-size: 0.85rem;">
                <i class="fas fa-map-marker-alt me-1 text-danger"></i> 
                <span id="txt_sucursal_actual" class="fw-bold"><?php echo $_SESSION['nombre_sucursal'] ?? 'Cargando...'; ?></span>
            </span>
        </div>
        <p class="text-muted mb-0">Gestión logística entre sucursales de la red</p>
    </div>
    
    <button class="btn btn-primary shadow-sm fw-bold px-4" onclick="abrirModalSolicitud()">
        <i class="fas fa-plus me-2"></i>Nueva Solicitud
    </button>
</div>

        <ul class="nav nav-pills mb-3 bg-white p-2 rounded shadow-sm border" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="tab-solicitudes-enviadas" data-bs-toggle="pill" data-bs-target="#pills-enviadas" type="button">
                    <i class="fas fa-paper-plane me-2"></i>Mis Pedidos (Entrantes)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-solicitudes-recibidas" data-bs-toggle="pill" data-bs-target="#pills-recibidas" type="button">
                    <i class="fas fa-truck-loading me-2"></i>Por Despachar (Salientes)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-historial" data-bs-toggle="pill" data-bs-target="#pills-historial" type="button">
                    <i class="fas fa-history me-2"></i>Historial
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-enviadas" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Producto</th>
                                    <th>Origen</th>
                                    <th class="text-center">Cant.</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_mis_pedidos"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pills-recibidas" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Producto</th>
                                    <th>Destino</th>
                                    <th class="text-center">Cant.</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_por_despachar"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pills-historial" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0 text-muted">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Producto</th>
                                    <th>Ruta</th>
                                    <th class="text-center">Cant.</th>
                                    <th>Fecha Final</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_historial"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNuevaSolicitud" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold">Crear Pedido de Mercancía</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6 border-end">
                            <label class="small fw-bold mb-1">1. Buscar Repuesto</label>
                            <div class="input-group input-group-sm mb-3">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="text" id="busqueda_art" class="form-control" placeholder="Nombre o ID del producto...">
                            </div>
                            <ul id="res_articulos" class="list-group shadow-sm d-none" style="max-height:200px; overflow-y:auto;"></ul>
                            
                            <div id="art_seleccionado" class="mt-3 p-3 border rounded bg-light d-none">
                                <div class="d-flex align-items-center">
                                    <img id="sel_img" src="" class="rounded border me-3" style="width:50px; height:50px; object-fit:cover;">
                                    <div>
                                        <h6 id="sel_nombre" class="fw-bold mb-0 small"></h6>
                                        <small class="text-muted" id="sel_id"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="small fw-bold mb-1">2. Sucursales con Disponibilidad</label>
                            <div id="lista_disponibilidad" class="list-group small">
                                <div class="text-center py-4 text-muted small">Seleccione un artículo para ver stock</div>
                            </div>
                            
                            <div class="mt-4">
                                <label class="small fw-bold">3. Cantidad a Pedir</label>
                                <input type="number" id="cant_pedir" class="form-control form-control-sm fw-bold border-primary text-center" min="1" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button class="btn btn-primary btn-sm px-4 fw-bold" onclick="guardarSolicitud()">Enviar Solicitud</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_Transferencia.js"></script>