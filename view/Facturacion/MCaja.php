<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="mt-4 mb-4 text-center">
            <h2 class="fw-bold"><i class="fas fa-cash-register me-2 text-primary"></i>Gestión de Caja</h2>
            <p class="text-muted small">Control de turnos y fondos para facturación</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <div class="card shadow-lg border-0 d-none" id="panelCajaCerrada">
                    <div class="card-header bg-dark text-white text-center py-3">
                        <h5 class="fw-bold mb-0"><i class="fas fa-lock text-warning me-2"></i>Apertura de Turno</h5>
                    </div>
                    <form id="formApertura" class="card-body p-4 bg-light">
                        <div class="mb-3">
                            <label class="fw-bold small text-dark mb-1">Monto Inicial en Efectivo</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white text-success fw-bold border-success">RD$</span>
                                <input type="number" step="0.01" class="form-control border-success text-center fw-bold" id="monto_inicial" name="monto_inicial" placeholder="0.00" required min="0">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold small text-muted mb-1">Notas de Apertura</label>
                            <textarea class="form-control" name="notas" rows="2" placeholder="Opcional..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow" id="btnAbrir">
                            <i class="fas fa-unlock-alt me-2"></i> Abrir Caja
                        </button>
                    </form>
                </div>

                <div class="card shadow-lg border-success border-2 d-none" id="panelCajaAbierta">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h3 class="fw-bold text-dark">Caja Abierta</h3>
                        <div class="bg-light border rounded p-3 text-start mb-4 shadow-sm">
                            <div class="d-flex justify-content-between mb-2 border-bottom pb-1">
                                <span class="small fw-bold text-muted">Cajero en Turno:</span>
                                <span class="small fw-bold text-primary" id="lbl_usuario_caja">---</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 border-bottom pb-1">
                                <span class="small fw-bold text-muted">Apertura:</span>
                                <span class="small text-dark" id="lbl_fecha_caja">---</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="small fw-bold text-muted">Fondo Inicial:</span>
                                <span class="fw-bold text-success" id="lbl_monto_caja">RD$ 0.00</span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="/Taller/Taller-Mecanica/view/Taller/EntregaServicio.php" class="btn btn-primary fw-bold py-2 shadow-sm">
                                <i class="fas fa-truck-loading me-2"></i> Entrega de Vehículos
                            </a>
                            
                            <a href="/Taller/Taller-Mecanica/view/Facturacion/Factura.php" class="btn btn-info fw-bold py-2 shadow-sm text-dark">
                                <i class="fas fa-file-invoice-dollar me-2"></i> Punto de Venta (POS)
                            </a>

                            <hr class="my-2">

                            <button class="btn btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#modalCierreCaja" onclick="prepararCierre()">
                                <i class="fas fa-lock me-2"></i> Realizar Cierre de Turno
                            </button>
                        </div>
                    </div>
                </div>

                <div class="text-center py-5" id="loaderCaja">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted fw-bold">Sincronizando caja...</p>
                </div>

            </div>
        </div>

        <div class="card shadow-sm border-0 mt-5">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center py-3">
                <span><i class="fas fa-history me-2"></i>Historial de Turnos y Cuadres</span>
                <button class="btn btn-sm btn-outline-light" onclick="cargarHistorialCaja()" title="Actualizar Historial"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0" style="font-size: 14px;">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>N° Turno</th>
                                <th>Cajero</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Fondo Inicial</th>
                                <th>Monto Contado</th>
                                <th>Diferencia</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyHistorialCaja">
                            <tr><td colspan="8" class="py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Cargando historial...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<div class="modal fade" id="modalCierreCaja" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-danger border-2 shadow-lg" id="formCierre">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-lock me-2"></i>Cierre de Turno</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="alert alert-warning small py-2 mb-4 border-warning shadow-sm">
                    <i class="fas fa-info-circle me-1"></i> Por seguridad, el sistema realiza un <b>cierre ciego</b>. Cuente todo el efectivo físico en su gaveta (incluyendo el fondo inicial) e ingrese la cantidad total.
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold small text-dark mb-1">Efectivo Total Contado</label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white text-danger fw-bold border-danger">RD$</span>
                        <input type="number" step="0.01" class="form-control border-danger text-center fw-bold fs-4" id="monto_cierre" name="monto_cierre" placeholder="0.00" required min="0">
                    </div>
                </div>
                <div class="mb-0">
                    <label class="fw-bold small text-muted mb-1">Observaciones o Justificación de Sobrante/Faltante</label>
                    <textarea class="form-control text-muted" name="notas_cierre" rows="2" placeholder="Opcional..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger fw-bold px-4" id="btnCerrarTurno">
                    Confirmar Cierre Definitivo
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Taller/Taller-Mecanica/modules/Facturacion/Scripts_Caja.js"></script>
</body>
</html>