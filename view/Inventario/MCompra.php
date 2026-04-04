<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
    .modal { 
        z-index: 105000 !important; 
    }
    .modal-backdrop { 
        z-index: 104900 !important; 
    }
    #modalCompra .modal-body { 
        max-height: 70vh; 
        overflow-y: auto; 
    }
    /* Estilo para que nuestro buscador luzca como un dropdown pegado al input */
    .lista-resultados {
        z-index: 106000; 
        display: none; 
        max-height: 200px; 
        overflow-y: auto; 
        top: 100%; 
        left: 0;
        cursor: pointer;
    }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-shopping-cart me-2 text-primary"></i>Órdenes de Compra a Proveedores</h2>
            <button class="btn btn-primary" onclick="nuevaCompra()">
                <i class="fas fa-plus me-2"></i>Crear Orden de Compra
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Orden #</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Cant. Artículos</th>
                                <th>Monto Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCompra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable mt-4">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModal">Formulario de Compra</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body bg-light p-4">
                    
                    <div id="contenedorBanner"></div> 

                    <form id="formCompraCabecera">
                        <input type="hidden" id="id_compra" name="id_compra">

                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                            <i class="fas fa-file-invoice me-2"></i>1. Datos de la Orden
                        </h6>
                        <div class="row">
                            <div class="col-md-4 mb-3 position-relative">
                                <label class="fw-bold mb-1">Buscar Proveedor <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-dark" id="buscar_proveedor" placeholder="Escriba nombre o RNC..." autocomplete="off">
                                <input type="hidden" id="id_proveedor" required> <div id="lista_proveedores" class="list-group position-absolute w-100 shadow lista-resultados"></div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold mb-1">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" id="id_metodo" required>
                                    <option value="">Seleccione método...</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="fw-bold mb-1">Moneda <span class="text-danger">*</span></label>
                                <select class="form-select border-dark" id="id_moneda" required>
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold mb-1">Estado de Orden</label>
                                <select class="form-select border-dark" id="estado" required>
                                    <option value="activo">Procesada</option>
                                    <option value="inactivo">En Borrador</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label class="fw-bold">Detalle / Notas Generales</label>
                                <input type="text" class="form-control border-dark" id="detalle" placeholder="Ej: Pedido urgente para reparación de flotilla">
                            </div>
                        </div>
                    </form>

                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-boxes me-2"></i>2. Agregar Artículos (Carrito)
                    </h6>
                    <div class="row align-items-end mb-3 bg-white p-3 rounded border border-2 border-secondary" id="seccion_agregar_articulos">
                        
                        <div class="col-md-5 position-relative">
                            <label class="fw-bold fs-6 mb-1">Buscar Artículo / Repuesto</label>
                            <input type="text" class="form-control border-dark" id="buscar_articulo" placeholder="Escriba nombre o código..." autocomplete="off">
                            <input type="hidden" id="id_articulo_seleccionado"> <div id="lista_articulos" class="list-group position-absolute w-100 shadow lista-resultados"></div>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="fw-bold fs-6 mb-1">Precio U.</label>
                            <input type="number" class="form-control border-dark text-end fw-bold" id="input_precio" placeholder="0.00" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold fs-6 mb-1">Cantidad</label>
                            <input type="number" class="form-control border-dark text-center" id="input_cantidad" placeholder="1" min="1" value="1">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-dark w-100" id="btnAgregarArticulo">
                                <i class="fas fa-cart-plus me-2"></i>Añadir a la Orden
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle bg-white">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Cód/Serie</th>
                                    <th class="text-start">Descripción del Artículo</th>
                                    <th>Precio Compra</th>
                                    <th>Cant.</th>
                                    <th>Subtotal</th>
                                    <th id="col_quitar">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaDetalles"></tbody>
                            <tfoot class="table-light fw-bold fs-5">
                                <tr>
                                    <td colspan="4" class="text-end text-primary">TOTAL ORDEN:</td>
                                    <td class="text-primary text-end px-3" id="lblTotalOrden">$ 0.00</td>
                                    <td id="col_quitar_foot"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalUI('modalCompra')">Cerrar</button>
                    <button type="button" class="btn btn-success" id="btnGuardarCompra">
                        <i class="fas fa-save me-2"></i>Guardar y Procesar Orden
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_Compra.js"></script>
</body>
</html>