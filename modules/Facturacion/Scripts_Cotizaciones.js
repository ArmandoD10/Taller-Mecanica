let cotizacionItems = [];
let cotizacionTotal = 0;

document.addEventListener("DOMContentLoaded", () => {
    listarPendientes();

    const searchInput = document.getElementById("buscador_items");
    if(searchInput) {
        searchInput.addEventListener("search", () => {
            document.getElementById("res_items").classList.add("d-none");
        });
    }

    // ==============================================================
    // VALIDACIONES Y MÁSCARAS EN TIEMPO REAL PARA CLIENTE OCASIONAL
    // ==============================================================
    
    const inputTelefono = document.getElementById('occ_telefono');
    if (inputTelefono) {
        inputTelefono.addEventListener('input', function(e) {
            let num = e.target.value.replace(/\D/g, '').substring(0, 10);
            let form = "";
            if (num.length > 0) {
                form += "(" + num.substring(0, 3);
                if (num.length > 3) form += ") " + num.substring(3, 6);
                if (num.length > 6) form += "-" + num.substring(6, 10);
            }
            e.target.value = form;
        });
    }


    const inputNombre = document.getElementById('occ_nombre');
    if (inputNombre) {
        inputNombre.addEventListener('input', function(e) {
            let valor = e.target.value;
            valor = valor.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            if (valor.length > 0) {
                valor = valor.charAt(0).toUpperCase() + valor.slice(1);
            }
            e.target.value = valor;
        });
    }

    const inputVehiculo = document.getElementById('occ_vehiculo');
    if (inputVehiculo) {
        inputVehiculo.addEventListener('input', function(e) {
            let valor = e.target.value;
            valor = valor.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
            if (valor.length > 0) {
                valor = valor.charAt(0).toUpperCase() + valor.slice(1);
            }
            e.target.value = valor;
        });
    }
});

function actualizar() {
    location.reload();
}

function filtrarCotizaciones() {
    const input = document.getElementById("buscar_cotizacion");
    if(!input) return;
    const texto = input.value.toLowerCase();
    const filas = document.querySelectorAll(".fila-cotizacion");
    
    filas.forEach(fila => {
        const contenido = fila.innerText.toLowerCase();
        fila.style.display = contenido.includes(texto) ? "" : "none";
    });
}

function listarPendientes() {
    fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=listar_pendientes")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaPendientes");
        tbody.innerHTML = "";
        if (data.success && data.data.length > 0) {
            data.data.forEach(o => {
                const tr = document.createElement("tr");
                tr.className = "fila-cotizacion"; 
                tr.style.cursor = "pointer";
                
                const badge = o.tipo_cotizacion === 'POS' ? '<span class="badge bg-success">POS</span>' : '<span class="badge bg-primary">Taller</span>';
                const warningOc = o.es_ocasional == 1 ? '<span class="badge bg-warning text-dark ms-1" title="Ocasional"><i class="fas fa-exclamation-triangle"></i></span>' : '';

                tr.innerHTML = `
                    <td class="fw-bold text-dark">COT-${o.id_cotizacion}</td>
                    <td>
                        <span class="d-block fw-bold text-dark small">${o.vehiculo}</span>
                        <span class="text-muted small"><i class="fas fa-user me-1"></i>${o.cliente} ${warningOc}</span>
                    </td>
                    <td class="text-center">${badge}</td>
                `;
                tr.onclick = () => cargarCotizacion(o.id_cotizacion, o.vehiculo, o.cliente, o.tipo_cotizacion, o.es_ocasional);
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center py-5 text-muted">No hay cotizaciones pendientes.</td></tr>`;
        }
    });
}

function limpiarConstructor() {
    cotizacionItems = [];
    cotizacionTotal = 0;
    document.getElementById("id_cotizacion_actual").value = "";
    document.getElementById("tipo_cotizacion_activa").value = "";
    document.getElementById("es_ocasional_activa").value = "";
    document.getElementById("lbl_orden_activa").innerText = "Seleccione una Cotización";
    
    const buscador = document.getElementById("buscador_items");
    if(buscador) buscador.value = "";
    const cantItem = document.getElementById("cantidad_item");
    if(cantItem) cantItem.value = 1;
    const resItems = document.getElementById("res_items");
    if(resItems) resItems.classList.add("d-none");

    document.getElementById("cuerpoDetalleCotizacion").innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Agregue servicios o repuestos al presupuesto.</td></tr>';
    document.getElementById('cot_subtotal').innerText = "RD$ 0.00";
    document.getElementById('cot_itbis').innerText = "RD$ 0.00";
    document.getElementById('cot_total').innerText = "RD$ 0.00";
    document.getElementById("panel_botones_accion").innerHTML = "";
    document.getElementById("capa_bloqueo").classList.remove("d-none");
}

function vaciarCarritoCotizacion() {
    if(cotizacionItems.length === 0) return; // Si ya está vacío, no hacemos nada

    if(confirm("¿Está seguro que desea vaciar todos los servicios y repuestos de este presupuesto?")) {
        cotizacionItems = [];
        renderizarTabla();
    }
}

function cargarCotizacion(id_cotizacion, vehiculo, cliente, tipo_cotizacion, es_ocasional) {
    limpiarConstructor(); 
    
    document.getElementById("id_cotizacion_actual").value = id_cotizacion;
    document.getElementById("tipo_cotizacion_activa").value = tipo_cotizacion;
    document.getElementById("es_ocasional_activa").value = es_ocasional;
    document.getElementById("lbl_orden_activa").innerText = `COT-${id_cotizacion} | ${cliente} [${tipo_cotizacion}]`;
    document.getElementById("capa_bloqueo").classList.add("d-none");

    let btnHtml = `<div class="col-md-3"><button class="btn btn-outline-dark w-100 fw-bold shadow-sm" onclick="guardarBorrador()"><i class="fas fa-save me-1"></i> Guardar</button></div>`;

    if (tipo_cotizacion === 'POS') {
        btnHtml += `<div class="col-md-5"><button class="btn btn-success w-100 fw-bold shadow-sm" onclick="abrirModalCobroPOS()"><i class="fas fa-cash-register me-1"></i> Facturar POS</button></div>`;
    } else {
        if (es_ocasional == 1) {
            btnHtml += `<div class="col-md-5"><button class="btn btn-secondary w-100 fw-bold shadow-sm" disabled title="Un cliente ocasional no puede generar una orden de taller."><i class="fas fa-ban me-1"></i> Taller Bloqueado</button></div>`;
        } else {
            btnHtml += `<div class="col-md-5"><button class="btn btn-primary w-100 fw-bold shadow-sm" onclick="aprobarCotizacion()"><i class="fas fa-tools me-1"></i> Aprobar a Taller</button></div>`;
        }
    }
    
    btnHtml += `<div class="col-md-4"><button class="btn btn-danger w-100 fw-bold shadow-sm" onclick="rechazarCotizacion()"><i class="fas fa-archive me-1"></i> Archivar / Anular</button></div>`;
    document.getElementById("panel_botones_accion").innerHTML = btnHtml;

    document.getElementById("cuerpoDetalleCotizacion").innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-2"></i>Cargando detalles...</td></tr>';

    fetch(`../../modules/Facturacion/Archivo_Cotizaciones.php?action=obtener_detalle&id_cotizacion=${id_cotizacion}`)
    .then(res => res.json())
    .then(data => {
        if(data.success && data.data.length > 0) {
            cotizacionItems = data.data.map(item => ({
                id: item.id,
                descripcion: item.descripcion,
                precio: parseFloat(item.precio),
                cantidad: parseInt(item.cantidad),
                tipo: item.tipo
            }));
        } else {
            cotizacionItems = [];
        }
        renderizarTabla();
    });
}

function buscarItems(input) {
    const term = input.value.trim();
    const resDiv = document.getElementById("res_items");
    const tipoCot = document.getElementById("tipo_cotizacion_activa").value;

    if (term.length < 2) { resDiv.classList.add("d-none"); return; }

    Promise.all([
        fetch(`../../modules/Facturacion/Archivo_Cotizaciones.php?action=buscar_servicios&term=${encodeURIComponent(term)}`).then(r => r.json()),
        fetch(`../../modules/Facturacion/Archivo_Cotizaciones.php?action=buscar_repuestos&term=${encodeURIComponent(term)}`).then(r => r.json())
    ])
    .then(([resServicios, resRepuestos]) => {
        resDiv.innerHTML = "";
        let found = false;

        if (tipoCot !== 'POS' && resServicios.success && resServicios.data.length > 0) {
            found = true;
            resServicios.data.forEach(s => {
                const btn = document.createElement("button");
                btn.className = "list-group-item list-group-item-action py-2";
                btn.innerHTML = `<div class="d-flex justify-content-between"><span><i class="fas fa-wrench text-secondary me-2"></i>${s.descripcion}</span> <span class="fw-bold">RD$ ${parseFloat(s.precio).toLocaleString()}</span></div>`;
                btn.onclick = (e) => { e.preventDefault(); agregarItem(s); };
                resDiv.appendChild(btn);
            });
        }

        if (resRepuestos.success && resRepuestos.data.length > 0) {
            found = true;
            resRepuestos.data.forEach(r => {
                const btn = document.createElement("button");
                btn.className = "list-group-item list-group-item-action py-2";
                btn.innerHTML = `<div class="d-flex justify-content-between"><span><i class="fas fa-cogs text-primary me-2"></i>${r.descripcion} <small class="text-muted">(Stock: ${r.stock})</small></span> <span class="fw-bold text-success">RD$ ${parseFloat(r.precio).toLocaleString()}</span></div>`;
                btn.onclick = (e) => { e.preventDefault(); agregarItem(r); };
                resDiv.appendChild(btn);
            });
        }

        if(found) resDiv.classList.remove("d-none");
        else resDiv.classList.add("d-none");
    });
}

function agregarItem(itemData) {
    const cant = parseInt(document.getElementById("cantidad_item").value) || 1;
    
    if(itemData.tipo === 'repuesto' && cant > parseInt(itemData.stock)) {
        return alert(`Solo hay ${itemData.stock} unidades en inventario.`);
    }

    const existe = cotizacionItems.find(i => i.id === itemData.id && i.tipo === itemData.tipo);
    if(existe) existe.cantidad += cant;
    else {
        cotizacionItems.push({
            id: itemData.id,
            descripcion: itemData.descripcion,
            precio: parseFloat(itemData.precio),
            cantidad: cant,
            tipo: itemData.tipo
        });
    }

    document.getElementById("buscador_items").value = "";
    document.getElementById("res_items").classList.add("d-none");
    document.getElementById("cantidad_item").value = 1;
    renderizarTabla();
}

function eliminarItem(index) {
    cotizacionItems.splice(index, 1);
    renderizarTabla();
}

function renderizarTabla() {
    const tbody = document.getElementById("cuerpoDetalleCotizacion");
    tbody.innerHTML = "";
    let subtotal = 0;

    if(cotizacionItems.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Agregue servicios o repuestos al presupuesto.</td></tr>`;
    } else {
        cotizacionItems.forEach((item, index) => {
            const lineaTotal = item.precio * item.cantidad;
            subtotal += lineaTotal;
            
            const badge = item.tipo === 'servicio' ? '<span class="badge bg-secondary">Mano Obra</span>' : '<span class="badge bg-success">Repuesto</span>';

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td class="small fw-bold text-dark">${item.descripcion}</td>
                <td class="text-center">${badge}</td>
                <td class="text-center">${item.cantidad}</td>
                <td class="text-end small">RD$ ${item.precio.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                <td class="text-end fw-bold small">RD$ ${lineaTotal.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                <td class="text-center">
                    <button class="btn btn-sm text-danger p-0" onclick="eliminarItem(${index})"><i class="fas fa-times-circle"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    const itbis = subtotal * 0.18;
    cotizacionTotal = subtotal + itbis;

    document.getElementById('cot_subtotal').innerText = `RD$ ${subtotal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    document.getElementById('cot_itbis').innerText = `RD$ ${itbis.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    document.getElementById('cot_total').innerText = `RD$ ${cotizacionTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
}

function abrirModalNuevaCotizacion() {
    document.getElementById("tipo_reg").checked = true;
    document.getElementById("tipo_taller").checked = true;
    toggleTipoClienteExpress();
    document.getElementById("buscador_vehiculo_express").value = "";
    document.getElementById("id_cliente_express").value = "";
    document.getElementById("id_vehiculo_express").value = "";
    document.getElementById("info_vehiculo_express").classList.add("d-none");
    document.getElementById("res_vehiculos_express").classList.add("d-none");
    
    document.getElementById("occ_nombre").value = "";
    document.getElementById("occ_telefono").value = "";
    document.getElementById("occ_vehiculo").value = "";
    
    abrirModalUI('modalNuevaCotizacion');
}

function cerrarModalNuevaCotizacion() { cerrarModalUI('modalNuevaCotizacion'); }

function toggleTipoClienteExpress() {
    const esReg = document.getElementById("tipo_reg").checked;
    if(esReg) {
        document.getElementById("seccion_registrado").classList.remove("d-none");
        document.getElementById("seccion_ocasional").classList.add("d-none");
    } else {
        document.getElementById("seccion_registrado").classList.add("d-none");
        document.getElementById("seccion_ocasional").classList.remove("d-none");
    }
}

function buscarVehiculosExpress(input) {
    const term = input.value.trim();
    const resDiv = document.getElementById("res_vehiculos_express");
    if (term.length < 2) { resDiv.classList.add("d-none"); return; }

    fetch(`../../modules/Facturacion/Archivo_Cotizaciones.php?action=buscar_vehiculos&term=${encodeURIComponent(term)}`)
    .then(res => res.json())
    .then(data => {
        resDiv.innerHTML = "";
        if (data.success && data.data.length > 0) {
            resDiv.classList.remove("d-none");
            data.data.forEach(v => {
                const li = document.createElement("li");
                li.className = "list-group-item list-group-item-action py-2";
                li.style.cursor = "pointer";
                li.innerHTML = `<strong>${v.vehiculo_desc}</strong><br><small class="text-muted"><i class="fas fa-user me-1"></i>${v.cliente}</small>`;
                li.onclick = () => {
                    document.getElementById("buscador_vehiculo_express").value = "";
                    document.getElementById("id_cliente_express").value = v.id_cliente;
                    document.getElementById("id_vehiculo_express").value = v.id_vehiculo;
                    document.getElementById("tel_cliente_express").value = v.telefono || '';
                    document.getElementById("lbl_exp_cliente").innerText = v.cliente;
                    document.getElementById("lbl_exp_vehiculo").innerText = v.vehiculo_desc;
                    document.getElementById("info_vehiculo_express").classList.remove("d-none");
                    resDiv.classList.add("d-none");
                };
                resDiv.appendChild(li);
            });
        } else resDiv.classList.add("d-none");
    });
}

function crearCotizacionExpress() {
    const tipoCli = document.getElementById("tipo_reg").checked ? 'registrado' : 'ocasional';
    const tipoCot = document.getElementById("tipo_taller").checked ? 'Taller' : 'POS';
    const fd = new FormData();
    fd.append("tipo_cliente", tipoCli);
    fd.append("tipo_cotizacion", tipoCot); 

    let clienteName = "", vehiculoDesc = "";

    if(tipoCli === 'registrado') {
        const id_vehiculo = document.getElementById("id_vehiculo_express").value;
        const id_cliente = document.getElementById("id_cliente_express").value;
        if(!id_vehiculo) return Swal.fire('Atención', "Debe seleccionar un vehículo de la lista.", 'warning');
        
        fd.append("id_vehiculo", id_vehiculo);
        fd.append("id_cliente", id_cliente);
        clienteName = document.getElementById("lbl_exp_cliente").innerText;
        vehiculoDesc = document.getElementById("lbl_exp_vehiculo").innerText;
        fd.append("nombre_cliente", clienteName);
        fd.append("vehiculo_desc", vehiculoDesc);
        fd.append("telefono_cliente", document.getElementById("tel_cliente_express").value);
    } else {
        const nom = document.getElementById("occ_nombre").value.trim();
        const tel = document.getElementById("occ_telefono").value.trim();
        const veh = document.getElementById("occ_vehiculo").value.trim();
        if(!nom) return Swal.fire('Campo Requerido', "El Nombre es obligatorio para un cliente ocasional.", 'warning');
        
        fd.append("nombre_ocasional", nom);
        fd.append("telefono_ocasional", tel);
        fd.append("vehiculo_ocasional", veh || 'Vehículo no especificado');
        clienteName = nom;
        vehiculoDesc = veh;
    }

    Swal.fire({
        title: 'Creando Cotización...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=crear_directa", { method: "POST", body: fd })
    .then(async res => {
        const text = await res.text(); 
        try { return JSON.parse(text); } catch (e) { throw new Error(text); }
    })
    .then(data => {
        Swal.close();
        if(data.success) {
            cerrarModalNuevaCotizacion();
            listarPendientes();
            cargarCotizacion(data.id_cotizacion, vehiculoDesc, clienteName, tipoCot, tipoCli === 'ocasional' ? 1 : 0);
        } else {
            Swal.fire('Error de Base de Datos', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.close();
        Swal.fire('Error Fatal', "Detalle: " + err.message.substring(0, 100), 'error');
    });
}

function guardarBorrador() {
    const id_cotizacion = document.getElementById("id_cotizacion_actual").value;
    if(!id_cotizacion) return;

    Swal.fire({
        title: 'Guardando presupuesto...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=guardar_cotizacion", {
        method: "POST", headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify({ id_cotizacion: id_cotizacion, items: cotizacionItems, total_final: cotizacionTotal })
    })
    .then(async r => {
        const text = await r.text();
        try { return JSON.parse(text); } catch(e) { throw new Error(text); }
    })
    .then(res => {
        if(res.success) { 
            Swal.fire({
                title: '¡Guardado!',
                text: "Cotización guardada exitosamente.",
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                limpiarConstructor(); 
                listarPendientes(); 
            });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error de Conexión', err.message, 'error');
    });
}

function aprobarCotizacion() {
    const id_cotizacion = document.getElementById("id_cotizacion_actual").value;
    if(!id_cotizacion || cotizacionItems.length === 0) return Swal.fire('Presupuesto Vacío', "No puede aprobar una cotización sin items.", 'warning');

    Swal.fire({
        title: '¿Aprobar Presupuesto?',
        text: "¿Desea pasar este vehículo al Taller para Diagnóstico?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1a73e8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, enviar a taller',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=guardar_cotizacion", {
                method: "POST", headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ id_cotizacion, items: cotizacionItems, total_final: cotizacionTotal })
            }).then(() => {
                const fd = new FormData(); fd.append("id_cotizacion", id_cotizacion);
                fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=aprobar_cotizacion", { method: "POST", body: fd })
                .then(res => res.json())
                .then(res => {
                    if(res.success) { 
                        Swal.fire('¡Aprobada!', res.message, 'success').then(() => {
                            limpiarConstructor(); 
                            window.location.href = `../Taller/MInspeccion.php?id_orden=${res.id_orden}`;
                        });
                    } else Swal.fire('Error', res.message, 'error');
                });
            });
        }
    });
}

// --- REDIRECCIÓN AL MÓDULO POS ---
function abrirModalCobroPOS() {
    const id_cotizacion = document.getElementById("id_cotizacion_actual").value;
    if(!id_cotizacion || cotizacionItems.length === 0) return alert("El presupuesto está vacío.");
    
    // Guardamos borrador en silencio antes de redirigir
    fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=guardar_cotizacion", {
        method: "POST", headers: { 'Content-Type': 'application/json' }, 
        body: JSON.stringify({ id_cotizacion: id_cotizacion, items: cotizacionItems, total_final: cotizacionTotal })
    }).then(() => {
        window.location.href = `Factura.php?id_cotizacion=${id_cotizacion}`;
    });
}

function rechazarCotizacion(ocultarAlerta = false) {
    const id_cotizacion = document.getElementById("id_cotizacion_actual").value;
    if(!id_cotizacion) return;

    if(ocultarAlerta || confirm("¿Está seguro de Archivar/Anular esta cotización para quitarla de los pendientes?")) {
        const fd = new FormData(); fd.append("id_cotizacion", id_cotizacion);
        fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=rechazar_cotizacion", { method: "POST", body: fd })
        .then(r => r.json()).then(res => {
            if(res.success) { 
                if(!ocultarAlerta) alert(res.message); 
                limpiarConstructor();
                listarPendientes(); 
            }
        });
    }
}

function imprimirCotizacion() {
    if(cotizacionItems.length === 0) return alert("El presupuesto está vacío.");
    const esOcasional = document.getElementById("es_ocasional_activa").value;
    
    let htmlItems = "";
    cotizacionItems.forEach(i => {
        htmlItems += `
            <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                <span>${i.cantidad}x ${i.descripcion} (${i.tipo})</span>
                <span>RD$ ${(i.precio * i.cantidad).toLocaleString(undefined,{minimumFractionDigits:2})}</span>
            </div>`;
    });

    const v = window.open('', '_blank', 'width=400,height=600');
    v.document.write(`
        <html><head><style>body{font-family:monospace;}</style></head>
        <body style="padding:20px;">
            <h3 style="text-align:center;">PRESUPUESTO ESTIMADO</h3>
            <p style="text-align:center;">${document.getElementById("lbl_orden_activa").innerText}</p>
            <hr>
            ${htmlItems}
            <hr>
            <h4>TOTAL ESTIMADO: ${document.getElementById("cot_total").innerText}</h4>
        </body></html>
    `);
    v.document.close();
    setTimeout(() => { 
        v.print(); 
        v.close(); 
        if (esOcasional == 1) {
            setTimeout(() => {
                if(confirm("El presupuesto ha sido impreso. Como es un cliente ocasional, ¿Desea archivar (cerrar) esta cotización para que no siga en pendientes?")) {
                    rechazarCotizacion(true); 
                }
            }, 1000);
        }
    }, 500);
}

function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try {
        if (typeof bootstrap !== 'undefined') {
            let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el); m.show();
        } else { throw new Error(); }
    } catch (e) {
        if (typeof jQuery !== 'undefined') {
            $('#' + id).modal('show');
        } else {
            el.classList.add('show'); el.style.display = 'block';
            document.body.classList.add('modal-open');
            document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
            const b = document.createElement('div'); b.id = 'm-bd-' + id; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
        }
    }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { 
        if (typeof bootstrap !== 'undefined') { 
            let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); 
        } else { throw new Error(); }
    } catch (e) {
        if (typeof jQuery !== 'undefined') {
            $('#' + id).modal('hide');
        } else {
            el.classList.remove('show'); el.style.display = 'none';
            document.body.classList.remove('modal-open');
            const b = document.getElementById('m-bd-' + id); if(b) b.remove();
            document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
        }
    }
}