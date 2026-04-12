<?php require("../../layout.php"); require("../../header.php"); ?>

<style>
    :root { --wa-color: #25D366; --wa-dark: #128C7E; }
    .wa-card { border-top: 5px solid var(--wa-color) !important; border-radius: 12px; }
    .btn-wa { background-color: var(--wa-color); color: white; border: none; font-weight: bold; border-radius: 20px; transition: 0.3s; }
    .btn-wa:hover { background-color: var(--wa-dark); color: white; transform: scale(1.05); }
    .wa-icon { color: var(--wa-color); font-size: 1.5rem; }
</style>

<main class="contenido">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center mt-4 mb-4">
            <i class="fab fa-whatsapp wa-icon me-3"></i>
            <h2 class="fw-bold mb-0">Recordatorios de Mantenimiento</h2>
        </div>

        <div class="card shadow-sm wa-card border-0 bg-white">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold text-muted">CLIENTES PARA CAMBIO DE ACEITE (> 6 MESES)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Cliente</th>
                            <th>Vehículo / Placa</th>
                            <th>Última Visita</th>
                            <th>Estado</th>
                            <th class="text-center">Enviar Mensaje</th>
                        </tr>
                    </thead>
                    <tbody id="tabla_api_whatsapp">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/modules/Submodulos/Scripts_ApiW.js"></script>