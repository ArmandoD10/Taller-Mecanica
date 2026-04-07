console.log("Scripts_Entrega.js: Módulo de entregas cargado.");

document.addEventListener("DOMContentLoaded", () => {
    listar();

    // ==========================================
    // PROCESAR LA ENTREGA DEL VEHÍCULO
    // ==========================================
    const formEntrega = document.getElementById("formEntrega");
    if(formEntrega) {
        formEntrega.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch("../../modules/Taller/Archivo_Entrega.php?action=procesar_entrega", { 
                method: "POST", 
                body: formData 
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) { 
                    cerrarModalEntrega(); 
                    listar(); 
                    alert("¡Vehículo marcado como Entregado exitosamente!\n\n" + data.message); 
                } else {
                    alert("ERROR AL PROCESAR ENTREGA:\n" + data.message);
                }
            })
            .catch(err => console.error("Error en petición de entrega:", err));
        });
    }
});

// ==========================================
// LISTADO DE ÓRDENES EN PROCESO DE SALIDA
// ==========================================
function listar() {
    fetch("../../modules/Taller/Archivo_Entrega.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaEntregas");
        if(!tbody) return;
        tbody.innerHTML = "";
        
        let countListos = 0;
        let countCalidad = 0;
        let countEntregadosHoy = 0;

        if (data.success && data.data.length > 0) {
            data.data.forEach(o => {
                // Contadores para las tarjetas
                if(o.estado_orden === 'Listo') countListos++;
                if(o.estado_orden === 'Control Calidad') countCalidad++;
                if(o.estado_orden === 'Entregado') countEntregadosHoy++;

                // Formato Badge Estado Pago
                let badgePago = "";
                if (o.estado_pago === 'Pagado') badgePago = `<span class="badge bg-success"><i class="fas fa-check me-1"></i> Pagado</span>`;
                else if (o.estado_pago === 'Pendiente') badgePago = `<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pendiente</span>`;
                else badgePago = `<span class="badge bg-secondary"><i class="fas fa-file-invoice me-1"></i> Sin Facturar</span>`;

                // Formato Badge Estado Orden
                let badgeOrden = "";
                let btnAccion = "";
                if (o.estado_orden === 'Listo') {
                    badgeOrden = `<span class="badge bg-primary fs-6">Listo para Entrega</span>`;
                    btnAccion = `<button class="btn btn-sm btn-success fw-bold shadow-sm" onclick="prepararEntrega(${o.id_orden}, '${o.cliente}', '${o.vehiculo}', '${o.monto_total_fmt}', '${o.estado_pago}', '${o.estado_orden}')"><i class="fas fa-key me-1"></i> Entregar</button>`;
                } else if (o.estado_orden === 'Control Calidad') {
                    badgeOrden = `<span class="badge bg-info text-dark fw-bold">En Control de Calidad</span>`;
                    btnAccion = `<button class="btn btn-sm btn-outline-secondary" disabled><i class="fas fa-tools me-1"></i> En Revisión</button>`;
                } else if (o.estado_orden === 'Entregado') {
                    badgeOrden = `<span class="badge bg-dark">Entregado</span>`;
                    btnAccion = `<i class="fas fa-check-circle text-success fs-4" title="Entregado"></i>`;
                }

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-primary">ORD-${o.id_orden}</td>
                    <td>${o.cliente}</td>
                    <td>${o.vehiculo}</td>
                    <td class="fw-bold">${o.monto_total_fmt}</td>
                    <td>${badgePago}</td>
                    <td>${badgeOrden}</td>
                    <td class="text-center">${btnAccion}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No hay vehículos listos o en proceso de salida en este momento.</td></tr>`;
        }

        // Actualizar tarjetas
        document.getElementById('count_listos').innerText = countListos;
        document.getElementById('count_calidad').innerText = countCalidad;
        document.getElementById('count_entregados').innerText = countEntregadosHoy;
    })
    .catch(err => console.error("Error listar entregas:", err));
}

// ==========================================
// PREPARACIÓN DE MODAL Y VALIDACIÓN VISUAL
// ==========================================
function prepararEntrega(id_orden, cliente, vehiculo, monto, estado_pago, estado_orden) {
    document.getElementById("id_orden_entrega").value = id_orden;
    document.getElementById("estado_anterior").value = estado_orden; // Guardamos para el historial
    
    document.getElementById("lbl_orden").innerText = "ORD-" + id_orden;
    document.getElementById("lbl_cliente").innerText = cliente;
    document.getElementById("lbl_vehiculo").innerText = vehiculo;
    document.getElementById("lbl_monto").innerText = monto;

    const alertaPago = document.getElementById("alerta_pago");
    const txtAlerta = document.getElementById("txt_alerta_pago");

    // Advertencia visual si no está pagado
    if (estado_pago === 'Pendiente' || estado_pago === 'Sin Facturar') {
        txtAlerta.innerText = estado_pago.toUpperCase();
        alertaPago.classList.remove("d-none");
    } else {
        alertaPago.classList.add("d-none");
    }

    abrirModalUI('modalEntrega');
}

// ==========================================
// GESTIÓN DE MODALES
// ==========================================
function cerrarModalEntrega() { 
    document.getElementById("formEntrega").reset();
    cerrarModalUI('modalEntrega'); 
}

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
        if(!document.getElementById('m-bd-entrega')){
            const b = document.createElement('div'); b.id = 'm-bd-entrega'; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
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
    const b = document.getElementById('m-bd-entrega'); if(b) b.remove();
    document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
}