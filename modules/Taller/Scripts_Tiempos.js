let cacheOrdenes = [];
let cacheMecanicos = [];
let cacheMaquinaria = [];
let mecanicosSeleccionados = []; 
let maquinariaSeleccionada = []; 

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // Listener para precio sugerido
    const selTipoServicio = document.getElementById("id_tipo_servicio");
    const lblPrecioSugerido = document.getElementById("lbl_precio_sugerido");

    if(selTipoServicio) {
        selTipoServicio.addEventListener("change", function() {
            const selectedOption = this.options[this.selectedIndex];
            if(selectedOption) {
                const precioSugerido = selectedOption.getAttribute("data-precio");
                if(precioSugerido && parseFloat(precioSugerido) >= 0) {
                    if(lblPrecioSugerido) lblPrecioSugerido.innerHTML = `Sugerido por servicio: <span class="fw-bold text-success">RD$ ${parseFloat(precioSugerido).toFixed(2)}</span>`;
                } else {
                    if(lblPrecioSugerido) lblPrecioSugerido.innerHTML = `Sugerido por servicio: <span class="fw-bold text-muted">N/A</span>`;
                }
            }
        });
    }

    // Buscador de Órdenes
    const txtOrden = document.getElementById('txt_buscar_orden');
    const listaOrdenes = document.getElementById('lista_ordenes');
    const hiddenOrden = document.getElementById('id_orden');

    if(txtOrden) {
        txtOrden.addEventListener('input', function() {
            const busca = this.value.toLowerCase().trim();
            listaOrdenes.innerHTML = '';
            
            if (busca.length < 1) { 
                listaOrdenes.classList.add('d-none'); 
                document.getElementById('info_orden_seleccionada').classList.add('d-none');
                hiddenOrden.value = ""; 
                cargarServiciosPorOrden(""); 
                return; 
            }
            
            const filtrados = cacheOrdenes.filter(o => 
                `ord-${o.id_orden}`.includes(busca) || 
                (o.descripcion && o.descripcion.toLowerCase().includes(busca)) ||
                (o.cliente && o.cliente.toLowerCase().includes(busca)) ||
                (o.vehiculo && o.vehiculo.toLowerCase().includes(busca))
            );

            if (filtrados.length > 0) {
                listaOrdenes.classList.remove('d-none');
                filtrados.forEach(o => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action py-2';
                    li.style.cursor = 'pointer';
                    li.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary">ORD-${o.id_orden}</span>
                            <small class="text-muted">${o.descripcion || 'Sin descripción'}</small>
                        </div>
                        <div class="small mt-1">
                            <i class="fas fa-user text-secondary"></i> ${o.cliente} | 
                            <i class="fas fa-car text-secondary"></i> ${o.vehiculo}
                        </div>
                    `;
                    li.onclick = () => {
                        txtOrden.value = `ORD-${o.id_orden} - ${o.descripcion || ''}`;
                        hiddenOrden.value = o.id_orden;
                        listaOrdenes.classList.add('d-none');
                        
                        document.getElementById('info_orden_seleccionada').classList.remove('d-none');
                        document.getElementById('lbl_orden_cliente').innerText = o.cliente;
                        document.getElementById('lbl_orden_vehiculo').innerText = o.vehiculo;

                        cargarServiciosPorOrden(o.id_orden, null, 0);
                    };
                    listaOrdenes.appendChild(li);
                });
            } else {
                listaOrdenes.classList.remove('d-none');
                listaOrdenes.innerHTML = `<li class="list-group-item text-muted">No hay órdenes pendientes...</li>`;
                hiddenOrden.value = "";
                document.getElementById('info_orden_seleccionada').classList.add('d-none');
                cargarServiciosPorOrden("");
            }
        });
    }

    // Buscador de Mecánicos
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
                    li.style.cursor = 'pointer';
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

    // Buscador de Maquinaria
    const txtMaquinaria = document.getElementById('txt_buscar_maquinaria');
    const listaMaquinaria = document.getElementById('lista_maquinaria');
    const hiddenMaquinariaTemp = document.getElementById('id_maquinaria_temp');

    if(txtMaquinaria) {
        txtMaquinaria.addEventListener('input', function() {
            const busca = this.value.toLowerCase().trim();
            listaMaquinaria.innerHTML = '';
            if (busca.length < 1) { listaMaquinaria.classList.add('d-none'); return; }
            
            const filtrados = cacheMaquinaria.filter(m => m.nombre.toLowerCase().includes(busca));
            if (filtrados.length > 0) {
                listaMaquinaria.classList.remove('d-none');
                filtrados.forEach(m => {
                    const bloqueada = (m.en_uso == 1);
                    const icono = bloqueada ? '🔴' : '🟢';
                    
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action py-1';
                    
                    if (bloqueada) {
                        li.classList.add('text-muted');
                        li.style.cursor = 'not-allowed';
                        li.innerHTML = `${icono} ${m.nombre} (Asignada/En Uso)`;
                    } else {
                        li.style.cursor = 'pointer';
                        li.innerHTML = `${icono} ${m.nombre}`;
                        li.onclick = () => {
                            txtMaquinaria.value = m.nombre;
                            hiddenMaquinariaTemp.value = m.id_maquinaria;
                            listaMaquinaria.classList.add('d-none');
                        };
                    }
                    listaMaquinaria.appendChild(li);
                });
            } else { listaMaquinaria.classList.add('d-none'); }
        });
    }

    // Ocultar listas al hacer click afuera
    document.addEventListener('click', (e) => {
        if (txtOrden && !txtOrden.contains(e.target)) listaOrdenes.classList.add('d-none');
        if (txtMecanico && !txtMecanico.contains(e.target)) listaMecanico.classList.add('d-none');
        if (txtMaquinaria && !txtMaquinaria.contains(e.target)) listaMaquinaria.classList.add('d-none');
    });

    // Envío del Formulario de Asignación
    const formAsig = document.getElementById("formAsignacion");
    if(formAsig) {
        formAsig.addEventListener("submit", function(e) {
            e.preventDefault();
            if(document.getElementById('id_orden').value === "") { Swal.fire('Atención', 'Seleccione una Orden válida.', 'warning'); return; }
            if(document.getElementById('id_bahia').value === "") { Swal.fire('Atención', 'Seleccione una Bahía.', 'warning'); return; }
            if(document.getElementById('id_precio').value === "") { Swal.fire('Atención', 'Seleccione una Tarifa a aplicar.', 'warning'); return; }
            if(mecanicosSeleccionados.length === 0) { Swal.fire('Atención', 'Asigne al menos un mecánico.', 'warning'); return; }
            
            const formData = new FormData(this);
            
            if (document.getElementById('id_tipo_servicio').disabled) {
                formData.append("id_tipo_servicio", document.getElementById('id_tipo_servicio').value);
            }

            formData.append("mecanicos", JSON.stringify(mecanicosSeleccionados));
            formData.append("maquinarias", JSON.stringify(maquinariaSeleccionada));
            
            fetch("../../modules/Taller/Archivo_Tiempos.php?action=guardar_asignacion", { 
                method: "POST", 
                body: formData 
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) { 
                    cerrarModalAsignacion(); 
                    listar(); 
                    cargarDependencias(); 
                    Swal.fire('Éxito', data.message, 'success'); 
                } else {
                    Swal.fire('Atención', data.message, 'warning');
                }
            })
            .catch(err => console.error("Error al guardar:", err));
        });
    }

    // Finalizar Tiempo
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
                    cargarDependencias(); 
                    Swal.fire('Completado', data.message, 'success'); 
                } else { Swal.fire('Error', data.message, 'error'); }
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
                        <button class="btn btn-sm btn-warning text-dark me-1 shadow-sm" onclick="editarAsignacion(${a.id_asignacion})" title="Editar Asignación">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-success me-1 shadow-sm" onclick="iniciarTrabajo(${a.id_asignacion})" title="Iniciar Cronómetro">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-sm btn-danger shadow-sm" onclick="eliminarAsignacion(${a.id_asignacion})" title="Eliminar Asignación">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    `;
                } else if (a.estado_asignacion === "En Curso") {
                    badge = `<span class="badge bg-info text-dark">En Curso</span>`;
                    btn = `<button class="btn btn-sm btn-danger shadow-sm" onclick="abrirModalTiempos(${a.id_asignacion}, '${a.hora_inicio_fmt}')" title="Finalizar Trabajo"><i class="fas fa-stop"></i></button>`;
                } else {
                    badge = `<span class="badge bg-success">Completado</span>`;
                    btn = `<i class="fas fa-check-circle text-success fs-5"></i>`;
                }

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold ps-3">ORD-${a.id_orden}</td>
                    <td>${a.servicio}</td>
                    <td>${a.mecanicos_nombres}</td>
                    <td>${badge}</td>
                    <td>${a.hora_inicio_fmt || '--:--'}</td>
                    <td>${a.hora_fin_fmt || '--:--'}</td>
                    <td class="text-center text-nowrap">${btn}</td>
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
            cacheOrdenes = data.data.ordenes;

            const selBahia = document.getElementById("id_bahia");
            selBahia.innerHTML = '<option value="">Seleccione Bahía...</option>';
            data.data.bahias.forEach(b => { 
                let bloqueada = b.en_uso == 1 ? 'disabled' : '';
                let icono = b.en_uso == 1 ? '🔴' : '🟢';
                selBahia.innerHTML += `<option value="${b.id_bahia}" ${bloqueada}>${icono} ${b.descripcion}</option>`; 
            });

            const selPrecio = document.getElementById("id_precio");
            selPrecio.innerHTML = '<option value="">Seleccione tarifa...</option>';
            if(data.data.precios) {
                data.data.precios.forEach(p => { 
                    selPrecio.innerHTML += `<option value="${p.id_precio}">Tarifa: RD$ ${p.monto}</option>`; 
                });
            }

            cacheMecanicos = data.data.mecanicos;
            cacheMaquinaria = data.data.maquinaria;
        }
    })
    .catch(err => console.error("Error cargarDependencias:", err));
}

function cargarServiciosPorOrden(id_orden, id_servicio_preseleccionado = null, id_asig_edit = 0) {
    const selServicio = document.getElementById("id_tipo_servicio");
    if(!id_orden) { 
        selServicio.innerHTML = '<option value="" data-precio="">Seleccione primero una orden válida...</option>';
        selServicio.disabled = true; 
        document.getElementById('lbl_precio_sugerido').innerHTML = `Sugerido por servicio: <span class="fw-bold text-muted">RD$ 0.00</span>`;
        return; 
    }

    selServicio.innerHTML = '<option value="" data-precio="">Cargando servicios disponibles...</option>';
    selServicio.disabled = true;

    fetch(`../../modules/Taller/Archivo_Tiempos.php?action=cargar_servicios_orden&id_orden=${id_orden}&id_asig=${id_asig_edit}`)
    .then(res => res.json())
    .then(data => {
        selServicio.innerHTML = '';
        if (data.success && data.data.length > 0) {
            selServicio.innerHTML = '<option value="" data-precio="">Seleccione servicio de la lista...</option>';
            data.data.forEach(s => {
                selServicio.innerHTML += `<option value="${s.id_tipo_servicio}" data-precio="${s.precio}">${s.nombre_servicio}</option>`;
            });
            selServicio.disabled = false;
            
            if(id_servicio_preseleccionado) {
                selServicio.value = id_servicio_preseleccionado;
                selServicio.dispatchEvent(new Event('change'));
            }
        } else {
            selServicio.innerHTML = '<option value="" data-precio="">Todos los servicios ya fueron asignados</option>';
            selServicio.disabled = true;
            document.getElementById('lbl_precio_sugerido').innerHTML = `Sugerido por servicio: <span class="fw-bold text-muted">N/A</span>`;
        }
    })
    .catch(err => console.error("Error servicios orden:", err));
}

// ==========================================
// LISTAS DINÁMICAS (Mecánicos y Maquinaria)
// ==========================================

function agregarMecanicoLista() {
    const id = document.getElementById('id_empleado_temp').value;
    const nombre = document.getElementById('txt_buscar_empleado').value;
    if (!id || !nombre) { Swal.fire('Atención', 'Seleccione un mecánico de la lista.', 'warning'); return; }
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
    div.className = "badge bg-primary me-2 mb-2 p-2 fs-6 shadow-sm";
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

function agregarMaquinariaLista() {
    const id = document.getElementById('id_maquinaria_temp').value;
    const nombre = document.getElementById('txt_buscar_maquinaria').value;
    if (!id || !nombre) { Swal.fire('Atención', 'Seleccione una maquinaria de la lista.', 'warning'); return; }
    agregarMaquinariaVisual(id, nombre);
    document.getElementById('id_maquinaria_temp').value = '';
    document.getElementById('txt_buscar_maquinaria').value = '';
}

function agregarMaquinariaVisual(id, nombre) {
    const idStr = String(id);
    if (maquinariaSeleccionada.includes(idStr)) return;
    maquinariaSeleccionada.push(idStr);
    document.getElementById('msg_sin_maquinaria').style.display = 'none';
    
    const div = document.createElement('span');
    div.className = "badge bg-info text-dark border border-secondary shadow-sm me-2 mb-2 p-2 fs-6";
    div.id = `badge_maq_${idStr}`;
    div.innerHTML = `${nombre} <i class="fas fa-times ms-2 text-danger" style="cursor:pointer" onclick="removerMaquinaria('${idStr}')"></i>`;
    document.getElementById('contenedor_maquinaria').appendChild(div);
}

function removerMaquinaria(id) {
    const idStr = String(id);
    maquinariaSeleccionada = maquinariaSeleccionada.filter(i => i !== idStr);
    document.getElementById(`badge_maq_${idStr}`).remove();
    if(maquinariaSeleccionada.length === 0) document.getElementById('msg_sin_maquinaria').style.display = 'block';
}

function iniciarTrabajo(id) {
    Swal.fire({ 
        title: '¿Iniciar este trabajo?', 
        text: 'Se ocupará la bahía y maquinaria asignada.', 
        icon: 'question', 
        showCancelButton: true, 
        confirmButtonText: 'Sí, empezar ahora',
        cancelButtonText: 'Cancelar'
    }).then(r => {
        if(r.isConfirmed) {
            const f = new FormData(); 
            f.append("id_asignacion", id);
            fetch("../../modules/Taller/Archivo_Tiempos.php?action=iniciar_tiempo", { method: "POST", body: f })
            .then(res => res.json())
            .then(data => {
                if(data.success) { 
                    listar(); 
                    cargarDependencias(); 
                } else { 
                    Swal.fire('Acceso Denegado', data.message, 'error'); 
                } 
            });
        }
    });
}

function eliminarAsignacion(id) {
    Swal.fire({ 
        title: '¿Eliminar asignación?', 
        text: "Esta acción liberará la Bahía y la Maquinaria asociada si es el único trabajo pendiente.", 
        icon: 'warning', 
        showCancelButton: true, 
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then(r => {
        if(r.isConfirmed) {
            const fd = new FormData(); 
            fd.append("id_asignacion", id);
            fetch("../../modules/Taller/Archivo_Tiempos.php?action=eliminar_asignacion", { method: "POST", body: fd })
            .then(res => res.json())
            .then(data => { 
                if(data.success) { 
                    listar(); 
                    cargarDependencias(); 
                    Swal.fire('Eliminado', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// ==========================================
// GESTIÓN DE MODALES (UI) Y EDICIÓN
// ==========================================

function nuevaAsignacion() {
    const titulo = document.getElementById("tituloModalAsignacion");
    const btnGuardar = document.getElementById("btnGuardarAsig");
    
    if(titulo) titulo.innerHTML = "Nueva Asignación Detallada";
    if(btnGuardar) btnGuardar.innerHTML = '<i class="fas fa-save me-2"></i>Crear Asignación';
    
    document.getElementById("formAsignacion").reset();
    document.getElementById("id_asignacion").value = "";
    
    document.getElementById("id_orden").value = "";
    document.getElementById("txt_buscar_orden").value = "";
    document.getElementById("txt_buscar_orden").disabled = false;
    document.getElementById('info_orden_seleccionada').classList.add('d-none');
    document.getElementById('lbl_orden_cliente').innerText = "---";
    document.getElementById('lbl_orden_vehiculo').innerText = "---";
    
    const lblPrecio = document.getElementById('lbl_precio_sugerido');
    if(lblPrecio) lblPrecio.innerHTML = `Sugerido por servicio: <span class="fw-bold text-muted">RD$ 0.00</span>`;
    
    mecanicosSeleccionados = [];
    document.getElementById('contenedor_mecanicos').innerHTML = '<p class="text-muted small m-0" id="msg_sin_mecanicos">No hay mecánicos asignados.</p>';
    
    maquinariaSeleccionada = [];
    document.getElementById('contenedor_maquinaria').innerHTML = '<p class="text-muted small m-0" id="msg_sin_maquinaria">Ninguna asignada.</p>';
    
    const selServicio = document.getElementById("id_tipo_servicio");
    selServicio.innerHTML = '<option value="" data-precio="">Seleccione primero una orden válida...</option>';
    selServicio.disabled = true;

    // AUTOCOMPLETADO FECHA Y HORA ACTUAL
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    document.getElementById("fecha_asignacion").value = `${year}-${month}-${day}`;

    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById("hora_asignacion").value = `${hours}:${minutes}`;
    
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
            document.getElementById("txt_buscar_orden").value = `ORD-${d.id_orden} (Bloqueado por Edición)`;
            document.getElementById("txt_buscar_orden").disabled = true; 
            
            const ordCache = cacheOrdenes.find(o => o.id_orden == d.id_orden);
            if(ordCache) {
                document.getElementById('info_orden_seleccionada').classList.remove('d-none');
                document.getElementById('lbl_orden_cliente').innerText = ordCache.cliente;
                document.getElementById('lbl_orden_vehiculo').innerText = ordCache.vehiculo;
            }

            cargarServiciosPorOrden(d.id_orden, d.id_tipo_servicio, d.id_asignacion);
            
            document.getElementById("id_bahia").value = d.id_bahia || '';
            document.getElementById("id_precio").value = d.id_precio || ''; 
            document.getElementById("fecha_asignacion").value = d.fecha_asignacion;
            document.getElementById("hora_asignacion").value = d.hora_asignacion.substring(0, 5);
            
            mecanicosSeleccionados = [];
            document.getElementById('contenedor_mecanicos').innerHTML = '<p class="text-muted small m-0" id="msg_sin_mecanicos" style="display:none;">No hay mecánicos asignados.</p>';
            d.mecanicos.forEach(idEmp => {
                const mec = cacheMecanicos.find(m => m.id_empleado == idEmp);
                if(mec) agregarMecanicoVisual(mec.id_empleado, mec.nombre_completo);
            });

            maquinariaSeleccionada = [];
            document.getElementById('contenedor_maquinaria').innerHTML = '<p class="text-muted small m-0" id="msg_sin_maquinaria" style="display:none;">Ninguna asignada.</p>';
            if(d.maquinarias) {
                d.maquinarias.forEach(idMaq => {
                    const maq = cacheMaquinaria.find(m => m.id_maquinaria == idMaq);
                    if(maq) agregarMaquinariaVisual(maq.id_maquinaria, maq.nombre);
                });
            }

            abrirModalUI('modalAsignacion');
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => console.error("Error en editarAsignacion:", err));
}

function abrirModalTiempos(id, inicio) {
    document.getElementById("id_asignacion_tiempo").value = id;
    document.getElementById("lbl_hora_inicio").innerText = inicio;
    document.getElementById("lbl_hora_fin").innerText = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    const cb = document.getElementById("forzar_calidad");
    if(cb) cb.checked = false;
    
    abrirModalUI('modalTiempos');
}

function cerrarModalAsignacion() { cerrarModalUI('modalAsignacion'); }
function cerrarModalTiempos() { cerrarModalUI('modalTiempos'); }

// Funciones Robusetas Originales para abrir Modales
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