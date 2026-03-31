<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mb-4">Gestión de Créditos y Cobranzas</h2>
        
        <form method="POST" id="formulario">
            <input type="hidden" id="id_oculto" name="id_credito">
            
            <div class="row align-items-stretch mb-4">
                
                <div class="col-lg-6 mb-3">
                    <div class="card p-4 shadow-sm h-100 border-0">
                        <h5 class="mb-4" style="color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            <i class="fas fa-hand-holding-usd me-2"></i>Asignación de Crédito
                        </h5>
                        
                        <div class="row">
                            <div class="col-12 mb-3 position-relative">
                                <label class="form-label fw-bold">Buscar Cliente Beneficiario</label>
                                
                                <input type="text" class="form-control" id="buscador_cliente" placeholder="Escriba el nombre o cédula..." autocomplete="off" required>
                                
                                <input type="hidden" id="id_cliente" name="id_cliente" required>
                                
                                <ul class="list-group position-absolute w-100 d-none shadow-sm" id="lista_clientes" style="z-index: 1000; max-height: 200px; overflow-y: auto; top: 100%;">
                                </ul>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Monto Aprobado (RD$)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="monto_credito" name="monto_credito" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ref. DataCrédito (Opcional)</label>
                                <input type="text" class="form-control" id="referencia_datacredito" name="referencia_datacredito" placeholder="Ej. DC-99823">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3">
                    <div class="card p-4 shadow-sm h-100 border-0">
                        <h5 class="mb-4" style="color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            <i class="fas fa-calendar-check me-2"></i>Condiciones y Estado
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                            </div>
                            
                            <div class="col-md-6 mb-3 d-none" id="contenedor_estado_credito">
                                <label class="form-label fw-bold">Estado del Crédito</label>
                                <select class="form-select" id="estado_credito" name="estado_credito">
                                    <option value="Activo">Activo</option>
                                    <option value="Pagado">Pagado</option>
                                    <option value="Vencido">Vencido</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                            
                            <div class="col-12 text-muted small mt-2">
                                <i class="fas fa-info-circle me-1"></i> El saldo pendiente iniciará en RD$ 0.00 y se consumirá automáticamente al facturar a crédito.
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>

            <div class="d-flex gap-4 mb-4 mt-0">
                <button type="submit" class="btn btn-success" style="width: 150px" id="btnGuardar">Aprobar Crédito</button>
                <button type="button" class="btn btn-secondary" style="width: 150px" onclick="limpiarFormulario()">Limpiar</button>
            </div>

            <hr class="mt-5 mb-5">

            <div class="mt-5">
                <h2>Historial de Líneas de Crédito</h2>
                <div class="mb-4 p-3 bg-light rounded d-flex align-items-center gap-3">
                    <div class="input-group" style="width: 40%;">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="filtro" placeholder="Buscar por nombre de cliente o cédula...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-2" id="tabladatos">
                        <thead class="table-dark">
                            <tr>
                                <th class="p-2">Id</th>
                                <th>Cliente</th>
                                <th>Cédula/RNC</th>
                                <th>Monto Aprobado</th>
                                <th>Saldo Deudor</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th>Acciones</th> 
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla"></tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Cliente/Scripts_Credito.js"></script>
</body>
</html>