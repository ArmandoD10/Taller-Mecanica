document.addEventListener("DOMContentLoaded", () => {
    // Cargar con las fechas por defecto al entrar
    cargarHistorial();

    // Evento del formulario de filtros
    const formFiltros = document.getElementById("formFiltrosHistorial");
    if(formFiltros) {
        formFiltros.addEventListener("submit", function(e) {
            e.preventDefault();
            cargarHistorial();
        });
    }

    // Buscador rápido (Filtro en cliente del lado del JS)
    const buscador = document.getElementById("buscador_tabla");
    if (buscador) {
        buscador.addEventListener("keyup", function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll("#cuerpoTablaHistorial tr");
            
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                if(text.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    }
});

function cargarHistorial() {
    const btn = document.querySelector('#formFiltrosHistorial button[type="submit"]');
    const f_inicio = document.getElementById("fecha_inicio").value;
    const f_fin = document.getElementById("fecha_fin").value;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>...';
    btn.disabled = true;

    fetch(`/Taller/Taller-Mecanica/modules/Autolavado/Archivo_HistorialLavado.php?action=listar_historial&fecha_inicio=${f_inicio}&fecha_fin=${f_fin}`)
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = '<i class="fas fa-filter me-1"></i> Filtrar';
        btn.disabled = false;

        const tbody = document.getElementById("cuerpoTablaHistorial");
        tbody.innerHTML = "";
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(l => {
                
                // Badges de Origen
                let badgeOrigen = l.origen_orden === 'Express' 
                    ? `<span class="badge bg-secondary shadow-sm">Express</span>` 
                    : `<span class="badge bg-primary shadow-sm text-wrap" style="max-width:80px;">${l.origen_orden}</span>`;

                // Badges de Estado
                let badgeEstado = "";
                if(l.estado_lavado === 'Entregado') badgeEstado = `<span class="badge bg-success"><i class="fas fa-check-double"></i> Finalizado</span>`;
                else if (l.estado_lavado === 'Listo') badgeEstado = `<span class="badge bg-info text-dark"><i class="fas fa-flag-checkered"></i> En Caja</span>`;
                else badgeEstado = `<span class="badge bg-warning text-dark"><i class="fas fa-tint"></i> Pista</span>`;

                // Botón de Acción (Solo imprimir si es Express y ya se facturó)
                let btnAccion = `<span class="text-muted small">N/A</span>`;
                if (l.es_express == 1 && l.estado_lavado === 'Entregado' && l.id_factura_express) {
                    btnAccion = `<button class="btn btn-sm btn-outline-dark" title="Reimprimir Ticket" onclick="reimprimirTicket(${l.id_factura_express})">
                                    <i class="fas fa-receipt"></i> Ticket
                                 </button>`;
                } else if (l.origen_orden !== 'Express') {
                    btnAccion = `<span class="text-muted" style="font-size: 11px;"><i class="fas fa-tools"></i> Facturado en Taller</span>`;
                }

                // Dar formato al dinero
                let montoFmt = parseFloat(l.monto_total).toLocaleString('es-DO', {minimumFractionDigits: 2});

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="small text-muted">${l.fecha_fmt}</td>
                    <td>${badgeOrigen}</td>
                    <td class="text-start">
                        <span class="fw-bold text-dark d-block">${l.vehiculo}</span>
                        <small class="text-muted"><i class="fas fa-user me-1"></i>${l.cliente}</small>
                    </td>
                    <td><span class="text-secondary fw-bold">${l.tipo_lavado}</span></td>
                    <td class="text-end fw-bold text-success">${montoFmt}</td>
                    <td>${badgeEstado}</td>
                    <td class="no-print">${btnAccion}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-5">No hay lavados registrados en este rango de fechas.</td></tr>`;
        }
    })
    .catch(err => {
        btn.innerHTML = '<i class="fas fa-filter me-1"></i> Filtrar';
        btn.disabled = false;
        alert("Error al cargar el historial.");
    });
}

// Reutilizamos el endpoint del Archivo_Lavado.php que ya construimos para el ticket
function reimprimirTicket(id_factura) {
    fetch(`/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Lavado.php?action=obtener_ticket&id_factura=${id_factura}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const tk = data.data;
            document.getElementById('tk_num').innerText = String(tk.id_factura_lavado).padStart(6, '0');
            document.getElementById('tk_fecha').innerText = tk.fecha;
            document.getElementById('tk_ncf').innerText = tk.NCF;
            document.getElementById('tk_cliente').innerText = tk.cliente;
            document.getElementById('tk_placa').innerText = tk.placa;
            document.getElementById('tk_servicio').innerText = tk.servicio;

            let total = parseFloat(tk.monto_total);
            let base = total / 1.18;
            let itbis = total - base;

            document.getElementById('tk_subtotal').innerText = 'RD$ ' + base.toFixed(2);
            document.getElementById('tk_itbis').innerText = 'RD$ ' + itbis.toFixed(2);
            document.getElementById('tk_total').innerText = 'RD$ ' + total.toFixed(2);

            abrirModalUI('modalTicketLavado');
        } else {
            alert("No se pudo cargar la información del ticket.");
        }
    });
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { 
        if (typeof bootstrap !== 'undefined') { let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); } 
        else throw new Error();
    } catch (e) {
        if (typeof jQuery !== 'undefined') { $('#' + id).modal('hide'); } 
        else {
            el.classList.remove('show'); el.style.display = 'none'; document.body.classList.remove('modal-open');
            const b = document.getElementById('m-bd-' + id); if(b) b.remove();
            document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
        }
    }
}

function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try {
        if (typeof bootstrap !== 'undefined') {
            let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el); m.show();
        } else throw new Error();
    } catch (e) {
        if (typeof jQuery !== 'undefined') { $('#' + id).modal('show'); } 
        else {
            el.classList.add('show'); el.style.display = 'block'; document.body.classList.add('modal-open');
            document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
            const b = document.createElement('div'); b.id = 'm-bd-' + id; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
        }
    }
}