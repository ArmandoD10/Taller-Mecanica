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
});

function filtrarCotizaciones() {
    const input = document.getElementById("buscar_cotizacion");
    if(!input) return;
    const texto = input.value.toLowerCase();
    const filas = document.querySelectorAll(".fila-cotizacion");
    
    filas.forEach(fila => {
        const contenido = fila.innerText.toLowerCase();
        if(contenido.includes(texto)) {
            fila.style.display = "";
        } else {
            fila.style.display = "none";
        }
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
                tr.innerHTML = `
                    <td class="fw-bold text-primary">COT-${o.id_cotizacion}</td>
                    <td>
                        <span class="d-block fw-bold text-dark small">${o.vehiculo}</span>
                        <span class="text-muted small"><i class="fas fa-user me-1"></i>${o.cliente}</span>
                    </td>
                    <td class="text-center fw-bold text-success">RD$ ${parseFloat(o.monto_total).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                `;
                tr.onclick = () => cargarCotizacion(o.id_cotizacion, o.vehiculo, o.cliente);
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center py-5 text-muted">No hay cotizaciones pendientes.</td></tr>`;
        }
    });
}

function cargarCotizacion(id_cotizacion, vehiculo, cliente) {
    document.getElementById("id_cotizacion_actual").value = id_cotizacion;
    document.getElementById("lbl_orden_activa").innerText = `COT-${id_cotizacion} | ${cliente}`;
    document.getElementById("capa_bloqueo").classList.add("d-none");
    
    cotizacionItems = [];
    document.getElementById("buscador_items").value = "";
    document.getElementById("cantidad_item").value = 1;
    document.getElementById("cuerpoDetalleCotizacion").innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Cargando...</td></tr>';

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
        }
        renderizarTabla();
    });
}

function buscarItems(input) {
    const term = input.value.trim();
    const resDiv = document.getElementById("res_items");
    if (term.length < 2) { resDiv.classList.add("d-none"); return; }

    Promise.all([
        fetch(`../../modules/Facturacion/Archivo_Cotizaciones.php?action=buscar_servicios&term=${encodeURIComponent(term)}`).then(r => r.json()),
        fetch(`../../modules/Facturacion/Archivo_Cotizaciones.php?action=buscar_repuestos&term=${encodeURIComponent(term)}`).then(r => r.json())
    ])
    .then(([resServicios, resRepuestos]) => {
        resDiv.innerHTML = "";
        let found = false;

        if (resServicios.success && resServicios.data.length > 0) {
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
    if(existe) {
        existe.cantidad += cant;
    } else {
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
            
            const badge = item.tipo === 'servicio' ? '<span class="badge bg-secondary">Mano de Obra</span>' : '<span class="badge bg-primary">Repuesto</span>';

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

function cerrarModalNuevaCotizacion() {
    cerrarModalUI('modalNuevaCotizacion');
}

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
                    document.getElementById("tel_cliente_express").value = v.telefono;
                    
                    document.getElementById("lbl_exp_cliente").innerText = v.cliente;
                    document.getElementById("lbl_exp_vehiculo").innerText = v.vehiculo_desc;
                    document.getElementById("info_vehiculo_express").classList.remove("d-none");
                    resDiv.classList.add("d-none");
                };
                resDiv.appendChild(li);
            });
        } else {
            resDiv.classList.add("d-none");
        }
    });
}

function crearCotizacionExpress() {
    const tipo = document.getElementById("tipo_reg").checked ? 'registrado' : 'ocasional';
    const fd = new FormData();
    fd.append("tipo_cliente", tipo);

    let clienteName = "";
    let vehiculoDesc = "";

    if(tipo === 'registrado') {
        const id_vehiculo = document.getElementById("id_vehiculo_express").value;
        const id_cliente = document.getElementById("id_cliente_express").value;
        
        if(!id_vehiculo) return alert("Debe seleccionar un vehículo de la lista.");
        
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
        
        if(!nom || !veh) return alert("El Nombre y el Vehículo son obligatorios para un cliente ocasional.");
        
        fd.append("nombre_ocasional", nom);
        fd.append("telefono_ocasional", tel);
        fd.append("vehiculo_ocasional", veh);
        
        clienteName = nom;
        vehiculoDesc = veh;
    }

    fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=crear_directa", {
        method: "POST", body: fd
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            cerrarModalNuevaCotizacion();
            listarPendientes();
            cargarCotizacion(data.id_cotizacion, vehiculoDesc, clienteName);
        } else {
            alert("Error al crear: " + data.message);
        }
    })
    .catch(err => alert("Error de conexión al intentar crear la cotización."));
}

function guardarBorrador() {
    const id_cotizacion = document.getElementById("id_cotizacion_actual").value;
    if(!id_cotizacion) return alert("Seleccione una cotización.");

    const data = { id_cotizacion: id_cotizacion, items: cotizacionItems, total_final: cotizacionTotal };

    fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=guardar_cotizacion", {
        method: "POST", headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
    }).then(res => res.json()).then(res => {
        if(res.success) { alert(res.message); listarPendientes(); }
        else alert("Error: " + res.message);
    });
}

function aprobarCotizacion() {
    const id_cotizacion = document.getElementById("id_cotizacion_actual").value;
    if(!id_cotizacion) return alert("Seleccione una cotización.");
    if(cotizacionItems.length === 0) return alert("El presupuesto está vacío.");

    if(confirm("¿Está seguro que el cliente APROBÓ este presupuesto?\nSe creará una Orden oficial y pasará a Inspección (Diagnóstico).")) {
        const data = { id_cotizacion: id_cotizacion, items: cotizacionItems, total_final: cotizacionTotal };
        
        fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=guardar_cotizacion", {
            method: "POST", headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
        }).then(() => {
            const fd = new FormData(); fd.append("id_cotizacion", id_cotizacion);
            fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=aprobar_cotizacion", { method: "POST", body: fd })
            .then(res => res.json()).then(res => {
                if(res.success) { 
                    alert(res.message); 
                    
                    // AQUI SE AGREGO LA REDIRECCION A INSPECCION
                    window.location.href = `../Taller/MInspeccion.php?id_orden=${res.id_orden}`;
                    
                } else {
                    alert("Error: " + res.message);
                }
            });
        });
    }
}

function rechazarCotizacion() {
    const id_cotizacion = document.getElementById("id_cotizacion_actual").value;
    if(!id_cotizacion) return alert("Seleccione una cotización.");

    if(confirm("¿Está seguro que desea RECHAZAR/ANULAR esta cotización?")) {
        const fd = new FormData(); fd.append("id_cotizacion", id_cotizacion);
        fetch("../../modules/Facturacion/Archivo_Cotizaciones.php?action=rechazar_cotizacion", { method: "POST", body: fd })
        .then(res => res.json()).then(res => {
            if(res.success) { 
                alert(res.message); 
                document.getElementById("capa_bloqueo").classList.remove("d-none");
                listarPendientes(); 
            }
        });
    }
}

function imprimirCotizacion() {
    if(cotizacionItems.length === 0) return alert("El presupuesto está vacío.");
    
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
            <h3 style="text-align:center;">PRESUPUESTO DE SERVICIO</h3>
            <p style="text-align:center;">${document.getElementById("lbl_orden_activa").innerText}</p>
            <hr>
            ${htmlItems}
            <hr>
            <h4>TOTAL ESTIMADO: ${document.getElementById("cot_total").innerText}</h4>
            <p style="text-align:center; font-size:10px; margin-top:20px;">Este documento es un presupuesto estimado. Los precios pueden variar.</p>
        </body></html>
    `);
    v.document.close();
    setTimeout(() => { v.print(); v.close(); }, 500);
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