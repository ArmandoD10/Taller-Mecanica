<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h2><i class="fas fa-shield-alt me-2 text-primary"></i>Historial de Garantías</h2>
            <button class="btn btn-secondary shadow-sm fw-bold" onclick="cargarGarantias()">
                <i class="fas fa-sync-alt me-2"></i>Actualizar
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white shadow-sm border-0">
                    <div class="card-body text-center">
                        <h6 class="fw-bold mb-1">Activas</h6>
                        <h3 class="mb-0 fw-bold" id="lbl_activas">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark shadow-sm border-0">
                    <div class="card-body text-center">
                        <h6 class="fw-bold mb-1">Vencidas</h6>
                        <h3 class="mb-0 fw-bold" id="lbl_vencidas">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white shadow-sm border-0">
                    <div class="card-body text-center">
                        <h6 class="fw-bold mb-1">Anuladas</h6>
                        <h3 class="mb-0 fw-bold" id="lbl_anuladas">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center py-3">
                <span><i class="fas fa-list me-2"></i>Certificados Emitidos</span>
                <input type="text" id="buscador_garantia" class="form-control form-control-sm w-25" placeholder="Buscar placa, cliente..." onkeyup="filtrarTabla()">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th class="text-start">Cliente</th>
                                <th class="text-start">Vehículo</th>
                                <th>Emisión</th>
                                <th>Vence</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaGarantias">
                            <tr><td colspan="7" class="py-4 text-muted">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalAnular" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger border-2 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-shield-lock me-2"></i>Autorización de Cancelación</h5>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarModalAnular()"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" id="anular_id_garantia">
                <div class="text-center mb-3">
                    <p class="text-muted small mb-1">CÓDIGO DE CERTIFICADO</p>
                    <h4 class="text-danger fw-bold" id="lbl_codigo_anular"></h4>
                </div>

                <div class="bg-white p-3 border rounded mb-3">
                    <h6 class="fw-bold mb-3 border-bottom pb-2 text-secondary"><i class="fas fa-user-shield me-2"></i>Validación Admin</h6>
                    <div class="mb-2">
                        <label class="fw-bold small mb-1">Usuario</label>
                        <input type="text" id="admin_user" class="form-control border-danger" required autocomplete="off">
                    </div>
                    <div class="mb-0">
                        <label class="fw-bold small mb-1">Contraseña</label>
                        <input type="password" id="admin_pass" class="form-control border-danger" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold small mb-1">Motivo de Anulación</label>
                    <textarea id="anular_motivo" class="form-control" rows="2" placeholder="Especifique el motivo..." required></textarea>
                </div>

                <div class="alert alert-warning small p-2 mb-0 border-warning">
                    <i class="fas fa-info-circle me-1"></i> Esta acción es irreversible y el certificado perderá toda validez legal.
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary fw-bold" onclick="cerrarModalAnular()">Cancelar</button>
                <button type="button" class="btn btn-danger fw-bold px-4" onclick="confirmarAnulacion()">Validar y Anular</button>
            </div>
        </div>
    </div>
</div>

<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_HistorialGarantias.js"></script>
</body>
</html>