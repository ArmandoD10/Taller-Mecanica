<?php
require("../../layout.php");
require("../../header.php");
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<main class="contenido mb-5">
    <div class="container mt-5 text-center">
        <h2 class="mb-4"><i class="fas fa-id-badge me-2 text-primary"></i>Mi Identificación Institucional</h2>
        
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                
                <div id="carnet_para_captura" class="card shadow-lg border-0 mx-auto" style="width: 350px; border-radius: 20px; background: white; overflow: hidden; border: 1px solid #eee !important;">
                    
                    <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-center">
                        <img src="/Taller/Taller-Mecanica/img/logo.png" alt="Logo" style="height: 40px; margin-right: 10px;">
                        <span class="fw-bold text-dark small text-uppercase" style="letter-spacing: 1px;">Mecánica Díaz Pantaleón</span>
                    </div>

                    <div class="card-body py-4">
                        <div class="mb-3">
                            <div class="bg-primary mx-auto d-flex align-items-center justify-content-center shadow" style="width: 130px; height: 130px; border-radius: 15%; border: 4px solid #f8f9fa;">
                                <i class="fas fa-user text-white" style="font-size: 80px;"></i>
                            </div>
                        </div>

                        <div class="mt-3">
                            <h4 id="carnet_nombre" class="fw-bold text-dark mb-1 text-uppercase">...</h4>
                            <p id="carnet_puesto" class="text-primary fw-bold mb-3" style="font-size: 1.1rem;">...</p>
                            
                            <div class="bg-light p-2 rounded-3 mx-4">
                                <span class="small text-muted d-block fw-bold text-uppercase">Código de Empleado</span>
                                <span id="carnet_id" class="fs-4 fw-bold text-dark">000</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-primary p-2">
                        <small class="text-white fw-bold">EMPLEADO ACTIVO</small>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-dark btn-lg shadow-sm fw-bold px-5" onclick="imprimirCarnet()">
                        <i class="fas fa-print me-2 text-warning"></i> IMPRIMIR CARNET
                    </button>
                    <p class="text-muted small mt-2">Se generará una imagen descargable de su carnet.</p>
                </div>

            </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Submodulos/Scripts_Carnet.js"></script>