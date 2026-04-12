<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-11 col-lg-9">
                
                <div class="card shadow-lg border-0 overflow-hidden" style="border-radius: 15px;">
                    <div class="row g-0">
                        
                        <div class="col-md-4 bg-primary d-flex flex-column align-items-center justify-content-center p-4 text-white text-center">
                            <div class="mb-3">
                                <i class="fas fa-user-circle fa-8x shadow-sm rounded-circle"></i>
                            </div>
                            <h4 id="perfil_nombre_completo" class="fw-bold mb-1 text-uppercase">...</h4>
                            <span id="perfil_puesto" class="badge bg-white text-primary px-3 py-2 fs-6 mt-2 rounded-pill shadow-sm">
                                Cargando...
                            </span>
                        </div>

                        <div class="col-md-8 bg-white p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                                <h5 class="text-primary fw-bold mb-0"><i class="fas fa-id-card me-2"></i>Información del Empleado</h5>
                                <span id="perfil_nivel" class="badge bg-light text-muted border">--</span>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="small text-muted fw-bold d-block text-uppercase"><i class="fas fa-at me-1"></i> Usuario</label>
                                    <span id="perfil_username" class="fs-5 text-dark">--</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-muted fw-bold d-block text-uppercase"><i class="fas fa-envelope me-1"></i> Correo Institucional</label>
                                    <span id="perfil_correo_org" class="text-info fw-bold">--</span>
                                </div>

                                <div class="col-md-6">
                                    <label class="small text-muted fw-bold d-block text-uppercase"><i class="fas fa-building me-1"></i> Sucursal</label>
                                    <span id="perfil_sucursal" class="text-dark">--</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-muted fw-bold d-block text-uppercase"><i class="fas fa-sitemap me-1"></i> Departamento</label>
                                    <span id="perfil_dep" class="text-dark">--</span>
                                </div>

                                <div class="col-md-6">
                                    <label class="small text-muted fw-bold d-block text-uppercase"><i class="fas fa-phone me-1"></i> Teléfono</label>
                                    <span id="perfil_tel" class="text-dark">--</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="small text-muted fw-bold d-block text-uppercase"><i class="fas fa-address-card me-1"></i> Cédula</label>
                                    <span id="perfil_cedula" class="text-dark">--</span>
                                </div>

                                <div class="col-md-12">
                                    <label class="small text-muted fw-bold d-block text-uppercase"><i class="fas fa-map-marker-alt me-1"></i> Dirección de Residencia</label>
                                    <span id="perfil_dir" class="text-muted italic">--</span>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top text-end">
                                <small class="text-muted italic">Mecánica Díaz Pantaleón - Sistema de Gestión Interna (SIG)</small>
                            </div>
                        </div>
                    </div> </div> </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Submodulos/Scripts_Perfil.js"></script>