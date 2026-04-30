<?php
require("../../layout.php");
require("../../header.php");
?>

<style>
    :root { --form-border-color: #0b2a70; --form-bg-header: #e6edff; }
    .hoja-inspeccion { background: #fff; border: 2px solid var(--form-border-color); padding: 20px; max-width: 1000px; margin: 0 auto; font-family: Arial, sans-serif; font-size: 11px; color: #000; }
    .section-title { background-color: var(--form-border-color); color: white; padding: 5px 10px; font-weight: bold; text-align: center; margin-top: 10px; margin-bottom: 8px; font-size: 13px; }
    .sub-section-title { background-color: var(--form-bg-header); color: var(--form-border-color); border: 1px solid var(--form-border-color); padding: 4px; text-align: center; font-weight: bold; }
    .table-inspeccion { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .table-inspeccion th, .table-inspeccion td { border: 1px solid #ccc; padding: 2px 4px; vertical-align: middle; }
    .car-diagram-box { border: 1px solid #ccc; text-align: center; padding: 5px; }
    .img-referencia { max-width: 100%; height: auto; object-fit: contain; }

    #lista_clientes_busqueda, #resultados_trabajos { z-index: 1050; max-height: 200px; overflow-y: auto; }

    @page { size: letter; margin: 0; }
    @media print {
        body * { visibility: hidden; }
        #hoja-impresion, #hoja-impresion * { visibility: visible; }
        #hoja-impresion { position: absolute; left: 0; top: 0; width: 100%; border: none; padding: 10mm; margin: 0; }
        .btn-imprimir, header, footer, .sidebar, nav, .navbar { display: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        select, input { border: none !important; background: transparent !important; appearance: none; -webkit-appearance: none; -moz-appearance: none; font-weight: bold; padding: 0 !important; }
        #lista_clientes_busqueda, #buscar_trabajo_container, .btn-close { display: none !important; } 
        .input-group-text { background: transparent !important; border: none !important; }
        #msg_contexto { display: none !important; }
        #lista_trabajos_agregados { border: none !important; background: transparent !important; padding: 0 !important; }
        .badge { color: #000 !important; background-color: transparent !important; border: 1px solid #000 !important; padding: 2px 5px !important; margin-bottom: 2px !important; }
    }
</style>

<main class="contenido mb-5">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-3 mt-3 btn-imprimir">
            <h3 class="mb-0">Recepción de Vehículo</h3>
            <div>
                <button class="btn btn-primary me-2" onclick="window.print()"><i class="fas fa-print me-2"></i>Imprimir</button>
                <button type="submit" form="formulario_inspeccion" class="btn btn-success" id="btnGuardar"><i class="fas fa-save me-2"></i>Guardar Inspección</button>
            </div>
        </div>

        <div class="hoja-inspeccion" id="hoja-impresion">
            <form id="formulario_inspeccion" method="POST">
                
                <input type="hidden" id="id_orden" name="id_orden" value="">
                <div id="msg_contexto"></div>
                <div class="row align-items-center mb-2">
                    <div class="col-4 text-center">
                        <img src="../../img/logo.png" alt="Logo Taller" style="max-width: 180px; height: auto; display: block; margin: 0 auto;">
                        <small class="fw-bold d-block mt-1" style="color: var(--form-border-color); font-size: 10px;">MECÁNICA AUTOMOTRIZ</small>
                    </div>
                    <div class="col-4 text-center"><h5 class="fw-bold mb-0">HOJA DE INSPECCIÓN</h5></div>
                    <div class="col-4">
                        <table class="table table-bordered table-sm mb-0">
                            <tr><th class="bg-light p-1">FECHA</th><td><input type="date" class="form-control form-control-sm border-0" value="<?= date('Y-m-d') ?>"></td></tr>
                            <tr><th class="bg-light p-1">ASESOR</th><td><select class="form-select form-select-sm border-0 fw-bold" id="id_empleado" name="id_empleado" required></select></td></tr>
                        </table>
                    </div>
                </div>

                <div class="section-title text-start ps-3 mt-0">INFORMACIÓN DEL CLIENTE Y VEHÍCULO</div>
                <div class="row px-2">
                    <div class="col-6 mb-1 position-relative">
                        <label class="fw-bold">Buscar Cliente:</label>
                        <input type="text" class="form-control form-control-sm border-dark" id="txt_buscar_cliente" placeholder="Nombre o Cédula..." autocomplete="off">
                        <input type="hidden" id="id_cliente" name="id_cliente" required>
                        <ul class="list-group position-absolute w-100 d-none shadow" id="lista_clientes_busqueda"></ul>
                    </div>
                    <div class="col-6 mb-1">
                        <label class="fw-bold">Vehículo Vinculado:</label>
                        <select class="form-select form-select-sm border-dark" id="id_vehiculo" name="id_vehiculo" required>
                            <option value="">Seleccione un cliente primero...</option>
                        </select>
                    </div>
                </div>

                <div class="row px-2 mt-2">
                    <div class="col-6">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text fw-bold">KM</span>
                            <input type="number" class="form-control border-dark" name="kilometraje_recepcion" required>
                            <div class="input-group-text border-dark">
                                <input class="form-check-input mt-0 me-1" type="radio" name="km_milla" value="Kms" checked> Kms
                                <input class="form-check-input mt-0 ms-2 me-1" type="radio" name="km_milla" value="Millas"> Mi
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text fw-bold">Nivel Combustible</span>
                            <select class="form-select border-dark" name="nivel_combustible" required>
                                <option value="Reserva">Reserva (E)</option>
                                <option value="1/4">1/4</option>
                                <option value="1/2" selected>1/2</option>
                                <option value="3/4">3/4</option>
                                <option value="Lleno">Lleno (F)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section-title text-start ps-3 mt-2">MOTIVO DE VISITA / TRABAJOS SOLICITADOS</div>
                <div class="row px-2 mb-2">
                    <div class="col-md-12 mb-2" id="buscar_trabajo_container">
                        <div class="input-group input-group-sm position-relative">
                            <span class="input-group-text bg-white border-dark"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="buscar_trabajo" class="form-control border-dark" placeholder="Buscar y añadir trabajos del catálogo..." oninput="buscarTrabajos(this.value)">
                            <div id="resultados_trabajos" class="list-group position-absolute w-100 shadow d-none" style="top: 100%;"></div>
                        </div>
                    </div>
                    
                    <div class="col-md-12 mb-2">
                        <div id="lista_trabajos_agregados" class="d-flex flex-wrap gap-1 p-2 bg-light rounded border border-dark min-vh-50" style="min-height: 40px;">
                            <span class="text-muted w-100 text-center pt-1" style="font-style: italic;">No se han añadido trabajos específicos.</span>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <textarea class="form-control form-control-sm border-dark" name="motivo_visita" id="motivo_visita" rows="2" placeholder="Notas adicionales del cliente..."></textarea>
                    </div>
                </div>

                <div class="section-title text-start ps-3 mt-2">ESTADO GENERAL DEL VEHÍCULO</div>
                <div class="text-center fw-bold mb-2" style="font-size: 12px; color: var(--form-border-color);">
                    LEYENDA: &nbsp;&nbsp; <span class="border px-2 py-1 mx-1">B = Bueno</span> &nbsp;&nbsp; <span class="border px-2 py-1 mx-1">F = Faltante</span> &nbsp;&nbsp; <span class="border px-2 py-1 mx-1">D = Dañado</span>
                </div>

                <div class="row px-2">
                    <div class="col-4">
                        <div class="sub-section-title">Interior</div>
                        <table class="table-inspeccion">
                            <thead><tr><th>Elemento</th><th>B</th><th>F</th><th>D</th></tr></thead>
                            <tbody>
                                <?php
                                $items = ['Beeper', 'Pito/Bocina', 'Luces int.', 'Aire Cond.', 'Radio', 'Cristales', 'Seguros', 'Retrovisor'];
                                foreach ($items as $i => $item) {
                                    echo "<tr><td>$item</td><td class='text-center'><input type='radio' name='int_$i' value='B' checked></td><td class='text-center'><input type='radio' name='int_$i' value='F'></td><td class='text-center'><input type='radio' name='int_$i' value='D'></td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-4">
                        <div class="sub-section-title">Exterior</div>
                        <table class="table-inspeccion">
                            <thead><tr><th>Elemento</th><th>B</th><th>F</th><th>D</th></tr></thead>
                            <tbody>
                                <?php
                                $items_ext = ['Goma Rep.', 'Gato', 'Herram.', 'Llave Rueda', 'Luces Tras.', 'Tapa Comb.', 'Botiquín', 'Triángulo'];
                                foreach ($items_ext as $i => $item) {
                                    echo "<tr><td>$item</td><td class='text-center'><input type='radio' name='ext_$i' value='B' checked></td><td class='text-center'><input type='radio' name='ext_$i' value='F'></td><td class='text-center'><input type='radio' name='ext_$i' value='D'></td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-4">
                        <div class="sub-section-title">Motor</div>
                        <table class="table-inspeccion">
                            <thead><tr><th>Elemento</th><th>B</th><th>F</th><th>D</th></tr></thead>
                            <tbody>
                                <?php
                                $items_mot = ['Varilla Aceite', 'Tapón Aceite', 'Radiador', 'Batería', 'Agua L/V', 'Filtro Aire', 'Correas', 'Tapas'];
                                foreach ($items_mot as $i => $item) {
                                    echo "<tr><td>$item</td><td class='text-center'><input type='radio' name='mot_$i' value='B' checked></td><td class='text-center'><input type='radio' name='mot_$i' value='F'></td><td class='text-center'><input type='radio' name='mot_$i' value='D'></td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="section-title text-start ps-3 mt-2">ESTADO FÍSICO Y TESTIGOS</div>
                <div class="row px-2">
                    <div class="col-3">
                        <div class="border p-2 bg-light mb-2">
                            <p class="mb-1 fw-bold">Simbología Daños:</p>
                            <ul class="list-unstyled mb-0 ms-1" style="font-size: 10px;">
                                <li><strong>X</strong> = Falta pieza | <strong>O</strong> = Abolladura</li>
                                <li><strong>—</strong> = Rayazo | <strong>∆</strong> = Roto</li>
                            </ul>
                        </div>
                        <img src="../../img/iconos_motor.jpeg" alt="Testigos" class="img-referencia">
                    </div>
                    <div class="col-9">
                        <div class="car-diagram-box">
                            <div class="row gx-1">
                                <div class="col-6 border-end" style="height: 250px;"><img src="../../img/vehicle-diagram-Converted.jpg" class="img-referencia" style="max-height: 100%;"></div>
                                <div class="col-6" style="height: 250px;"><img src="../../img/interior.png" class="img-referencia" style="max-height: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row text-center mt-5 mb-2 pb-2">
                    <div class="col-6 mb-2">
                        <div class="mx-auto" style="width: 70%; border-bottom: 1px solid #000; height: 40px;"></div>
                        <label class="fw-bold mt-1 text-dark" style="font-size: 12px;">Firma del Cliente</label>
                        <p class="text-muted mb-0" style="font-size: 9px;">Acepta las condiciones de recepción</p>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="mx-auto" style="width: 70%; border-bottom: 1px solid #000; height: 40px;"></div>
                        <label class="fw-bold mt-1 text-dark" style="font-size: 12px;">Firma Asesor de Servicio</label>
                        <p class="text-muted mb-0" style="font-size: 9px;">Mecánica Automotriz Díaz & Pantaleón</p>
                    </div>
                </div>

            </form>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/Taller/Taller-Mecanica/modules/Taller/Scripts_Inspeccion.js"></script>
</body>
</html>