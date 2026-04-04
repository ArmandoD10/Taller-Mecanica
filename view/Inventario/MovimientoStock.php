<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="mt-4 mb-4">
            <h2 class="mb-0"><i class="fas fa-map-marked-alt me-2 text-primary"></i>Ubicación de Mercancía Recibida</h2>
            <p class="text-muted">Asigne una góndola y nivel a los productos que acaban de llegar por Conduce.</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-dolly-flatbed me-2 text-warning"></i>Artículos en Área de Recepción</h5>
                <span class="badge bg-primary">
                    Sucursal: <?php echo $_SESSION['nombre_sucursal'] ?? 'Cargando...'; ?>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Conduce</th>
                                <th>Producto</th>
                                <th class="text-center">Cant. Pendiente</th>
                                <th>Almacén Entrada</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_pendientes">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalUbicar" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered"> <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold">Asignar Ubicación Física</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-4 border-end">
                        <div class="text-center p-2">
                            <img id="m_img" src="" class="rounded border mb-2 shadow-sm" style="width:100px; height:100px; object-fit:cover;">
                            <h6 id="m_nombre" class="fw-bold mb-1"></h6>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                Pendiente: <span id="m_max">0</span>
                            </span>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-12 position-relative">
                                <label class="small fw-bold">1. Buscar Sucursal Destino</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                    <input type="text" id="buscar_sucursal" class="form-control" placeholder="Escriba nombre de sucursal...">
                                </div>
                                <ul id="res_sucursales" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index:1050; max-height:150px; overflow-y:auto;"></ul>
                                
                                <div id="sucursal_seleccionada" class="mt-2 p-2 border rounded bg-light d-none">
                                    <input type="hidden" id="id_sucursal_dest">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-primary"><i class="fas fa-map-marker-alt me-1"></i> <span id="lbl_sucursal_nombre"></span></span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick="deseleccionarSucursal()"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold">2. Almacén</label>
                                <select id="m_almacen" class="form-select form-select-sm" disabled onchange="cargarGondolasDestino(this.value)"></select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold">3. Góndola y Nivel</label>
                                <div class="input-group input-group-sm">
                                    <select id="m_gondola" class="form-select" disabled onchange="actualizarNiveles(this)"></select>
                                    <select id="m_nivel" class="form-select" style="max-width: 80px;"></select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="small fw-bold">4. Cantidad a Ubicar</label>
                                <input type="number" id="m_cant" class="form-control form-control-sm fw-bold border-primary text-center">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button class="btn btn-primary btn-sm px-4 fw-bold" onclick="confirmarUbicacion()">Confirmar Ubicación</button>
            </div>
        </div>
    </div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_MovimientoS.js"></script>