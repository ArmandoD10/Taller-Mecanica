let planesGlobales = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarDependencias();
    listarPlanes();
    cargarReporte();

    // Guardar Plan (Mantenimiento)
    const formPlan = document.getElementById("formPlanMembresia");
    if (formPlan) {
        formPlan.addEventListener("submit", function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>...';
            btn.disabled = true;

            fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Membresia.php?action=guardar_plan", {
                method: "POST", body: new FormData(this)
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalText; btn.disabled = false;
                if (data.success) {
                    cerrarModalManual('modalPlanMembresia');
                    listarPlanes();
                    cargarDependencias(); 
                } else alert("Error: " + data.message);
            }).catch(err => { btn.disabled = false; alert("Error de conexión."); });
        });
    }

    // Guardar Asignación (Suscripción)
    const formAsig = document.getElementById("formAsignarMembresia");
    if (formAsig) {
        formAsig.addEventListener("submit", function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;

            fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Membresia.php?action=asignar_membresia", {
                method: "POST", body: new FormData(this)
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    alert(data.message);
                    cerrarModalManual('modalAsignarMembresia');
                    cargarReporte();
                } else alert(data.message);
            }).catch(err => { btn.disabled = false; alert("Error de red."); });
        });
    }

    // Buscador Rápido del Reporte Principal
    document.getElementById("buscadorReporte").addEventListener("keyup", function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll("#tablaReporte tr");
        rows.forEach(row => { row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none"; });
    });
});

// ==== SISTEMA DE PESTAÑAS MANUAL E INDESTRUCTIBLE ====
function cambiarPestanaManual(tipo, elemento) {
    // 1. Quitar activo de los botones
    document.getElementById("tab-btn-planes").classList.remove("active");
    document.getElementById("tab-btn-reporte").classList.remove("active");
    elemento.classList.add("active");

    // 2. Ocultar todos los paneles
    document.getElementById("panel-planes").classList.add("d-none");
    document.getElementById("panel-reporte").classList.add("d-none");

    // 3. Mostrar solo el que queremos
    document.getElementById("panel-" + tipo).classList.remove("d-none");
}

// ==== SISTEMA DE MODALES MANUAL (SIN BOOTSTRAP/JQUERY) ====
function abrirModalManual(id) {
    const modal = document.getElementById(id);
    if(modal) {
        modal.classList.add("show");
        modal.style.display = "block";
        document.body.style.overflow = "hidden"; // Bloquea el scroll del fondo
    }
}

function cerrarModalManual(id) {
    const modal = document.getElementById(id);
    if(modal) {
        modal.classList.remove("show");
        modal.style.display = "none";
        document.body.style.overflow = "auto";
    }
}

function abrirModalAsignar() {
    document.getElementById("formAsignarMembresia").reset();
    document.getElementById("info_asig_seleccionado").classList.add("d-none");
    document.getElementById("id_cliente_asig").value = "";
    abrirModalManual('modalAsignarMembresia'); // LLAMADA DIRECTA AL NUEVO SISTEMA
}

// ==== CARGA DE DATOS Y TABLAS ====
function cargarDependencias() {
    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Membresia.php?action=cargar_dependencias")
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const dlTipo = document.getElementById("listaTiposMembresia");
            dlTipo.innerHTML = '';
            data.data.tipos.forEach(t => { dlTipo.innerHTML += `<option value="${t.nombre}">`; });

            const selPrecio = document.getElementById("id_precio");
            selPrecio.innerHTML = '<option value="" disabled selected>Seleccione monto...</option>';
            data.data.precios.forEach(p => { 
                selPrecio.innerHTML += `<option value="${p.id_precio}">RD$ ${parseFloat(p.monto).toLocaleString()}</option>`; 
            });
        }
    });

    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Membresia.php?action=listar_planes")
    .then(r => r.json()).then(resPlanes => {
        if(resPlanes.success) {
            planesGlobales = resPlanes.data;
            const selAsig = document.getElementById("id_plan_asig");
            selAsig.innerHTML = '<option value="" disabled selected>Seleccione un plan...</option>';
            planesGlobales.forEach(p => {
                if(p.estado === 'activo') {
                    selAsig.innerHTML += `<option value="${p.id_plan}">${p.tipo_membresia} (RD$ ${parseFloat(p.precio_mensual).toLocaleString()})</option>`;
                }
            });
        }
    });
}

function listarPlanes() {
    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Membresia.php?action=listar_planes")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("tablaPlanes");
        tbody.innerHTML = "";
        if (data.success && data.data.length > 0) {
            data.data.forEach(p => {
                let badgeEstado = p.estado === 'activo' ? `<span class="badge bg-success">Activo</span>` : `<span class="badge bg-danger">Inactivo</span>`;
                let btnEstado = p.estado === 'activo'
                    ? `<button class="btn btn-sm btn-outline-danger" onclick="cambiarEstadoPlan(${p.id_plan}, 'inactivo')"><i class="fas fa-power-off"></i></button>`
                    : `<button class="btn btn-sm btn-outline-success" onclick="cambiarEstadoPlan(${p.id_plan}, 'activo')"><i class="fas fa-check"></i></button>`;
                
                let lavados = p.limite_lavado == 0 ? '<span class="badge bg-info text-dark shadow-sm"><i class="fas fa-infinity"></i> Ilimitados</span>' : p.limite_lavado;
                
                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold text-dark">${p.tipo_membresia}</td>
                        <td class="fw-bold text-success">RD$ ${parseFloat(p.precio_mensual).toLocaleString('es-DO', {minimumFractionDigits:2})}</td>
                        <td>${lavados}</td>
                        <td>${badgeEstado}</td>
                        <td class="no-print">
                            <button class="btn btn-sm btn-dark me-1" onclick="editarPlan(${p.id_plan})"><i class="fas fa-pencil-alt"></i></button>
                            ${btnEstado}
                        </td>
                    </tr>`;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-muted py-4">No hay planes creados.</td></tr>`;
        }
    });
}

function editarPlan(id) {
    fetch(`/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Membresia.php?action=obtener_plan&id_plan=${id}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById("id_plan").value = data.data.id_plan;
            document.getElementById("nombre_tipo_membresia").value = data.data.nombre_tipo;
            document.getElementById("id_precio").value = data.data.precio_mensual;
            document.getElementById("limite_lavado").value = data.data.limite_lavado;
            document.getElementById("tituloModalPlan").innerHTML = '<i class="fas fa-edit me-2"></i>Editar Plan';
            abrirModalManual('modalPlanMembresia');
        }
    });
}

function cambiarEstadoPlan(id, estado) {
    if(!confirm(`¿Desea cambiar el estado a ${estado}?`)) return;
    const fd = new FormData(); fd.append("id_plan", id); fd.append("estado", estado);
    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Membresia.php?action=cambiar_estado_plan", { method: "POST", body: fd })
    .then(res => res.json()).then(data => { if(data.success) { listarPlanes(); cargarDependencias(); }});
}

// ==== ASIGNACIÓN ====
function buscarParaAsignar(input) {
    const term = input.value.trim();
    const resDiv = document.getElementById("res_busc_asig");
    if (term.length < 2) { resDiv.classList.add("d-none"); return; }

    fetch(`/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Membresia.php?action=buscar_clientes&term=${encodeURIComponent(term)}`)
    .then(res => res.json())
    .then(data => {
        resDiv.innerHTML = "";
        if (data.success && data.data.length > 0) {
            resDiv.classList.remove("d-none");
            data.data.forEach(c => {
                const li = document.createElement("li");
                li.className = "list-group-item list-group-item-action py-2";
                li.style.cursor = "pointer";
                li.innerHTML = `<div class="fw-bold">${c.cliente}</div><small class="text-muted"><i class="fas fa-id-card me-1"></i>${c.num_documento || 'Sin Doc'}</small>`;
                li.onclick = () => {
                    document.getElementById("id_cliente_asig").value = c.id_cliente;
                    document.getElementById("lbl_asig_cliente").innerText = c.cliente;
                    document.getElementById("lbl_asig_doc").innerText = "Doc: " + (c.num_documento || 'N/A');
                    document.getElementById("info_asig_seleccionado").classList.remove("d-none");
                    resDiv.classList.add("d-none");
                    input.value = "";
                };
                resDiv.appendChild(li);
            });
        } else resDiv.classList.add("d-none");
    });
}

function seleccionarPlanAsig(sel) {
    const plan = planesGlobales.find(p => p.id_plan == sel.value);
    if (plan) {
        document.getElementById("lavados_asig").value = plan.limite_lavado;
        const hoy = new Date();
        hoy.setDate(hoy.getDate() + 30);
        document.getElementById("fecha_vencimiento_asig").value = hoy.toISOString().split('T')[0];
    }
}

function cargarReporte() {
    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Membresia.php?action=reporte_suscripciones")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("tablaReporte");
        tbody.innerHTML = "";
        if (data.success && data.data.length > 0) {
            data.data.forEach(s => {
                let badge = s.estado === 'activo' ? 'bg-success' : (s.estado === 'inactivo' ? 'bg-danger' : 'bg-secondary');
                let lavados = s.lavado_restantes == 0 ? '<i class="fas fa-infinity text-muted"></i>' : s.lavado_restantes;
                tbody.innerHTML += `
                    <tr>
                        <td class="text-start fw-bold text-dark"><i class="fas fa-user me-1 text-muted"></i>${s.cliente}</td>
                        <td><span class="fw-bold">${s.plan_nombre}</span><br><small class="text-success">RD$ ${parseFloat(s.precio).toLocaleString()}</small></td>
                        <td class="small"><span class="text-muted">Inicia:</span> ${s.inicio}<br><span class="text-dark fw-bold">Vence:</span> ${s.fin}</td>
                        <td><span class="badge bg-primary fs-6">${lavados}</span></td>
                        <td><span class="badge ${badge}">${s.estado}</span></td>
                    </tr>`;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-muted py-5">No hay suscripciones registradas.</td></tr>`;
        }
    });
}