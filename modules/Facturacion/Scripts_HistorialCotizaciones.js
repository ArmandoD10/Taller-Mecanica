document.addEventListener("DOMContentLoaded", () => {
    cargarHistorialCotizaciones();
});

document.getElementById("form_filtros_cotizaciones").addEventListener("submit", function(e) {
    e.preventDefault();
    cargarHistorialCotizaciones();
});

function cargarHistorialCotizaciones() {
    const form = document.getElementById("form_filtros_cotizaciones");
    const formData = new FormData(form);

    const tbody = document.getElementById("cuerpoTablaHistorialCot");
    tbody.innerHTML = `<tr><td colspan="8" class="py-5 text-muted fw-bold"><i class="fas fa-spinner fa-spin me-2"></i>Cargando datos...</td></tr>`;

    fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=listar_historial", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(c => {
                // Insignias para el Estado
                let badgeEstado = "bg-warning text-dark"; // Pendiente
                if (c.estado === 'Aprobada') badgeEstado = "bg-success";
                else if (c.estado === 'Rechazada') badgeEstado = "bg-danger";

                // Insignias para el Tipo (Taller / POS)
                let badgeTipo = c.tipo_cotizacion === 'POS' ? '<span class="badge bg-success">POS</span>' : '<span class="badge bg-primary">Taller</span>';
                
                // Aviso de Ocasional
                let warningOc = c.es_ocasional == 1 ? '<span class="badge bg-warning text-dark ms-1" title="Cliente Ocasional"><i class="fas fa-exclamation-triangle"></i></span>' : '';

                const isRechazada = c.estado === 'Rechazada';
                const filaEstilo = isRechazada ? 'text-decoration-line-through text-muted' : '';

                const tr = document.createElement("tr");
                tr.className = `fila-cotizacion-historial ${filaEstilo}`; 
                tr.innerHTML = `
                    <td class="fw-bold text-dark">COT-${c.id_cotizacion}</td>
                    <td class="small">${c.fecha}</td>
                    <td class="text-start fw-bold">${c.cliente} ${warningOc}</td>
                    <td class="text-start small">${c.vehiculo}</td>
                    <td>${badgeTipo}</td>
                    <td><span class="badge ${badgeEstado}">${c.estado}</span></td>
                    <td class="text-end fw-bold">RD$ ${parseFloat(c.monto_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="abrirDetalleCot(${c.id_cotizacion}, '${c.cliente}', '${c.vehiculo}', '${c.fecha}', '${c.tipo_cotizacion}', '${c.estado}', ${c.monto_total})" title="Ver Detalles y Reimprimir">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="py-5 text-muted fw-bold text-center">No hay cotizaciones en este rango de fechas.</td></tr>`;
        }
        
        document.getElementById("buscador_dinamico").value = "";
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="8" class="py-5 text-danger fw-bold text-center">Error de conexión con el servidor.</td></tr>`;
    });
}

function filtrarTablaCotizaciones() {
    const texto = document.getElementById("buscador_dinamico").value.toLowerCase();
    const filas = document.querySelectorAll(".fila-cotizacion-historial");
    
    filas.forEach(fila => {
        const contenidoFila = fila.innerText.toLowerCase();
        fila.style.display = contenidoFila.includes(texto) ? "" : "none";
    });
}

function abrirDetalleCot(id_cotizacion, cliente, vehiculo, fecha, tipo, estado, total) {
    document.getElementById("det_cot_numero").innerText = `COT-${id_cotizacion}`;
    document.getElementById("det_cot_cliente").innerText = cliente;
    document.getElementById("det_cot_vehiculo").innerText = vehiculo;
    document.getElementById("det_cot_fecha").innerText = `Creada: ${fecha}`;
    
    const bdgTipo = document.getElementById("det_cot_tipo");
    bdgTipo.className = tipo === 'POS' ? 'badge bg-success ms-1' : 'badge bg-primary ms-1';
    bdgTipo.innerText = `Tipo: ${tipo}`;

    const bdgEstado = document.getElementById("det_cot_estado");
    bdgEstado.className = 'badge ms-1 ' + (estado === 'Aprobada' ? 'bg-success' : (estado === 'Rechazada' ? 'bg-danger' : 'bg-warning text-dark'));
    bdgEstado.innerText = estado;

    document.getElementById("det_cot_total").innerText = `RD$ ${parseFloat(total).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    
    const tbody = document.getElementById("det_cot_items");
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

    fetch(`../../modules/Facturacion/Archivo_Cotizaciones.php?action=obtener_detalle&id_cotizacion=${id_cotizacion}`)
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        let itemsParaImpresion = [];

        if(data.success && data.data.length > 0) {
            data.data.forEach(i => {
                itemsParaImpresion.push({
                    descripcion: i.descripcion,
                    cantidad: i.cantidad,
                    precio: i.precio,
                    subtotal: i.cantidad * i.precio,
                    tipo: i.tipo // servicio o repuesto
                });

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="small fw-bold">${i.descripcion} <span class="badge bg-light text-dark border ms-1" style="font-size:0.6rem">${i.tipo}</span></td>
                    <td class="text-center">${i.cantidad}</td>
                    <td class="text-end small">RD$ ${parseFloat(i.precio).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-end fw-bold small text-primary">RD$ ${parseFloat(i.cantidad * i.precio).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">No se encontraron detalles.</td></tr>';
        }
        
        document.getElementById("btn_reimprimir_cot").onclick = () => {
            ejecutarReimpresionCotizacion(id_cotizacion, cliente, vehiculo, fecha, itemsParaImpresion, total, tipo);
        };

        // --- SOLUCIÓN APLICADA AQUÍ ---
        abrirModalUI('modalDetalleCotizacion');
    });
}

function ejecutarReimpresionCotizacion(id, cliente, vehiculo, fecha, items, total, tipo) {
    // 1. Cargamos la librería para el PDF si no está presente
    if (typeof html2pdf === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        document.head.appendChild(script);
        script.onload = () => procesarPDF();
    } else {
        procesarPDF();
    }

    function procesarPDF() {
        let tablaItems = "";
        items.forEach(i => {
            tablaItems += `
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: center;">${i.cantidad}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">${i.descripcion}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">RD$ ${parseFloat(i.precio || 0).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right; font-weight: bold;">RD$ ${parseFloat(i.subtotal).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                </tr>`;
        });

        const element = document.createElement('div');
        element.innerHTML = `
            <div style="width: 210mm; min-height: 297mm; padding: 40px; border: 20px solid #0d47a1; background: white; font-family: 'Poppins', sans-serif; box-sizing: border-box; position: relative;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 5px solid #0d47a1; padding-bottom: 20px; margin-bottom: 30px;">
                    <img src="../../img/logo.png" style="max-width: 200px; height: auto;" onerror="this.style.visibility='hidden'">
                    <div style="background: #0d47a1; color: white; padding: 15px 30px; border-radius: 10px; text-align: center;">
                        <span style="font-size: 11px; display: block; letter-spacing: 1px; font-weight: 300;">COTIZACIÓN OFICIAL</span>
                        <span style="font-size: 26px; font-weight: 800;">COT-${id}</span>
                    </div>
                </div>

                <h2 style="text-align: center; color: #0d47a1; text-transform: uppercase; margin-bottom: 30px; letter-spacing: 2px;">Presupuesto Estimado de Servicio</h2>

                <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
                    <div style="background: #f8fafc; padding: 15px; border-radius: 10px; border-left: 6px solid #0d47a1; width: 48%;">
                        <span style="font-size: 10px; color: #64748b; font-weight: 800; text-transform: uppercase;">Propietario / Cliente</span>
                        <span style="font-size: 16px; font-weight: 700; display: block; color: #1e293b; margin-top: 5px;">${cliente}</span>
                        <span style="font-size: 10px; color: #64748b; font-weight: 800; text-transform: uppercase; margin-top: 15px; display: block;">Vehículo Detalle</span>
                        <span style="font-size: 14px; font-weight: 600; display: block; color: #1e293b;">${vehiculo}</span>
                    </div>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 10px; border-left: 6px solid #0d47a1; width: 48%;">
                        <span style="font-size: 10px; color: #64748b; font-weight: 800; text-transform: uppercase;">Fecha de Emisión</span>
                        <span style="font-size: 16px; font-weight: 700; display: block; color: #1e293b; margin-top: 5px;">${fecha}</span>
                        <span style="font-size: 10px; color: #64748b; font-weight: 800; text-transform: uppercase; margin-top: 15px; display: block;">Estado del Registro</span>
                        <span style="font-size: 14px; font-weight: 600; display: block; color: #1e293b;">${tipo}</span>
                    </div>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background: #f1f5f9; color: #0d47a1;">
                            <th style="padding: 12px; font-size: 12px; text-align: center;">CANT.</th>
                            <th style="padding: 12px; font-size: 12px; text-align: left;">DESCRIPCIÓN DEL SERVICIO</th>
                            <th style="padding: 12px; font-size: 12px; text-align: right;">PRECIO</th>
                            <th style="padding: 12px; font-size: 12px; text-align: right;">SUBTOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tablaItems}
                    </tbody>
                </table>

                <div style="margin-top: 30px; background: #0d47a1; color: white; padding: 25px; text-align: right; border-radius: 0 0 10px 10px;">
                    <span style="font-size: 16px; font-weight: 300;">MONTO TOTAL ESTIMADO:</span><br>
                    <span style="font-size: 32px; font-weight: 800;">RD$ ${parseFloat(total).toLocaleString(undefined,{minimumFractionDigits:2})}</span>
                </div>

                <div style="margin-top: 40px; font-size: 11px; color: #475569; border: 1px solid #e2e8f0; padding: 20px; border-radius: 10px; line-height: 1.6; background: #fff;">
                    <b style="color: #0d47a1; font-size: 12px;">TÉRMINOS Y POLÍTICAS DE SERVICIO:</b><br>
                    1. Esta cotización tiene una validez de 15 días calendario.<br>
                    2. Los precios de repuestos pueden variar según disponibilidad de proveedores.<br>
                    3. Servicios de mano de obra cuentan con garantía de 30 días.<br>
                    4. Copia generada desde el Historial Oficial de Taller Mecánica Diaz.
                </div>

                <div style="margin-top: 70px; display: flex; justify-content: space-around; text-align: center;">
                    <div style="border-top: 2px solid #1e293b; width: 220px; padding-top: 10px; font-weight: 700; font-size: 13px;">Firma Autorizada / Sello</div>
                    <div style="border-top: 2px solid #1e293b; width: 220px; padding-top: 10px; font-weight: 700; font-size: 13px;">Aceptación del Cliente</div>
                </div>
            </div>
        `;

        const opt = {
            margin: 0,
            filename: `Cotizacion_COT-${id}.pdf`,
            image: { type: 'jpeg', quality: 1 },
            html2canvas: { scale: 2, useCORS: true, letterRendering: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        // Generar y descargar el PDF
        html2pdf().set(opt).from(element).save();
    }
}

// ==========================================
// FUNCIONES BLINDADAS PARA MODALES
// ==========================================
function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try {
        if (typeof bootstrap !== 'undefined') {
            let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el); m.show();
        } else { throw new Error(); }
    } catch (e) {
        if (typeof jQuery !== 'undefined') {
            $('#' + id).modal('show');
        } else {
            el.classList.add('show'); el.style.display = 'block';
            document.body.classList.add('modal-open');
            document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
            const b = document.createElement('div'); b.id = 'm-bd-' + id; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
        }
    }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { 
        if (typeof bootstrap !== 'undefined') { 
            let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); 
        } else { throw new Error(); }
    } catch (e) {
        if (typeof jQuery !== 'undefined') {
            $('#' + id).modal('hide');
        } else {
            el.classList.remove('show'); el.style.display = 'none';
            document.body.classList.remove('modal-open');
            const b = document.getElementById('m-bd-' + id); if(b) b.remove();
            document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
        }
    }
}