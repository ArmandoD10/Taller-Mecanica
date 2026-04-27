<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mt-4 mb-4 fw-bold"><i class="fas fa-boxes me-2 text-primary"></i>Gestión de Paquetes de Autoadorno</h2>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div id="tituloForm" class="card-header bg-white fw-bold py-3 text-primary">
                        <i class="fas fa-plus-circle me-2"></i>Configurador de Paquete
                    </div>
                    <div class="card-body">
                        <form id="formPaquete">
                            <input type="hidden" id="id_paquete_edit" value="">

                            <div class="mb-3">
                                <label class="small fw-bold mb-1 text-muted">Nombre del Paquete / Combo</label>
                                <input type="text" id="nombre_paquete" class="form-control border-primary shadow-sm" placeholder="Ej: Combo Brillo Extremo" required>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold d-block mb-2 text-muted text-center">Estado del Paquete</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="estado_paquete" id="paq_activo" value="activo" checked>
                                    <label class="btn btn-outline-success border-2 fw-bold" for="paq_activo">ACTIVO</label>
                                    
                                    <input type="radio" class="btn-check" name="estado_paquete" id="paq_inactivo" value="inactivo">
                                    <label class="btn btn-outline-danger border-2 fw-bold" for="paq_inactivo">INACTIVO</label>
                                </div>
                            </div>

                            <div class="mb-3 position-relative">
                                <label class="small fw-bold mb-1 text-muted text-primary">Añadir Productos</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-primary"><i class="fas fa-search text-primary"></i></span>
                                    <input type="text" id="buscar_producto" class="form-control border-primary shadow-sm" placeholder="Escriba nombre del artículo...">
                                </div>
                                <ul id="lista_resultados" class="list-group position-absolute w-100 d-none shadow-lg" style="z-index: 1050; max-height: 200px; overflow-y: auto;"></ul>
                            </div>

                            <div id="contenedor_items_paquete" class="border rounded bg-light p-2 mb-3" style="min-height: 200px; max-height: 350px; overflow-y: auto;">
                                </div>

                            <div class="p-3 bg-dark text-white rounded shadow-sm d-flex justify-content-between align-items-center mb-3">
                                <span class="small">TOTAL COMBO:</span>
                                <h4 class="mb-0 fw-bold" id="total_paquete">RD$ 0.00</h4>
                            </div>

                            <button type="button" class="btn btn-success w-100 py-2 fw-bold shadow-sm" onclick="guardarPaquete()" id="btn_principal_paquete">
                                <i class="fas fa-save me-2"></i>GUARDAR PAQUETE
                            </button>
                            
                            <button type="button" class="btn btn-link w-100 btn-sm text-muted mt-2" onclick="limpiarFormularioPaquete()">
                                Cancelar / Limpiar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 50%;">Nombre del Paquete</th>
                                        <th style="width: 25%;">Total</th>
                                        <th class="text-center" style="width: 25%;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla_paquetes">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/modules/Autoadorno/Scripts_Paquetes.js"></script>
