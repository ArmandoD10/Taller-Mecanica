document.addEventListener("DOMContentLoaded", () => {
    listarCuentas();

    const formCobro = document.getElementById("formCobro");
    if(formCobro) {
        formCobro.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const montoPagar = parseFloat(document.getElementById("monto_pago").value);
            const maximo = parseFloat(document.getElementById("cobro_maximo").value);

            if(montoPagar <= 0) {
                return alert("El monto a pagar debe ser mayor a cero.");
            }
            if(montoPagar > maximo) {
                return alert(`No puedes cobrar más del balance pendiente (Máximo: RD$ ${maximo.toLocaleString()}).`);
            }

            const formData = new FormData(this);
            
            fetch("../../modules/Facturacion/Archivo_Cobros.php?action=procesar_pago", { 
                method: "POST", 
                body: formData 
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    cerrarModalCobro();
                    listarCuentas(); 
                    imprimirReciboPago(data.id_abono); 
                } else {
                    alert("ERROR AL PROCESAR PAGO:\n" + data.message);
                }
            })
            .catch(err => {
                console.error("Error crítico al procesar el pago:", err);
                alert("Ocurrió un error de conexión con el servidor. Revisa la consola (F12).");
            });
        });
    }
});

// ==========================================
// 1. CARGAR Y FILTRAR LA TABLA DE DEUDAS
// ==========================================
function listarCuentas() {
    const tbody = document.getElementById("cuerpoTablaCxC");
    if(!tbody) return;

    fetch("../../modules/Facturacion/Archivo_Cobros.php?action=listar_pendientes")
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";

        if (data.success) {
            if(data.data.length > 0) {
                data.data.forEach(c => {
                    const ordRef = c.id_orden ? `<small class="text-muted">ORD-${c.id_orden}</small>` : `<small class="text-muted">Venta Mostrador</small>`;
                    
                    // BOTONES DE ACCIÓN: Detalle (Ojito) + Cobrar
                    const btnDetalle = `<button class="btn btn-sm btn-outline-info fw-bold shadow-sm me-2" onclick="verDetalle(${c.id_factura}, '${c.cliente}')" title="Ver Detalles de la Factura"><i class="fas fa-eye"></i></button>`;
                    const btnCobrar = `<button class="btn btn-sm btn-success fw-bold px-3 shadow-sm" onclick="abrirModalCobro(${c.id_factura}, ${c.id_credito}, ${c.restante}, '${c.cliente}')"><i class="fas fa-hand-holding-usd me-1"></i> Cobrar</button>`;

                    const tr = document.createElement("tr");
                    tr.className = "fila-cxc"; // Clase clave para que funcione el buscador
                    tr.innerHTML = `
                        <td class="fw-bold text-primary">FAC-${c.id_factura} <br>${ordRef}</td>
                        <td class="text-start fw-bold text-dark">${c.cliente}</td>
                        <td class="text-muted">${c.fecha_emision}</td>
                        <td class="text-muted">RD$ ${parseFloat(c.monto_total).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td class="text-danger fw-bold fs-5">RD$ ${parseFloat(c.restante).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        <td>
                            <div class="d-flex justify-content-center">
                                ${btnDetalle}
                                ${btnCobrar}
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-laugh-beam me-2 text-success fs-4"></i> No hay facturas pendientes de cobro actualmente.</td></tr>`;
            }
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Error: ${data.message}</td></tr>`;
        }
        
        // Ejecutamos el filtro por si había texto escrito antes de actualizar
        filtrarCuentas();
    })
    .catch(err => {
        console.error("Error al listar cuentas:", err);
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger fw-bold"><i class="fas fa-bug me-2"></i>Error interno al cargar los datos. Revisa el Archivo_Cobros.php</td></tr>`;
    });
}

function filtrarCuentas() {
    const input = document.getElementById("buscador_cxc");
    if(!input) return;
    const textoBusqueda = input.value.toUpperCase();
    const filas = document.getElementsByClassName("fila-cxc");
    
    for (let i = 0; i < filas.length; i++) {
        const textoFila = filas[i].innerText.toUpperCase();
        filas[i].style.display = textoFila.includes(textoBusqueda) ? "" : "none";
    }
}

// ==========================================
// 2. VISOR DE DETALLES DE LA FACTURA
// ==========================================
function verDetalle(id_factura, cliente) {
    document.getElementById('lbl_detalle_factura').innerText = "FAC-" + id_factura;
    document.getElementById('lbl_detalle_cliente').innerText = cliente;
    
    const tbody = document.getElementById("cuerpoTablaDetalle");
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-info spinner-border-sm me-2"></div> Buscando detalles...</td></tr>';
    document.getElementById('lbl_detalle_total').innerText = "RD$ 0.00";
    
    abrirModalUI('modalDetalle');

    fetch(`../../modules/Facturacion/Archivo_Cobros.php?action=obtener_detalle&id_factura=${id_factura}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            tbody.innerHTML = "";
            if (data.data.length > 0) {
                let totalCalculado = 0;
                data.data.forEach(d => {
                    const subtotal = parseFloat(d.subtotal);
                    totalCalculado += subtotal;
                    tbody.innerHTML += `
                        <tr>
                            <td class="text-start fw-bold small text-dark">${d.descripcion}</td>
                            <td class="text-center small">${d.cantidad}</td>
                            <td class="text-end small">RD$ ${parseFloat(d.precio).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                            <td class="text-end fw-bold small">RD$ ${subtotal.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                        </tr>
                    `;
                });
                document.getElementById('lbl_detalle_total').innerText = "RD$ " + totalCalculado.toLocaleString(undefined, {minimumFractionDigits:2});
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No se encontraron detalles específicos para esta factura.</td></tr>';
            }
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger fw-bold py-4">Error al cargar detalles.</td></tr>`;
        }
    })
    .catch(err => {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger fw-bold py-4">Error de conexión.</td></tr>`;
    });
}

function cerrarModalDetalle() {
    cerrarModalUI('modalDetalle');
}

// ==========================================
// 3. LÓGICA DEL MODAL DE PAGOS
// ==========================================
function abrirModalCobro(id_factura, id_credito, restante, cliente) {
    document.getElementById("formCobro").reset();
    
    document.getElementById("cobro_id_factura").value = id_factura;
    document.getElementById("cobro_id_credito").value = id_credito;
    document.getElementById("cobro_maximo").value = restante;
    
    document.getElementById("lbl_cobro_factura").innerText = "FAC-" + id_factura;
    document.getElementById("lbl_cobro_cliente").innerText = cliente;
    document.getElementById("lbl_balance_pendiente").innerText = "RD$ " + parseFloat(restante).toLocaleString(undefined, {minimumFractionDigits:2});
    
    verificarMetodo();
    abrirModalUI('modalCobro');
}

function saldarCompleto(isChecked) {
    const maximo = document.getElementById("cobro_maximo").value;
    const inputMonto = document.getElementById("monto_pago");
    
    if(isChecked) {
        inputMonto.value = maximo;
        inputMonto.readOnly = true;
    } else {
        inputMonto.value = "";
        inputMonto.readOnly = false;
        inputMonto.focus();
    }
}

function verificarMetodo() {
    const metodo = document.getElementById("metodo_pago").value;
    const ref = document.getElementById("referencia_pago");
    if(metodo === "Efectivo") {
        ref.value = "N/A";
        ref.readOnly = true;
    } else {
        ref.value = "";
        ref.readOnly = false;
        ref.focus();
    }
}

function cerrarModalCobro() {
    cerrarModalUI('modalCobro');
}

// ==========================================
// 4. IMPRESIÓN TÉRMICA DEL RECIBO
// ==========================================
function imprimirReciboPago(id_abono) {
    fetch(`../../modules/Facturacion/Archivo_Cobros.php?action=obtener_recibo&id_abono=${id_abono}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const r = data.data;
            const htmlRecibo = `
            <html>
            <head>
                <title>Recibo de Pago #${r.id_abono}</title>
                <style>
                    body { font-family: 'Courier New', monospace; width: 300px; margin: 0 auto; color: #000; font-size:12px; }
                    .text-center { text-align: center; }
                    .fw-bold { font-weight: bold; }
                    .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
                    .row-flex { display: flex; justify-content: space-between; margin-bottom:3px; }
                </style>
            </head>
            <body>
                <div class="text-center">
                    <h3 style="margin-bottom:5px;">MECÁNICA DÍAZ PANTALEÓN</h3>
                    <div style="font-size:12px;">RECIBO DE INGRESO / ABONO</div>
                </div>
                
                <div class="divider"></div>
                <div><b>Recibo N°:</b> ${r.id_abono}</div>
                <div><b>Fecha:</b> ${r.fecha}</div>
                <div><b>Cliente:</b> ${r.cliente}</div>
                <div><b>Cajero:</b> ${r.cajero}</div>
                <div class="divider"></div>
                
                <div class="text-center fw-bold" style="margin-bottom:5px;">APLICADO A LA FACTURA FAC-${r.id_factura}</div>
                <div class="row-flex">
                    <span>Total Factura Orig:</span>
                    <span>RD$ ${parseFloat(r.monto_total).toLocaleString(undefined,{minimumFractionDigits:2})}</span>
                </div>
                
                <div class="row-flex text-center" style="margin:10px 0;">
                    <span style="border:1px solid #000; padding:5px; width:100%; font-size:14px; background:#f2f2f2;">
                        <b>MONTO PAGADO:<br>RD$ ${parseFloat(r.monto).toLocaleString(undefined,{minimumFractionDigits:2})}</b>
                    </span>
                </div>
                
                <div class="row-flex"><span>Método Pago:</span><span>${r.metodo_pago}</span></div>
                <div class="row-flex"><span>Referencia:</span><span>${r.referencia}</span></div>
                
                <div class="divider"></div>
                <div class="row-flex fw-bold" style="font-size:14px;">
                    <span>BALANCE RESTANTE:</span>
                    <span>RD$ ${parseFloat(r.balance_restante).toLocaleString(undefined,{minimumFractionDigits:2})}</span>
                </div>
                <div class="divider"></div>
                
                <div class="text-center" style="font-size:11px;">
                    Gracias por su pago.<br>
                    Su crédito ha sido restaurado proporcionalmente.
                </div>
            </body>
            </html>`;

            const ventana = window.open('', '_blank', 'width=350,height=600');
            if (!ventana) {
                alert("⚠️ El navegador bloqueó el recibo. Por favor, permite las ventanas emergentes (pop-ups) para este sitio.");
                return;
            }
            ventana.document.write(htmlRecibo);
            ventana.document.close();
            ventana.focus();
            setTimeout(() => { ventana.print(); ventana.close(); }, 500);
        }
    });
}

// ==========================================
// 5. UTILIDADES DE MODALES 
// ==========================================
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