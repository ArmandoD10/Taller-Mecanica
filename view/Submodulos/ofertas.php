<?php
require("../../layout.php");
require("../../header.php");
?>

<main class="contenido">
    <div class="container-fluid px-4">
        <h2 class="mt-4 fw-bold"><i class="fas fa-tags me-2 text-danger"></i>Mantenimiento de Ofertas</h2>
        <p class="text-muted small">Gestione exclusivamente descuentos porcentuales para el punto de venta.</p>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div id="tituloForm" class="card-header bg-white fw-bold py-3">
                        <i class="fas fa-plus-circle me-2 text-danger"></i>Nueva Oferta de Descuento
                    </div>
                    <div class="card-body">
                        <form id="formOferta">
                            <input type="hidden" id="id_oferta" name="id_oferta">
                            <input type="hidden" id="id_tipo" name="id_tipo" value="1">

                            <div class="mb-3">
                                <label class="small fw-bold mb-1">Nombre de la Promoción</label>
                                <input type="text" id="nombre_oferta" name="nombre_oferta" class="form-control" placeholder="Ej: Especial de Madres" required>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold mb-1">Porcentaje de Descuento</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold text-danger">%</span>
                                    <input type="number" id="porciento" name="porciento" class="form-control fw-bold" step="0.01" min="0" max="100" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="small fw-bold mb-1">Fecha Inicio</label>
                                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold mb-1">Fecha Fin</label>
                                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="small fw-bold d-block mb-2 text-muted">Estado Inicial</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="estado_promo" id="est_activo" value="activo" checked>
                                    <label class="btn btn-outline-success border-2 fw-bold" for="est_activo">ACTIVO</label>
                                    
                                    <input type="radio" class="btn-check" name="estado_promo" id="est_inactivo" value="inactivo">
                                    <label class="btn btn-outline-secondary border-2 fw-bold" for="est_inactivo">INACTIVO</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 fw-bold py-3 shadow-sm">
                                <i class="fas fa-save me-2"></i>GUARDAR DESCUENTO
                            </button>
                            
                            <button type="button" class="btn btn-link w-100 btn-sm text-muted mt-2" onclick="cancelarEdicionOferta()">
                                Limpiar / Cancelar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Vigencia</th>
                                    <th class="text-center">Valor</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_ofertas"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/modules/Submodulos/Scripts_Oferta.js"></script>