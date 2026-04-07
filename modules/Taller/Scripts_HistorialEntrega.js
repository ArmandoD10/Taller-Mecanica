console.log("Scripts_HistorialEntrega.js: Módulo de auditoría de entregas cargado.");

document.addEventListener("DOMContentLoaded", () => {
    listar();
});

// ==========================================
// LISTADO DE ENTREGAS
// ==========================================
function listar() {
    fetch("../../modules/Taller/Archivo_HistorialEntrega.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaEntregas");
        if(!tbody) return;
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(o => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-success">ORD-${o.id_orden}</td>
                    <td class="fw-bold">${o.fecha_entrega}</td>
                    <td>${o.cliente}</td>
                    <td>${o.vehiculo} <span class="badge bg-secondary ms-1">${o.placa}</span></td>
                    <td class="text-success fw-bold">${o.monto_total_fmt}</td>
                    <td><i class="fas fa-user-check text-muted me-1"></i> ${o.entregado_por}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-success fw-bold" onclick="verComprobante(${o.id_orden})" title="Ver Acta">
                            <i class="fas fa-file-signature me-1"></i> Acta
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Aún no se han registrado vehículos entregados.</td></tr>`;
        }
    })
    .catch(err => console.error("Error listar entregas:", err));
}

// ==========================================
// OBTENER Y MOSTRAR COMPROBANTE
// ==========================================
function verComprobante(id_orden) {
    fetch(`../../modules/Taller/Archivo_HistorialEntrega.php?action=obtener_acta&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const d = data.data;

            document.getElementById("acta_orden").innerText = "ORD-" + d.id_orden;
            document.getElementById("acta_ingreso").innerText = d.fecha_ingreso;
            document.getElementById("acta_salida").innerText = d.fecha_entrega;
            document.getElementById("acta_usuario").innerText = d.entregado_por;
            
            document.getElementById("acta_cliente").innerText = d.cliente;
            document.getElementById("acta_vehiculo").innerText = d.vehiculo;
            document.getElementById("acta_placa").innerText = d.placa;
            document.getElementById("acta_vin").innerText = d.vin_chasis;
            
            document.getElementById("acta_monto").innerText = d.monto_total_fmt;

            abrirModalUI('modalComprobante');
        } else {
            alert("Error al cargar acta: " + data.message);
        }
    })
    .catch(err => console.error("Error ver comprobante:", err));
}

// ==========================================
// UTILIDADES: MODAL E IMPRESIÓN
// ==========================================
function imprimirComprobante() {
    const contenido = document.getElementById('areaImpresionEntrega').innerHTML;
    const ventana = window.open('', '_blank', 'width=800,height=600');
    ventana.document.write(`
        <html>
            <head>
                <title>Acta de Entrega de Vehículo</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 30px; color: #333; }
                    .text-center { text-align: center; }
                    .border-bottom { border-bottom: 2px solid #ddd; margin-bottom: 15px; padding-bottom: 10px; }
                    .border-top { border-top: 1px solid #000; margin-top: 50px; padding-top: 10px; }
                    .row { display: flex; width: 100%; margin-bottom: 20px; }
                    .col-6 { width: 50%; float: left; }
                    .fw-bold { font-weight: bold; }
                    .text-muted { color: #666; font-size: 12px; }
                    .text-success { color: #198754; }
                    .card { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px; }
                    p { margin: 5px 0; }
                    h3, h4, h6 { margin-top: 0; }
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

function cerrarModalComprobante() { cerrarModalUI('modalComprobante'); }

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
        if(!document.getElementById('m-bd-entrega-hist')){
            const b = document.createElement('div'); b.id = 'm-bd-entrega-hist'; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
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
    const b = document.getElementById('m-bd-entrega-hist'); if(b) b.remove();
    document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
}