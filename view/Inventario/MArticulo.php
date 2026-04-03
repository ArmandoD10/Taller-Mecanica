<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
    /* Elevación de niveles para el modal sobre el sidebar */
    .modal { z-index: 105000 !important; }
    .modal-backdrop { z-index: 104900 !important; }
    
    /* Scroll interno para el cuerpo del modal */
    #modalArticulo .modal-body {
        max-height: 70vh; 
        overflow-y: auto; 
    }

    /* Estilos de las Cards de Inventario */
    .card-articulo {
        transition: all 0.3s ease;
        cursor: pointer;
        border: 1px solid #eee;
        border-radius: 12px;
    }

    .card-articulo:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        border-color: var(--bs-primary);
    }

    .img-articulo-container {
        width: 70px;
        height: 70px;
        flex-shrink: 0;
    }

    .img-articulo-lista {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    /* Contenedor de carga de imagen en el Formulario */
    .drop-zone-image {
        width: 100%;
        height: 200px;
        border: 2px dashed #0d6efd;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        overflow: hidden;
    }

    #img_preview {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .text-precio {
        color: #198754;
        font-weight: 700;
        font-size: 1.1rem;
    }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 mb-4 gap-3">
            <div>
                <h2 class="mb-0"><i class="fas fa-boxes me-2 text-primary"></i>Gestión de Repuestos</h2>
                <p class="text-muted">Catálogo visual de artículos en existencia</p>
            </div>
            <div class="d-flex gap-2">
                <input type="text" id="filtroBusqueda" class="form-control" placeholder="Buscar por nombre o serie..." style="width: 250px;">
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
                                            <div class="col-md-8">
                                                <label class="form-label fw-bold">Nombre del Producto</label>
                                                <input type="text" class="form-control" name="nombre" id="nombre" required placeholder="Ej: Disco de Freno Delantero">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">No. Serie / Parte</label>
                                                <input type="text" class="form-control" name="num_serie" id="num_serie" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Marca</label>
                                                <select class="form-select" name="id_marca_producto" id="id_marca_producto" required>
                                                    <option value="">Seleccione marca...</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Proveedor</label>
                                                <select class="form-select" name="id_proveedor" id="id_proveedor" required>
                                                    <option value="">Seleccione proveedor...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">2. Costos y Logística</h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Precio Costo</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" class="form-control" name="precio_costo" id="precio_costo">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Estado</label>
                                                <select class="form-select" name="estado" id="estado">
                                                    <option value="activo">Activo</option>
                                                    <option value="inactivo">Inactivo</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Vencimiento</label>
                                                <input type="date" class="form-control" name="fecha_caducidad" id="fecha_caducidad">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">3. Imagen Referencial</h6>
                                        <div class="drop-zone-image mb-3">
                                            <img id="img_preview" src="/Taller/img/default-part.png" alt="Vista previa">
                                        </div>
                                        <input type="file" class="form-control" name="imagen_file" id="imagen_file" accept="image/*">
                                        <p class="small text-muted mt-2">La imagen se guardará automáticamente vinculada al ID del producto.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-white">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="fas fa-save me-2"></i>Guardar Repuesto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_Articulo.js"></script>