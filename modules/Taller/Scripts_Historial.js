console.log("Scripts_Historial.js: Módulo de historial cargado.");

document.addEventListener("DOMContentLoaded", () => {
    listar();
});

// ==========================================
// LISTADO GENERAL DEL HISTORIAL
// ==========================================
function listar() {
    fetch("../../modules/Taller/Archivo_Historial.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaHistorial");
        if(!tbody) return;
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(o => {
                
                // Formato de insignias para el estado
                let badgeOrden = "";
                switch(o.estado_orden) {
                    case 'Entregado': badgeOrden = `<span class="badge bg-success">Entregado</span>`; break;
                    case 'Listo': badgeOrden = `<span class="badge bg-primary">Listo</span>`; break;
                    case 'Control Calidad': badgeOrden = `<span class="badge bg-info text-dark">Control Calidad</span>`; break;
                    case 'Reparación': badgeOrden = `<span class="badge bg-warning text-dark">Reparación</span>`; break;
                    case 'Diagnóstico': badgeOrden = `<span class="badge bg-secondary">Diagnóstico</span>`; break;
                    default: badgeOrden = `<span class="badge bg-dark">${o.estado_orden}</span>`; break;
                }

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-primary">ORD-${o.id_orden}</td>
                    <td>${o.fecha_fmt}</td>
                    <td>${o.cliente}</td>
                    <td>${o.vehiculo}</td>
                    <td><span class="badge bg-secondary">${o.placa}</span></td>
                    <td>${badgeOrden}</td>
                    <td class="fw-bold">${o.monto_total_fmt}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalles(${o.id_orden})" title="Ver Detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No hay registros en el historial.</td></tr>`;
        }
    })
    .catch(err => console.error("Error listar historial:", err));
}

// ==========================================
// OBTENER Y MOSTRAR DETALLES (RADIOGRAFÍA)
// ==========================================
function verDetalles(id_orden) {
    fetch(`../../modules/Taller/Archivo_Historial.php?action=obtener_detalles&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const d = data.data.cabecera;
            const servicios = data.data.servicios;

            // Llenar Cabecera
            document.getElementById("lbl_id_orden_modal").innerText = "ORD-" + d.id_orden;
            document.getElementById("lbl_estado_orden_modal").innerText = d.estado_orden.toUpperCase();
            document.getElementById("lbl_fecha_orden_modal").innerText = d.fecha_fmt;
            
            // Llenar Cliente
            document.getElementById("det_cliente").innerText = d.cliente;
            document.getElementById("det_cedula").innerText = d.cedula || 'N/A';
            // Nota: Aquí podrías vincular el teléfono si tuvieras el cruce, por ahora lo dejamos genérico o N/A
            document.getElementById("det_telefono").innerText = d.telefono || 'Revisar perfil'; 

            // Llenar Vehículo
            document.getElementById("det_vehiculo").innerText = d.vehiculo;
            document.getElementById("det_placa").innerText = d.placa;
            document.getElementById("det_vin").innerText = d.vin_chasis;
            document.getElementById("det_km").innerText = d.kilometraje;
            
            // Total
            document.getElementById("det_total").innerText = d.monto_total_fmt;

            // Llenar Tabla de Servicios
            const tbodyS = document.getElementById("cuerpoServiciosDetalle");
            tbodyS.innerHTML = "";

            if(servicios.length > 0) {
                servicios.forEach(s => {
                    let estadoServ = s.estado_asignacion === 'Completado' 
                        ? '<i class="fas fa-check-circle text-success" title="Completado"></i>' 
                        : '<i class="fas fa-clock text-warning" title="Pendiente/En Curso"></i>';

                    const notas = s.notas_hallazgos ? s.notas_hallazgos : '<em class="text-muted">Sin notas registradas</em>';

                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td class="fw-bold">${estadoServ} ${s.servicio}</td>
                        <td><small>${s.mecanicos || 'Ninguno'}</small></td>
                        <td><small>${s.hora_inicio || '--:--'}</small></td>
                        <td><small>${s.hora_fin || '--:--'}</small></td>
                        <td style="max-width: 300px; word-wrap: break-word;"><small>${notas}</small></td>
                    `;
                    tbodyS.appendChild(tr);
                });
            } else {
                tbodyS.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No se han registrado servicios o tiempos para esta orden.</td></tr>`;
            }

            abrirModalUI('modalDetalleOrden');
        } else {
            alert("Error al cargar detalles: " + data.message);
        }
    })
    .catch(err => console.error("Error ver detalles:", err));
}

// ==========================================
// UTILIDADES: MODAL E IMPRESIÓN
// ==========================================
function imprimirReporte() {
    const contenido = document.getElementById('areaImpresion').innerHTML;
    const ventana = window.open('', '_blank', 'width=800,height=600');
    ventana.document.write(`
        <html>
            <head>
                <title>Reporte de Servicio</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .badge { border: 1px solid #000; padding: 3px 6px; border-radius: 3px; font-size: 12px;}
                    .text-success { color: green; }
                    .fw-bold { font-weight: bold; }
                </style>
            </head>
            <body>
                ${contenido}
            </body>
        </html>
    `);
    ventana.document.close();
    ventana.focus();
    setTimeout(() => { ventana.print(); ventana.close(); }, 500);
}

function cerrarModalDetalle() { cerrarModalUI('modalDetalleOrden'); }

function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try {
        if (typeof bootstrap !== 'undefined') {
            let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
            m.show();
        } else { throw new Error(); }
    } catch (e) {
        el.classList.add('show'); el.style.display = 'block';
        el.setAttribute('aria-modal', 'true'); el.setAttribute('role', 'dialog');
        document.body.classList.add('modal-open'); document.body.style.overflow = 'hidden';
        if(!document.getElementById('m-bd-hist')){
            const b = document.createElement('div'); b.id = 'm-bd-hist'; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
        }
    }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { if (typeof bootstrap !== 'undefined') { let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); } } catch (e) {}
    el.classList.remove('show'); el.style.display = 'none';
    el.removeAttribute('aria-modal'); el.removeAttribute('role');
    document.body.classList.remove('modal-open'); document.body.style.overflow = '';
    const b = document.getElementById('m-bd-hist'); if(b) b.remove();
    document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
}