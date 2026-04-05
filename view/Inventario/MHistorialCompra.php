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

    /* =========================================
       SISTEMA DUAL DE IMPRESIÓN (PRINT CSS)
       ========================================= */
       
    /* 1. ELIMINAR LA URL Y FECHA DEL NAVEGADOR */
    @page {
        margin: 0 !important; 
    }

    @media print {
        /* Fuerza a la impresora a respetar los fondos oscuros y colores (ahorro de tinta OFF) */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* ----- MODO REPORTE GLOBAL (LA TABLA) ----- */
        body.modo-reporte * {
            visibility: hidden;
        }
        
        body.modo-reporte .card.shadow-sm, 
        body.modo-reporte .card.shadow-sm * {
            visibility: visible;
        }
        
        body.modo-reporte .card.shadow-sm {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 1cm !important; /* Padding interno para que no corte en los bordes del papel */
        }

        /* Reducimos el tamaño de letra de la tabla solo en el papel para que quepa mejor */
        body.modo-reporte table {
            font-size: 11px !important;
        }

        /* Quita el scroll de Bootstrap que corta las tablas al imprimir */
        body.modo-reporte .table-responsive {
            overflow: visible !important;
        }

        /* Ocultar elementos innecesarios mediante clases explícitas */
        body.modo-reporte .card-header input, 
        body.modo-reporte .col-acciones {
            display: none !important;
        }
    }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Historial y Auditoría de Compras</h2>
            <div>
                <button class="btn btn-outline-secondary me-2 fw-bold" onclick="imprimirReporteGlobal()">
                    <i class="fas fa-print me-2"></i>Imprimir Reporte
                </button>
                <a href="/Taller/Taller-Mecanica/view/Inventario/MCompra.php" class="btn btn-outline-primary fw-bold">
                    <i class="fas fa-external-link-alt me-2"></i>Ir al Gestor de Compras
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Reporte General de Órdenes</h6>
                <div style="width: 300px;">
                    <input type="text" id="buscadorHistorial" class="form-control border-dark" placeholder="Buscar orden o proveedor...">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Orden #</th>
                                <th>Fecha Emisión</th>
                                <th>Proveedor</th>
                                <th>Estado Mercancía</th>
                                <th>Total Orden</th>
                                <th>Balance Pendiente</th>
                                <th>Estado Orden</th>
                                <th class="col-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaHistorial"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetallesOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-primary">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-box-open me-2"></i>Artículos de la Orden <span id="lbl_submodal_orden" class="text-warning"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalUI('modalDetallesOrden')"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-secondary shadow-sm mb-4">
                        <i class="fas fa-info-circle me-2"></i>Vista de solo lectura. Para modificar una orden activa, diríjase al Gestor de Compras.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle bg-white">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Cód/Serie</th>
                                    <th class="text-start">Descripción del Artículo</th>
                                    <th>Precio U.</th>
                                    <th>Cant. Pedida</th>
                                    <th>Cant. Recibida</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaArticulos"></tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold fs-5">VALOR TOTAL ORDEN:</td>
                                    <td class="text-primary fw-bold fs-5" id="lbl_total_valor">$ 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary me-auto" onclick="imprimirOrdenIndividual()"><i class="fas fa-print me-2"></i>Imprimir Orden Original</button>
                    <button type="button" class="btn btn-primary" onclick="cerrarModalUI('modalDetallesOrden')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_HistorialCompra.js"></script>
</body>
</html>