let listaImpuestosTaller = [];
let totalFacturaFinalNum = 0;
let subtotalFacturaNum = 0;
let itbisFacturaNum = 0;
let detallesFacturaActual = [];
let repuestosExtra = [];
let ordenActualID = 0;
let clienteActualID = 0;

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarImpuestosTaller();

    const inputNCF = document.getElementById("fac_ncf");
    if (inputNCF) {
        inputNCF.addEventListener("input", function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
        });
    }

    const searchInput = document.getElementById("buscar_prod_entrega");
    if(searchInput) {
        searchInput.addEventListener("search", () => {
            document.getElementById("res_prod_entrega").classList.add("d-none");
        });
    }

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
                } else { alert("ERROR:\n" + data.message); }
            });
        });
    }

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
                } else { alert("ACCESO DENEGADO:\n" + data.message); }
            });
        });
    }
});

// ==========================================
// 1. LISTADO Y BUSCADOR PRINCIPAL
// ==========================================
function listar() {
    fetch("../../modules/Taller/Archivo_Entrega.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaEntregas");
        if(!tbody) return;
        tbody.innerHTML = "";
        
        let countListos = 0; let countCalidad = 0; let countEntregados = 0;

        if (data.success && data.data.length > 0) {
            data.data.forEach(o => {
                if(o.estado_orden === 'Listo') countListos++;
                if(o.estado_orden === 'Control Calidad') countCalidad++;
                if(o.estado_orden === 'Entregado') countEntregados++;

                let badgePago = ""; let pagado = false;
                if (o.estado_pago === 'Pagado') {
                    badgePago = `<span class="badge bg-success"><i class="fas fa-check me-1"></i> Pagado</span>`; pagado = true;
                } else if (o.estado_pago === 'Pendiente') {
                    badgePago = `<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Crédito Pend.</span>`; pagado = true; 
                } else {
                    badgePago = `<span class="badge bg-secondary"><i class="fas fa-file-invoice me-1"></i> Sin Facturar</span>`;
                }

                let badgeOrden = ""; let btnAccion = "";
                
                if (o.estado_orden === 'Control Calidad') {
                    badgeOrden = `<span class="badge bg-info text-dark fw-bold">Control Calidad</span>`;
                    btnAccion = `<button class="btn btn-sm btn-info fw-bold shadow-sm text-dark" onclick="abrirModalCalidad(${o.id_orden}, '${o.vehiculo}')"><i class="fas fa-clipboard-check me-1"></i> Evaluar</button>`;
                } 
                else if (o.estado_orden === 'Listo') {
                    badgeOrden = `<span class="badge bg-primary">Listo</span>`;
                    if(pagado) {
                        btnAccion = `<button class="btn btn-sm btn-success fw-bold shadow-sm" onclick="prepararEntrega(${o.id_orden}, '${o.cliente}', '${o.vehiculo}')"><i class="fas fa-key me-1"></i> Entregar</button>`;
                    } else {
                        btnAccion = `<button class="btn btn-sm btn-primary fw-bold shadow-sm" onclick="abrirModalFacturacion(${o.id_orden}, '${o.cliente}', '${o.vehiculo}', ${o.id_cliente})"><i class="fas fa-file-invoice-dollar me-1"></i> Facturar / Cobrar</button>`;
                    }
                } 
                else if (o.estado_orden === 'Entregado') {
                    badgeOrden = `<span class="badge bg-dark">Entregado</span>`;
                    btnAccion = `<button class="btn btn-sm btn-outline-dark" onclick="mostrarComprobanteInmediato(${o.id_orden})" title="Reimprimir Acta"><i class="fas fa-print"></i> Acta</button>`;
                }

                const tr = document.createElement("tr");
                tr.className = "fila-entrega";
                tr.innerHTML = `
                    <td class="fw-bold text-primary">ORD-${o.id_orden}</td>
                    <td>${o.cliente}</td>
                    <td>${o.vehiculo}</td>
                    <td class="fw-bold text-muted">${o.monto_total_fmt}</td>
                    <td>${badgePago}</td>
                    <td>${badgeOrden}</td>
                    <td class="text-center">${btnAccion}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No hay vehículos en proceso de salida.</td></tr>`;
        }

        document.getElementById('count_listos').innerText = countListos;
        document.getElementById('count_calidad').innerText = countCalidad;
        document.getElementById('count_entregados').innerText = countEntregados;
        
        filtrarEntregas();
    });
}

function filtrarEntregas() {
    const input = document.getElementById("buscar_entrega");
    if(!input) return;
    const textoBusqueda = input.value.toUpperCase();
    const filas = document.getElementsByClassName("fila-entrega");
    for (let i = 0; i < filas.length; i++) {
        const textoFila = filas[i].innerText.toUpperCase();
        filas[i].style.display = textoFila.includes(textoBusqueda) ? "" : "none";
    }
}

// ==========================================
// 2. FACTURACIÓN Y PRODUCTOS EXTRAS (CON IMÁGENES)
// ==========================================
function cargarImpuestosTaller() {
    fetch("../../modules/Taller/Archivo_Entrega.php?action=listar_impuestos")
    .then(res => res.json())
    .then(res => { if (res.success) listaImpuestosTaller = res.data; });
}

function abrirModalFacturacion(id_orden, cliente, vehiculo, id_cliente) {
    ordenActualID = id_orden;
    clienteActualID = id_cliente;
    
    document.getElementById('fac_id_orden').value = id_orden;
    document.getElementById('fac_id_cliente').value = id_cliente;
    document.getElementById('fac_lbl_orden').innerText = 'ORD-' + id_orden;
    document.getElementById('fac_lbl_cliente').innerText = cliente;
    document.getElementById('fac_lbl_vehiculo').innerText = vehiculo;
    document.getElementById('fac_ncf').value = '';
    document.getElementById('fac_metodo_pago').value = '1';
    
    document.getElementById('fac_switch_credito').checked = false;
    toggleCreditoTaller(false);

    repuestosExtra = [];
    document.getElementById("buscar_prod_entrega").value = "";
    document.getElementById("cant_prod_entrega").value = 1;

    fetch(`../../modules/Taller/Archivo_Entrega.php?action=obtener_detalle_facturacion&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            detallesFacturaActual = data.data;
            renderizarTablaFactura();
        }
    });
    
    abrirModalUI('modalFacturacion');
}

function buscarProductoEntrega(input) {
    const term = input.value.trim();
    const resDiv = document.getElementById("res_prod_entrega");
    
    if (term.length < 2) { resDiv.classList.add("d-none"); return; }

    fetch(`../../modules/Taller/Archivo_Entrega.php?action=buscar_productos&term=${encodeURIComponent(term)}`)
    .then(res => res.json())
    .then(data => {
        resDiv.innerHTML = "";
        if (data.success && data.data.length > 0) {
            resDiv.classList.remove("d-none");
            data.data.forEach(p => {
                const stockNum = parseInt(p.stock);
                const stockClass = stockNum <= 5 ? 'text-danger fw-bold' : 'text-success';
                // Lógica de imagen por defecto si no tiene
                const img = p.imagen ? p.imagen : '/Taller/Taller-Mecanica/img/default.png';

                const btn = document.createElement("button");
                btn.className = "list-group-item list-group-item-action d-flex align-items-center gap-3 py-2";
                
                // Estructura HTML con la imagen a la izquierda
                btn.innerHTML = `
                    <div style="width: 40px; height: 40px;" class="flex-shrink-0">
                        <img src="${img}" class="rounded shadow-sm border w-100 h-100" style="object-fit: cover; object-position: center;">
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-dark text-truncate" style="max-width: 180px;">${p.nombre}</span>
                            <span class="badge bg-success shadow-sm">RD$ ${parseFloat(p.precio_venta).toLocaleString(undefined, {minimumFractionDigits:2})}</span>
                        </div>
                        <div class="text-start mt-1">
                            <small class="text-muted" style="font-size: 10px;">Stock: <span class="${stockClass}">${stockNum}</span></small>
                        </div>
                    </div>
                `;
                
                btn.onclick = (e) => { e.preventDefault(); agregarRepuestoExtra(p); };
                resDiv.appendChild(btn);
            });
        } else {
            resDiv.classList.add("d-none");
        }
    });
}

function agregarRepuestoExtra(p) {
    const cantInput = document.getElementById("cant_prod_entrega");
    const cantAAgregar = parseInt(cantInput.value) || 1;
    const stockDisponible = parseInt(p.stock);

    const itemExistente = repuestosExtra.find(item => item.id === p.id_articulo);

    if (itemExistente) {
        if ((itemExistente.cantidad + cantAAgregar) > stockDisponible) {
            return alert(`No puedes agregar más. El stock máximo es ${stockDisponible}`);
        }
        itemExistente.cantidad += cantAAgregar;
    } else {
        if (cantAAgregar > stockDisponible) {
            return alert(`Stock insuficiente. Solo quedan ${stockDisponible} unidades.`);
        }
        repuestosExtra.push({
            id: p.id_articulo,
            descripcion: p.nombre,
            precio: parseFloat(p.precio_venta),
            cantidad: cantAAgregar,
            es_extra: true
        });
    }

    document.getElementById("buscar_prod_entrega").value = "";
    document.getElementById("res_prod_entrega").classList.add("d-none");
    cantInput.value = 1;
    renderizarTablaFactura();
}

function eliminarRepuestoExtra(index) {
    repuestosExtra.splice(index, 1);
    renderizarTablaFactura();
}

function renderizarTablaFactura() {
    const tbody = document.getElementById("fac_tabla_detalles");
    tbody.innerHTML = "";
    subtotalFacturaNum = 0;

    const itemsCombinados = [...detallesFacturaActual, ...repuestosExtra];

    if(itemsCombinados.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No hay servicios ni repuestos registrados.</td></tr>`;
    } else {
        itemsCombinados.forEach((d, index) => {
            let subt = parseFloat(d.precio) * parseInt(d.cantidad);
            subtotalFacturaNum += subt;
            
            let btnEliminar = "";
            let badgeExtra = "";
            
            if (d.es_extra) {
                badgeExtra = `<span class="badge bg-warning text-dark ms-1" style="font-size: 9px;">EXTRA</span>`;
                let indexExtra = index - detallesFacturaActual.length;
                btnEliminar = `<button class="btn btn-sm btn-link text-danger p-0 m-0" onclick="eliminarRepuestoExtra(${indexExtra})"><i class="fas fa-times-circle"></i></button>`;
            }

            tbody.innerHTML += `
                <tr>
                    <td class="small fw-bold text-dark">${d.descripcion} ${badgeExtra}</td>
                    <td class="text-center small">${d.cantidad}</td>
                    <td class="text-end small">RD$ ${parseFloat(d.precio).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                    <td class="text-end small fw-bold">RD$ ${subt.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                    <td class="text-center">${btnEliminar}</td>
                </tr>`;
        });
    }

    itbisFacturaNum = subtotalFacturaNum * 0.18;
    totalFacturaFinalNum = subtotalFacturaNum + itbisFacturaNum;

    document.getElementById('fac_subtotal').innerText = `RD$ ${subtotalFacturaNum.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    document.getElementById('fac_itbis').innerText = `RD$ ${itbisFacturaNum.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    document.getElementById('fac_total_final').innerText = `RD$ ${totalFacturaFinalNum.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
}

// ==========================================
// 3. COBRO Y CRÉDITO
// ==========================================
function toggleCreditoTaller(checked) {
    const infoCredito = document.getElementById("fac_info_credito");
    const selPago = document.getElementById("fac_metodo_pago");
    const id_cliente = document.getElementById('fac_id_cliente').value;

    if (checked) {
        if (!id_cliente || id_cliente == "0") {
            alert("No se ha podido identificar al cliente de esta orden.");
            document.getElementById("fac_switch_credito").checked = false;
            return;
        }

        selPago.value = "1";
        selPago.disabled = true;
        infoCredito.classList.remove("d-none");
        
        document.getElementById('fac_credito_disponible').innerText = 'Cargando...';
        document.getElementById('fac_credito_limite').innerText = '...';
        
        fetch(`../../modules/Taller/Archivo_Entrega.php?action=verificar_credito&id_cliente=${id_cliente}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                document.getElementById('fac_credito_limite').innerText = `RD$ ${parseFloat(data.limite).toLocaleString(undefined, {minimumFractionDigits:2})}`;
                document.getElementById('fac_credito_disponible').innerText = `RD$ ${parseFloat(data.disponible).toLocaleString(undefined, {minimumFractionDigits:2})}`;
                document.getElementById('fac_id_credito').value = data.id_credito;
                
                if (totalFacturaFinalNum > parseFloat(data.disponible)) {
                    document.getElementById('fac_credito_disponible').classList.replace('text-success', 'text-danger');
                } else {
                    document.getElementById('fac_credito_disponible').classList.replace('text-danger', 'text-success');
                }
            } else {
                alert(data.message);
                document.getElementById("fac_switch_credito").checked = false;
                toggleCreditoTaller(false);
            }
        })
    } else {
        infoCredito.classList.add("d-none");
        selPago.disabled = false;
        document.getElementById('fac_id_credito').value = '';
    }
}

function iniciarCobroOrden() {
    const esCredito = document.getElementById("fac_switch_credito").checked;
    const metodo = document.getElementById("fac_metodo_pago").value;

    if (esCredito) {
        ejecutarFacturacionFinal(null, true);
    } else if (metodo === "2") { 
        cerrarModalFacturacion();
        const displayAzul = document.getElementById('azul_monto_display');
        if(displayAzul) displayAzul.innerText = `RD$ ${totalFacturaFinalNum.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        abrirModalUI('modalAzulTaller');
    } else {
        ejecutarFacturacionFinal(null, false);
    }
}

function procesarAzulTaller() {
    const tarjeta = document.getElementById("azul_tarjeta_taller").value;
    if (tarjeta.length < 15) return alert("Número de tarjeta inválido.");

    document.getElementById("azul_formulario_taller").classList.add("d-none");
    document.getElementById("azul_cargando_taller").classList.remove("d-none");

    const fd = new FormData();
    fd.append("tarjeta", tarjeta);
    fd.append("monto", totalFacturaFinalNum);

    fetch("../../modules/Taller/Archivo_Entrega.php?action=simular_azul", { method: "POST", body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                setTimeout(() => {
                    alert("TRANSACCIÓN AZUL APROBADA\nRef: " + res.referencia);
                    ejecutarFacturacionFinal(res.referencia, false);
                    cerrarModalUI('modalAzulTaller');
                    document.getElementById("azul_formulario_taller").classList.remove("d-none");
                    document.getElementById("azul_cargando_taller").classList.add("d-none");
                    document.getElementById("azul_tarjeta_taller").value = "";
                }, 1500);
            } else {
                alert("Error Pasarela: " + res.message);
                document.getElementById("azul_formulario_taller").classList.remove("d-none");
                document.getElementById("azul_cargando_taller").classList.add("d-none");
            }
        });
}

function ejecutarFacturacionFinal(refAzul, esCredito) {
    const data = {
        id_orden: document.getElementById("fac_id_orden").value,
        id_cliente: document.getElementById("fac_id_cliente").value,
        ncf: document.getElementById("fac_ncf").value || 'B0200000001',
        metodo_pago: document.getElementById("fac_metodo_pago").value,
        total_final: totalFacturaFinalNum,
        impuestos_ids: [], 
        referencia_azul: refAzul,
        es_credito: esCredito,
        id_credito: document.getElementById("fac_id_credito").value,
        repuestos_extra: repuestosExtra
    };

    fetch("../../modules/Taller/Archivo_Entrega.php?action=guardar_factura_orden", {
        method: "POST",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            cerrarModalFacturacion();
            imprimirFacturaVoucher(res.id_factura, data);
            listar(); 
        } else {
            alert("ERROR AL FACTURAR:\n" + res.message);
        }
    });
}

// ==========================================
// 4. IMPRESIÓN Y VOUCHERS
// ==========================================
function imprimirFacturaVoucher(id_factura, dataCobro) {
    const cliente = document.getElementById('fac_lbl_cliente').innerText;
    const vehiculo = document.getElementById('fac_lbl_vehiculo').innerText;
    const fecha = new Date().toLocaleString();
    
    let htmlItems = "";
    const itemsCombinados = [...detallesFacturaActual, ...repuestosExtra];
    
    itemsCombinados.forEach(d => {
        let subt = parseFloat(d.precio) * parseInt(d.cantidad);
        htmlItems += `
        <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:3px;">
            <span>${d.cantidad}x ${d.descripcion.substring(0,20)}</span>
            <span>RD$${subt.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>`;
    });

    const htmlFactura = `
    <html>
    <head>
        <title>Factura ${id_factura}</title>
        <style>
            body { font-family: 'Courier New', monospace; width: 300px; margin: 0 auto; color: #000; }
            .text-center { text-align: center; }
            .fw-bold { font-weight: bold; }
            .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
            .row-flex { display: flex; justify-content: space-between; }
        </style>
    </head>
    <body>
        <div class="text-center">
            <h3 style="margin-bottom:5px;">MECÁNICA DÍAZ PANTALEÓN</h3>
            <div style="font-size:12px;">RNC: 131-XXXXX-1</div>
            <div style="font-size:12px;">Santiago de los Caballeros, RD</div>
            <div style="font-size:12px;">Tel: 809-545-6872</div>
        </div>
        
        <div class="divider"></div>
        
        <div style="font-size:12px;">
            <div><b>Factura N°:</b> ${id_factura}</div>
            <div><b>NCF:</b> ${dataCobro.ncf}</div>
            <div><b>Fecha:</b> ${fecha}</div>
            <div><b>Cliente:</b> ${cliente}</div>
            <div><b>Vehículo:</b> ${vehiculo}</div>
        </div>

        <div class="divider"></div>
        
        <div style="font-size:12px; font-weight:bold; display:flex; justify-content:space-between; margin-bottom:5px;">
            <span>DESCRIPCIÓN</span>
            <span>VALOR</span>
        </div>
        
        ${htmlItems}
        
        <div class="divider"></div>
        
        <div class="row-flex" style="font-size:13px;">
            <span>Sub-Total:</span>
            <span>RD$ ${subtotalFacturaNum.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>
        <div class="row-flex" style="font-size:13px;">
            <span>ITBIS (18%):</span>
            <span>RD$ ${itbisFacturaNum.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>
        <div class="row-flex fw-bold" style="font-size:16px; margin-top:5px;">
            <span>TOTAL:</span>
            <span>RD$ ${totalFacturaFinalNum.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>

        <div class="divider"></div>
        <div class="text-center" style="font-size:11px;">
            Gracias por preferir nuestros servicios.<br>
            <i>Esta factura cumple con los requisitos de la DGII.</i>
        </div>
    </body>
    </html>`;

    const ventana = window.open('', '_blank', 'width=350,height=600');
    if (!ventana) { alert("Tu navegador bloqueó el pop-up del recibo."); return; }
    ventana.document.write(htmlFactura);
    ventana.document.close();
    ventana.focus();
    setTimeout(() => { ventana.print(); ventana.close(); }, 500);
}

function abrirModalCalidad(id_orden, vehiculo) {
    document.getElementById("id_orden_calidad").value = id_orden;
    document.getElementById("lbl_calidad_orden").innerText = "ORDEN: ORD-" + id_orden;
    document.getElementById("lbl_calidad_vehiculo").innerText = "Vehículo: " + vehiculo;
    document.getElementById("formCalidad").reset();
    abrirModalUI('modalCalidad');
}

function prepararEntrega(id_orden, cliente, vehiculo) {
    document.getElementById("id_orden_entrega").value = id_orden;
    document.getElementById("lbl_orden").innerText = "ORD-" + id_orden;
    document.getElementById("lbl_cliente").innerText = cliente;
    document.getElementById("lbl_vehiculo").innerText = vehiculo;
    abrirModalUI('modalEntrega');
}

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
        }
    });
}

function imprimirComprobante() {
    const contenido = document.getElementById('areaImpresionEntrega').innerHTML;
    const ventana = window.open('', '_blank', 'width=800,height=600');
    if (!ventana) { alert("Tu navegador bloqueó el pop-up del acta."); return; }
    ventana.document.write(`
        <html><head><title>Acta de Entrega</title>
        <style>body{font-family:Arial;padding:30px;} .text-center{text-align:center;} .border-bottom{border-bottom:2px solid #ddd;padding-bottom:10px;margin-bottom:15px;} .border-top{border-top:1px solid #000;padding-top:10px;margin-top:50px;} .row{display:flex;width:100%;} .col-6{width:50%;float:left;} .card{background:#f8f9fa;padding:20px;border-radius:5px;} p{margin:5px 0;}</style>
        </head><body>${contenido}</body></html>
    `);
    ventana.document.close(); ventana.focus();
    setTimeout(() => { ventana.print(); ventana.close(); }, 500);
}

// ==========================================
// 5. UTILIDADES MODALES
// ==========================================
function cerrarModalFacturacion() { cerrarModalUI('modalFacturacion'); }
function cerrarModalAzul() { cerrarModalUI('modalAzulTaller'); }
function cerrarModalEntrega() { cerrarModalUI('modalEntrega'); }
function cerrarModalCalidad() { cerrarModalUI('modalCalidad'); }
function cerrarModalComprobante() { cerrarModalUI('modalComprobante'); }

function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try {
        if (typeof bootstrap !== 'undefined') {
            let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el); m.show();
        } else { throw new Error(); }
    } catch (e) {
        el.classList.add('show'); el.style.display = 'block';
        document.body.classList.add('modal-open');
        document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
        const b = document.createElement('div'); b.id = 'm-bd-ent-' + id; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
    }
}
function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { if (typeof bootstrap !== 'undefined') { let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); } } catch (e) {}
    el.classList.remove('show'); el.style.display = 'none';
    document.body.classList.remove('modal-open');
    const b = document.getElementById('m-bd-ent-' + id); if(b) b.remove();
    document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
}