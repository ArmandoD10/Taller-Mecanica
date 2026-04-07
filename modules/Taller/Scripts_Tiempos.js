let cacheMecanicos = [];
let mecanicosSeleccionados = []; 

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // ==========================================
    // 1. BUSCADOR DINÁMICO DE MECÁNICOS
    // ==========================================
    const txtMecanico = document.getElementById('txt_buscar_empleado');
    const listaMecanico = document.getElementById('lista_empleado');
    const hiddenMecanicoTemp = document.getElementById('id_empleado_temp');

    if(txtMecanico) {
        txtMecanico.addEventListener('input', function() {
            const busca = this.value.toLowerCase().trim();
            listaMecanico.innerHTML = '';
            if (busca.length < 1) { listaMecanico.classList.add('d-none'); return; }
            const filtrados = cacheMecanicos.filter(m => m.nombre_completo.toLowerCase().includes(busca));
            if (filtrados.length > 0) {
                listaMecanico.classList.remove('d-none');
                filtrados.forEach(m => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action py-1';
                    li.textContent = m.nombre_completo;
                    li.onclick = () => {
                        txtMecanico.value = m.nombre_completo;
                        hiddenMecanicoTemp.value = m.id_empleado;
                        listaMecanico.classList.add('d-none');
                    };
                    listaMecanico.appendChild(li);
                });
            } else { listaMecanico.classList.add('d-none'); }
        });
    }

    document.addEventListener('click', (e) => {
        if (txtMecanico && !txtMecanico.contains(e.target)) listaMecanico.classList.add('d-none');
    });

    // ==========================================
    // 2. GUARDAR / EDITAR ASIGNACIÓN
    // ==========================================
    const formAsig = document.getElementById("formAsignacion");
    if(formAsig) {
        formAsig.addEventListener("submit", function(e) {
            e.preventDefault();
            if(document.getElementById('id_orden').value === "") { alert("Seleccione una Orden."); return; }
            if(document.getElementById('id_bahia').value === "") { alert("Seleccione una Bahía."); return; }
            if(mecanicosSeleccionados.length === 0) { alert("Asigne al menos un mecánico."); return; }
            
            const formData = new FormData(this);
            
            // SOLUCIÓN AL ERROR: Inyección manual de campos deshabilitados
            // HTML no envía selects bloqueados, así que los forzamos aquí
            if (document.getElementById('id_orden').disabled) {
                formData.append("id_orden", document.getElementById('id_orden').value);
            }
            if (document.getElementById('id_tipo_servicio').disabled) {
                formData.append("id_tipo_servicio", document.getElementById('id_tipo_servicio').value);
            }

            formData.append("mecanicos", JSON.stringify(mecanicosSeleccionados));
            
            fetch("../../modules/Taller/Archivo_Tiempos.php?action=guardar_asignacion", { 
                method: "POST", 
                body: formData 
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) { 
                    cerrarModalAsignacion(); 
                    listar(); 
                    cargarDependencias(); // Recargar para actualizar estados visuales
                    alert(data.message); 
                } else {
                    alert("ATENCIÓN:\n" + data.message);
                }
            })
            .catch(err => console.error("Error al guardar:", err));
        });
    }

    // ==========================================
    // 3. FINALIZAR TIEMPO
    // ==========================================
    const formTiempos = document.getElementById("formTiempos");
    if(formTiempos) {
        formTiempos.addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch("../../modules/Taller/Archivo_Tiempos.php?action=finalizar_tiempo", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) { 
                    cerrarModalTiempos(); 
                    listar(); 
                    cargarDependencias(); // Recargar para liberar Bahías/Maquinas visualmente
                    alert(data.message); 
                } else { alert(data.message); }
            })
            .catch(err => console.error("Error al finalizar:", err));
        });
    }
});

// ==========================================
// FUNCIONES DE CARGA Y LISTADO
// ==========================================

function listar() {
    fetch("../../modules/Taller/Archivo_Tiempos.php?action=listar")
    .then(res => res.json()).then(data => {
        const tbody = document.getElementById("cuerpoTablaAsignaciones");
        if(!tbody) return;
        tbody.innerHTML = "";
        if (data.success && data.data.length > 0) {
            data.data.forEach(a => {
                let badge = "";
                let btn = "";

                if (a.estado_asignacion === "Pendiente") {
                    badge = `<span class="badge bg-secondary">Pendiente</span>`;
                    btn = `
                        <button class="btn btn-sm btn-warning text-dark me-1" onclick="editarAsignacion(${a.id_asignacion})" title="Editar Asignación">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-success" onclick="iniciarTrabajo(${a.id_asignacion})" title="Iniciar Cronómetro">
                            <i class="fas fa-play"></i>
                        </button>
                    `;
                } else if (a.estado_asignacion === "En Curso") {
                    badge = `<span class="badge bg-info text-dark">En Curso</span>`;
                    btn = `<button class="btn btn-sm btn-danger" onclick="abrirModalTiempos(${a.id_asignacion}, '${a.hora_inicio_fmt}')" title="Finalizar"><i class="fas fa-stop"></i></button>`;
                } else {
                    badge = `<span class="badge bg-success">Completado</span>`;
                    btn = `<i class="fas fa-check-circle text-success fs-5"></i>`;
                }

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold">ORD-${a.id_orden}</td>
                    <td>${a.servicio}</td>
                    <td>${a.mecanicos_nombres}</td>
                    <td>${badge}</td>
                    <td>${a.hora_inicio_fmt || '--:--'}</td>
                    <td>${a.hora_fin_fmt || '--:--'}</td>
                    <td class="text-center">${btn}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No hay asignaciones registradas.</td></tr>`;
        }
    })
    .catch(err => console.error("Error listar:", err));
}

function cargarDependencias() {
    fetch("../../modules/Taller/Archivo_Tiempos.php?action=cargar_dependencias")
    .then(res => res.json()).then(data => {
        if (data.success) {
            const selOrden = document.getElementById("id_orden");
            selOrden.innerHTML = '<option value="">Seleccione una orden...</option>';
            data.data.ordenes.forEach(o => { selOrden.innerHTML += `<option value="${o.id_orden}">ORD-${o.id_orden} - ${o.descripcion || ''}</option>`; });

            const selBahia = document.getElementById("id_bahia");
            selBahia.innerHTML = '<option value="">Seleccione Bahía...</option>';
            data.data.bahias.forEach(b => { 
                let bloqueada = b.estado_bahia === 'Ocupada' ? 'disabled' : '';
                let icono = b.estado_bahia === 'Ocupada' ? '🔴' : '🟢';
                selBahia.innerHTML += `<option value="${b.id_bahia}" ${bloqueada}>${icono} ${b.descripcion}</option>`; 
            });

            const selMaq = document.getElementById("id_maquinaria");
            selMaq.innerHTML = '<option value="">Ninguna / Manual</option>';
            data.data.maquinaria.forEach(m => { 
                let bloqueada = (m.estado_maquina === 'Ocupada' || m.estado_maquina === 'En Uso') ? 'disabled' : '';
                let icono = bloqueada ? '🔴' : '🟢';
                selMaq.innerHTML += `<option value="${m.id_maquinaria}" ${bloqueada}>${icono} ${m.nombre}</option>`; 
            });

            cacheMecanicos = data.data.mecanicos;
        }
    })
    .catch(err => console.error("Error cargarDependencias:", err));
}

function cargarServiciosPorOrden(id_orden, id_servicio_preseleccionado = null) {
    const selServicio = document.getElementById("id_tipo_servicio");
    if(!id_orden) { 
        selServicio.innerHTML = '<option value="">Seleccione primero una orden...</option>';
        selServicio.disabled = true; 
        return; 
    }

    selServicio.innerHTML = '<option value="">Cargando servicios...</option>';
    selServicio.disabled = true;

    fetch(`../../modules/Taller/Archivo_Tiempos.php?action=cargar_servicios_orden&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(data => {
        selServicio.innerHTML = '';
        if (data.success && data.data.length > 0) {
            selServicio.innerHTML = '<option value="">Seleccione servicio de la lista...</option>';
            data.data.forEach(s => {
                selServicio.innerHTML += `<option value="${s.id_tipo_servicio}">${s.nombre_servicio}</option>`;
            });
            selServicio.disabled = false;
            
            // Si estamos en modo Editar, autoseleccionamos el servicio que guardó antes
            if(id_servicio_preseleccionado) {
                selServicio.value = id_servicio_preseleccionado;
            }
        } else {
            selServicio.innerHTML = '<option value="">La orden no tiene servicios activos</option>';
            selServicio.disabled = true;
        }
    })
    .catch(err => console.error("Error servicios orden:", err));
}

// ==========================================
// FUNCIONES DE MECÁNICOS
// ==========================================

function agregarMecanicoLista() {
    const id = document.getElementById('id_empleado_temp').value;
    const nombre = document.getElementById('txt_buscar_empleado').value;
    if (!id || !nombre) { alert("Busque y seleccione un mecánico de la lista."); return; }
    agregarMecanicoVisual(id, nombre);
    document.getElementById('id_empleado_temp').value = '';
    document.getElementById('txt_buscar_empleado').value = '';
}

function agregarMecanicoVisual(id, nombre) {
    const idStr = String(id);
    if (mecanicosSeleccionados.includes(idStr)) return;
    
    mecanicosSeleccionados.push(idStr);
    document.getElementById('msg_sin_mecanicos').style.display = 'none';
    
    const div = document.createElement('span');
    div.className = "badge bg-primary me-2 mb-2 p-2 fs-6";
    div.id = `badge_mec_${idStr}`;
    div.innerHTML = `${nombre} <i class="fas fa-times ms-2" style="cursor:pointer" onclick="removerMecanico('${idStr}')"></i>`;
    
    document.getElementById('contenedor_mecanicos').appendChild(div);
}

function removerMecanico(id) {
    const idStr = String(id);
    mecanicosSeleccionados = mecanicosSeleccionados.filter(i => i !== idStr);
    document.getElementById(`badge_mec_${idStr}`).remove();
    if(mecanicosSeleccionados.length === 0) document.getElementById('msg_sin_mecanicos').style.display = 'block';
}

function iniciarTrabajo(id) {
    if (confirm("¿Desea iniciar el cronómetro para este trabajo? Se ocupará la Bahía y Maquinaria asignada.")) {
        const f = new FormData();
        f.append("id_asignacion", id);
        fetch("../../modules/Taller/Archivo_Tiempos.php?action=iniciar_tiempo", { method: "POST", body: f })
        .then(res => res.json())
        .then(data => { 
            if(data.success) {
                listar();
                cargarDependencias(); 
            } else {
                alert("NO SE PUDO INICIAR:\n" + data.message);
            }
        });
    }
}

// ==========================================
// GESTIÓN DE MODALES (UI) Y EDICIÓN
// ==========================================

function nuevaAsignacion() {
    document.getElementById("tituloModalAsignacion").innerHTML = "Nueva Asignación Detallada";
    document.getElementById("btnGuardarAsig").innerHTML = '<i class="fas fa-save me-2"></i>Crear Asignación';
    
    document.getElementById("formAsignacion").reset();
    document.getElementById("id_asignacion").value = "";
    document.getElementById("id_orden").disabled = false;
    
    mecanicosSeleccionados = [];
    document.getElementById('contenedor_mecanicos').innerHTML = '<p class="text-muted small m-0" id="msg_sin_mecanicos">No hay mecánicos asignados.</p>';
    
    const selServicio = document.getElementById("id_tipo_servicio");
    selServicio.innerHTML = '<option value="">Seleccione primero una orden...</option>';
    selServicio.disabled = true;

    const now = new Date();
    document.getElementById("fecha_asignacion").value = now.toISOString().split('T')[0];
    document.getElementById("hora_asignacion").value = now.toTimeString().split(' ')[0].substring(0, 5);
    
    abrirModalUI('modalAsignacion');
}

function editarAsignacion(id) {
    document.getElementById("tituloModalAsignacion").innerHTML = "Editar Asignación Pendiente";
    document.getElementById("btnGuardarAsig").innerHTML = '<i class="fas fa-sync me-2"></i>Actualizar Cambios';
    
    fetch(`../../modules/Taller/Archivo_Tiempos.php?action=obtener_asignacion&id=${id}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const d = data.data;
            document.getElementById("id_asignacion").value = d.id_asignacion;
            document.getElementById("id_orden").value = d.id_orden;
            document.getElementById("id_orden").disabled = true; 
            
            cargarServiciosPorOrden(d.id_orden, d.id_tipo_servicio);
            
            document.getElementById("id_bahia").value = d.id_bahia || '';
            document.getElementById("id_maquinaria").value = d.id_maquinaria || '';
            document.getElementById("fecha_asignacion").value = d.fecha_asignacion;
            document.getElementById("hora_asignacion").value = d.hora_asignacion.substring(0, 5);
            
            mecanicosSeleccionados = [];
            document.getElementById('contenedor_mecanicos').innerHTML = '<p class="text-muted small m-0" id="msg_sin_mecanicos" style="display:none;">No hay mecánicos asignados.</p>';
            
            d.mecanicos.forEach(idEmp => {
                const mec = cacheMecanicos.find(m => m.id_empleado == idEmp);
                if(mec) agregarMecanicoVisual(mec.id_empleado, mec.nombre_completo);
            });

            abrirModalUI('modalAsignacion');
        } else {
            alert("Error al cargar datos: " + data.message);
        }
    })
    .catch(err => console.error("Error en editarAsignacion:", err));
}

function abrirModalTiempos(id, inicio) {
    document.getElementById("id_asignacion_tiempo").value = id;
    document.getElementById("lbl_hora_inicio").innerText = inicio;
    document.getElementById("lbl_hora_fin").innerText = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    abrirModalUI('modalTiempos');
}

function cerrarModalAsignacion() { cerrarModalUI('modalAsignacion'); }
function cerrarModalTiempos() { cerrarModalUI('modalTiempos'); }

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
        if(!document.getElementById('m-bd')){
            const b = document.createElement('div'); b.id = 'm-bd'; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
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
    const b = document.getElementById('m-bd'); if(b) b.remove();
    document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
}