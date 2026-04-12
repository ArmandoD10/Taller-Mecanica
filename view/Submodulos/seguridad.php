<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg border-0" style="border-radius: 15px;">
                    <div class="card-header bg-dark text-white p-3">
                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Seguridad de la Cuenta</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row mb-4 bg-light p-3 rounded mx-0">
                            <div class="col-6">
                                <label class="small fw-bold text-muted">ID USUARIO</label>
                                <p id="txt_id_user" class="mb-0 fw-bold">--</p>
                            </div>
                            <div class="col-6 border-start">
                                <label class="small fw-bold text-muted">USERNAME</label>
                                <p id="txt_username" class="mb-0 text-primary">--</p>
                            </div>
                            <div class="col-12 mt-2 border-top pt-2">
                                <label class="small fw-bold text-muted">CORREO INSTITUCIONAL</label>
                                <p id="txt_correo" class="mb-0">--</p>
                            </div>
                        </div>

                        <form id="form_cambio_pass">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Contraseña Actual</label>
                                <input type="password" id="pass_actual" class="form-control" required>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Nueva Contraseña</label>
                                <input type="password" id="pass_nueva" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold small">Confirmar Nueva Contraseña</label>
                                <input type="password" id="pass_confirma" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                                <i class="fas fa-sync-alt me-2"></i>ACTUALIZAR CREDENCIALES
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-center bg-white border-0 pb-3">
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Solo puede cambiar su clave cada 48 horas.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Submodulos/Scripts_Seguridad.js"></script>