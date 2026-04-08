console.log("Scripts_Entrega.js: Módulo de entregas y calidad cargado.");

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
            const id_orden_procesada = document.getElementById("id_orden_entrega").value;
            
            fetch("../../modules/Taller/Archivo_Entrega.php?action=procesar_entrega", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) { 
                    cerrarModalEntrega(); 
                    listar(); 
                    mostrarComprobanteInmediato(id_orden_procesada);
                } else {
                    alert("ERROR AL PROCESAR ENTREGA:\n" + data.message);
                }
            })
            .catch(err => console.error("Error en petición de entrega:", err));
        });
    }

    // ==========================================
    // PROCESAR CONTROL DE CALIDAD
    // ==========================================
    const formCalidad = document.getElementById("formCalidad");
    if(formCalidad) {
        formCalidad.addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch("../../modules/Taller/Archivo_Entrega.php?action=procesar_calidad", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) { 
                    alert(data.message);
                    cerrarModalCalidad(); 
                    listar(); 
                } else {
                    alert("ACCESO DENEGADO:\n" + data.message);
                }
            })
            .catch(err => console.error("Error en petición de calidad:", err));
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
                if(o.estado_orden === 'Listo') countListos++;
                if(o.estado_orden === 'Control Calidad') countCalidad++;
                if(o.estado_orden === 'Entregado') countEntregadosHoy++;

                let badgePago = "";
                if (o.estado_pago === 'Pagado') badgePago = `<span class="badge bg-success"><i class="fas fa-check me-1"></i> Pagado</span>`;
                else if (o.estado_pago === 'Pendiente') badgePago = `<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pendiente</span>`;
                else badgePago = `<span class="badge bg-secondary"><i class="fas fa-file-invoice me-1"></i> Sin Facturar</span>`;

                let badgeOrden = "";
                let btnAccion = "";
                
                if (o.estado_orden === 'Listo') {
                    badgeOrden = `<span class="badge bg-primary fs-6">Listo para Entrega</span>`;
                    btnAccion = `<button class="btn btn-sm btn-success fw-bold shadow-sm" onclick="prepararEntrega(${o.id_orden}, '${o.cliente}', '${o.vehiculo}', '${o.monto_total_fmt}', '${o.estado_pago}')"><i class="fas fa-key me-1"></i> Entregar</button>`;
                } else if (o.estado_orden === 'Control Calidad') {
                    badgeOrden = `<span class="badge bg-info text-dark fw-bold">En Control de Calidad</span>`;
                    // BOTÓN DE EVALUAR CALIDAD HABILITADO
                    btnAccion = `<button class="btn btn-sm btn-info fw-bold shadow-sm text-dark" onclick="abrirModalCalidad(${o.id_orden}, '${o.vehiculo}')"><i class="fas fa-clipboard-check me-1"></i> Evaluar</button>`;
                } else if (o.estado_orden === 'Entregado') {
                    badgeOrden = `<span class="badge bg-dark">Entregado</span>`;
                    btnAccion = `<button class="btn btn-sm btn-outline-dark" onclick="mostrarComprobanteInmediato(${o.id_orden})" title="Reimprimir Acta"><i class="fas fa-print"></i> Acta</button>`;
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

        document.getElementById('count_listos').innerText = countListos;
        document.getElementById('count_calidad').innerText = countCalidad;
        document.getElementById('count_entregados').innerText = countEntregadosHoy;
    })
    .catch(err => console.error("Error listar entregas:", err));
}

// ==========================================
// PREPARACIÓN DE MODAL CONTROL CALIDAD
// ==========================================
function abrirModalCalidad(id_orden, vehiculo) {
    document.getElementById("id_orden_calidad").value = id_orden;
    document.getElementById("lbl_calidad_orden").innerText = "ORDEN: ORD-" + id_orden;
    document.getElementById("lbl_calidad_vehiculo").innerText = "Vehículo: " + vehiculo;
    
    document.getElementById("formCalidad").reset();
    abrirModalUI('modalCalidad');
}

function cerrarModalCalidad() {
    cerrarModalUI('modalCalidad');
}

// ==========================================
// PREPARACIÓN DE MODAL DE ENTREGA
// ==========================================
function prepararEntrega(id_orden, cliente, vehiculo, monto, estado_pago) {
    document.getElementById("id_orden_entrega").value = id_orden;
    
    document.getElementById("lbl_orden").innerText = "ORD-" + id_orden;
    document.getElementById("lbl_cliente").innerText = cliente;
    document.getElementById("lbl_vehiculo").innerText = vehiculo;
    document.getElementById("lbl_monto").innerText = monto;

    const alertaPago = document.getElementById("alerta_pago");
    const txtAlerta = document.getElementById("txt_alerta_pago");

    if (estado_pago === 'Pendiente' || estado_pago === 'Sin Facturar') {
        txtAlerta.innerText = estado_pago.toUpperCase();
        alertaPago.classList.remove("d-none");
    } else {
        alertaPago.classList.add("d-none");
    }

    abrirModalUI('modalEntrega');
}

// ==========================================
// CARGAR Y MOSTRAR ACTA DE ENTREGA
// ==========================================
function mostrarComprobanteInmediato(id_orden) {
    fetch(`../../modules/Taller/Archivo_Entrega.php?action=obtener_acta&id_orden=${id_orden}`)
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
            alert("El vehículo fue entregado, pero hubo un error al generar el acta visual: " + data.message);
        }
    })
    .catch(err => console.error("Error al cargar acta:", err));
}

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

function cerrarModalEntrega() { 
    document.getElementById("formEntrega").reset();
    cerrarModalUI('modalEntrega'); 
}

function cerrarModalComprobante() { 
    cerrarModalUI('modalComprobante'); 
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
        
        document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
        const b = document.createElement('div'); b.id = 'm-bd-entrega-' + id; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
    }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { if (typeof bootstrap !== 'undefined') { let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); } } catch (e) {}
    el.classList.remove('show'); el.style.display = 'none';
    el.removeAttribute('aria-modal'); el.removeAttribute('role');
    document.body.classList.remove('modal-open'); document.body.style.overflow = '';
    
    const b = document.getElementById('m-bd-entrega-' + id); if(b) b.remove();
    document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
}