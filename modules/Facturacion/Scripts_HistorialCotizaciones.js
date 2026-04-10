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
    let htmlItems = "";
    items.forEach(i => {
        htmlItems += `
            <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                <span>${i.cantidad}x ${i.descripcion}</span>
                <span>RD$ ${parseFloat(i.subtotal).toLocaleString(undefined,{minimumFractionDigits:2})}</span>
            </div>`;
    });

    const v = window.open('', '_blank', 'width=400,height=600');
    v.document.write(`
        <html><head><style>body{font-family:monospace;}</style></head>
        <body style="padding:20px;">
            <h3 style="text-align:center;">PRESUPUESTO ESTIMADO (${tipo})</h3>
            <p style="text-align:center; font-weight:bold;">COT-${id}</p>
            <p style="font-size: 12px;">
                <b>Fecha:</b> ${fecha}<br>
                <b>Cliente:</b> ${cliente}<br>
                <b>Vehículo:</b> ${vehiculo}
            </p>
            <hr>
            ${htmlItems}
            <hr>
            <h4>TOTAL ESTIMADO: RD$ ${parseFloat(total).toLocaleString(undefined,{minimumFractionDigits:2})}</h4>
            <p style="text-align:center; font-size:10px; margin-top:30px; color:#666;">
                Copia generada desde el Historial. Este documento no es una factura válida para crédito fiscal.
            </p>
        </body></html>
    `);
    v.document.close();
    setTimeout(() => { v.print(); v.close(); }, 500);
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