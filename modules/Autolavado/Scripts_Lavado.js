document.addEventListener("DOMContentLoaded", () => {
    listarLavados();
    cargarDependenciasLavado();

    const formNuevoLavado = document.getElementById("formNuevoLavado");
    if (formNuevoLavado) {
        formNuevoLavado.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const idOrden = document.getElementById("id_orden_taller").value;
            const tipoCliente = document.querySelector('input[name="tipo_cliente_lav"]:checked').value;
            
            if (!idOrden) {
                if (tipoCliente === 'registrado') {
                    if(!document.getElementById("id_vehiculo_express").value) return alert("⚠️ Debe buscar un Vehículo Registrado.");
                } else {
                    if(!document.getElementById("occ_nombre_lav").value.trim() || !document.getElementById("occ_vehiculo_lav").value.trim()) return alert("⚠️ Complete los datos del cliente ocasional.");
                }
            }

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';
            btn.disabled = true;

            fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Lavado.php?action=registrar_lavado", {
                method: "POST", body: new FormData(this)
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalText; btn.disabled = false;
                if (data.success) {
                    cerrarModalLavado();
                    listarLavados();
                } else alert("Error: " + data.message);
            }).catch(err => { btn.innerHTML = originalText; btn.disabled = false; alert("Error de conexión."); });
        });
    }

    document.getElementById("id_orden_taller").addEventListener("change", function() {
        const panelDirecto = document.getElementById("panel_nuevo_lavado_directo");
        if (this.value !== "") panelDirecto.classList.add("d-none");
        else panelDirecto.classList.remove("d-none");
    });
});

function toggleTipoClienteLav() {
    const esReg = document.getElementById("lav_reg").checked;
    if (esReg) {
        document.getElementById("seccion_express").classList.remove("d-none");
        document.getElementById("seccion_ocasional").classList.add("d-none");
    } else {
        document.getElementById("seccion_express").classList.add("d-none");
        document.getElementById("seccion_ocasional").classList.remove("d-none");
    }
}

function listarLavados() {
    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Lavado.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaLavados");
        tbody.innerHTML = "";
        let cEspera = 0, cLavando = 0, cListos = 0;

        if (data.success && data.data.length > 0) {
            data.data.forEach(l => {
                let badgeEstado = ""; let btnAccion = "";

                if (l.estado_actual === 'Pendiente' || l.estado_actual === 'En Cola') {
                    cEspera++;
                    badgeEstado = `<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> En Cola</span>`;
                    btnAccion = `<button class="btn btn-sm btn-info fw-bold text-dark w-100 shadow-sm" onclick="cambiarEstadoLavado(${l.id_lavado}, 'En Proceso')"><i class="fas fa-play me-1"></i> Iniciar</button>`;
                } else if (l.estado_actual === 'En Proceso' || l.estado_actual === 'En Lavado') {
                    cLavando++;
                    badgeEstado = `<span class="badge bg-info text-dark"><i class="fas fa-tint"></i> Lavando</span>`;
                    btnAccion = `<button class="btn btn-sm btn-success fw-bold w-100 shadow-sm" onclick="cambiarEstadoLavado(${l.id_lavado}, 'Listo')"><i class="fas fa-check me-1"></i> Terminar</button>`;
                } else {
                    cListos++;
                    badgeEstado = `<span class="badge bg-success"><i class="fas fa-flag-checkered"></i> Listo</span>`;
                    
                    if (l.es_express == 1) {
                        btnAccion = `<button class="btn btn-sm btn-primary fw-bold w-100 shadow-sm" onclick="facturarLavadoExpress(${l.id_lavado}, '${l.id_orden}')"><i class="fas fa-file-invoice-dollar me-1"></i> Facturar Express</button>`;
                    } else {
                        btnAccion = `<button class="btn btn-sm btn-outline-dark w-100 shadow-sm" disabled><i class="fas fa-tools text-warning me-1"></i> Cobrar en Taller</button>`;
                    }
                }

                let badgeSuciedad = l.nivel_suciedad === 'Alto' ? 'text-danger fw-bold' : 'text-muted';
                let numOrden = (l.es_express == 1 || l.id_orden == 'Express') ? `<span class="badge bg-secondary">Express</span>` : `ORD-${l.id_orden}`;

                tbody.innerHTML += `
                    <tr>
                        <td>
                            <span class="fw-bold text-primary">${numOrden}</span><br>
                            <small class="text-muted" style="font-size: 10px;">LAV-${l.id_lavado} | ${l.fecha}</small>
                        </td>
                        <td class="text-start">
                            <span class="fw-bold text-dark d-block">${l.vehiculo}</span>
                            <small class="text-muted"><i class="fas fa-user me-1"></i>${l.cliente}</small>
                        </td>
                        <td><span class="badge bg-secondary shadow-sm">${l.tipo_lavado}</span></td>
                        <td><span class="${badgeSuciedad}">${l.nivel_suciedad}</span></td>
                        <td>${badgeEstado}</td>
                        <td>${btnAccion}</td>
                    </tr>`;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-5">No hay vehículos en pista en este momento.</td></tr>`;
        }

        document.getElementById("count_espera").innerText = cEspera;
        document.getElementById("count_lavando").innerText = cLavando;
        document.getElementById("count_listos").innerText = cListos;
    });
}

function cambiarEstadoLavado(id_lavado, nuevo_estado) {
    const fd = new FormData(); fd.append("id_lavado", id_lavado); fd.append("nuevo_estado", nuevo_estado);
    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Lavado.php?action=cambiar_estado", { method: "POST", body: fd })
    .then(res => res.json()).then(data => {
        if(data.success) listarLavados(); else alert("Error: " + data.message);
    });
}

function facturarLavadoExpress(id_lavado, id_orden) {
    if(!confirm("¿Generar factura y comprobante para este vehículo?")) return;
    const fd = new FormData(); fd.append("id_lavado", id_lavado); fd.append("id_orden", id_orden);
    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Lavado.php?action=facturar_express", { method: "POST", body: fd })
    .then(res => res.json()).then(data => {
        if(data.success) { listarLavados(); abrirTicketLavado(data.id_factura); } else alert("Error: " + data.message);
    });
}

function abrirTicketLavado(id_factura) {
    fetch(`/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Lavado.php?action=obtener_ticket&id_factura=${id_factura}`)
    .then(res => res.json()).then(data => {
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
        }
    });
}

function cargarDependenciasLavado() {
    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Lavado.php?action=cargar_dependencias")
    .then(res => res.json()).then(data => {
        if (data.success) {
            const selTipo = document.getElementById("id_tipo_lavado");
            selTipo.innerHTML = '<option value="" disabled selected>Seleccione...</option>';
            data.data.tipos.forEach(t => { selTipo.innerHTML += `<option value="${t.id_tipo}">${t.nombre}</option>`; });

            const selPrecio = document.getElementById("id_precio");
            selPrecio.innerHTML = '<option value="" disabled selected>Seleccione...</option>';
            data.data.precios.forEach(p => { selPrecio.innerHTML += `<option value="${p.id_precio}">RD$ ${parseFloat(p.monto).toLocaleString()}</option>`; });

            const selOrden = document.getElementById("id_orden_taller");
            selOrden.innerHTML = '<option value="">-- Cliente de calle (Lavado Directo) --</option>';
            data.data.ordenes.forEach(o => { selOrden.innerHTML += `<option value="${o.id_orden}">ORD-${o.id_orden} | ${o.vehiculo} (${o.cliente})</option>`; });
        }
    });
}

// ==== MAGIA DE MEMBRESÍAS ====
function buscarVehiculosLavado(input) {
    const term = input.value.trim();
    const resDiv = document.getElementById("res_vehiculos_lavado");
    if (term.length < 2) { resDiv.classList.add("d-none"); return; }

    fetch(`/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Cotizaciones.php?action=buscar_vehiculos&term=${encodeURIComponent(term)}`)
    .then(res => res.json()).then(data => {
        resDiv.innerHTML = "";
        if (data.success && data.data.length > 0) {
            resDiv.classList.remove("d-none");
            data.data.forEach(v => {
                const li = document.createElement("li");
                li.className = "list-group-item list-group-item-action py-2";
                li.style.cursor = "pointer";
                li.innerHTML = `<strong>${v.vehiculo_desc}</strong><br><small class="text-muted"><i class="fas fa-user me-1"></i>${v.cliente}</small>`;
                li.onclick = () => {
                    document.getElementById("buscador_vehiculo_lavado").value = "";
                    document.getElementById("id_vehiculo_express").value = v.id_vehiculo;
                    document.getElementById("lbl_lav_vehiculo").innerText = v.vehiculo_desc;
                    document.getElementById("lbl_lav_cliente").innerText = v.cliente;
                    document.getElementById("info_vehiculo_seleccionado").classList.remove("d-none");
                    resDiv.classList.add("d-none");
                    
                    // Verificar Membresía Automáticamente
                    verificarMembresiaActiva(v.id_vehiculo);
                };
                resDiv.appendChild(li);
            });
        } else resDiv.classList.add("d-none");
    });
}

function verificarMembresiaActiva(id_vehiculo) {
    fetch(`/Taller/Taller-Mecanica/modules/Autolavado/Archivo_Lavado.php?action=verificar_membresia&sec_vehiculo=${id_vehiculo}`)
    .then(res => res.json()).then(data => {
        const alerta = document.getElementById("alerta_membresia");
        const chk = document.getElementById("usar_membresia");
        
        if(data.success && data.data) {
            document.getElementById("id_membresia_activa").value = data.data.id_membresia;
            document.getElementById("lbl_mem_nombre").innerText = "Membresía: " + data.data.plan;
            
            let rest = parseInt(data.data.lavado_restantes);
            let lim = parseInt(data.data.limite_lavado);
            document.getElementById("lbl_mem_restantes").innerText = (lim === 0) ? "Lavados: Ilimitados ♾️" : "Lavados Restantes: " + rest;
            
            chk.checked = true;
            alerta.classList.remove("d-none");
        } else {
            alerta.classList.add("d-none");
            chk.checked = false;
            document.getElementById("id_membresia_activa").value = "";
        }
        toggleMembresiaLavado();
    });
}

function toggleMembresiaLavado() {
    const usar = document.getElementById("usar_membresia").checked;
    const divPrecio = document.getElementById("id_precio").parentNode;
    if(usar) {
        divPrecio.style.opacity = '0.4';
        divPrecio.style.pointerEvents = 'none'; 
    } else {
        divPrecio.style.opacity = '1';
        divPrecio.style.pointerEvents = 'auto';
    }
}

// ==== LIMPIAR Y ABRIR MODAL ====
function abrirModalNuevoLavado() {
    document.getElementById("formNuevoLavado").reset();
    document.getElementById("id_vehiculo_express").value = "";
    document.getElementById("info_vehiculo_seleccionado").classList.add("d-none");
    
    // Resetear Membresías
    document.getElementById("alerta_membresia").classList.add("d-none");
    document.getElementById("usar_membresia").checked = false;
    document.getElementById("id_membresia_activa").value = "";
    toggleMembresiaLavado();

    document.getElementById("panel_nuevo_lavado_directo").classList.remove("d-none");
    document.getElementById("lav_reg").checked = true;
    toggleTipoClienteLav();
    abrirModalUI('modalNuevoLavado');
}

function cerrarModalLavado() { cerrarModalUI('modalNuevoLavado'); }

function abrirModalUI(id) {
    const el = document.getElementById(id); if(!el) return;
    try { if (typeof bootstrap !== 'undefined') { let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el); m.show(); } else throw new Error(); } 
    catch (e) { if (typeof jQuery !== 'undefined') { $('#' + id).modal('show'); } else { el.classList.add('show'); el.style.display = 'block'; document.body.classList.add('modal-open'); document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove()); const b = document.createElement('div'); b.id = 'm-bd-' + id; b.className = 'modal-backdrop fade show'; document.body.appendChild(b); } }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id); if(!el) return;
    try { if (typeof bootstrap !== 'undefined') { let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); } else throw new Error(); } 
    catch (e) { if (typeof jQuery !== 'undefined') { $('#' + id).modal('hide'); } else { el.classList.remove('show'); el.style.display = 'none'; document.body.classList.remove('modal-open'); const b = document.getElementById('m-bd-' + id); if(b) b.remove(); document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove()); } }
}