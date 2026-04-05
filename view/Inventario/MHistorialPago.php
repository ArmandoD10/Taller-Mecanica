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
    .voucher-box {
        background-color: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 20px;
    }

    /* =========================================
       SISTEMA DUAL DE IMPRESIÓN (PRINT CSS)
       ========================================= */
       
    @page {
        margin: 0 !important; /* Mata la URL y Fecha automática */
    }

    @media print {
        /* Fuerza a la impresora a respetar los fondos oscuros y colores (ahorro de tinta OFF) */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* ----- MODO 1: IMPRESIÓN DEL VOUCHER ----- */
        body.modo-voucher * {
            visibility: hidden;
        }
        
        body.modo-voucher .modal, 
        body.modo-voucher .modal-dialog, 
        body.modo-voucher .modal-content, 
        body.modo-voucher .modal-body {
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

        body.modo-voucher .voucher-box {
            visibility: visible;
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            max-width: 400px !important; 
            padding: 20px !important;
            margin-top: 1.5cm !important;
            margin-left: 1cm !important;
            border: none !important; 
            background-color: white !important;
            color: black !important;
        }

        body.modo-voucher .voucher-box * { visibility: visible; }
        body.modo-voucher .modal-header, 
        body.modo-voucher .modal-footer, 
        body.modo-voucher #alerta_estado_pago { display: none !important; }


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
            padding: 1cm !important; 
        }

        /* Reducimos el tamaño de letra de la tabla solo en el papel para que quepa mejor */
        body.modo-reporte table {
            font-size: 11px !important;
        }

        /* CRÍTICO: Quita el scroll de Bootstrap que corta las tablas al imprimir */
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
            <h2><i class="fas fa-money-check-alt me-2 text-primary"></i>Historial y Auditoría de Pagos</h2>
            <div>
                <button class="btn btn-outline-secondary me-2 fw-bold" onclick="imprimirReporteGlobal()">
                    <i class="fas fa-print me-2"></i>Imprimir Reporte
                </button>
                <a href="/Taller/Taller-Mecanica/view/Inventario/MPagoCompra.php" class="btn btn-outline-primary fw-bold">
                    <i class="fas fa-plus me-2"></i>Registrar Nuevo Pago
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list me-2"></i>Reporte General de Salidas de Dinero</h6>
                <div style="width: 350px;">
                    <input type="text" id="buscadorPagos" class="form-control border-dark" placeholder="Buscar por proveedor, orden o referencia...">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle text-center" id="tablaAuditoria">
                        <thead class="table-dark">
                            <tr>
                                <th>Recibo de Pago</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Abono a la Orden</th>
                                <th>Método de Pago</th>
                                <th>Ref. Bancaria / Cheque</th>
                                <th>Monto Pagado</th>
                                <th>Estado</th>
                                <th class="col-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaPagos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetallePago" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-primary">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt me-2"></i>Comprobante de Pago <span id="lbl_voucher_id" class="text-warning"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalUI('modalDetallePago')"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <div id="alerta_estado_pago"></div>

                    <div class="voucher-box">
                        <div class="text-center border-bottom pb-3 mb-3">
                            <h4 class="fw-bold text-secondary mb-0">Mecánica Automotriz Díaz Pantaleón</h4>
                            <small class="text-muted">Detalle Transaccional a Proveedor</small>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-5 fw-bold text-muted">Fecha y Hora:</div>
                            <div class="col-7 text-end fw-bold" id="vd_fecha"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 fw-bold text-muted">Proveedor:</div>
                            <div class="col-7 text-end fw-bold text-dark" id="vd_proveedor"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 fw-bold text-muted">RNC:</div>
                            <div class="col-7 text-end" id="vd_rnc"></div>
                        </div>
                        <div class="row mb-2 border-bottom pb-2">
                            <div class="col-5 fw-bold text-muted">Aplicado a Orden:</div>
                            <div class="col-7 text-end text-primary fw-bold" id="vd_orden"></div>
                        </div>
                        
                        <div class="row mb-2 pt-2">
                            <div class="col-5 fw-bold text-muted">Vía de Pago:</div>
                            <div class="col-7 text-end" id="vd_metodo"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 fw-bold text-muted">Referencia:</div>
                            <div class="col-7 text-end fst-italic" id="vd_referencia"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 fw-bold text-muted">Moneda:</div>
                            <div class="col-7 text-end" id="vd_moneda"></div>
                        </div>
                        <div class="row mb-3 border-bottom pb-3">
                            <div class="col-5 fw-bold text-muted">Procesado por:</div>
                            <div class="col-7 text-end fw-bold text-info text-decoration-underline" id="vd_usuario"></div>
                        </div>

                        <div class="row align-items-center bg-white p-2 rounded border border-success">
                            <div class="col-5 fw-bold text-success fs-5">TOTAL PAGADO:</div>
                            <div class="col-7 text-end fw-bold text-success fs-3" id="vd_monto"></div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary me-auto" onclick="imprimirVoucher()"><i class="fas fa-print me-2"></i>Imprimir Voucher</button>
                    <button type="button" class="btn btn-primary" onclick="cerrarModalUI('modalDetallePago')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="/Taller/Taller-Mecanica/modules/Inventario/Scripts_HistorialPago.js"></script>
</body>
</html>