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
    .print-box {
        background-color: white;
    }
    /* Cabecera que solo se verá al imprimir el detalle */
    .print-header { 
        display: none; 
    }

    /* =========================================
       SISTEMA DUAL DE IMPRESIÓN (PRINT CSS)
       ========================================= */
       
    @page {
        margin: 0 !important; 
    }

    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* ----- MODO 1: IMPRESIÓN DE DETALLE DE RECEPCIÓN (MODAL) ----- */
        body.modo-detalle * {
            visibility: hidden;
        }
        
        body.modo-detalle .modal, 
        body.modo-detalle .modal-dialog, 
        body.modo-detalle .modal-content, 
        body.modo-detalle .modal-body {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            border: none !important;
            box-shadow: none !important;
            transform: none !important;
            background: transparent !important;
        }

        body.modo-detalle .print-box {
            visibility: visible;
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            /* Usamos padding en lugar de margin para que no se salga de la hoja */
            padding: 1.5cm !important;
            margin: 0 !important;
            border: none !important; 
        }

        body.modo-detalle .print-box * { visibility: visible; }
        body.modo-detalle .modal-header, 
        body.modo-detalle .modal-footer, 
        body.modo-detalle .no-print { display: none !important; }
        
        body.modo-detalle .print-header { display: block !important; }

        /* CRÍTICO: Quita el bloqueo de Bootstrap que corta la tabla */
        body.modo-detalle .table-responsive {
            overflow: visible !important;
        }
        
        /* Ajuste de fuente para que todo quepa cómodamente en vertical */
        body.modo-detalle table {
            font-size: 12px !important;
            width: 100% !important;
        }


        /* ----- MODO 2: IMPRESIÓN DEL REPORTE GLOBAL (LA TABLA) ----- */
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
            padding: 1.5cm !important; 
        }

        body.modo-reporte table {
            font-size: 11px !important;
        }

        body.modo-reporte .table-responsive {
            overflow: visible !important;
        }

        /* Ocultar elementos innecesarios */
        body.modo-reporte .card-header input, 
        body.modo-reporte .col-acciones {
            display: none !important;
        }
    }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-boxes me-2 text-primary"></i>Historial de Recepciones de Mercancía</h2>
            <div>
                <button class="btn btn-outline-secondary me-2 fw-bold" onclick="imprimirReporteGlobal()">
                    <i class="fas fa-print me-2"></i>Imprimir Reporte
                </button>
                <a href="/Taller/Taller-Mecanica/view/Inventario/RecepcionCompra.php" class="btn btn-primary fw-bold">
                    <i class="fas fa-truck-loading me-2"></i>Registrar Nueva Recepción
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Listado General de Entradas a Almacén</h6>
                <div style="width: 350px;">
                    <input type="text" id="buscadorRecepciones" class="form-control border-dark" placeholder="Buscar por proveedor, conduce u orden...">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>ID Recepción</th>
                                <th>Fecha de Entrada</th>
                                <th>No. Conduce / Factura</th>
                                <th>Proveedor</th>
                                <th>No. Orden (OC)</th>
                                <th>Valor Recibido</th>
                                <th>Estado</th>
                                <th class="col-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetallesRecepcion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-primary">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-check me-2"></i>Artículos Recibidos en la Orden <span id="lbl_submodal_orden" class="text-warning"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalUI('modalDetallesRecepcion')"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="alert alert-info shadow-sm m-4 no-print">
                        <i class="fas fa-info-circle me-2"></i>Esta vista es de solo lectura. Muestra las cantidades que ingresaron físicamente a la góndola del taller.
                    </div>
                    
                    <div class="print-box px-4 pb-4">
                        <div class="text-center border-bottom pb-3 mb-4 print-header">
                            <h4 class="fw-bold text-secondary mb-0">Mecánica Automotriz Díaz Pantaleón</h4>
                            <small class="text-muted">Comprobante de Entrada de Mercancía a Almacén</small>
                            <h5 class="mt-3 text-primary fw-bold">Asociado a Orden: <span id="lbl_submodal_orden_print"></span></h5>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center align-middle bg-white">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Cód/Serie</th>
                                        <th class="text-start">Descripción del Artículo</th>
                                        <th>Cant. Pedida</th>
                                        <th>Cant. Recibida</th>
                                        <th>Subtotal Recibido</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoTablaArticulos"></tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold fs-5">TOTAL VALOR RECIBIDO:</td>
                                        <td class="text-success fw-bold fs-5" id="lbl_total_valor">$ 0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary me-auto" onclick="imprimirDetalle()"><i class="fas fa-print me-2"></i>Imprimir Comprobante</button>
                    <button type="button" class="btn btn-primary" onclick="cerrarModalUI('modalDetallesRecepcion')">Entendido</button>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_HistorialRecepcion.js"></script>
</body>
</html>