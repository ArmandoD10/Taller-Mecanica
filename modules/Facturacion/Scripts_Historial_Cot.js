document.addEventListener("DOMContentLoaded", () => {
    listarHistorial();
});

function listarHistorial() {
    const estadoFiltro = document.getElementById("filtro_estado").value;
    
    fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=listar_historial")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoHistorial");
        tbody.innerHTML = "";
        
        if (data.success && data.data.length > 0) {
            let filtrados = data.data;
            if(estadoFiltro !== "TODOS") {
                filtrados = data.data.filter(c => c.estado === estadoFiltro);
            }

            filtrados.forEach(c => {
                let badge = "";
                switch(c.estado) {
                    case 'Pendiente': badge = '<span class="badge bg-warning text-dark">Pendiente</span>'; break;
                    case 'Aprobada': badge = '<span class="badge bg-success">Aprobada</span>'; break;
                    case 'Rechazada': badge = '<span class="badge bg-danger">Rechazada</span>'; break;
                }

                const tr = document.createElement("tr");
                tr.className = "fila-historial";
                tr.innerHTML = `
                    <td class="fw-bold">COT-${c.id_cotizacion}</td>
                    <td class="small">${c.fecha}</td>
                    <td class="text-start pe-3"><b>${c.cliente}</b></td>
                    <td class="small text-start">${c.vehiculo}</td>
                    <td class="fw-bold text-success">RD$ ${parseFloat(c.monto_total).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                    <td>${badge}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="verDetalle(${c.id_cotizacion}, '${c.cliente}', '${c.vehiculo}', '${c.fecha}', ${c.monto_total})" title="Ver Detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="py-5 text-muted text-center">No se encontraron registros en el historial.</td></tr>`;
        }
    });
}

function filtrarHistorial() {
    const busqueda = document.getElementById("busqueda_historial").value.toLowerCase();
    const filas = document.querySelectorAll(".fila-historial");
    filas.forEach(f => {
        f.style.display = f.innerText.toLowerCase().includes(busqueda) ? "" : "none";
    });
}

function verDetalle(id, cliente, vehiculo, fecha, total) {
    document.getElementById("det_cliente").innerText = cliente;
    document.getElementById("det_fecha").innerText = "Emitida el: " + fecha;
    document.getElementById("det_vehiculo").innerText = vehiculo;
    document.getElementById("det_total").innerText = "RD$ " + parseFloat(total).toLocaleString(undefined,{minimumFractionDigits:2});
    
    const tbody = document.getElementById("det_tabla_items");
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3">Cargando ítems...</td></tr>';

    fetch(`../../modules/Facturacion/Archivo_Cotizaciones.php?action=obtener_detalle&id_cotizacion=${id}`)
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        let itemsParaImpresion = [];

        data.data.forEach(i => {
            const subt = parseInt(i.cantidad) * parseFloat(i.precio);
            itemsParaImpresion.push({
                descripcion: i.descripcion,
                cantidad: i.cantidad,
                precio: i.precio,
                tipo: i.tipo
            });

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td class="small">${i.descripcion}</td>
                <td class="text-center small text-capitalize">${i.tipo}</td>
                <td class="text-center">${i.cantidad}</td>
                <td class="text-end small">RD$ ${parseFloat(i.precio).toLocaleString()}</td>
                <td class="text-end fw-bold small">RD$ ${subt.toLocaleString()}</td>
            `;
            tbody.appendChild(tr);
        });
        
        // CORRECCIÓN: Ahora el botón de reimprimir genera el documento dinámicamente
        document.getElementById("btn_reimprimir").onclick = () => {
            ejecutarImpresionHistorial(id, cliente, vehiculo, itemsParaImpresion, total);
        };

        abrirModalUI('modalDetalleHistorial');
    });
}

// FUNCIÓN DE IMPRESIÓN DINÁMICA (Sin necesidad de archivos .php extras)
function ejecutarImpresionHistorial(id, cliente, vehiculo, items, total) {
    let htmlItems = "";
    items.forEach(i => {
        htmlItems += `
            <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:12px;">
                <span>${i.cantidad}x ${i.descripcion} (${i.tipo})</span>
                <span>RD$ ${(parseFloat(i.precio) * parseInt(i.cantidad)).toLocaleString(undefined,{minimumFractionDigits:2})}</span>
            </div>`;
    });

    const v = window.open('', '_blank', 'width=450,height=600');
    v.document.write(`
        <html><head>
            <title>Cotización COT-${id}</title>
            <style>
                body{font-family: 'Courier New', Courier, monospace; padding:20px; color:#333;}
                .text-center{text-align:center;}
                .divider{border-bottom: 1px dashed #ccc; margin: 10px 0;}
            </style>
        </head>
        <body>
            <h3 class="text-center" style="margin-bottom:5px;">MECÁNICA DÍAZ PANTALEÓN</h3>
            <p class="text-center" style="font-size:12px; margin-top:0;">REIMPRESIÓN DE COTIZACIÓN</p>
            <div class="divider"></div>
            <p style="font-size:12px;">
                <b>COTIZACIÓN:</b> COT-${id}<br>
                <b>CLIENTE:</b> ${cliente}<br>
                <b>VEHÍCULO:</b> ${vehiculo}
            </p>
            <div class="divider"></div>
            ${htmlItems}
            <div class="divider"></div>
            <h3 style="text-align:right; margin-top:10px;">TOTAL: RD$ ${parseFloat(total).toLocaleString(undefined,{minimumFractionDigits:2})}</h3>
            <p style="text-align:center; font-size:10px; margin-top:30px; color:#666;">
                Este documento es una copia informativa del presupuesto original.<br>
                Los precios están sujetos a cambios.
            </p>
        </body></html>
    `);
    v.document.close();
    v.focus();
    setTimeout(() => { v.print(); v.close(); }, 500);
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