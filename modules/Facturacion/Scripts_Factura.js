/**
 * Scripts_Facturacion.js
 * Manejo de lógica de facturación, impuestos y pasarela de pago.
 */

let listaItemsFactura = []; // Almacena servicios y repuestos
let listaImpuestosDisponibles = []; // Impuestos cargados de la DB
let listaImpuestosSeleccionados = []; // Impuestos marcados por el usuario
let subtotalGlobal = 0;

document.addEventListener("DOMContentLoaded", () => {
    listarOrdenesPendientes();
    cargarImpuestosDesdeDB();

    // Buscador de órdenes en la tabla superior
    document.getElementById("filtro_ordenes").addEventListener("input", function(e) {
        const busqueda = e.target.value.toLowerCase();
        const filas = document.querySelectorAll("#tbody_ordenes tr");
        filas.forEach(fila => {
            const texto = fila.innerText.toLowerCase();
            fila.style.display = texto.includes(busqueda) ? "" : "none";
        });
    });
});

// ==========================================
// 1. CARGA DE DATOS INICIALES
// ==========================================

function listarOrdenesPendientes() {
    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=listar_ordenes_pendientes")
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById("tbody_ordenes");
            tbody.innerHTML = "";
            
            if (data.success && data.data.length > 0) {
                data.data.forEach(o => {
                    // VALIDACIÓN: Si el nombre viene vacío desde la DB por algún error de JOIN
                    const nombreCliente = o.cliente && o.cliente.trim() !== "null null" 
                                          ? o.cliente 
                                          : '<span class="text-danger small">Sin Cliente Registrado</span>';

                    tbody.innerHTML += `
                        <tr>
                            <td class="ps-4">
                                <input type="checkbox" class="form-check-input" onchange="toggleOrden(${o.id_orden}, this)">
                            </td>
                            <td>
                                <span class="fw-bold text-success">ORD-${o.id_orden}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">${o.placa}</span>
                                    <span class="text-muted small">${nombreCliente}</span>
                                </div>
                            </td>
                            <td class="text-muted small">${o.fecha_formateada}</td>
                            <td class="text-end pe-4">
                                <span class="fw-bold text-primary">RD$ ${parseFloat(o.total).toLocaleString()}</span>
                            </td>
                        </tr>`;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No hay órdenes listas para procesar</td></tr>`;
            }
        });
}

function cargarImpuestosDesdeDB() {
    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=listar_impuestos_activos")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                listaImpuestosDisponibles = data.data;
                const container = document.getElementById("lista_impuestos_check");
                container.innerHTML = "";
                
                listaImpuestosDisponibles.forEach(imp => {
                    container.innerHTML += `
                        <label class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <input class="form-check-input me-2" type="checkbox" 
                                       onchange="toggleImpuesto(${imp.id_config_impuesto}, this.checked)">
                                ${imp.nombre_impuesto}
                            </div>
                            <span class="badge bg-primary rounded-pill">${imp.porcentaje}%</span>
                        </label>`;
                });
            }
        });
}

// ==========================================
// 2. GESTIÓN DE ITEMS (ÓRDENES Y PRODUCTOS)
// ==========================================

function toggleOrden(id_orden, checkbox) {
    if (checkbox.checked) {
        fetch(`/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=obtener_detalle_orden&id_orden=${id_orden}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    data.items.forEach(item => {
                        listaItemsFactura.push({
                            id: item.id_item,
                            nombre: item.nombre,
                            tipo: item.tipo,
                            precio: parseFloat(item.precio),
                            cantidad: parseInt(item.cantidad),
                            id_orden: id_orden
                        });
                    });
                    actualizarInterfazFactura();
                }
            });
    } else {
        listaItemsFactura = listaItemsFactura.filter(item => item.id_orden !== id_orden);
        actualizarInterfazFactura();
    }
}

function buscarProductoAdicional(input) {
    const term = input.value;
    const listaSug = document.getElementById("lista_busqueda_productos");
    
    if (term.length < 2) {
        listaSug.classList.add("d-none");
        return;
    }

    fetch(`/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Factura.php?action=buscar_productos&term=${term}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                listaSug.innerHTML = "";
                listaSug.classList.remove("d-none");

                data.data.forEach(p => {
                    // LÓGICA DE IMAGEN:
                    // 1. Intentamos usar p.imagen (que viene de Repuesto_Articulo)
                    // 2. Si p.imagen es solo el nombre, le ponemos la ruta /img/
                    // 3. Si no hay nada, usamos el default.png
                    let imgPath = (p.imagen && p.imagen !== "") ? p.imagen : '/Taller/Taller-Mecanica/img/default.png';

                    const btn = document.createElement("button");
                    btn.className = "list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 border-start-0 border-end-0";
                    btn.style.cursor = "pointer";
                    
                    btn.innerHTML = `
                        <div style="width: 45px; height: 45px;" class="flex-shrink-0">
                            <img src="${imgPath}" 
                                 class="rounded shadow-sm border w-100 h-100" 
                                 style="object-fit: cover;" 
                                 onerror="this.src='/Taller/Taller-Mecanica/img/default.png'">
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small text-dark text-truncate">${p.nombre}</span>
                                <span class="text-primary fw-bold small">RD$ ${parseFloat(p.precio_venta).toLocaleString()}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted" style="font-size: 0.75rem;">Stock: <span class="fw-bold">${p.stock}</span></small>
                                <i class="fas fa-plus-circle text-success small"></i>
                            </div>
                        </div>
                    `;
                    
                    btn.onclick = (e) => {
                        e.preventDefault();
                        agregarProductoManual(p);
                        listaSug.classList.add("d-none");
                        input.value = "";
                    };
                    listaSug.appendChild(btn);
                });
            } else {
                listaSug.classList.add("d-none");
            }
        });
}
// AGREGAR PRODUCTO MANUAL CON CANTIDAD VARIABLE
function agregarProductoManual(p) {
    const cantidadInput = document.getElementById("cant_extra");
    const cantidad = parseInt(cantidadInput.value);

    if (isNaN(cantidad) || cantidad <= 0) {
        alert("Por favor, ingrese una cantidad válida.");
        return;
    }

    listaItemsFactura.push({
        id: p.id_articulo,
        nombre: p.nombre,
        tipo: 'Producto',
        precio: parseFloat(p.precio_venta),
        cantidad: cantidad,
        id_orden: null 
    });

    // Resetear cantidad y buscador
    cantidadInput.value = 1;
    document.getElementById("buscar_producto").value = "";
    document.getElementById("lista_busqueda_productos").classList.add("d-none");
    
    actualizarInterfazFactura();
}

function eliminarItemFactura(index) {
    listaItemsFactura.splice(index, 1);
    actualizarInterfazFactura();
}

// ==========================================
// 3. CÁLCULOS E IMPUESTOS
// ==========================================

function toggleImpuesto(id, isChecked) {
    const impuesto = listaImpuestosDisponibles.find(i => i.id_config_impuesto == id);
    if (isChecked) {
        listaImpuestosSeleccionados.push(impuesto);
    } else {
        listaImpuestosSeleccionados = listaImpuestosSeleccionados.filter(i => i.id_config_impuesto != id);
    }
    actualizarInterfazFactura();
}

function actualizarInterfazFactura() {
    const tbody = document.getElementById("detalle_factura_items");
    tbody.innerHTML = "";
    subtotalGlobal = 0;

    listaItemsFactura.forEach((item, index) => {
        const totalLinea = item.precio * item.cantidad;
        subtotalGlobal += totalLinea;

        tbody.innerHTML += `
            <tr>
                <td>${item.nombre}</td>
                <td><span class="badge bg-light text-dark border">${item.tipo}</span></td>
                <td class="text-center">${item.cantidad}</td>
                <td class="text-end">RD$ ${item.precio.toLocaleString()}</td>
                <td class="text-end fw-bold">RD$ ${totalLinea.toLocaleString()}</td>
                <td class="text-center">
                    <button class="btn btn-sm text-danger" onclick="eliminarItemFactura(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>`;
    });

    calcularTotalesFinales();
}

// ACTUALIZAR MONTO EN EL MODAL DE AZUL
function calcularTotalesFinales() {
    let montoImpuestosTotal = 0;
    const contenedorDesglose = document.getElementById("desglose_impuestos");
    
    // Limpiar para que no se dupliquen al recalcular
    contenedorDesglose.innerHTML = "";

    listaImpuestosSeleccionados.forEach(imp => {
        let calculoIndividual = subtotalGlobal * (parseFloat(imp.porcentaje) / 100);
        montoImpuestosTotal += calculoIndividual;

        // Crear la línea de desglose
        const divImp = document.createElement("div");
        divImp.className = "d-flex justify-content-between text-muted";
        divImp.style.fontSize = "0.75rem";
        
        // ¡OJO AQUÍ! Usamos ${imp.nombre_impuesto} para que cambie según el registro
        divImp.innerHTML = `
            <span>${imp.nombre_impuesto} (${imp.porcentaje}%):</span>
            <span>RD$ ${calculoIndividual.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
        `;
        contenedorDesglose.appendChild(divImp);
    });

    // Actualizar el resto de totales...
    const totalFinal = subtotalGlobal + montoImpuestosTotal;
    document.getElementById("impuestos_valor").innerText = `RD$ ${montoImpuestosTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    document.getElementById("total_final_valor").innerText = `RD$ ${totalFinal.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
}

// ==========================================
// 4. PROCESO DE PAGO Y AZUL
// ==========================================

function ejecutarAccionPago() {
    if (listaItemsFactura.length === 0) {
        alert("Debe seleccionar al menos una orden o producto para facturar.");
        return;
    }

    const metodo = document.getElementById("metodo_pago").value;
    
    if (metodo === "2") { // Asumiendo que 2 es Tarjeta/Azul
        const modalAzul = new bootstrap.Modal(document.getElementById('modalAzul'));
        modalAzul.show();
    } else {
        guardarFacturaFinal(null); // Pago en efectivo o transferencia
    }
}

function simularProcesandoAzul() {
    document.getElementById("azul_formulario").classList.add("d-none");
    document.getElementById("azul_cargando").classList.remove("d-none");

    // Simulación de respuesta de API (3 segundos)
    setTimeout(() => {
        const referenciaSimulada = "AZL-" + Math.floor(Math.random() * 1000000);
        alert("Pago aprobado por Azul. Referencia: " + referenciaSimulada);
        guardarFacturaFinal(referenciaSimulada);
    }, 3000);
}

function guardarFacturaFinal(referenciaAzul) {
    const dataFactura = {
        ncf: document.getElementById("ncf_factura").value,
        es_credito: document.getElementById("es_credito").checked ? 1 : 0,
        metodo_pago: document.getElementById("metodo_pago").value,
        items: listaItemsFactura,
        impuestos: listaImpuestosSeleccionados.map(i => i.id_config_impuesto),
        total: subtotalGlobal, // El backend debería recalcular por seguridad
        referencia_azul: referenciaAzul
    };

    console.log("Enviando a guardar:", dataFactura);
    
    // Aquí harías el fetch POST a Archivo_Facturacion.php?action=guardar_factura
    // Mostramos un alert por ahora
    alert("¡Factura procesada con éxito!");
    location.reload();
}

function toggleCredito(checked) {
    if (checked) {
        document.getElementById("metodo_pago").disabled = true;
        // Podrías añadir lógica para validar límite de crédito aquí
    } else {
        document.getElementById("metodo_pago").disabled = false;
    }
}

/**
 * Cierra el modal de Azul y limpia los campos internos
 */
function cancelarPagoAzul() {
    // 1. Obtener la instancia del modal de Bootstrap
    const modalElement = document.getElementById('modalAzul');
    const instance = bootstrap.Modal.getInstance(modalElement);
    
    if (instance) {
        // 2. Ocultar el modal
        instance.hide();
        
        // 3. Resetear visualmente el formulario de Azul (por si estaba en 'cargando')
        setTimeout(() => {
            document.getElementById("azul_formulario").classList.remove("d-none");
            document.getElementById("azul_cargando").classList.add("d-none");
            
            // Limpiar inputs de tarjeta (seguridad básica)
            const inputs = modalElement.querySelectorAll('input');
            inputs.forEach(input => input.value = "");
        }, 500);
    }
}