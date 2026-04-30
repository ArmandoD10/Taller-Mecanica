/**
 * Scripts_Factura.js - Versión Profesional Completa
 * Manejo de POS, Inventario por Sucursal, Pasarela Azul, ITBIS, Crédito, Caja, Cálculo de Efectivo e Impresión iFrame.
 */

let listaItemsFactura = [];
let listaImpuestosDB = []; 
let clienteSeleccionado = null;
let subtotalGlobal = 0;
let descuentoTotalOfertas = 0;
let ofertasSeleccionadasParaFactura = [];
let cotizacionVinculadaID = null; 

document.addEventListener("DOMContentLoaded", () => {
    console.log("Sistema de Facturación POS inicializado...");

    cargarImpuestosAuto();
    
    document.getElementById("buscar_producto").addEventListener("search", () => {
        document.getElementById("res_productos").classList.add("d-none");
    });

    const inputNCF = document.getElementById("ncf_factura");
    if (inputNCF) {
        inputNCF.addEventListener("input", function(e) {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
        });
    }

    // Revisamos si venimos desde el módulo de Cotizaciones
    setTimeout(revisarContextoCotizacion, 500);
});

// ==========================================
// MANEJO DE EFECTIVO Y CAMBIO
// ==========================================
function toggleMetodoPago(metodo) {
    const panelEfectivo = document.getElementById("panel_efectivo");
    const esCredito = document.getElementById("switch_credito").checked;
    
    if (metodo === "1" && !esCredito) {
        panelEfectivo.classList.remove("d-none");
        calcularCambio();
    } else {
        panelEfectivo.classList.add("d-none");
    }
}

function calcularCambio() {
    const totalStr = document.getElementById("total_final_valor").innerText.replace("RD$ ", "").replace(/,/g, "");
    const total = parseFloat(totalStr) || 0;
    const recibido = parseFloat(document.getElementById("efectivo_recibido").value) || 0;
    
    let cambio = recibido - total;
    const labelCambio = document.getElementById("cambio_devolver");
    
    if (cambio < 0 && recibido > 0) {
        labelCambio.innerText = "Faltan RD$ " + Math.abs(cambio).toLocaleString(undefined, {minimumFractionDigits: 2});
        labelCambio.className = "fw-bold text-danger mb-0";
    } else {
        labelCambio.innerText = "RD$ " + Math.max(0, cambio).toLocaleString(undefined, {minimumFractionDigits: 2});
        labelCambio.className = "fw-bold text-success mb-0";
    }
}

// ==========================================
// 1. CARGAR DATOS DESDE COTIZACIÓN
// ==========================================
function revisarContextoCotizacion() {
    const urlParams = new URLSearchParams(window.location.search);
    const idCot = urlParams.get('id_cotizacion');

    if (idCot) {
        fetch(`/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=cargar_datos_cotizacion&id_cotizacion=${idCot}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                cotizacionVinculadaID = res.cotizacion.id_cotizacion;

                document.getElementById('buscar_producto').disabled = true;
                document.getElementById('cant_extra').disabled = true;
                document.getElementById('buscar_cliente').disabled = true;
                
                const banner = document.createElement('div');
                banner.className = "alert alert-success py-2 mb-3 fw-bold border-success text-center shadow-sm";
                banner.innerHTML = `<i class="fas fa-file-invoice me-2"></i>Facturando Cotización POS: COT-${cotizacionVinculadaID}`;
                const panelPrincipal = document.querySelector('.card-body') || document.body;
                panelPrincipal.prepend(banner);

                if (res.cotizacion.id_cliente) {
                    const switchC = document.getElementById("switch_credito");
                    if(switchC) switchC.disabled = false;

                    clienteSeleccionado = {
                        id_cliente: res.cotizacion.id_cliente,
                        nombre: res.cotizacion.nombre_cliente,
                        id_credito: res.cotizacion.id_credito,
                        disponible: res.cotizacion.disponible
                    };
                    document.getElementById("buscar_cliente").value = res.cotizacion.nombre_cliente;
                    
                    if(res.cotizacion.id_credito > 0) {
                        document.getElementById("info_credito_cliente").classList.remove("d-none");
                        document.getElementById("c_nombre").innerText = res.cotizacion.nombre_cliente;
                        document.getElementById("c_disponible").innerText = `RD$ ${parseFloat(res.cotizacion.disponible).toLocaleString()}`;
                    }
                } else {
                    document.getElementById("buscar_cliente").value = res.cotizacion.nombre_cliente + " (Ocasional)";
                }

                listaItemsFactura = res.items.map(i => ({
                    id: i.id_articulo,
                    nombre: i.nombre,
                    precio: parseFloat(i.precio_venta),
                    cantidad: parseInt(i.cantidad)
                }));
                
                actualizarInterfaz();
            }
        });
    }
}

// ==========================================
// 2. BUSCADOR DE PRODUCTOS E INVENTARIO
// ==========================================
function buscarProducto(input) {
    const term = input.value.trim();
    const resDiv = document.getElementById("res_productos");
    
    if (term.length < 2) {
        resDiv.classList.add("d-none");
        return;
    }

    fetch(`/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=buscar_productos&term=${encodeURIComponent(term)}`)
        .then(res => res.json())
        .then(data => {
            resDiv.innerHTML = "";
            if (data.success && data.data.length > 0) {
                resDiv.classList.remove("d-none");
                data.data.forEach(p => {
                    const stockNum = parseInt(p.stock);
                    const stockClass = stockNum <= 5 ? 'text-danger fw-bold' : 'text-success';
                    const img = p.imagen ? p.imagen : '/Taller/Taller-Mecanica/img/default.png';

                    const btn = document.createElement("button");
                    btn.className = "list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 border-start-0 border-end-0";
                    btn.innerHTML = `
                        <div style="width: 45px; height: 45px;" class="flex-shrink-0">
                            <img src="${img}" class="rounded shadow-sm border w-100 h-100" style="object-fit: cover;">
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small text-dark text-truncate">${p.nombre}</span>
                                <span class="text-primary fw-bold small">RD$ ${parseFloat(p.precio_venta).toLocaleString()}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted" style="font-size: 0.75rem;">Stock: <span class="${stockClass}">${stockNum}</span></small>
                                <i class="fas fa-plus-circle text-success small"></i>
                            </div>
                        </div>`;
                    
                    btn.onclick = (e) => {
                        e.preventDefault();
                        agregarProductoAlCarrito(p);
                    };
                    resDiv.appendChild(btn);
                });
            } else {
                resDiv.classList.add("d-none");
            }
        })
        .catch(err => console.error("Error en búsqueda:", err));
}

function agregarProductoAlCarrito(p) {
    const cantInput = document.getElementById("cant_extra");
    const cantAAgregar = parseInt(cantInput.value) || 1;
    const stockDisponible = parseInt(p.stock);

    const itemExistente = listaItemsFactura.find(item => item.id === p.id_articulo);

    if (itemExistente) {
        if ((itemExistente.cantidad + cantAAgregar) > stockDisponible) {
            Swal.fire('Stock insuficiente', `No puedes agregar más. El stock máximo disponible es ${stockDisponible}`, 'error');
            return;
        }
        itemExistente.cantidad += cantAAgregar;
    } else {
        if (cantAAgregar > stockDisponible) {
           Swal.fire('Stock insuficiente', `Solo quedan ${stockDisponible} unidades en inventario.`, 'warning');
            return;
        }
        listaItemsFactura.push({
            id: p.id_articulo,
            nombre: p.nombre,
            precio: parseFloat(p.precio_venta),
            cantidad: cantAAgregar
        });
    }

    document.getElementById("buscar_producto").value = "";
    document.getElementById("res_productos").classList.add("d-none");
    cantInput.value = 1;
    actualizarInterfaz();
}

// ==========================================
// 3. INTERFAZ Y CÁLCULOS (DGII ITBIS)
// ==========================================
function actualizarInterfaz() {
    const tbody = document.getElementById("detalle_factura_items");
    tbody.innerHTML = "";
    subtotalGlobal = 0;

    if (listaItemsFactura.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted small">Añada productos para facturar</td></tr>';
    }

    listaItemsFactura.forEach((item, index) => {
        let lineaTotal = item.precio * item.cantidad;
        subtotalGlobal += lineaTotal;

        const btnEliminar = cotizacionVinculadaID ? '' : 
            `<button class="btn btn-sm btn-link text-danger p-0" onclick="eliminarItem(${index})"><i class="fas fa-times-circle"></i></button>`;

        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td class="small fw-bold text-dark">${item.nombre}</td>
            <td class="text-center fw-bold">${item.cantidad}</td>
            <td class="text-end text-muted">RD$ ${item.precio.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold">RD$ ${lineaTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-center">
                ${btnEliminar}
            </td>`;
        tbody.appendChild(tr);
    });

    calcularTotales();
}

function eliminarItem(index) {
    listaItemsFactura.splice(index, 1);
    actualizarInterfaz();
}

function cargarImpuestosAuto() {
    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=listar_impuestos_automaticos")
        .then(res => res.json())
        .then(res => { 
            if(res.success) {
                listaImpuestosDB = res.data; 
                calcularTotales(); 
            }
        });
}

function calcularTotales() {
    const desgloseCont = document.getElementById("desglose_impuestos_dinamico");
    
    if (!desgloseCont) return; 
    
    desgloseCont.innerHTML = ""; 
    let totalImpuestosCalculados = 0;

    let subtotalConDescuento = subtotalGlobal - descuentoTotalOfertas;

    listaImpuestosDB.forEach(imp => {
        let valorCalculado = subtotalConDescuento * (parseFloat(imp.porcentaje) / 100);
        totalImpuestosCalculados += valorCalculado;

        desgloseCont.innerHTML += `
            <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>${imp.nombre_impuesto} (${imp.porcentaje}%):</span>
                <span class="text-dark fw-bold">RD$ ${valorCalculado.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>`;
    });

    let totalFinal = subtotalConDescuento + totalImpuestosCalculados;

    document.getElementById("subtotal_valor").innerText = `RD$ ${subtotalGlobal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    
    const filaOf = document.getElementById("fila_ofertas");
    if (descuentoTotalOfertas > 0) {
        filaOf.classList.remove("d-none");
        document.getElementById("ofertas_valor").innerText = `- RD$ ${descuentoTotalOfertas.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    } else {
        filaOf.classList.add("d-none");
    }

    const totalStr = totalFinal.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById("total_final_valor").innerText = `RD$ ${totalStr}`;
    document.getElementById("total_general_display").innerText = `RD$ ${totalStr}`;
    
    const montoAzul = document.getElementById("monto_azul_display");
    if(montoAzul) {
        montoAzul.innerText = totalStr;
    }

    // Actualizamos el panel de efectivo en tiempo real si está activo
    if (document.getElementById("metodo_pago").value === "1") {
        calcularCambio();
    }
}

// ==========================================
// 4. CLIENTES Y CRÉDITO
// ==========================================
function toggleModoCredito(checked) {
    const contenedor = document.getElementById("contenedor_cliente");
    const selectPago = document.getElementById("metodo_pago");
    
    if (checked) {
        contenedor.classList.remove("d-none");
        selectPago.value = "1"; 
        selectPago.disabled = true;
        document.getElementById("panel_efectivo").classList.add("d-none");
    } else {
        contenedor.classList.add("d-none");
        selectPago.disabled = false;
        if(selectPago.value === "1") {
            document.getElementById("panel_efectivo").classList.remove("d-none");
            calcularCambio();
        }
        if (!cotizacionVinculadaID) { 
            clienteSeleccionado = null;
            document.getElementById("info_credito_cliente").classList.add("d-none");
            document.getElementById("buscar_cliente").value = "";
        }
    }
}

function buscarClienteCredito(input) {
    const term = input.value.trim();
    const resDiv = document.getElementById("res_clientes");
    
    if (term.length < 2) {
        resDiv.classList.add("d-none");
        return;
    }

    fetch(`/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=buscar_cliente_credito&term=${encodeURIComponent(term)}`)
        .then(res => res.json())
        .then(data => {
            resDiv.innerHTML = "";
            if (data.success && data.data.length > 0) {
                resDiv.classList.remove("d-none");
                data.data.forEach(c => {
                    const btn = document.createElement("button");
                    btn.className = "list-group-item list-group-item-action py-3";
                    btn.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-dark">${c.nombre} ${c.apellido_p || ''}</span><br>
                                <small class="text-muted">ID Cliente: ${c.id_cliente}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-soft-primary text-primary border border-primary-subtle">
                                    Disponible: RD$ ${parseFloat(c.disponible).toLocaleString()}
                                </span>
                            </div>
                        </div>`;
                    btn.onclick = () => {
                        seleccionarCliente(c);
                        resDiv.classList.add("d-none");
                    };
                    resDiv.appendChild(btn);
                });
            }
        });
}

function seleccionarCliente(c) {
    clienteSeleccionado = c;
    document.getElementById("buscar_cliente").value = `${c.nombre} ${c.apellido_p || ''}`;
    document.getElementById("info_credito_cliente").classList.remove("d-none");
    document.getElementById("c_nombre").innerText = c.nombre;
    document.getElementById("c_limite").innerText = `RD$ ${parseFloat(c.limite).toLocaleString()}`;
    document.getElementById("c_disponible").innerText = `RD$ ${parseFloat(c.disponible).toLocaleString()}`;
}

// ==========================================
// 5. FLUJO DE PAGO Y VOUCHER
// ==========================================
function previsualizarVoucher() {
    if (listaItemsFactura.length === 0) return Swal.fire("Carrito vacío", "Debe añadir productos al carrito para cobrar.", "warning");
    
    const esCredito = document.getElementById("switch_credito").checked;
    if (esCredito && (!clienteSeleccionado || clienteSeleccionado.id_credito == 0)) {
        return Swal.fire("Cliente no válido", "El cliente seleccionado no tiene crédito activo disponible.", "warning");
    }

    const metodo = document.getElementById("metodo_pago").value;
    const totalFinalNum = parseFloat(document.getElementById("total_final_valor").innerText.replace("RD$ ", "").replace(/,/g, ""));
    let recibido = 0;
    let cambio = 0;

    // Validación y lógica de Efectivo para el voucher
    if (!esCredito && metodo === "1") {
        recibido = parseFloat(document.getElementById("efectivo_recibido").value) || 0;
        if (recibido < totalFinalNum) {
            return Swal.fire("Efectivo Insuficiente", "El cliente debe entregar un monto igual o mayor al total de la cuenta.", "error");
        }
        cambio = recibido - totalFinalNum;
    }

    // Verificación de caja abierta en backend
    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=verificar_caja_abierta")
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            Swal.fire({
                title: 'Turno Cerrado',
                text: data.message,
                icon: 'warning',
                confirmButtonText: 'Ir a Gestión de Caja',
                allowOutsideClick: false
            }).then(() => {
                window.location.href = "/Taller/Taller-Mecanica/view/Facturacion/MCaja.php";
            });
            return; 
        }

        // Mostrar Voucher
        const modal = new bootstrap.Modal(document.getElementById('modalVoucher'));
        const cont = document.getElementById("voucher_content");
        
        let lineasItems = "";
        listaItemsFactura.forEach(i => {
            lineasItems += `
                <div class="d-flex justify-content-between small py-1">
                    <span>${i.cantidad}x ${i.nombre.substring(0, 18)}</span>
                    <span>$${(i.precio * i.cantidad).toFixed(2)}</span>
                </div>`;
        });

        let itbis = subtotalGlobal * 0.18; // Referencia visual
        
        let bloqueEfectivo = "";
        if (!esCredito && metodo === "1") {
            bloqueEfectivo = `
            <div class="d-flex justify-content-between small text-muted border-top border-dark pt-2 mt-2">
                <span>Efectivo Recibido:</span>
                <span>$${recibido.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>
            <div class="d-flex justify-content-between small fw-bold text-dark">
                <span>Cambio Entregado:</span>
                <span>$${cambio.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>`;
        }

        cont.innerHTML = `
            <div class="text-center mb-3">
                <h6 class="fw-bold mb-0">MECÁNICA DÍAZ & PANTALEÓN</h6>
                <small class="text-muted">RNC: 131-XXXXX-1</small><br>
                <small class="text-muted">Tel: 809-545-6872</small>
            </div>
            <hr style="border-style: dashed;">
            <div class="text-start mb-3 small">
                <b>CLIENTE:</b> ${clienteSeleccionado ? clienteSeleccionado.nombre : 'CONSUMIDOR FINAL'}<br>
                <b>NCF:</b> ${document.getElementById("ncf_factura").value || 'B0200000001'}<br>
                <b>FECHA:</b> ${new Date().toLocaleString()}
            </div>
            <div class="mb-2 border-bottom pb-2">
                <div class="d-flex justify-content-between fw-bold small">
                    <span>DESCRIPCIÓN</span><span>VALOR</span>
                </div>
                ${lineasItems}
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Sub-Total:</span>
                <span>$${subtotalGlobal.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>
            <div class="d-flex justify-content-between small text-muted border-bottom pb-2 mb-2">
                <span>ITBIS (18%):</span>
                <span>$${itbis.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold h5 mb-0 mt-3">
                <span>TOTAL:</span>
                <span>${document.getElementById("total_final_valor").innerText}</span>
            </div>
            ${bloqueEfectivo}
            <p class="mt-4 small text-muted">*** GRACIAS POR SU PREFERENCIA ***</p>
        `;
        
        modal.show();
    })
    .catch(err => {
        Swal.fire('Error', 'No se pudo verificar el estado de la caja con el servidor.', 'error');
    });
}

function finalizarTodo() {
    const esCredito = document.getElementById("switch_credito").checked;
    const metodo = document.getElementById("metodo_pago").value;

    if (esCredito) {
        guardarFacturaFinal(null, true);
    } else if (metodo === "2") { 
        bootstrap.Modal.getInstance(document.getElementById('modalVoucher')).hide();
        new bootstrap.Modal(document.getElementById('modalAzul')).show();
    } else {
        guardarFacturaFinal(null, false);
    }
}

function simularAzul() {
    const tarjeta = document.getElementById("tarjeta_numero").value;
    if (tarjeta.length < 16) return Swal.fire('Tarjeta incompleta', 'El número de tarjeta debe tener 16 dígitos.', 'warning');

    document.getElementById("azul_formulario").classList.add("d-none");
    document.getElementById("azul_cargando").classList.remove("d-none");

    const fd = new FormData();
    fd.append("tarjeta", tarjeta);
    fd.append("monto", subtotalGlobal);

    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=simular_azul", { method: "POST", body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                Swal.fire({
                    title: '¡Transacción Aprobada!',
                    html: `<b>Banco Popular Dominicana</b><br>Referencia: ${res.referencia}`,
                    icon: 'success',
                    confirmButtonColor: '#004481' 
                }).then(() => {
                    guardarFacturaFinal(res.referencia, false);
                });
            } else {
                Swal.fire('Transacción Declinada', res.message, 'error');
                document.getElementById("azul_formulario").classList.remove("d-none");
                document.getElementById("azul_cargando").classList.add("d-none");
            }
        });
}

function guardarFacturaFinal(refAzul, esCredito) {
    const totalNum = parseFloat(document.getElementById("total_final_valor").innerText.replace("RD$ ", "").replace(/,/g, ""));
    const recibido = document.getElementById("efectivo_recibido") ? parseFloat(document.getElementById("efectivo_recibido").value) || 0 : 0;

    const data = {
        id_cotizacion: cotizacionVinculadaID,
        id_cliente: clienteSeleccionado ? clienteSeleccionado.id_cliente : null,
        ncf: document.getElementById("ncf_factura").value || 'B0200000001',
        metodo_pago: document.getElementById("metodo_pago").value,
        items: listaItemsFactura,
        impuestos_ids: listaImpuestosDB.map(i => i.id_impuesto), 
        total_final: totalNum,
        referencia_azul: refAzul,
        es_credito: esCredito,
        id_credito: clienteSeleccionado ? clienteSeleccionado.id_credito : null,
        ofertas_aplicadas: ofertasSeleccionadasParaFactura,
        efectivo_recibido: recibido
    };

    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=guardar_factura_pos", {
        method: "POST",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Swal.fire({
                title: '¡Factura Procesada!',
                text: 'La operación se guardó correctamente.',
                icon: 'success',
                confirmButtonColor: '#198754'
            }).then(() => {
                imprimirFacturaVoucher(res.id_factura, data);
            });
        } else {
            Swal.fire("ERROR CRÍTICO", res.message, "error");
        }
    });
}

// === NUEVA FUNCIÓN DE IMPRESIÓN POR IFRAME ===
function imprimirFacturaVoucher(id_factura, dataCobro) {
    const cliente = document.getElementById('buscar_cliente') && document.getElementById('buscar_cliente').value ? document.getElementById('buscar_cliente').value : 'CONSUMIDOR FINAL';
    const fecha = new Date().toLocaleString();
    
    let htmlItems = "";
    listaItemsFactura.forEach(d => {
        let subt = parseFloat(d.precio) * parseInt(d.cantidad);
        htmlItems += `
        <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:3px;">
            <span>${d.cantidad}x ${d.nombre.substring(0,20)}</span>
            <span>RD$${subt.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>`;
    });

    let htmlDescuento = "";
    if(descuentoTotalOfertas > 0) {
        htmlDescuento = `
        <div style="display:flex; justify-content:space-between; font-size:13px; font-weight:bold; color:red; margin-top:5px;">
            <span>Descuento Aplicado:</span>
            <span>- RD$ ${descuentoTotalOfertas.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>`;
    }
    
    // BLOQUE DE EFECTIVO (Si aplica)
    let htmlEfectivo = "";
    if (dataCobro && dataCobro.metodo_pago === "1" && !dataCobro.es_credito && dataCobro.efectivo_recibido) {
        let recibido = parseFloat(dataCobro.efectivo_recibido);
        let cambio = recibido - dataCobro.total_final;
        htmlEfectivo = `
        <div class="divider"></div>
        <div style="display:flex; justify-content:space-between; font-size:12px; margin-top:5px; color:#555;">
            <span>Efectivo Recibido:</span>
            <span>RD$ ${recibido.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:12px; font-weight:bold;">
            <span>Cambio a Devolver:</span>
            <span>RD$ ${cambio.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>`;
    }

    const htmlFactura = `
    <html>
    <head>
        <title>Factura ${id_factura}</title>
        <style>
            body { font-family: 'Courier New', monospace; width: 300px; margin: 0 auto; color: #000; padding: 10px; }
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
            <div><b>NCF:</b> ${dataCobro.ncf || 'B0200000001'}</div>
            <div><b>Fecha:</b> ${fecha}</div>
            <div><b>Cliente:</b> ${cliente}</div>
        </div>

        <div class="divider"></div>
        
        <div style="font-size:12px; font-weight:bold; display:flex; justify-content:space-between; margin-bottom:5px;">
            <span>DESCRIPCIÓN</span>
            <span>VALOR</span>
        </div>
        
        ${htmlItems}
        
        <div class="divider"></div>
        
        <div class="row-flex" style="font-size:13px;">
            <span>Sub-Total Bruto:</span>
            <span>RD$ ${subtotalGlobal.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>
        
        ${htmlDescuento}

        <div class="divider"></div>

        <div class="row-flex fw-bold" style="font-size:16px; margin-top:5px;">
            <span>TOTAL A PAGAR:</span>
            <span>RD$ ${dataCobro.total_final.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
        </div>

        ${htmlEfectivo}

        <div class="divider"></div>
        <div class="text-center" style="font-size:11px;">
            Gracias por preferir nuestros servicios.<br>
            <i>Esta factura cumple con los requisitos de la DGII.</i>
        </div>
    </body>
    </html>`;

    // TÉCNICA IFRAME INVISIBLE
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);
    
    iframe.contentDocument.write(htmlFactura);
    iframe.contentDocument.close();

    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        
        setTimeout(() => {
            document.body.removeChild(iframe);
            if (typeof cotizacionVinculadaID !== 'undefined' && cotizacionVinculadaID) {
                window.location.href = "Cotizaciones.php";
            } else {
                location.reload();
            }
        }, 2000);
    }, 500);
}

// ==========================================
// 6. GESTIÓN DE OFERTAS Y REGALOS
// ==========================================
function abrirAuthOferta() {
    new bootstrap.Modal(document.getElementById('modalAuthAdmin')).show();
}

function validarAccesoOfertas() {
    const user = document.getElementById("auth_user").value;
    const pass = document.getElementById("auth_pass").value;

    const fd = new FormData();
    fd.append('usuario', user);
    fd.append('password', pass);

    fetch(`/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=validar_admin`, {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalAuthAdmin')).hide();
            cargarOfertasVigentes();
        } else {
            Swal.fire('Atención', 'Acceso denegado. Solo administradores.', 'error');
        }
    });
}

function cargarOfertasVigentes() {
    const contenedor = document.getElementById("lista_ofertas_disponibles");
    contenedor.innerHTML = "";

    fetch(`/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=listar_ofertas_vigentes`)
    .then(res => res.json())
    .then(data => {
        if (data.data.length === 0) {
            contenedor.innerHTML = "<div class='p-4 text-center'>No hay descuentos disponibles.</div>";
            return;
        }

        data.data.forEach(o => {
            contenedor.innerHTML += `
                <label class="list-group-item d-flex justify-content-between align-items-center py-3">
                    <div>
                        <input class="form-check-input me-3 checkbox-oferta" type="checkbox" 
                               value="${o.id_oferta}" data-valor="${o.porciento}">
                        <span class="fw-bold">${o.nombre_oferta}</span>
                    </div>
                    <span class="badge bg-danger">-${parseFloat(o.porciento)}%</span>
                </label>`;
        });
        new bootstrap.Modal(document.getElementById('modalSeleccionOfertas')).show();
    });
}

function aplicarOfertasSeleccionadas() {
    const checks = document.querySelectorAll(".checkbox-oferta:checked");
    ofertasSeleccionadasParaFactura = [];
    descuentoTotalOfertas = 0;

    checks.forEach(check => {
        const porciento = parseFloat(check.getAttribute("data-valor"));
        descuentoTotalOfertas += (subtotalGlobal * (porciento / 100));
        ofertasSeleccionadasParaFactura.push(check.value);
    });

    bootstrap.Modal.getInstance(document.getElementById('modalSeleccionOfertas')).hide();
    actualizarInterfaz(); 
}

function agregarRegaloAlCarrito(id, nombre) {
    const existe = listaItemsFactura.find(i => i.id == id && i.precio == 0);
    if (!existe) {
        listaItemsFactura.push({
            id: id,
            nombre: `[OFERTA] ${nombre}`,
            precio: 0,
            cantidad: 1
        });
        actualizarInterfaz();
    }
}