<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
    .modal { z-index: 105000 !important; }
    .modal-backdrop { z-index: 104900 !important; }
    #modalArticulo .modal-body { max-height: 70vh; overflow-y: auto; }
    .card-articulo { transition: all 0.3s ease; cursor: pointer; border: 1px solid #eee; border-radius: 12px; }
    .card-articulo:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; border-color: #0d6efd; }
    .img-articulo-container { width: 70px; height: 70px; flex-shrink: 0; }
    .img-articulo-lista { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
    .drop-zone-image { width: 100%; height: 200px; border: 2px dashed #0d6efd; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; overflow: hidden; }
    #img_preview { max-width: 100%; max-height: 100%; object-fit: contain; }
    .text-precio { color: #198754; font-weight: 700; font-size: 1.1rem; }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 mb-4 gap-3">
            <div>
                <h2 class="mb-0"><i class="fas fa-boxes me-2 text-primary"></i>Gestión de Repuestos</h2>
                <p class="text-muted">Inventario con descripción y precios de venta</p>
            </div>
            <div class="d-flex gap-2">
                <input type="text" id="filtroBusqueda" class="form-control" placeholder="Buscar artículo..." style="width: 250px;">
                <button class="btn btn-primary" onclick="nuevoArticulo()">
                    <i class="fas fa-plus me-2"></i>Nuevo Artículo
                </button>
            </div>
        </div>

        <div id="contenedorCards" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3 mb-5">
            </div>
    </div>

    <div class="modal fade" id="modalArticulo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModal">Detalle del Artículo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form id="formArticulo" enctype="multipart/form-data">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_articulo" name="id_articulo">
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-body">
                                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">1. Información Principal</h6>
                                        <div class="row g-3">
                                            <div class="col-md-7">
                                                <label class="form-label fw-bold">Nombre del Producto</label>
                                                <input type="text" class="form-control" name="nombre" id="nombre" required>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label fw-bold">No. Serie / Parte</label>
                                                <input type="text" class="form-control" name="num_serie" id="num_serie" required>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label fw-bold">Descripción / Notas</label>
                                                <textarea class="form-control" name="descripcion" id="descripcion" rows="2"></textarea>
                                            </div>
                                            <div class="col-md-6 position-relative">
                                                    <label class="fw-bold">Marca <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control border-dark" id="txt_buscar_marca" placeholder="Escriba para buscar marca..." autocomplete="off">
                                                    <input type="hidden" name="id_marca_producto" id="id_marca_producto">
                                                    <ul id="lista_marcas" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1000; max-height: 200px; overflow-y: auto;"></ul>
                                                </div>
                                            <div class="col-md-6 position-relative">
                                                <label class="fw-bold">Proveedor Principal<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control border-dark" id="txt_buscar_proveedor" placeholder="Escriba nombre del proveedor..." autocomplete="off">
                                                <input type="hidden" name="id_proveedor" id="id_proveedor">
                                                <ul id="lista_proveedores" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1000; max-height: 200px; overflow-y: auto;"></ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">2. Costos y Precios</h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Precio Costo</label>
                                                <input type="number" step="0.01" class="form-control" name="precio_compra" id="precio_compra">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Precio Venta</label>
                                                <input type="number" step="0.01" class="form-control border-success" name="precio_venta" id="precio_venta">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold text-primary">Condición del Repuesto</label>
                                                <select class="form-select border-primary" name="estado_articulo" id="estado_articulo" required>
                                                    <option value="nuevo">Nuevo</option>
                                                    <option value="usado">Usado</option>
                                                    <option value="reparado">Reparado</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Vencimiento</label>
                                                <input type="date" class="form-control" name="fecha_caducidad" id="fecha_caducidad">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Estado</label>
                                                <select class="form-select" name="estado" id="estado">
                                                    <option value="activo">Activo</option>
                                                    <option value="inactivo">Inactivo</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">3. Multimedia</h6>
                                        <div class="drop-zone-image mb-3">
                                            <img id="img_preview" src="/Taller/Taller-Mecanica/img/default-part.webp" alt="Vista previa">
                                        </div>
                                        <input type="file" class="form-control" name="imagen_file" id="imagen_file" accept="image/*">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-2"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalleArticulo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detalle del Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5 text-center">
                        <div class="p-2 border rounded bg-light mb-3 mb-md-0">
                            <img id="det_imagen" src="" class="img-fluid rounded" style="max-height: 350px; object-fit: contain;">
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <h3 id="det_nombre" class="fw-bold text-primary mb-1"></h3>
                        <p id="det_serie" class="text-muted small mb-3"></p>
                        
                        <div class="mb-3 d-flex align-items-center flex-wrap gap-2">
                            <span class="badge bg-success fs-5" id="det_precio"></span>
                            <span class="badge bg-outline-secondary text-dark border" id="det_marca"></span>
                            <span id="det_estado_admin" class="badge"></span>
                            <span id="det_estado_fisico" class="badge text-uppercase"></span>
                        </div>

                        <h6 class="fw-bold border-bottom pb-1">Descripción:</h6>
                        <p id="det_descripcion" class="text-secondary" style="min-height: 50px;"></p>

                        <div class="row mt-4">
                            <div class="col-6 border-end">
                                <small class="d-block text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Vencimiento</small>
                                <strong id="det_fecha" class="text-dark"></strong>
                            </div>
                            <div class="col-6">
                                <small class="d-block text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Identificador único</small>
                                <strong class="text-dark">Codigo: #<span id="det_id_visual"></span></strong>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3 bg-light border rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold"><i class="fas fa-warehouse me-2 text-warning"></i>Stock Disponibilidad</span>
                                <span class="badge bg-secondary">Módulo en desarrollo</span>
                            </div>
                            <div class="row mt-2 text-center">
                                <div class="col-6 border-end">
                                    <small class="text-muted d-block">General</small>
                                    <h5 class="mb-0">--</h5>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Sucursal</small>
                                    <h5 class="mb-0">--</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_Articulo.js"></script>