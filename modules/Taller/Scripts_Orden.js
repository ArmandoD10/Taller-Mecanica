document.addEventListener('DOMContentLoaded', () => {
    actualizarTablaInspecciones();
    cargarMonitorTaller(); 
   // inicializarBuscadoresEspecializados(); 
});

// Variables globales para el "Carrito" de la Orden
let serviciosAgregados = [];
let repuestosAgregados = [];
let idInspeccionSeleccionada = 0;
let inventarioInspecciones = [];

/**
 * 1. CARGA DE INSPECCIONES PENDIENTES
 */


function agregarServicioDesdeSelect(select) {
    const option = select.options[select.selectedIndex];
    if (!option.value) return;

    const precio = parseFloat(option.getAttribute('data-precio')) || 0;
    
    serviciosAgregados.push({
        id_tipo: option.value,
        nombre: option.text,
        cant: 1,
        precio: precio
    });

    select.value = ""; 
    renderizarListasOrden();
}
/**
 * Carga las inspecciones finalizadas desde el servidor y las muestra en la tabla.
 */
function actualizarTablaInspecciones() {
    const busqueda = document.getElementById('filtro_inspeccion').value.toLowerCase();
    const tbody = document.getElementById('tbody_inspecciones');
    const cnt = document.getElementById('cnt_pendientes');
    
    if(!tbody) return;

    // Usamos la ruta absoluta para evitar errores de 404
    fetch('/Taller/Taller-Mecanica/modules/Taller/Archivo_Orden.php?action=listar_inspecciones')
    .then(res => res.text()) // Leemos como texto para capturar errores de PHP
    .then(text => {
        try {
            const res = JSON.parse(text);
            
            if (!res.success) {
                console.error("Error en el servidor:", res.message);
                return;
            }

            inventarioInspecciones = res.data;
            // 1. Filtrado dinámico (protegido contra valores nulos)
            const filtrados = res.data.filter(i => 
                (i.placa || '').toLowerCase().includes(busqueda) || 
                (i.cliente_nombre || '').toLowerCase().includes(busqueda) ||
                (i.id_inspeccion || '').toString().includes(busqueda)
            );

            // 2. ACTUALIZAR EL RECUADRO AMARILLO
            if(cnt) {
                cnt.textContent = `${filtrados.length} Inspecciones`;
            }

            // 3. Limpiar y llenar la tabla
            tbody.innerHTML = '';

            if (filtrados.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted small">No hay inspecciones pendientes en esta sucursal.</td></tr>';
                return;
            }

            filtrados.forEach(ins => {
                // Validación para evitar el error de 'undefined' en marca/modelo
                const marca = ins.marca || 'S/M';
                const modelo = ins.modelo || 'S/M';
                const placa = ins.placa || 'SIN PLACA';
                const cliente = ins.cliente_nombre || 'Cliente no reg.';
                const criticos = parseInt(ins.hallazgos_criticos || 0);
                
                // Color del badge de hallazgos
                const badgeColor = criticos > 0 ? 'bg-danger' : 'bg-warning text-dark';

                tbody.innerHTML += `
                    <tr>
                        <td class="ps-4 fw-bold text-primary">#${ins.id_inspeccion}</td>
                        <td>
                            <div class="fw-bold text-dark">${marca} ${modelo}</div>
                            <small class="badge bg-dark" style="font-size:0.7rem">${placa}</small>
                        </td>
                        <td>${cliente}</td>
                        <td><span class="badge bg-light text-dark border small">General</span></td>
                        <td class="text-center">
                            <span class="badge ${badgeColor}">${criticos} Críticos</span>
                        </td>
                        <td class="small text-muted">${ins.fecha_inspeccion}</td>
                        <td class="text-center">
                            <button class="btn btn-success btn-sm fw-bold px-3 shadow-sm" 
                                    onclick="prepararNuevaOrden(${ins.id_inspeccion})">
                                <i class="fas fa-file-signature me-1"></i> Crear Orden
                            </button>
                        </td>
                    </tr>`;
            });

        } catch (e) {
            // Si el PHP manda un error de SQL, lo verás aquí detallado
            console.error("Error al procesar JSON. El servidor respondió:", text);
        }
    })
    .catch(err => console.error("Error en la petición:", err));
}

/**
 * 2. PREPARACIÓN DEL MODAL DE ORDEN
 */
function prepararNuevaOrden(idInspeccion) {
    idInspeccionSeleccionada = idInspeccion;
    
    // Primero buscamos en la tabla local la info que ya tenemos para no esperar al servidor
    const inspeccionLocal = inventarioInspecciones.find(i => i.id_inspeccion == idInspeccion);
    if(inspeccionLocal) {
        document.getElementById('modal_cliente_nombre').textContent = inspeccionLocal.cliente_nombre;
    }

    // Luego pedimos los hallazgos detallados al servidor
    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Orden.php?action=obtener_detalle_inspeccion&id=${idInspeccion}`)
    .then(res => res.json())
    .then(res => {
        const listaH = document.getElementById('lista_hallazgos_sugeridos');
        listaH.innerHTML = '';
        
        if(res.hallazgos && res.hallazgos.length > 0) {
            res.hallazgos.forEach(h => {
                const color = h.estado === 'D' ? 'text-danger' : 'text-warning';
                listaH.innerHTML += `<li class="list-group-item small"><i class="fas fa-exclamation-triangle ${color} me-2"></i> ${h.elemento} (${h.categoria})</li>`;
            });
        } else {
            listaH.innerHTML = '<li class="list-group-item small text-muted text-center">Sin hallazgos críticos</li>';
        }

        const myModal = new bootstrap.Modal(document.getElementById('modalCrearOrden'));
        myModal.show();
    });
}

/**
 * 3. LÓGICA DEL "CARRITO" DE LA ORDEN
 */
function agregarServicioA_Orden(id, nombre, precio) {
    // Evitar duplicados si lo deseas, o permitir varios del mismo
    serviciosAgregados.push({ id_tipo: id, nombre: nombre, cant: 1, precio: parseFloat(precio) });
    renderizarListasOrden();
}

function agregarRepuestoA_Orden(id, nombre, precio) {
    repuestosAgregados.push({ id_art: id, nombre: nombre, cant: 1, precio: parseFloat(precio) });
    renderizarListasOrden();
}

function eliminarItem(tipo, index) {
    if(tipo === 'servicio') serviciosAgregados.splice(index, 1);
    else repuestosAgregados.splice(index, 1);
    renderizarListasOrden();
}

function renderizarListasOrden() {
    const listaServ = document.getElementById('lista_servicios_orden');
    const listaRep = document.getElementById('lista_repuestos_orden');
    let total = 0;

    listaServ.innerHTML = '';
    listaRep.innerHTML = '';

    // --- RENDER SERVICIOS ---
    serviciosAgregados.forEach((s, i) => { // 's' para servicio
        const precio = parseFloat(s.precio) || 0;
        const cant = parseInt(s.cant) || 1;
        const sub = cant * precio;
        total += sub;

        listaServ.innerHTML += `
            <tr>
                <td class="small">${s.nombre}</td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm text-center" 
                           style="width:60px; margin:auto;" value="${cant}" 
                           onchange="actualizarCant('serv', ${i}, this.value)">
                </td>
                <td class="text-end">$${precio.toFixed(2)}</td>
                <td class="text-end fw-bold">$${sub.toFixed(2)}</td>
                <td class="text-center"><button class="btn btn-link text-danger p-0" onclick="eliminarItem('servicio', ${i})"><i class="fas fa-times"></i></button></td>
            </tr>`;
    });

    // --- RENDER REPUESTOS (Aquí estaba el error) ---
    repuestosAgregados.forEach((r, i) => { // ASEGÚRATE de que aquí diga 'r'
        const precio = parseFloat(r.precio) || 0;
        const cant = parseInt(r.cant) || 1;
        const sub = cant * precio;
        total += sub;

        listaRep.innerHTML += `
            <tr>
                <td class="small">${r.nombre}</td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm text-center" 
                           style="width:60px; margin:auto;" value="${cant}" 
                           onchange="actualizarCant('rep', ${i}, this.value)">
                </td>
                <td class="text-end">$${precio.toFixed(2)}</td>
                <td class="text-end fw-bold">$${sub.toFixed(2)}</td>
                <td class="text-center"><button class="btn btn-link text-danger p-0" onclick="eliminarItem('repuesto', ${i})"><i class="fas fa-times"></i></button></td>
            </tr>`;
    });

    document.getElementById('txt_total_orden').textContent = `$${total.toFixed(2)}`;
    window.montoTotalCalculado = total;
}

/**
 * 4. GUARDADO FINAL
 */
function guardarOrdenServicio() {
    if(serviciosAgregados.length === 0) {
        alert("⚠️ Debe agregar al menos un servicio.");
        return;
    }

    const payload = {
        id_inspeccion: idInspeccionSeleccionada,
        descripcion: document.getElementById('obs_orden').value,
        monto_total: window.montoTotalCalculado,
        servicios: serviciosAgregados,
        repuestos: repuestosAgregados
    };

    fetch('/Taller/Taller-Mecanica/modules/Taller/Archivo_Orden.php?action=guardar_orden_maestra', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            alert("✅ Orden de Servicio #" + res.id_orden + " creada correctamente.");
            location.reload();
        } else {
            alert("❌ Error: " + res.message);
        }
    });
}

// 1. Cargar el nombre del cliente y datos del vehículo al abrir
function prepararNuevaOrden(idInspeccion) {
    idInspeccionSeleccionada = idInspeccion;
    const dataRow = inventarioInspecciones.find(i => i.id_inspeccion == idInspeccion);
    
    if(dataRow) {
        document.getElementById('modal_cliente_nombre').textContent = dataRow.cliente_nombre;
        document.getElementById('modal_vehiculo_placa').textContent = dataRow.placa;
        document.getElementById('modal_vehiculo_modelo').textContent = `${dataRow.marca} ${dataRow.modelo}`;
        document.getElementById('modal_vehiculo_color').textContent = dataRow.color_nombre || 'No especificado';
    }

    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Orden.php?action=obtener_detalle_inspeccion&id=${idInspeccion}`)
    .then(res => res.json())
    .then(res => {
        const listaH = document.getElementById('lista_hallazgos_sugeridos');
        listaH.innerHTML = '';
        
        // Si el PHP devuelve elementos con estado 'D' (Dañado) o 'F' (Faltante)
        if(res.hallazgos && res.hallazgos.length > 0) {
            res.hallazgos.forEach(h => {
                const icono = h.estado === 'D' ? 'fa-times-circle text-danger' : 'fa-exclamation-triangle text-warning';
                listaH.innerHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center small">
                        <span><i class="fas ${icono} me-2"></i>${h.elemento}</span>
                        <span class="badge bg-light text-dark border">${h.categoria}</span>
                    </li>`;
            });
        } else {
            listaH.innerHTML = '<li class="list-group-item small text-muted text-center py-3">Vehículo sin novedades críticas</li>';
        }
        cargarCatalogoServicios();
        new bootstrap.Modal(document.getElementById('modalCrearOrden')).show();
    });
}

// 2. Validación de Stock al agregar
function agregarRepuestoA_Orden(art) {
    // art trae: id_articulo, nombre, precio, stock_sucursal
    const stockDisponible = parseInt(art.stock_sucursal);

    if (stockDisponible <= 0) {
        alert(`❌ No hay stock de "${art.nombre}" en esta sucursal. Debe solicitar una transferencia.`);
        return;
    }

    // Si ya está en el carrito, validamos que la nueva suma no supere el stock
    const existe = repuestosAgregados.find(r => r.id_art === art.id_articulo);
    if(existe && (existe.cant + 1) > stockDisponible) {
        alert("⚠️ Cantidad máxima alcanzada según stock disponible.");
        return;
    }

    repuestosAgregados.push({ 
        id_art: art.id_articulo, 
        nombre: art.nombre, 
        cant: 1, 
        precio: art.precio,
        stock_limite: stockDisponible 
    });
    renderizarListasOrden();
}

// En Scripts_Orden_Servicio.js
function seleccionarRepuestoBuscador(art) {
    const id = art.id_articulo;
    const nombre = art.nombre;
    const precio = parseFloat(art.precio_venta) || 0;
    const stock = parseInt(art.stock_sucursal) || 0;

    if (stock <= 0) {
        alert(`❌ No hay stock de "${nombre}"`);
        return;
    }

    // Guardamos el objeto con los mismos nombres que usa el renderizador
    repuestosAgregados.push({
        id_art: id,
        nombre: nombre,
        cant: 1,
        precio: precio,
        stock_limite: stock
    });

    document.getElementById('busqueda_repuesto').value = '';
    document.getElementById('res_repuestos').classList.add('d-none');
    
    renderizarListasOrden(); // Esta es la que fallaba porque no encontraba 'r'
}


// Cargar Servicios al iniciar
function cargarCatalogoServicios() {
    fetch('/Taller/Taller-Mecanica/modules/Taller/Archivo_Orden.php?action=listar_catalogo_servicios')
    .then(res => res.json())
    .then(res => {
        const sel = document.getElementById('select_servicio');
        // Limpiamos pero mantenemos la opción por defecto
        sel.innerHTML = '<option value="">Seleccione un servicio...</option>';
        res.data.forEach(s => {
            // Es vital que el atributo se llame data-precio
            sel.innerHTML += `<option value="${s.id_tipo_servicio}" data-precio="${s.precio_valor}">${s.nombre}</option>`;
        });
    });
}

// Buscador de Repuestos con Imagen y STOCK REAL
document.getElementById('busqueda_repuesto').addEventListener('input', function() {
    const term = this.value.trim();
    const lista = document.getElementById('res_repuestos');
    
    if(term.length < 2) { lista.classList.add('d-none'); return; }

    // Cambiamos la ruta a la acción que busca con STOCK
    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Orden.php?action=buscar_repuestos_stock&term=${term}`)
    .then(res => res.json())
    .then(response => {
        lista.innerHTML = '';
        if(response.success && response.data.length > 0) {
            lista.classList.remove('d-none');
            response.data.forEach(art => {
                const img = art.imagen ? art.imagen : '/Taller/Taller-Mecanica/img/default_part.png';
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action d-flex align-items-center cursor-pointer';
                li.innerHTML = `
                    <img src="${img}" class="rounded me-2" style="width:40px; height:40px; object-fit:cover;">
                    <div class="flex-grow-1">
                        <div class="fw-bold small">${art.nombre}</div>
                        <small class="text-muted">Stock: ${art.stock_sucursal} | </small>
                        <small class="text-primary fw-bold">$${parseFloat(art.precio_venta).toFixed(2)}</small>
                    </div>
                `;
                // Pasar el objeto completo 'art' para que agregarRepuestoA_Orden tenga el precio y stock
                li.onclick = () => {
                    seleccionarRepuestoBuscador(art); // Usamos la función de selección
                    lista.classList.add('d-none');
                    document.getElementById('busqueda_repuesto').value = '';
                };
                lista.appendChild(li);
            });
        }
    });
});

function actualizarCant(tipo, index, nuevaCant) {
    const cantidad = parseInt(nuevaCant);

    // Validación básica
    if (isNaN(cantidad) || cantidad <= 0) {
        alert("La cantidad debe ser al menos 1");
        renderizarListasOrden();
        return;
    }

    if (tipo === 'rep') {
        const item = repuestosAgregados[index];
        // Validación de Stock (usando el límite que guardamos al agregar)
        if (cantidad > item.stock_limite) {
            alert(`⚠️ Solo hay ${item.stock_limite} unidades en stock.`);
            renderizarListasOrden();
            return;
        }
        repuestosAgregados[index].cant = cantidad;
    } else {
        serviciosAgregados[index].cant = cantidad;
    }

    // ESTO ES LO QUE FALTA: Recalcular y actualizar la vista del total
    renderizarListasOrden();
}


// --- AQUÍ ESTÁ LA FUNCIÓN CORREGIDA DEL MONITOR ---
function cargarMonitorTaller() {
    fetch('/Taller/Taller-Mecanica/modules/Taller/Archivo_Orden.php?action=listar_monitor_taller')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById('tbody_ordenes_activas');
        if(!tbody || !res.success) return;

        tbody.innerHTML = '';
        
        if(res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted small">No hay vehículos trabajando actualmente.</td></tr>';
            return;
        }

        res.data.forEach(o => {
            // Calcular progreso real
            const total = parseInt(o.total_servicios) || 0;
            const listos = parseInt(o.servicios_listos) || 0;
            const porcentaje = total > 0 ? Math.round((listos / total) * 100) : 0;
            
            // Color de la barra según progreso
            let colorBarra = 'bg-info';
            if(porcentaje > 50) colorBarra = 'bg-primary';
            if(porcentaje === 100) colorBarra = 'bg-success';

            // Badge de estado dinámico
            let badgeEstado = `<span class="badge bg-light text-dark border small"><i class="fas fa-clock me-1"></i> ${o.nombre_proceso}</span>`;
            if(o.nombre_proceso === 'Reparación' || o.nombre_proceso === 'En Reparación') {
                badgeEstado = `<span class="badge bg-warning text-dark border-warning small"><i class="fas fa-wrench me-1"></i> ${o.nombre_proceso}</span>`;
            }

            tbody.innerHTML += `
                <tr>
                    <td class="ps-4 fw-bold text-success">ORD-${o.id_orden}</td>
                    <td>
                        <div class="fw-bold text-dark">${o.marca} ${o.modelo}</div>
                        <small class="text-muted">${o.cliente_nombre} | <b>${o.placa}</b></small>
                    </td>
                    <td>
                        <div class="small fw-bold text-secondary">
                            <i class="fas fa-user-cog me-1"></i> ${o.tecnico_principal || 'Pendiente Asignar'}
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress" style="height: 8px; width: 80px; flex-shrink: 0;">
                                <div class="progress-bar ${colorBarra} progress-bar-striped progress-bar-animated" style="width: ${porcentaje}%"></div>
                            </div>
                            <small class="fw-bold" style="font-size: 0.7rem;">${porcentaje}%</small>
                        </div>
                        <div class="mt-1">${badgeEstado}</div>
                    </td>
                    <td>
                        <span class="badge bg-soft-primary text-primary" title="Servicios">
                            <i class="fas fa-concierge-bell me-1"></i> ${listos}/${total}
                        </span>
                        <span class="badge bg-soft-warning text-warning" title="Repuestos">
                            <i class="fas fa-box-open me-1"></i> ${o.total_repuestos}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-outline-dark btn-sm shadow-sm" onclick="verDetalleOrden(${o.id_orden})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>`;
        });
    });
}

/**
 * Abre un modal o muestra la información detallada de una orden guardada
 */
function verDetalleOrden(idOrden) {
    document.getElementById('det_id_orden').textContent = `#${idOrden}`;
    
    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Orden.php?action=obtener_detalle_orden_completo&id=${idOrden}`)
    .then(res => res.json())
    .then(res => {
        if (!res.success) return;

        // --- SERVICIOS ---
        const contServ = document.getElementById('det_lista_servicios');
        contServ.innerHTML = '';
        res.servicios.forEach(s => {
            const pUnit = parseFloat(s.precio) || 0; // Usamos .precio
            const cant = parseInt(s.cantidad) || 0;
            const subtotal = pUnit * cant;

            contServ.innerHTML += `
                <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                    <div class="small fw-bold">${s.nombre}</div>
                    <div class="text-end">
                        <div class="small text-muted">${cant} x $${pUnit.toFixed(2)}</div>
                        <div class="fw-bold text-dark">$${subtotal.toFixed(2)}</div>
                    </div>
                </div>`;
        });

        // --- REPUESTOS ---
        const contRep = document.getElementById('det_lista_repuestos');
        contRep.innerHTML = '';
        res.repuestos.forEach(r => {
            const pUnit = parseFloat(r.precio) || 0; // Usamos .precio
            const cant = parseInt(r.cantidad) || 0;
            const subtotal = pUnit * cant;
            const img = r.imagen ? r.imagen : '/Taller/Taller-Mecanica/img/default_part.png';

            contRep.innerHTML += `
                <div class="list-group-item d-flex align-items-center p-2">
                    <img src="${img}" class="rounded me-2" style="width:40px; height:40px; object-fit:cover;">
                    <div class="flex-grow-1 small fw-bold">${r.nombre}</div>
                    <div class="text-end">
                        <div class="small text-muted">${cant} x $${pUnit.toFixed(2)}</div>
                        <div class="fw-bold text-dark">$${subtotal.toFixed(2)}</div>
                    </div>
                </div>`;
        });

        const totalFinal = parseFloat(res.total) || 0;
        document.getElementById('det_total_orden').textContent = `$${totalFinal.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        
        new bootstrap.Modal(document.getElementById('modalVerDetalleOrden')).show();
    });
}