// --- VARIABLES GLOBALES ---
let itemParaUbicar = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarPendientesRecepcion();
    inicializarBuscadorSucursal();
});

//---------------------------------------------------------
// 📊 1. CARGAR TABLA PRINCIPAL (PENDIENTES)
//---------------------------------------------------------
function cargarPendientesRecepcion() {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_MovimientoS.php?action=listar_pendientes_recepcion')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById('tbody_pendientes');
        tbody.innerHTML = '';

        if(!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay productos pendientes de ubicar en esta sucursal.</td></tr>';
            return;
        }

        res.data.forEach(i => {
            tbody.innerHTML += `
                <tr>
                    <td class="ps-4">
                        <span class="badge bg-light text-primary border border-primary-subtle">
                            <i class="fas fa-file-invoice me-1"></i> ${i.num_conduze}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${i.imagen || '/Taller/Taller-Mecanica/img/default.png'}" 
                                 class="rounded border me-2" style="width:40px; height:40px; object-fit:cover;">
                            <div>
                                <span class="fw-bold d-block">${i.nombre}</span>
                                <small class="text-muted">ID: ${i.id_articulo}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 px-3">
                            ${i.cantidad_recibida}
                        </span>
                    </td>
                    <td><i class="fas fa-warehouse me-1 text-secondary"></i> ${i.almacen_nombre}</td>
                    <td class="text-center">
                        <button class="btn btn-primary btn-sm px-3 shadow-sm fw-bold" 
                                onclick='abrirModalUbicar(${JSON.stringify(i)})'>
                            <i class="fas fa-map-marker-alt me-1"></i> Ubicar
                        </button>
                    </td>
                </tr>`;
        });
    })
    .catch(err => console.error("Error al cargar pendientes:", err));
}

//---------------------------------------------------------
// 🔍 2. LÓGICA DEL BUSCADOR DINÁMICO DE SUCURSAL
//---------------------------------------------------------
function inicializarBuscadorSucursal() {
    const inputBusqueda = document.getElementById('buscar_sucursal');
    const listaResultados = document.getElementById('res_sucursales');

    if(!inputBusqueda) return;

    inputBusqueda.addEventListener('input', function() {
        const term = this.value.trim();

        if (term.length < 2) {
            listaResultados.classList.add('d-none');
            return;
        }

        fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_MovimientoS.php?action=buscar_sucursal&term=${term}`)
        .then(res => res.json())
        .then(data => {
            listaResultados.innerHTML = '';
            if (data.length > 0) {
                listaResultados.classList.remove('d-none');
                data.forEach(suc => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action small py-2 cursor-pointer';
                    li.innerHTML = `<i class="fas fa-store me-2 text-muted"></i>${suc.nombre}`;
                    li.onclick = () => seleccionarSucursal(suc.id_sucursal, suc.nombre);
                    listaResultados.appendChild(li);
                });
            } else {
                listaResultados.classList.add('d-none');
            }
        });
    });
}

function seleccionarSucursal(id, nombre) {
    document.getElementById('id_sucursal_dest').value = id;
    document.getElementById('lbl_sucursal_nombre').textContent = nombre;
    document.getElementById('sucursal_seleccionada').classList.remove('d-none');
    document.getElementById('buscar_sucursal').classList.add('d-none');
    document.getElementById('res_sucursales').classList.add('d-none');
    
    // Notificación tipo Toast rápida
    Swal.fire({
        icon: 'success',
        title: `Sucursal ${nombre} seleccionada`,
        toast: true,
        position: 'top-end',
        timer: 2000,
        showConfirmButton: false
    });

    const selAlmacen = document.getElementById('m_almacen');
    selAlmacen.disabled = false;
    cargarAlmacenesDestino(id);
}

function deseleccionarSucursal() {
    document.getElementById('id_sucursal_dest').value = '';
    document.getElementById('sucursal_seleccionada').classList.add('d-none');
    
    const input = document.getElementById('buscar_sucursal');
    input.classList.remove('d-none');
    input.value = '';
    input.focus();
    
    // Resetear y deshabilitar cascada
    const selAlm = document.getElementById('m_almacen');
    selAlm.innerHTML = '<option value="">Seleccione Almacén...</option>';
    selAlm.disabled = true;

    const selGon = document.getElementById('m_gondola');
    selGon.innerHTML = '<option value="">Góndola...</option>';
    selGon.disabled = true;

    document.getElementById('m_nivel').innerHTML = '<option value="">--</option>';
}

//---------------------------------------------------------
// 🪜 3. CARGA EN CASCADA (ALMACÉN -> GÓNDOLA -> NIVEL)
//---------------------------------------------------------
function cargarAlmacenesDestino(idSucursal) {
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_MovimientoS.php?action=get_almacenes_por_sucursal&id_sucursal=${idSucursal}`)
    .then(res => res.json())
    .then(res => {
        const sel = document.getElementById('m_almacen');
        sel.innerHTML = '<option value="">Seleccione Almacén...</option>';
        res.data.forEach(a => {
            sel.innerHTML += `<option value="${a.id_almacen}">${a.nombre}</option>`;
        });
    });
}

function cargarGondolasDestino(idAlmacen) {
    const selGon = document.getElementById('m_gondola');
    
    if(!idAlmacen) {
        selGon.innerHTML = '<option value="">Góndola...</option>';
        selGon.disabled = true;
        return;
    }

    // Cambiamos a habilitado antes de cargar
    selGon.disabled = false;

    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_MovimientoS.php?action=get_gondolas_almacen&id_almacen=${idAlmacen}`)
    .then(res => res.json())
    .then(res => {
        selGon.innerHTML = '<option value="">Seleccione Góndola...</option>';
        
        if(res.data && res.data.length > 0) {
            res.data.forEach(g => {
                // Importante: El alias 'niveles' debe venir del PHP
                selGon.innerHTML += `<option value="${g.id_gondola}" data-niveles="${g.niveles}">Góndola #${g.numero}</option>`;
            });
        } else {
            selGon.innerHTML = '<option value="">Sin góndolas en este almacén</option>';
        }
    })
    .catch(err => console.error("Error al cargar góndolas:", err));
}

function actualizarNiveles(select) {
    const niveles = select.options[select.selectedIndex].dataset.niveles || 0;
    const selNivel = document.getElementById('m_nivel');
    selNivel.innerHTML = '';
    if(niveles == 0) {
        selNivel.innerHTML = '<option value="">--</option>';
        return;
    }
    for(let i = 1; i <= niveles; i++) {
        selNivel.innerHTML += `<option value="${i}">${i}</option>`;
    }
}

//---------------------------------------------------------
// 📑 4. MODAL Y PROCESAMIENTO
//---------------------------------------------------------
function abrirModalUbicar(item) {
    itemParaUbicar = item;
    
    // Llenar info del producto
    document.getElementById('m_nombre').innerText = item.nombre;
    document.getElementById('m_img').src = item.imagen || '/Taller/Taller-Mecanica/img/default.png';
    document.getElementById('m_max').innerText = item.cantidad_recibida;
    document.getElementById('m_cant').value = item.cantidad_recibida;
    
    // Resetear el buscador de sucursal
    deseleccionarSucursal();

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalUbicar'));
    modal.show();
}

// Al confirmar la ubicación, incluimos el id_compra en el envío
function confirmarUbicacion() {
    const cant = parseInt(document.getElementById('m_cant').value);
    const idGondola = document.getElementById('m_gondola').value;
    const nivel = document.getElementById('m_nivel').value;
    const idSucursalDestino = document.getElementById('id_sucursal_dest').value;

    // Validaciones con SweetAlert2 sobre el modal
    if (!idSucursalDestino) {
        Swal.fire({ title: 'Atención', text: "Debe buscar y seleccionar una sucursal de destino.", icon: 'warning', target: document.getElementById('modalUbicar') });
        return;
    }

    if (!idGondola || !nivel) {
        Swal.fire({ title: 'Ubicación Incompleta', text: "Seleccione la góndola y el nivel del tramo.", icon: 'warning', target: document.getElementById('modalUbicar') });
        return;
    }

    if (isNaN(cant) || cant <= 0 || cant > itemParaUbicar.cantidad_recibida) {
        Swal.fire({ title: 'Cantidad Inválida', text: `Ingrese un número entre 1 y ${itemParaUbicar.cantidad_recibida}.`, icon: 'error', target: document.getElementById('modalUbicar') });
        return;
    }

    // Indicador de carga
    Swal.fire({
        title: 'Procesando...',
        text: 'Actualizando ubicación en el inventario',
        target: document.getElementById('modalUbicar'),
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const payload = {
        id_articulo: itemParaUbicar.id_articulo,
        id_compra: itemParaUbicar.id_compra, 
        cantidad: cant,
        id_gondola: idGondola,
        nivel: nivel
    };

    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_MovimientoS.php?action=procesar_ubicacion', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        Swal.close();
        if (res.success) {
            // Cerramos modal de Bootstrap
            const modalElement = document.getElementById('modalUbicar');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) modalInstance.hide();

            Swal.fire({
                title: '¡Ubicado!',
                text: "El stock ha sido asignado a su nueva posición física.",
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                cargarPendientesRecepcion(); // Recargar tabla
            });
        } else {
            Swal.fire({ title: 'Error', text: res.message, icon: 'error', target: document.getElementById('modalUbicar') });
        }
    })
    .catch(err => {
        Swal.close();
        Swal.fire({ title: 'Error de Red', text: 'No se pudo conectar con el servidor.', icon: 'error', target: document.getElementById('modalUbicar') });
    });
}