<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mb-4 text-success"><i class="fas fa-shield-alt me-2"></i>Maestro DataCrédito</h2>
        
        <div class="card p-4 shadow-sm border-0 mb-4 bg-white" style="border-left: 5px solid #28a745 !important;">
            <div class="row align-items-end">
                <div class="col-md-7">
                    <label class="form-label fw-bold text-success">Documento de Identidad (Cédula/RNC)</label>
                    <input type="text" 
                        class="form-control form-control-lg border-success" 
                        id="cedula_consulta" 
                        placeholder="001-0000000-0" 
                        maxlength="13">
                </div>
                <div class="col-md-5 d-flex gap-2">
                    <button class="btn btn-success btn-lg w-100" onclick="solicitarConsulta()">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                    </button>
                    <button class="btn btn-outline-secondary btn-lg" onclick="limpiarFormularioConsulta() ">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="loader_datacredito" class="text-center d-none my-5">
            <div class="spinner-border text-success" style="width: 3rem; height: 3rem;" role="status"></div>
            <p class="mt-3 fw-bold text-muted">Consultando bases de datos externas...</p>
        </div>

        <div id="msg_error" class="alert alert-danger d-none shadow-sm text-center">
            <i class="fas fa-times-circle fa-2x mb-2"></i><br>
            <strong>Solicitud Denegada:</strong> <span id="error_texto">No se encontraron datos de crédito para este documento.</span>
        </div>

        <div id="reporte_final" class="d-none">
            <div class="alert alert-success d-flex align-items-center shadow-sm mb-4">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div><strong>Solicitud Aprobada:</strong> El perfil crediticio ha sido recuperado exitosamente.</div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">HISTORIAL DE CRÉDITO CONSOLIDADO</h5>
                    <button class="btn btn-light btn-sm" id='btn_guardar_consulta' onclick="guardarConsultaBackend()"><i class="fas fa-save me-1"></i> Guardar Consulta</button>
                </div>
                <div class="card-body bg-white">
                    <div class="row" id="datos_generales_cliente">
                        </div>
                </div>
            </div>

            <h5 class="text-muted border-bottom pb-2 mb-3">Detalle de Cuentas y Obligaciones</h5>
            <div id="contenedor_cuentas">
                </div>
        </div>
    </div>
</main>

<script src="/Taller/Taller-Mecanica/modules/Cliente/Scripts_Apicredito.js"></script>
</body>
</html>