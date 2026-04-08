/**
 * Scripts_Factura.js - Versión Profesional Completa
 * Manejo de POS, Inventario por Sucursal, Pasarela Azul y Crédito.
 */

let listaItemsFactura = [];
let listaImpuestos = [];
let clienteSeleccionado = null;
let subtotalGlobal = 0;

document.addEventListener("DOMContentLoaded", () => {
    console.log("Sistema de Facturación POS inicializado...");
    cargarImpuestosAuto();

    // Evento para limpiar el buscador si se borra el texto
    document.getElementById("buscar_producto").addEventListener("search", () => {
        document.getElementById("res_productos").classList.add("d-none");
    });

    const inputNCF = document.getElementById("ncf_factura");
if (inputNCF) {
    inputNCF.addEventListener("input", function(e) {
        // 1. Convierte minúsculas a MAYÚSCULAS automáticamente
        // 2. Remueve cualquier caracter que NO sea una letra (A-Z) o un número (0-9)
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
    });
}
});

// ==========================================
// 1. CARGA Y CONFIGURACIÓN DE IMPUESTOS
// ==========================================

function cargarImpuestosAuto() {
    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=listar_impuestos_automaticos")
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                listaImpuestos = res.data;
                console.log("Impuestos cargados:", listaImpuestos.length);
                actualizarInterfaz(); // Para que inicie en 0 correctamente
            } else {
                console.error("Error al cargar impuestos:", res.message);
            }
        })
        .catch(err => console.error("Fallo crítico al cargar impuestos:", err));
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

    // Buscar si ya existe en el carrito
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

    // Resetear buscador
    document.getElementById("buscar_producto").value = "";
    document.getElementById("res_productos").classList.add("d-none");
    cantInput.value = 1;

    actualizarInterfaz();
}

// ==========================================
// 3. INTERFAZ Y CÁLCULOS
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

function calcularTotales() {
    let montoImpuestos = 0;
    const contDesglose = document.getElementById("desglose_impuestos");
    contDesglose.innerHTML = "";

    listaImpuestos.forEach(imp => {
        let calc = subtotalGlobal * (parseFloat(imp.porcentaje) / 100);
        montoImpuestos += calc;
        
        const div = document.createElement("div");
        div.className = "d-flex justify-content-between text-muted small mb-1";
        div.innerHTML = `<span>${imp.nombre_impuesto} (${imp.porcentaje}%):</span>
                         <span>RD$ ${calc.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>`;
        contDesglose.appendChild(div);
    });

    const totalFinal = subtotalGlobal + montoImpuestos;

    // Actualización de campos en pantalla
    const totalStr = totalFinal.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById("subtotal_valor").innerText = `RD$ ${subtotalGlobal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    document.getElementById("total_final_valor").innerText = `RD$ ${totalStr}`;
    document.getElementById("total_general_display").innerText = `RD$ ${totalStr}`;
    document.getElementById("monto_azul_display").innerText = totalStr;
}

// ==========================================
// 4. CLIENTES Y CRÉDITO
// ==========================================

function toggleModoCredito(checked) {
    const contenedor = document.getElementById("contenedor_cliente");
    const selectPago = document.getElementById("metodo_pago");
    
    if (checked) {
        contenedor.classList.remove("d-none");
        selectPago.value = "1"; // Forzamos efectivo o lo bloqueamos
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
                                    Disponible: RD$ ${parseFloat(c.saldo_pendiente).toLocaleString()}
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
    document.getElementById("c_limite").innerText = `RD$ ${parseFloat(c.monto_credito).toLocaleString()}`;
    document.getElementById("c_disponible").innerText = `RD$ ${parseFloat(c.saldo_pendiente).toLocaleString()}`;
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
                <span>${i.cantidad}x ${i.nombre.substring(0, 20)}</span>
                <span>$${(i.precio * i.cantidad).toFixed(2)}</span>
            </div>`;
    });

    cont.innerHTML = `
        <div class="text-center mb-3">
            <h6 class="fw-bold mb-0">MECÁNICA DÍAZ & PANTALEÓN</h6>
            <small class="text-muted">RNC: 131-XXXXX-1</small><br>
            <small class="text-muted">Tel: 809-XXX-XXXX</small>
        </div>
        <hr style="border-style: dashed;">
        <div class="text-start mb-3">
            <small class="fw-bold">CLIENTE:</small> <small>${clienteSeleccionado ? clienteSeleccionado.nombre : 'CONSUMIDOR FINAL'}</small><br>
            <small class="fw-bold">FECHA:</small> <small>${new Date().toLocaleString()}</small>
        </div>
        <div class="mb-3">
            ${lineasItems}
        </div>
        <hr style="border-style: dashed;">
        <div class="d-flex justify-content-between fw-bold h5">
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
    } else if (metodo === "2") { // Azul
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
        ncf: document.getElementById("ncf_factura").value,
        metodo_pago: document.getElementById("metodo_pago").value,
        items: listaItemsFactura,
        impuestos_ids: listaImpuestos.map(i => i.id_impuesto),
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

function eliminarItem(index) {
    if (confirm("¿Remover este producto de la factura?")) {
        listaItemsFactura.splice(index, 1);
        actualizarInterfaz();
    }
}

// Coloca esto dentro de document.addEventListener("DOMContentLoaded", () => { ... });

