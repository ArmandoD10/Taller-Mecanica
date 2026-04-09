/**
 * Scripts_Factura.js - Versión Profesional Completa
 * Manejo de POS, Inventario por Sucursal, Pasarela Azul, ITBIS y Crédito Actualizado.
 */

let listaItemsFactura = [];
let listaImpuestos = [];
let clienteSeleccionado = null;
let subtotalGlobal = 0;

document.addEventListener("DOMContentLoaded", () => {
    console.log("Sistema de Facturación POS inicializado...");

    cargarImpuestosAuto();
    
    // Ya no usamos cargarImpuestosAuto() porque el 18% está fijo en la lógica DGII

    document.getElementById("buscar_producto").addEventListener("search", () => {
        document.getElementById("res_productos").classList.add("d-none");
    });

    const inputNCF = document.getElementById("ncf_factura");
    if (inputNCF) {
        inputNCF.addEventListener("input", function(e) {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
        });
    }
});


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
            alert(`No puedes agregar más. El stock máximo es ${stockDisponible}`);
            return;
        }
        itemExistente.cantidad += cantAAgregar;
    } else {
        if (cantAAgregar > stockDisponible) {
            alert(`Stock insuficiente. Solo quedan ${stockDisponible} unidades.`);
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

        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td class="small fw-bold text-dark">${item.nombre}</td>
            <td class="text-center fw-bold">${item.cantidad}</td>
            <td class="text-end text-muted">RD$ ${item.precio.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-end fw-bold">RD$ ${lineaTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-link text-danger p-0" onclick="eliminarItem(${index})">
                    <i class="fas fa-times-circle"></i>
                </button>
            </td>`;
        tbody.appendChild(tr);
    });

    calcularTotales();
}

// Variable global para guardar impuestos de la DB
// Variable global al inicio del archivo
let listaImpuestosDB = []; 

// Función para cargar los impuestos de la base de datos
function cargarImpuestosAuto() {
    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=listar_impuestos_automaticos")
        .then(res => res.json())
        .then(res => { 
            if(res.success) {
                listaImpuestosDB = res.data; 
                console.log("Impuestos cargados desde DB:", listaImpuestosDB);
                calcularTotales(); // Forzar primer cálculo al cargar
            }
        });
}

// FUNCIÓN ÚNICA DE CÁLCULO (Sustituye a las otras dos)
function calcularTotales() {
    const desgloseCont = document.getElementById("desglose_impuestos_dinamico");
    
    // Validamos que el contenedor exista para evitar el error "null" de tu consola
    if (!desgloseCont) {
        console.warn("Contenedor 'desglose_impuestos_dinamico' no encontrado en el HTML.");
        return; 
    }
    
    desgloseCont.innerHTML = ""; // Limpiamos para redibujar
    let totalImpuestosCalculados = 0;

    // 1. Calculamos el subtotal base restando el descuento de ofertas si existe
    let subtotalConDescuento = subtotalGlobal - descuentoTotalOfertas;

    // 2. Dibujamos cada impuesto dinámico (ITBIS 18%, etc.) sobre el monto con descuento
    listaImpuestosDB.forEach(imp => {
        let valorCalculado = subtotalConDescuento * (parseFloat(imp.porcentaje) / 100);
        totalImpuestosCalculados += valorCalculado;

        desgloseCont.innerHTML += `
            <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>${imp.nombre_impuesto} (${imp.porcentaje}%):</span>
                <span class="text-dark fw-bold">RD$ ${valorCalculado.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>`;
    });

    // 3. Calculamos el Total Neto final
    let totalFinal = subtotalConDescuento + totalImpuestosCalculados;

    // 4. Actualizamos los campos generales del HTML
    document.getElementById("subtotal_valor").innerText = `RD$ ${subtotalGlobal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    
    // Mostramos u ocultamos la fila de ofertas
    const filaOf = document.getElementById("fila_ofertas");
    if (descuentoTotalOfertas > 0) {
        filaOf.classList.remove("d-none");
        document.getElementById("ofertas_valor").innerText = `- RD$ ${descuentoTotalOfertas.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    } else {
        filaOf.classList.add("d-none");
    }

    // Actualizamos el gran total y el indicador de arriba
    const totalStr = totalFinal.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById("total_final_valor").innerText = `RD$ ${totalStr}`;
    document.getElementById("total_general_display").innerText = `RD$ ${totalStr}`;
    
    // Actualizamos el monto en el modal de la pasarela Azul si está definido
    const montoAzul = document.getElementById("monto_azul_display");
    if(montoAzul) {
        montoAzul.innerText = totalStr;
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
    } else {
        contenedor.classList.add("d-none");
        selectPago.disabled = false;
        clienteSeleccionado = null;
        document.getElementById("info_credito_cliente").classList.add("d-none");
        document.getElementById("buscar_cliente").value = "";
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
                                <span class="fw-bold text-dark">${c.nombre} ${c.apellido_p}</span><br>
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
    document.getElementById("buscar_cliente").value = `${c.nombre} ${c.apellido_p}`;
    document.getElementById("info_credito_cliente").classList.remove("d-none");
    document.getElementById("c_nombre").innerText = c.nombre;
    document.getElementById("c_limite").innerText = `RD$ ${parseFloat(c.limite).toLocaleString()}`;
    document.getElementById("c_disponible").innerText = `RD$ ${parseFloat(c.disponible).toLocaleString()}`;
}

// ==========================================
// 5. FLUJO DE PAGO Y VOUCHER
// ==========================================
function previsualizarVoucher() {
    if (listaItemsFactura.length === 0) return alert("Debe añadir productos al carrito.");
    if (document.getElementById("switch_credito").checked && !clienteSeleccionado) {
        return alert("Debe seleccionar un cliente para la venta a crédito.");
    }

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

    let itbis = subtotalGlobal * 0.18;

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
        <div class="d-flex justify-content-between fw-bold h5 mb-0">
            <span>TOTAL:</span>
            <span>${document.getElementById("total_final_valor").innerText}</span>
        </div>
        <p class="mt-4 small text-muted">*** GRACIAS POR SU PREFERENCIA ***</p>
    `;
    
    modal.show();
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
    if (tarjeta.length < 16) return alert("Número de tarjeta incompleto.");

    document.getElementById("azul_formulario").classList.add("d-none");
    document.getElementById("azul_cargando").classList.remove("d-none");

    const fd = new FormData();
    fd.append("tarjeta", tarjeta);
    fd.append("monto", subtotalGlobal);

    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=simular_azul", { method: "POST", body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                setTimeout(() => {
                    alert("TRANSACCIÓN APROBADA POR BANCO POPULAR\nREFERENCIA: " + res.referencia);
                    guardarFacturaFinal(res.referencia, false);
                }, 1500);
            } else {
                alert("Error Azul: " + res.message);
                document.getElementById("azul_formulario").classList.remove("d-none");
                document.getElementById("azul_cargando").classList.add("d-none");
            }
        });
}

function guardarFacturaFinal(refAzul, esCredito) {
    const totalNum = parseFloat(document.getElementById("total_final_valor").innerText.replace("RD$ ", "").replace(/,/g, ""));

    const data = {
        id_cliente: clienteSeleccionado ? clienteSeleccionado.id_cliente : null,
        ncf: document.getElementById("ncf_factura").value || 'B0200000001',
        metodo_pago: document.getElementById("metodo_pago").value,
        items: listaItemsFactura,
        impuestos_ids: [], 
        total_final: totalNum,
        referencia_azul: refAzul,
        es_credito: esCredito,
        id_credito: clienteSeleccionado ? clienteSeleccionado.id_credito : null
    };

    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=guardar_factura_pos", {
        method: "POST",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert("¡OPERACIÓN EXITOSA!\nFactura #" + res.id_factura + " generada.");
            location.reload();
        } else {
            alert("ERROR CRÍTICO: " + res.message);
        }
    });
}

//Logica de ofertas.
let ofertasSeleccionadasParaFactura = [];
let descuentoTotalOfertas = 0;

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
            alert("Acceso denegado. Solo administradores.");
        }
    });
}

/**
 * Carga las ofertas que están en estado 'activo' y dentro del rango de fecha actual.
 * Evita la duplicidad visual y separa los beneficios de descuento y regalo.
 */
// 1. CARGAR OFERTAS EN EL MODAL
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

// 2. APLICAR DESCUENTOS Y RECALCULAR
function aplicarOfertasSeleccionadas() {
    const checks = document.querySelectorAll(".checkbox-oferta:checked");
    ofertasSeleccionadasParaFactura = [];
    descuentoTotalOfertas = 0;

    checks.forEach(check => {
        const porciento = parseFloat(check.getAttribute("data-valor"));
        // El descuento se calcula sobre el subtotal acumulado
        descuentoTotalOfertas += (subtotalGlobal * (porciento / 100));
        ofertasSeleccionadasParaFactura.push(check.value);
    });

    // Cerrar modal y refrescar la pantalla
    bootstrap.Modal.getInstance(document.getElementById('modalSeleccionOfertas')).hide();
    actualizarInterfaz(); 
}

// 3. FUNCIÓN DE CÁLCULO FINAL (Sincronizada con tu HTML)
function calcularTotales() {
    const desgloseCont = document.getElementById("desglose_impuestos_dinamico");
    if (!desgloseCont) return;

    desgloseCont.innerHTML = "";
    let totalImpuestos = 0;

    // Subtotal neto tras aplicar descuentos
    let subtotalConDescuento = subtotalGlobal - descuentoTotalOfertas;

    // Calcular impuestos dinámicos (ITBIS, etc.)
    listaImpuestosDB.forEach(imp => {
        let valor = subtotalConDescuento * (parseFloat(imp.porcentaje) / 100);
        totalImpuestos += valor;
        desgloseCont.innerHTML += `
            <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>${imp.nombre_impuesto} (${imp.porcentaje}%):</span>
                <span class="text-dark fw-bold">RD$ ${valor.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
            </div>`;
    });

    let totalFinal = subtotalConDescuento + totalImpuestos;

    // Actualizar UI
    document.getElementById("subtotal_valor").innerText = `RD$ ${subtotalGlobal.toLocaleString()}`;
    
    const filaOf = document.getElementById("fila_ofertas");
    if (descuentoTotalOfertas > 0) {
        filaOf.classList.remove("d-none");
        document.getElementById("ofertas_valor").innerText = `- RD$ ${descuentoTotalOfertas.toLocaleString()}`;
    } else {
        filaOf.classList.add("d-none");
    }

    document.getElementById("total_final_valor").innerText = `RD$ ${totalFinal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    document.getElementById("total_general_display").innerText = `RD$ ${totalFinal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
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
    }
}



// AL GUARDAR FINAL, ENVIAR ofertasSeleccionadasParaFactura EN EL JSON