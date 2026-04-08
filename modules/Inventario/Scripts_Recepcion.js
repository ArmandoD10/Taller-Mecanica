let cacheAlmacenes = [];
let compraActiva = null;

document.addEventListener('DOMContentLoaded', () => {
    // 1. Carga inicial de datos
    listarComprasPendientes();
    cargarAlmacenes();

    // 2. Filtro de búsqueda en la tabla principal
    const filtroTabla = document.getElementById('filtroTabla');
    if (filtroTabla) {
        filtroTabla.addEventListener('input', function() {
            const val = this.value.toLowerCase().trim();
            const filas = document.querySelectorAll('#tbody_compras_pendientes tr');
            
            filas.forEach(tr => {
                const contenido = tr.innerText.toLowerCase();
                tr.style.display = contenido.includes(val) ? '' : 'none';
            });
        });
    }

    // 3. BUSCADOR INTELIGENTE DE EMPLEADOS
    const inputEmp = document.getElementById('buscar_empleado');
    const listaEmp = document.getElementById('lista_empleados');
    const hiddenEmp = document.getElementById('id_empleado_seleccionado');

    if (inputEmp) {
        inputEmp.addEventListener('input', function() {
            const q = this.value.trim();
            
            if (q.length < 2) {
                listaEmp.classList.add('d-none');
                hiddenEmp.value = ''; // Limpiar ID si borra el texto
                return;
            }

            fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Recepcion.php?action=buscar_empleado&q=${q}`)
            .then(res => res.json())
            .then(data => {
                listaEmp.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(emp => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.classList.add('list-group-item', 'list-group-item-action', 'py-2');
                        btn.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <span>${emp.nombre}</span>
                                <small class="text-muted">#${emp.id_empleado}</small>
                            </div>`;
                        
                        btn.onclick = () => {
                            inputEmp.value = emp.nombre;
                            hiddenEmp.value = emp.id_empleado;
                            listaEmp.classList.add('d-none');
                            inputEmp.classList.remove('is-invalid');
                            inputEmp.classList.add('is-valid');
                        };
                        listaEmp.appendChild(btn);
                    });
                    listaEmp.classList.remove('d-none');
                } else {
                    listaEmp.classList.add('d-none');
                }
            })
            .catch(err => console.error("Error buscando empleados:", err));
        });

        // Cerrar lista si se hace clic fuera
        document.addEventListener('click', (e) => {
            if (!inputEmp.contains(e.target) && !listaEmp.contains(e.target)) {
                listaEmp.classList.add('d-none');
            }
        });
    }
});

// --- FUNCIONES DE CARGA ---

function listarComprasPendientes() {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Recepcion.php?action=buscar_compra')
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('tbody_compras_pendientes');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay compras pendientes de recepción.</td></tr>';
            return;
        }

        data.forEach(c => {
            tbody.innerHTML += `
                <tr>
                    <td class="ps-4 fw-bold text-primary">#${c.id_compra}</td>
                    <td><i class="far fa-calendar-alt me-1 text-muted"></i> ${c.fecha}</td>
                    <td><span class="fw-bold">${c.proveedor}</span></td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3">
                            ${c.total_items} Items
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="verDetalleModal(${c.id_compra})">
                                <i class="fas fa-eye me-1"></i> Detalle
                            </button>
                            <button class="btn btn-sm btn-success shadow-sm" onclick="seleccionarCompra(${c.id_compra})">
                                <i class="fas fa-arrow-right me-1"></i> Recibir
                            </button>
                        </div>
                    </td>
                </tr>`;
        });
    })
    .catch(err => console.error("Error al listar compras:", err));
}

function cargarAlmacenes() {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Recepcion.php?action=get_almacenes')
    .then(res => res.json())
    .then(res => { 
        if(res.success) cacheAlmacenes = res.data; 
    });
}

// --- MODAL DE VISTA PREVIA ---

function verDetalleModal(id) {
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Recepcion.php?action=obtener_detalle&id=${id}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const d = res.data;
            document.getElementById('md_proveedor').innerText = d.proveedor;
            document.getElementById('md_id_compra').innerText = `ID Orden de Compra: # ${d.id_compra}`;
            
            const tbody = document.getElementById('tbody_modal_detalle');
            tbody.innerHTML = '';
            
            let sumaTotal = 0; 

            d.articulos.forEach(art => {
                const img = art.imagen || '/Taller/Taller-Mecanica/img/default-part.webp';
                const subtotal = parseFloat(art.subtotal || 0);
                sumaTotal += subtotal;

                const subtotalFmt = subtotal.toLocaleString('en-US', {
                    style: 'currency', currency: 'USD'
                });

                tbody.innerHTML += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="${img}" class="rounded border me-3" style="width:50px; height:50px; object-fit:cover;">
                                <div>
                                    <div class="fw-bold text-dark">${art.nombre}</div>
                                    <small class="text-muted">ID: ${art.id_articulo}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center fw-bold">${art.cantidad_pedida}</td>
                        <td class="text-end">$${parseFloat(art.precio_compra).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                        <td class="text-end fw-bold text-success">${subtotalFmt}</td>
                    </tr>`;
            });

            document.getElementById('md_total').innerText = sumaTotal.toLocaleString('en-US', {
                style: 'currency', currency: 'USD'
            });

            document.getElementById('btn_iniciar_desde_modal').onclick = () => {
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalDetalleCompra'));
                if(modalInstance) modalInstance.hide();
                seleccionarCompra(id);
            };

            const myModal = new bootstrap.Modal(document.getElementById('modalDetalleCompra'));
            myModal.show();
        }
    });
}

// --- PROCESO DE RECEPCIÓN ---

function seleccionarCompra(id) {
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Recepcion.php?action=obtener_detalle&id=${id}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            compraActiva = res.data;
            
            const area = document.getElementById('area_recepcion');
            area.classList.remove('d-none');
            document.getElementById('lbl_detalle_compra').innerText = `Procesando Recepción de Orden #${id}`;
            document.getElementById('lbl_proveedor_recep').innerText = `Proveedor: ${res.data.proveedor}`;
            
            // Limpiar campos de empleado y conduce
            document.getElementById('num_conduze_recep').value = '';
            document.getElementById('buscar_empleado').value = '';
            document.getElementById('id_empleado_seleccionado').value = '';
            document.getElementById('buscar_empleado').classList.remove('is-valid', 'is-invalid');

            const tbody = document.getElementById('tbody_detalle_recepcion');
            tbody.innerHTML = '';

            res.data.articulos.forEach(art => {
                const faltante = art.cantidad_pedida - art.cantidad_recibida;
                
                if (faltante > 0) {
                    tbody.innerHTML += `
                        <tr data-id="${art.id_articulo}">
                            <td class="ps-4">
                                <div class="fw-bold">${art.nombre}</div>
                                <small class="text-muted">Recibido: ${art.cantidad_recibida} / ${art.cantidad_pedida}</small>
                            </td>
                            <td class="text-center font-monospace">${art.cantidad_pedida}</td>
                            <td class="text-center text-muted">${art.cantidad_recibida}</td>
                            <td class="text-center fw-bold text-primary fs-5">${faltante}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-center fw-bold border-primary input-recibir" 
                                       value="${faltante}" min="0" max="${faltante}">
                            </td>
                            <td>
                                <select class="form-select form-select-sm sel-almacen shadow-sm">
                                    ${cacheAlmacenes.map(a => `<option value="${a.id_almacen}">${a.nombre}</option>`).join('')}
                                </select>
                            </td>
                        </tr>`;
                }
            });

            area.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
}

function confirmarRecepcion() {
    const inputConduze = document.getElementById('num_conduze_recep');
    const numConduze = inputConduze.value.trim();
    const idEmpleado = document.getElementById('id_empleado_seleccionado').value;
    const inputEmpBusqueda = document.getElementById('buscar_empleado');
    
    // 1. Validaciones
    if (!idEmpleado) {
        alert("⚠️ Por favor, seleccione un empleado responsable de la lista de sugerencias.");
        inputEmpBusqueda.focus();
        inputEmpBusqueda.classList.add('is-invalid');
        return;
    }

    if (!numConduze) {
        alert("⚠️ Por favor, ingrese el número de conduce.");
        inputConduze.focus();
        return;
    }

    const items = [];
    document.querySelectorAll('#tbody_detalle_recepcion tr').forEach(tr => {
        const cant = parseInt(tr.querySelector('.input-recibir').value) || 0;
        if (cant > 0) {
            items.push({
                id_articulo: tr.getAttribute('data-id'),
                cantidad: cant,
                id_almacen: tr.querySelector('.sel-almacen').value
            });
        }
    });

    if (items.length === 0) {
        alert("Debe ingresar al menos una cantidad para recibir.");
        return;
    }

    if (!confirm("¿Desea confirmar la entrada de mercancía?")) return;

    // 2. Preparar Payload
    const payload = {
        id_compra: compraActiva.id_compra,
        num_conduze: numConduze,
        id_empleado: idEmpleado, // Nuevo campo enviado al backend
        items: items
    };

    // 3. Envío al servidor
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Recepcion.php?action=guardar_recepcion', {
        method: 'POST',
        body: JSON.stringify(payload),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert("✅ " + res.message);
            location.reload(); 
        } else {
            alert("❌ " + res.message);
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Ocurrió un error en la comunicación con el servidor.");
    });
}

function cerrarRecepcion() {
    if (confirm("¿Cerrar el formulario de recepción? Los datos no guardados se perderán.")) {
        document.getElementById('area_recepcion').classList.add('d-none');
        compraActiva = null;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}