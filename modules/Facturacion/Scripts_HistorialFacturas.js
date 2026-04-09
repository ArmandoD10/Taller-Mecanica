document.addEventListener("DOMContentLoaded", () => {
    cargarFacturas();
});

document.getElementById("form_filtros_facturas").addEventListener("submit", function(e) {
    e.preventDefault();
    cargarFacturas();
});

function cargarFacturas() {
    const form = document.getElementById("form_filtros_facturas");
    const formData = new FormData(form);

    const tbody = document.getElementById("cuerpoTablaFacturas");
    tbody.innerHTML = `<tr><td colspan="8" class="py-5 text-muted fw-bold"><i class="fas fa-spinner fa-spin me-2"></i>Cargando...</td></tr>`;

    fetch("../../modules/Facturacion/Archivo_HistorialFacturas.php?action=listar_facturas", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(f => {
                let badgeEstado = "bg-success";
                if (f.estado === 'inactivo' || f.estado_pago === 'Cancelado') {
                    badgeEstado = "bg-danger";
                } else if (f.estado_pago === 'Pendiente') {
                    badgeEstado = "bg-warning text-dark";
                }

                const tr = document.createElement("tr");
                tr.className = "fila-factura"; // Clase esencial para el buscador
                tr.innerHTML = `
                    <td class="fw-bold text-primary">FAC-${f.id_factura}</td>
                    <td class="small">${f.fecha}</td>
                    <td class="text-start fw-bold">${f.cliente}</td>
                    <td class="text-start small text-muted">${f.vehiculo}</td>
                    <td class="small">${f.ncf}</td>
                    <td><span class="badge ${badgeEstado}">${f.estado_pago || f.estado}</span></td>
                    <td class="text-end fw-bold">RD$ ${parseFloat(f.monto_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="abrirDetalle(${f.id_factura}, '${f.cliente}', '${f.vehiculo}', '${f.fecha}', '${f.ncf}', ${f.monto_total})" title="Ver Detalles y Reimprimir">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="py-5 text-muted fw-bold text-center">No hay facturas en este rango de fechas.</td></tr>`;
        }
        
        // Limpiamos el buscador dinámico si tenía algo
        document.getElementById("buscador_dinamico").value = "";
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="8" class="py-5 text-danger fw-bold text-center">Error de conexión al cargar facturas.</td></tr>`;
    });
}

// BUSCADOR DINÁMICO (Filtra la tabla en tiempo real sin ir al servidor)
function filtrarTablaFacturas() {
    const texto = document.getElementById("buscador_dinamico").value.toLowerCase();
    const filas = document.querySelectorAll(".fila-factura");
    
    filas.forEach(fila => {
        const contenidoFila = fila.innerText.toLowerCase();
        if (contenidoFila.includes(texto)) {
            fila.style.display = "";
        } else {
            fila.style.display = "none";
        }
    });
}

// CARGAR DETALLES EN EL MODAL
function abrirDetalle(id_factura, cliente, vehiculo, fecha, ncf, total) {
    document.getElementById("det_fac_numero").innerText = `FAC-${id_factura}`;
    document.getElementById("det_fac_cliente").innerText = cliente;
    document.getElementById("det_fac_vehiculo").innerText = vehiculo;
    document.getElementById("det_fac_fecha").innerText = `Emitida: ${fecha}`;
    document.getElementById("det_fac_ncf").innerText = `NCF: ${ncf}`;
    document.getElementById("det_fac_total").innerText = `RD$ ${parseFloat(total).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    
    const tbody = document.getElementById("det_fac_items");
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

    fetch(`../../modules/Facturacion/Archivo_HistorialFacturas.php?action=obtener_detalle&id_factura=${id_factura}`)
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
                    subtotal: i.subtotal
                });

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="small fw-bold">${i.descripcion}</td>
                    <td class="text-center">${i.cantidad}</td>
                    <td class="text-end small">RD$ ${parseFloat(i.precio).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class="text-end fw-bold small text-primary">RD$ ${parseFloat(i.subtotal).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">No se encontraron detalles para esta factura.</td></tr>';
        }
        
        // Configuramos el botón de reimprimir con los datos cargados
        document.getElementById("btn_reimprimir_factura").onclick = () => {
            ejecutarReimpresion(id_factura, cliente, vehiculo, fecha, ncf, itemsParaImpresion, total);
        };

        abrirModalUI('modalDetalleFactura');
    });
}

// MOTOR DE IMPRESIÓN DINÁMICO
function ejecutarReimpresion(id, cliente, vehiculo, fecha, ncf, items, total) {
    let htmlItems = "";
    items.forEach(i => {
        htmlItems += `
            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:13px;">
                <span style="flex:2;">${i.cantidad}x ${i.descripcion}</span>
                <span style="flex:1; text-align:right;">RD$ ${parseFloat(i.subtotal).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>`;
    });

    const ventana = window.open('', '_blank', 'width=450,height=650');
    ventana.document.write(`
        <html><head>
            <title>Factura FAC-${id}</title>
            <style>
                body{font-family: 'Courier New', Courier, monospace; padding:20px; color:#000; font-size:14px;}
                .text-center{text-align:center;}
                .divider{border-bottom: 2px dashed #333; margin: 15px 0;}
            </style>
        </head>
        <body>
            <h2 class="text-center" style="margin-bottom:5px;">MECÁNICA DÍAZ PANTALEÓN</h2>
            <p class="text-center" style="margin-top:0;">REIMPRESIÓN DE COMPROBANTE</p>
            
            <div class="divider"></div>
            
            <p>
                <b>FACTURA:</b> FAC-${id}<br>
                <b>FECHA:</b> ${fecha}<br>
                <b>NCF:</b> ${ncf}<br>
                <b>CLIENTE:</b> ${cliente}<br>
                <b>VEHÍCULO:</b> ${vehiculo}
            </p>
            
            <div class="divider"></div>
            
            <div style="font-weight:bold; display:flex; justify-content:space-between; margin-bottom:10px;">
                <span style="flex:2;">CANT x DESCRIPCIÓN</span>
                <span style="flex:1; text-align:right;">SUBTOTAL</span>
            </div>
            
            ${htmlItems}
            
            <div class="divider"></div>
            
            <h2 style="text-align:right; margin-top:10px;">TOTAL: RD$ ${parseFloat(total).toLocaleString(undefined,{minimumFractionDigits:2})}</h2>
            
            <p style="text-align:center; font-size:11px; margin-top:40px;">
                Copia generada por el sistema SIG.<br>
                ¡Gracias por preferir nuestros servicios!
            </p>
        </body></html>
    `);
    
    ventana.document.close();
    ventana.focus();
    setTimeout(() => { ventana.print(); ventana.close(); }, 800);
}

// FUNCIONES UI BLINDADAS
function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    if (typeof bootstrap !== 'undefined') {
        let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el); m.show();
    } else {
        el.classList.add('show'); el.style.display = 'block';
        document.body.classList.add('modal-open');
        const b = document.createElement('div'); b.id = 'm-bd-' + id; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
    }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    if (typeof bootstrap !== 'undefined') { 
        let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); 
    } else {
        el.classList.remove('show'); el.style.display = 'none';
        document.body.classList.remove('modal-open');
        const b = document.getElementById('m-bd-' + id); if(b) b.remove();
        document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
    }
}