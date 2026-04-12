<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="mt-4 mb-4 no-print">
            <h2 class="mb-0 fw-bold text-dark"><i class="fas fa-id-card-alt me-2 text-warning"></i>Gestión de Membresías</h2>
            <p class="text-muted mb-0">Configuración de planes y reporte de suscriptores</p>
        </div>

        <ul class="nav nav-tabs fw-bold mb-4 no-print">
            <li class="nav-item">
                <a class="nav-link active text-dark" id="tab-btn-planes" onclick="cambiarPestanaManual('planes', this)" style="cursor: pointer;">
                    <i class="fas fa-list-ul me-1"></i> Mantenimiento de Planes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" id="tab-btn-reporte" onclick="cambiarPestanaManual('reporte', this)" style="cursor: pointer;">
                    <i class="fas fa-users me-1"></i> Suscriptores
                </a>
            </li>
        </ul>

        <div id="panel-planes" class="panel-seccion">
            <div class="d-flex justify-content-end mb-3 no-print">
                <button type="button" class="btn btn-warning shadow-sm fw-bold text-dark" onclick="abrirModalManual('modalPlanMembresia')">
                    <i class="fas fa-plus me-2"></i>Nuevo Plan
                </button>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Tipo Membresía</th>
                                <th>Costo (Precio)</th>
                                <th>Límite Lavados</th>
                                <th>Estado</th>
                                <th class="no-print">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaPlanes"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="panel-reporte" class="panel-seccion d-none">
            <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                <div class="input-group w-50 shadow-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control" id="buscadorReporte" placeholder="Buscar cliente...">
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-dark shadow-sm fw-bold" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                    <button type="button" class="btn btn-success shadow-sm fw-bold" onclick="abrirModalAsignar()">
                        <i class="fas fa-user-plus me-2"></i>Asignar Membresía
                    </button>
                </div>
            </div>
            
            <h4 class="d-none d-print-block text-center mb-4">Reporte de Suscriptores Activos</h4>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-dark small text-uppercase">
                            <tr>
                                <th class="text-start">Cliente</th>
                                <th>Membresía Contratada</th>
                                <th>Vigencia</th>
                                <th>Restantes</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaReporte"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal no-print" id="modalPlanMembresia" tabindex="-1" style="display:none; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-warning border-2 shadow-lg">
                <div class="modal-header bg-warning text-dark py-3">
                    <h5 class="modal-title fw-bold" id="tituloModalPlan"><i class="fas fa-star me-2"></i>Registrar Plan</h5>
                    <button type="button" class="btn-close" onclick="cerrarModalManual('modalPlanMembresia')"></button>
                </div>
                <form id="formPlanMembresia">
                    <div class="modal-body bg-light">
                        <input type="hidden" id="id_plan" name="id_plan">
                        <div class="mb-3">
                            <label class="fw-bold small text-dark mb-1">Tipo de Membresía <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-warning fw-bold" id="nombre_tipo_membresia" name="nombre_tipo_membresia" list="listaTiposMembresia" autocomplete="off" required>
                            <datalist id="listaTiposMembresia"></datalist>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="fw-bold small text-dark mb-1">Precio Mensual <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_precio" name="id_precio" required></select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold small text-dark mb-1">Límite de Lavados</label>
                                <input type="number" class="form-control" id="limite_lavado" name="limite_lavado" value="0" required>
                                <small class="text-muted" style="font-size: 10px;">0 = Ilimitados</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-secondary fw-bold" onclick="cerrarModalManual('modalPlanMembresia')">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold text-dark shadow-sm"><i class="fas fa-save me-1"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal no-print" id="modalAsignarMembresia" tabindex="-1" style="display:none; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-success border-2 shadow-lg">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-check me-2"></i>Nueva Suscripción</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="cerrarModalManual('modalAsignarMembresia')"></button>
                </div>
                <form id="formAsignarMembresia">
                    <div class="modal-body bg-light">
                        <div class="mb-3 position-relative">
                            <label class="fw-bold small text-dark mb-1">Buscar Cliente</label>
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="busc_asig_cliente" placeholder="Escriba nombre o documento..." oninput="buscarParaAsignar(this)">
                            </div>
                            <ul class="list-group position-absolute w-100 d-none shadow" id="res_busc_asig" style="z-index: 1060; max-height: 200px; overflow-y: auto;"></ul>
                            <input type="hidden" id="id_cliente_asig" name="id_cliente_asig" required>
                        </div>

                        <div class="bg-white p-2 border rounded mb-3 d-none text-center" id="info_asig_seleccionado">
                            <span class="d-block fw-bold text-success" id="lbl_asig_cliente"></span>
                            <small class="text-muted" id="lbl_asig_doc"></small>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold small text-dark mb-1">Seleccionar Plan <span class="text-danger">*</span></label>
                            <select class="form-select border-success" id="id_plan_asig" name="id_plan_asig" onchange="seleccionarPlanAsig(this)" required>
                                <option value="" disabled selected>Cargando planes...</option>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="fw-bold small text-dark mb-1">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio_asig" name="fecha_inicio_asig" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold small text-dark mb-1">Vencimiento</label>
                                <input type="date" class="form-control" id="fecha_vencimiento_asig" name="fecha_vencimiento_asig" required>
                            </div>
                        </div>

                        <div class="mb-0 text-center">
                            <label class="fw-bold small text-dark mb-1">Lavados Incluidos</label>
                            <input type="number" class="form-control fw-bold text-center w-50 mx-auto" id="lavados_asig" name="lavados_asig" readonly>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-secondary fw-bold" onclick="cerrarModalManual('modalAsignarMembresia')">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold shadow-sm"><i class="fas fa-check-circle me-1"></i> Activar Suscripción</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body { background-color: white !important; }
            .no-print, nav, .sidebar { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            #panel-planes { display: none !important; }
            #panel-reporte { display: block !important; opacity: 1 !important; }
            table { width: 100% !important; border-collapse: collapse; }
            th, td { border: 1px solid #ddd !important; padding: 8px !important; }
        }
    </style>
</main>
<script src="/Taller/Taller-Mecanica/modules/Autolavado/Scripts_Membresia.js"></script>
</body>
</html>