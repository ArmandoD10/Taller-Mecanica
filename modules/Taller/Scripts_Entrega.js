let listaImpuestosTaller = [];
let totalFacturaFinalNum = 0;
let subtotalFacturaNum = 0;
let detallesFacturaActual = [];
let repuestosExtra = [];
let ordenActualID = 0;
let clienteActualID = 0;

let ofertasSeleccionadasParaFactura = [];
let descuentoTotalOfertas = 0;
let catalogoPoliticasGarantia = [];

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarImpuestosTaller();
    cargarCatalogoGarantias(); 

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
            
            const id_orden_procesada = document.getElementById("id_orden_entrega").value;
            
            let garantiasAsignadas = [];
            const selectores = document.querySelectorAll('.select-garantia-item');
            
            selectores.forEach(select => {
                garantiasAsignadas.push({
                    id_linea: select.getAttribute('data-id'),
                    tipo_linea: select.getAttribute('data-tipo'), 
                    id_politica: select.value 
                });
            });

            const payload = {
                id_orden_entrega: id_orden_procesada,
                garantias_asignadas: garantiasAsignadas
            };

            fetch("../../modules/Taller/Archivo_Entrega.php?action=procesar_entrega", { 
                method: "POST", 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) { 
                    cerrarModalEntrega(); 
                    listar(); 
                    mostrarComprobanteInmediato(id_orden_procesada);
                    
                    if (data.generar_certificado) {
                        window.open(`../../view/Garantias/CertificadoGarantia.php?id_orden=${id_orden_procesada}`, '_blank');
                    }
                } else { Swal.fire('Error', data.message, 'error'); }
            })
            .catch(err => Swal.fire('Error', 'Error de conexión al procesar entrega y garantía.', 'error'));
        });
    }

    const formCalidad = document.getElementById("formCalidad");
    if(formCalidad) {
        formCalidad.addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            const decision = document.getElementById("decision_calidad").value;
            if (decision === "Rechazado") {
                const rechazados = Array.from(document.querySelectorAll('.chk-calidad:checked')).map(cb => cb.value);
                if (rechazados.length === 0) {
                    Swal.fire('Atención', 'Debe seleccionar al menos un servicio para devolver a reparación.', 'warning');
                    return;
                }
                formData.append('servicios_rechazados', JSON.stringify(rechazados));
            }
            
            fetch("../../modules/Taller/Archivo_Entrega.php?action=procesar_calidad", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) { 
                    Swal.fire('Éxito', data.message, 'success');
                    cerrarModalCalidad(); 
                    listar(); 
                } else { Swal.fire('Acceso Denegado', data.message, 'error'); }
            })
            .catch(err => Swal.fire('Error', 'Error de conexión al procesar calidad.', 'error'));
        });
    }
});

// ==========================================
// NUEVO: MANEJO DE EFECTIVO Y CAMBIO
// ==========================================
function toggleMetodoPagoTaller(metodo) {
    const panelEfectivo = document.getElementById("panel_efectivo");
    const esCredito = document.getElementById("fac_switch_credito").checked;
    
    if (metodo === "1" && !esCredito) {
        panelEfectivo.classList.remove("d-none");
        calcularCambioTaller();
    } else {
        panelEfectivo.classList.add("d-none");
    }
}

function calcularCambioTaller() {
    const recibido = parseFloat(document.getElementById("efectivo_recibido").value) || 0;
    let cambio = recibido - totalFacturaFinalNum;
    const labelCambio = document.getElementById("cambio_devolver");
    
    if (cambio < 0 && recibido > 0) {
        labelCambio.innerText = "Faltan RD$ " + Math.abs(cambio).toLocaleString(undefined, {minimumFractionDigits: 2});
        labelCambio.className = "fw-bold text-danger mb-0";
    } else {
        labelCambio.innerText = "RD$ " + Math.max(0, cambio).toLocaleString(undefined, {minimumFractionDigits: 2});
        labelCambio.className = "fw-bold text-success mb-0";
    }
}

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
                        btnAccion = `<button class="btn btn-sm btn-success fw-bold shadow-sm" onclick="prepararEntrega(${o.id_orden}, '${o.cliente}', '${o.vehiculo}')"><i class="fas fa-key me-1"></i> Entregar Vehículo</button>`;
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
    })
    .catch(err => console.error("Error cargando listado:", err));
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

function abrirAuthOferta() {
    document.getElementById("auth_user").value = "";
    document.getElementById("auth_pass").value = "";
    abrirModalUI('modalAuthAdmin');
}

function validarAccesoOfertas() {
    const user = document.getElementById("auth_user").value;
    const pass = document.getElementById("auth_pass").value;
    const fd = new FormData();
    fd.append('usuario', user);
    fd.append('password', pass);

    fetch(`../../modules/Taller/Archivo_Entrega.php?action=validar_admin`, { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            cerrarModalUI('modalAuthAdmin');
            cargarOfertasVigentes();
        } else { Swal.fire('Error', data.message, 'error'); }
    })
    .catch(err => Swal.fire('Error', 'Error validando administrador.', 'error'));
}

function cargarOfertasVigentes() {
    const contenedor = document.getElementById("lista_ofertas_disponibles");
    contenedor.innerHTML = "";

    fetch(`../../modules/Taller/Archivo_Entrega.php?action=listar_ofertas_vigentes`)
    .then(res => res.json())
    .then(data => {
        if (data.data.length === 0) {
            contenedor.innerHTML = "<div class='p-4 text-center text-muted'>No hay descuentos activos vigentes.</div>";
        } else {
            data.data.forEach(o => {
                contenedor.innerHTML += `
                    <label class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <input class="form-check-input me-3 checkbox-oferta" type="checkbox" value="${o.id_oferta}" data-valor="${o.porciento}">
                            <span class="fw-bold">${o.nombre_oferta}</span>
                        </div>
                        <span class="badge bg-danger shadow-sm">-${parseFloat(o.porciento)}%</span>
                    </label>`;
            });
        }
        abrirModalUI('modalSeleccionOfertas');
    })
    .catch(err => Swal.fire('Error', 'Error cargando ofertas.', 'error'));
}

function aplicarOfertasSeleccionadas() {
    const checks = document.querySelectorAll(".checkbox-oferta:checked");
    ofertasSeleccionadasParaFactura = [];
    descuentoTotalOfertas = 0;

    checks.forEach(check => {
        const porciento = parseFloat(check.getAttribute("data-valor"));
        descuentoTotalOfertas += (subtotalFacturaNum * (porciento / 100));
        ofertasSeleccionadasParaFactura.push(check.value);
    });

    cerrarModalUI('modalSeleccionOfertas');
    renderizarTablaFactura(); 
}

function aplicarOfertasSeleccionadasAutomatico() {
    let subt = 0;
    [...detallesFacturaActual, ...repuestosExtra].forEach(i => { subt += (i.precio * i.cantidad); });
    
    const checks = document.querySelectorAll(".checkbox-oferta:checked");
    descuentoTotalOfertas = 0;
    checks.forEach(check => {
        const porciento = parseFloat(check.getAttribute("data-valor"));
        descuentoTotalOfertas += (subt * (porciento / 100));
    });
    renderizarTablaFactura();
}

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
    
    // Restablecemos el método de pago por defecto (Efectivo)
    const selMetodoPago = document.getElementById('fac_metodo_pago');
    selMetodoPago.value = '1';
    
    document.getElementById('fac_switch_credito').checked = false;
    toggleCreditoTaller(false);

    // Activamos el panel de efectivo por defecto y lo limpiamos
    document.getElementById("panel_efectivo").classList.remove("d-none");
    document.getElementById("efectivo_recibido").value = "";
    document.getElementById("cambio_devolver").innerText = "RD$ 0.00";

    repuestosExtra = [];
    ofertasSeleccionadasParaFactura = [];
    descuentoTotalOfertas = 0;
    
    document.getElementById("buscar_prod_entrega").value = "";
    document.getElementById("cant_prod_entrega").value = 1;
    
    const tbody = document.getElementById("fac_tabla_detalles");
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Cargando detalles de la orden...</td></tr>';
    abrirModalUI('modalFacturacion');

    fetch(`../../modules/Taller/Archivo_Entrega.php?action=obtener_detalle_facturacion&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            detallesFacturaActual = data.data;
            renderizarTablaFactura();
        } else {
            Swal.fire('Error', "Error al cargar los detalles de la orden.", 'error');
            cerrarModalFacturacion();
        }
    })
    .catch(err => {
        Swal.fire('Error', "Error de conexión al obtener detalles.", 'error');
        cerrarModalFacturacion();
    });
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
                const img = p.imagen ? p.imagen : '/Taller/Taller-Mecanica/img/default.png';

                const btn = document.createElement("button");
                btn.className = "list-group-item list-group-item-action d-flex align-items-center gap-3 py-2";
                btn.innerHTML = `
                    <div style="width: 40px; height: 40px;" class="flex-shrink-0">
                        <img src="${img}" class="rounded shadow-sm border w-100 h-100" style="object-fit: cover;">
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
    })
    .catch(err => console.error("Error buscando producto:", err));
}

function agregarRepuestoExtra(p) {
    const cantInput = document.getElementById("cant_prod_entrega");
    const cantAAgregar = parseInt(cantInput.value) || 1;
    const stockDisponible = parseInt(p.stock);

    const itemExistente = repuestosExtra.find(item => item.id === p.id_articulo);

    if (itemExistente) {
        if ((itemExistente.cantidad + cantAAgregar) > stockDisponible) return Swal.fire('Stock insuficiente', "Supera el stock disponible en almacén.", 'error');
        itemExistente.cantidad += cantAAgregar;
    } else {
        if (cantAAgregar > stockDisponible) return Swal.fire('Stock insuficiente', "No hay unidades suficientes en inventario.", 'warning');
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
    
    if (ofertasSeleccionadasParaFactura.length > 0) {
        aplicarOfertasSeleccionadasAutomatico();
    } else {
        renderizarTablaFactura();
    }
}

function eliminarRepuestoExtra(index) {
    repuestosExtra.splice(index, 1);
    if (ofertasSeleccionadasParaFactura.length > 0) {
        aplicarOfertasSeleccionadasAutomatico();
    } else {
        renderizarTablaFactura();
    }
}

function renderizarTablaFactura() {
    const tbody = document.getElementById("fac_tabla_detalles");
    const desgloseCont = document.getElementById("desglose_impuestos_dinamico");
    tbody.innerHTML = "";
    if(desgloseCont) desgloseCont.innerHTML = "";
    
    subtotalFacturaNum = 0;
    const itemsCombinados = [...detallesFacturaActual, ...repuestosExtra];

    if(itemsCombinados.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No hay servicios ni repuestos registrados.</td></tr>`;
    } else {
        itemsCombinados.forEach((d, index) => {
            let subt = parseFloat(d.precio) * parseInt(d.cantidad);
            subtotalFacturaNum += subt;
            
            let btnEliminar = ""; let badgeExtra = "";
            if (d.es_extra) {
                badgeExtra = `<span class="badge bg-warning text-dark ms-1" style="font-size: 9px;">EXTRA</span>`;
                let indexExtra = index - detallesFacturaActual.length;
                btnEliminar = `<button class="btn btn-sm btn-link text-danger p-0 m-0" onclick="eliminarRepuestoExtra(${indexExtra})" title="Remover"><i class="fas fa-times-circle"></i></button>`;
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

    let subtotalConDescuento = subtotalFacturaNum - descuentoTotalOfertas;
    if(subtotalConDescuento < 0) subtotalConDescuento = 0; 

    let totalImpuestos = 0;
    if(listaImpuestosTaller && listaImpuestosTaller.length > 0) {
        listaImpuestosTaller.forEach(imp => {
            let valor = subtotalConDescuento * (parseFloat(imp.porcentaje) / 100);
            totalImpuestos += valor;
            if(desgloseCont) {
                desgloseCont.innerHTML += `
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>${imp.nombre_impuesto} (${imp.porcentaje}%):</span>
                        <span class="text-dark fw-bold">RD$ ${valor.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                    </div>`;
            }
        });
    } else {
        let valor = subtotalConDescuento * 0.18;
        totalImpuestos += valor;
        if(desgloseCont) {
            desgloseCont.innerHTML += `
                <div class="d-flex justify-content-between mb-1 small text-muted">
                    <span>ITBIS (18%):</span>
                    <span class="text-dark fw-bold">RD$ ${valor.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                </div>`;
        }
    }

    totalFacturaFinalNum = subtotalConDescuento + totalImpuestos;
    document.getElementById('fac_subtotal').innerText = `RD$ ${subtotalFacturaNum.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    
    const filaOf = document.getElementById("fila_ofertas");
    if (filaOf) {
        if (descuentoTotalOfertas > 0) {
            filaOf.classList.remove("d-none");
            document.getElementById("ofertas_valor").innerText = `- RD$ ${descuentoTotalOfertas.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        } else {
            filaOf.classList.add("d-none");
        }
    }

    document.getElementById('fac_total_final').innerText = `RD$ ${totalFacturaFinalNum.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
    
    const displayAzul = document.getElementById('azul_monto_display');
    if(displayAzul) displayAzul.innerText = `RD$ ${totalFacturaFinalNum.toLocaleString(undefined, {minimumFractionDigits: 2})}`;

    // Actualizamos el panel de efectivo en tiempo real si está activo
    if (document.getElementById("fac_metodo_pago").value === "1") {
        calcularCambioTaller();
    }
}

function toggleCreditoTaller(checked) {
    const infoCredito = document.getElementById("fac_info_credito");
    const selPago = document.getElementById("fac_metodo_pago");
    const id_cliente = document.getElementById('fac_id_cliente').value;

    if (checked) {
        if (!id_cliente || id_cliente == "0") {
            Swal.fire("Atención", "No se ha podido identificar al cliente de esta orden.", "warning");
            document.getElementById("fac_switch_credito").checked = false;
            return;
        }

        selPago.value = "1";
        selPago.disabled = true;
        infoCredito.classList.remove("d-none");
        document.getElementById("panel_efectivo").classList.add("d-none");
        
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
                Swal.fire("Sin Crédito", data.message, "warning");
                document.getElementById("fac_switch_credito").checked = false;
                toggleCreditoTaller(false);
            }
        })
        .catch(err => console.error("Error consultando crédito:", err));
    } else {
        infoCredito.classList.add("d-none");
        selPago.disabled = false;
        document.getElementById('fac_id_credito').value = '';
        if(selPago.value === "1") {
            document.getElementById("panel_efectivo").classList.remove("d-none");
            calcularCambioTaller();
        }
    }
}

function iniciarCobroOrden() {
    // 1. Verificamos que el efectivo sea suficiente (Si aplica)
    const esCredito = document.getElementById("fac_switch_credito").checked;
    const metodo = document.getElementById("fac_metodo_pago").value;
    let recibido = 0;

    if (!esCredito && metodo === "1") {
        recibido = parseFloat(document.getElementById("efectivo_recibido").value) || 0;
        if (recibido < totalFacturaFinalNum) {
            return Swal.fire("Efectivo Insuficiente", "El cliente debe entregar un monto igual o mayor al total de la orden.", "error");
        }
    }

    // 2. Verificamos que la caja esté abierta
    fetch("../../modules/Taller/Archivo_Entrega.php?action=verificar_caja_abierta")
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            Swal.fire({
                icon: 'warning',
                title: 'Turno Cerrado',
                text: data.message,
                confirmButtonText: 'Ir a Gestión de Caja',
                confirmButtonColor: '#6f42c1', 
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "/Taller/Taller-Mecanica/view/Facturacion/MCaja.php";
                }
            });
            return;
        }

        // Si la caja está abierta, procedemos
        if (esCredito) {
            ejecutarFacturacionFinal(null, true);
        } else if (metodo === "2") { 
            cerrarModalFacturacion();
            abrirModalUI('modalAzulTaller');
        } else {
            ejecutarFacturacionFinal(null, false, recibido);
        }
    })
    .catch(err => Swal.fire('Error', 'Error de comunicación verificando la caja.', 'error'));
}

function procesarAzulTaller() {
    const tarjeta = document.getElementById("azul_tarjeta_taller").value;
    if (tarjeta.length < 15) return Swal.fire("Tarjeta incompleta", "Número de tarjeta inválido.", "warning");

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
                    Swal.fire({
                        title: '¡Transacción Aprobada!',
                        html: `<b>Banco Popular Dominicana</b><br>Referencia: ${res.referencia}`,
                        icon: 'success',
                        confirmButtonColor: '#004481'
                    }).then(() => {
                        ejecutarFacturacionFinal(res.referencia, false);
                        cerrarModalUI('modalAzulTaller');
                        document.getElementById("azul_formulario_taller").classList.remove("d-none");
                        document.getElementById("azul_cargando_taller").classList.add("d-none");
                        document.getElementById("azul_tarjeta_taller").value = "";
                    });
                }, 1500);
            } else {
                Swal.fire("Error Pasarela", res.message, "error");
                document.getElementById("azul_formulario_taller").classList.remove("d-none");
                document.getElementById("azul_cargando_taller").classList.add("d-none");
            }
        })
        .catch(err => {
            Swal.fire("Error", "Error de conexión con la pasarela.", "error");
            document.getElementById("azul_formulario_taller").classList.remove("d-none");
            document.getElementById("azul_cargando_taller").classList.add("d-none");
        });
}

function ejecutarFacturacionFinal(refAzul, esCredito, efectivo_recibido = 0) {
    const data = {
        id_orden: document.getElementById("fac_id_orden").value,
        id_cliente: document.getElementById("fac_id_cliente").value,
        ncf: document.getElementById("fac_ncf").value || 'B0200000001',
        metodo_pago: document.getElementById("fac_metodo_pago").value,
        total_final: totalFacturaFinalNum,
        impuestos_ids: listaImpuestosTaller.map(i => i.id_impuesto), 
        referencia_azul: refAzul,
        es_credito: esCredito,
        id_credito: document.getElementById("fac_id_credito").value,
        repuestos_extra: repuestosExtra,
        ofertas_ids: ofertasSeleccionadasParaFactura,
        acuerdo_pago: esCredito ? window.acuerdoPagoGlobal : null,
        efectivo_recibido: efectivo_recibido
    };

    if (esCredito && (!data.acuerdo_pago || data.acuerdo_pago.length === 0)) {
        Swal.fire("Atención", "No se ha definido un plan de cuotas. Por favor, active el switch de crédito nuevamente.", "warning");
        return;
    }

    fetch("../../modules/Taller/Archivo_Entrega.php?action=guardar_factura_orden", {
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
                cerrarModalFacturacion();
                window.acuerdoPagoGlobal = null;
                imprimirFacturaVoucher(res.id_factura, data);
                listar(); 
            });
        } else {
            Swal.fire("ERROR AL FACTURAR", res.message, "error");
        }
    })
    .catch(err => Swal.fire("Error", "Error de red al intentar facturar.", "error"));
}

function mostrarComprobanteInmediato(id_orden) {
    fetch(`../../modules/Taller/Archivo_Entrega.php?action=obtener_acta&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const d = data.data;
            const setVal = (id, valor) => {
                const elemento = document.getElementById(id);
                if(elemento) elemento.innerText = valor || 'N/A';
            };

            setVal("acta_orden", "ORD-" + d.id_orden);
            setVal("acta_ingreso", d.fecha_ingreso);
            setVal("acta_salida", d.fecha_entrega);
            setVal("acta_usuario", d.entregado_por);
            setVal("acta_cliente", d.cliente);
            setVal("acta_vehiculo", d.vehiculo);
            setVal("acta_placa", d.placa);
            setVal("acta_vin", d.vin_chasis);
            setVal("acta_monto", d.monto_total_fmt);
            
            abrirModalUI('modalComprobante');
        }
    })
    .catch(err => console.error("Error obteniendo acta:", err));
}

// === NUEVA FUNCIÓN DE IMPRESIÓN POR IFRAME PARA EL TALLER ===
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
            <span>Cambio Entregado:</span>
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
            <span>Sub-Total Bruto:</span>
            <span>RD$ ${subtotalFacturaNum.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
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

    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);
    
    iframe.contentDocument.write(htmlFactura);
    iframe.contentDocument.close();

    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(() => document.body.removeChild(iframe), 2000);
    }, 500);
}

function toggleServiciosCalidad() {
    const decision = document.getElementById("decision_calidad").value;
    const cont = document.getElementById("contenedor_servicios_calidad");
    if (decision === "Rechazado") {
        cont.classList.remove("d-none");
    } else {
        cont.classList.add("d-none");
        document.querySelectorAll('.chk-calidad').forEach(cb => cb.checked = false);
    }
}

function abrirModalCalidad(id_orden, vehiculo) {
    document.getElementById("id_orden_calidad").value = id_orden;
    document.getElementById("lbl_calidad_orden").innerText = "ORD-" + id_orden;
    document.getElementById("lbl_calidad_vehiculo").innerText = "Vehículo: " + vehiculo;
    document.getElementById("formCalidad").reset();
    toggleServiciosCalidad(); 

    const lista = document.getElementById("lista_servicios_calidad");
    lista.innerHTML = '<div class="p-2 text-center small text-muted">Cargando servicios...</div>';

    fetch(`../../modules/Taller/Archivo_Entrega.php?action=obtener_servicios_calidad&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(data => {
        lista.innerHTML = "";
        if(data.success && data.data.length > 0) {
            data.data.forEach(s => {
                lista.innerHTML += `
                    <label class="list-group-item d-flex gap-2 align-items-center small py-2" style="cursor: pointer;">
                        <input class="form-check-input flex-shrink-0 chk-calidad" type="checkbox" value="${s.id_asignacion}">
                        <span class="fw-bold">${s.nombre}</span>
                    </label>
                `;
            });
        } else {
            lista.innerHTML = '<div class="p-2 text-center small text-muted">No se encontraron servicios completados.</div>';
        }
    });

    abrirModalUI('modalCalidad');
}

function cargarCatalogoGarantias() {
    fetch('../../modules/Taller/Archivo_Entrega.php?action=obtener_catalogo_politicas')
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            catalogoPoliticasGarantia = res.data;
        }
    });
}

function prepararEntrega(id_orden, cliente, vehiculo) {
    document.getElementById("id_orden_entrega").value = id_orden;
    document.getElementById("lbl_orden").innerText = "ORD-" + id_orden;
    document.getElementById("lbl_cliente").innerText = cliente;
    document.getElementById("lbl_vehiculo").innerText = vehiculo;

    const tbody = document.getElementById('tbody_items_garantia');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-2"></i>Cargando ítems...</td></tr>';
    
    let opcionesHTML = '<option value="">-- Sin Cobertura --</option>';
    catalogoPoliticasGarantia.forEach(pol => {
        opcionesHTML += `<option value="${pol.id_politica}">${pol.nombre} (${pol.tiempo_cobertura} ${pol.unidad_tiempo})</option>`;
    });

    fetch(`../../modules/Taller/Archivo_Entrega.php?action=obtener_items_para_garantia&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(res => {
        tbody.innerHTML = "";
        if(res.success && res.data.length > 0) {
            res.data.forEach(item => {
                const icono = item.tipo === 'servicio' ? '<i class="fas fa-tools text-primary"></i>' : '<i class="fas fa-box-open text-warning"></i>';
                const badge = item.tipo === 'servicio' ? 'Mano de Obra' : 'Repuesto';
                
                tbody.innerHTML += `
                    <tr>
                        <td>${icono} <span class="badge bg-light text-dark border ms-1">${badge}</span></td>
                        <td class="fw-bold">${item.descripcion}</td>
                        <td>
                            <select class="form-select form-select-sm select-garantia-item border-info" 
                                    data-id="${item.id}" data-tipo="${item.tipo}">
                                ${opcionesHTML}
                            </select>
                        </td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No hay ítems facturables en esta orden.</td></tr>';
        }
    });

    abrirModalUI('modalEntrega');
}

function imprimirComprobante() {
    const contenido = document.getElementById('areaImpresionEntrega').innerHTML;
    const htmlActa = `
        <html><head><title>Acta de Entrega</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>body{font-family:Arial;padding:30px;} .text-center{text-align:center;} .border-bottom{border-bottom:2px solid #ddd;padding-bottom:10px;margin-bottom:15px;} .border-top{border-top:1px solid #000;padding-top:10px;margin-top:50px;} .row{display:flex;width:100%;} .col-6{width:50%;float:left;} .card{background:#f8f9fa;padding:20px;border-radius:5px;} p{margin:5px 0;}</style>
        </head><body>${contenido}</body></html>
    `;
    
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);
    
    iframe.contentDocument.write(htmlActa);
    iframe.contentDocument.close();

    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(() => document.body.removeChild(iframe), 2000);
    }, 500); 
}

function cerrarModalFacturacion() { cerrarModalUI('modalFacturacion'); }
function cerrarModalAzul() { 
    cerrarModalUI('modalAzulTaller'); 
    document.getElementById("azul_formulario_taller").classList.remove("d-none");
    document.getElementById("azul_cargando_taller").classList.add("d-none");
}
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

document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'switch_credito') {
        if (e.target.checked) {
            const elTotal = document.getElementById('total_facturar_txt') || 
                            document.getElementById('total_a_cobrar_id') ||
                            document.querySelector('.text-success.fw-bold.h3') ||
                            document.getElementById('fac_total_final'); 
            
            if (elTotal) {
                const montoTexto = elTotal.innerText || elTotal.textContent;
                const montoLimpio = montoTexto.replace(/[^\d.]/g, ''); 
                
                const inputAcuerdo = document.getElementById('total_acuerdo');
                if (inputAcuerdo) {
                    inputAcuerdo.value = "RD$ " + parseFloat(montoLimpio).toLocaleString('en-US', {minimumFractionDigits: 2});
                }
                
                const modalDiv = document.getElementById('modalAcuerdoPago');
                if (modalDiv) {
                    const myModal = new bootstrap.Modal(modalDiv);
                    myModal.show();
                    generarCronograma(); 
                }
            } else {
                Swal.fire("Atención", "Primero debe haber un monto total calculado para habilitar el crédito.", "warning");
                e.target.checked = false; 
            }
        } else {
            window.acuerdoPagoGlobal = null;
        }
    }
});

function gestionarModalCredito(checkbox) {
    if (checkbox.checked) {
        const elTotal = document.getElementById('fac_total_final');
        const inputAcuerdo = document.getElementById('total_acuerdo');
        
        if (elTotal && inputAcuerdo) {
            const montoLimpio = elTotal.innerText.replace(/[^\d.]/g, ''); 
            inputAcuerdo.value = "RD$ " + parseFloat(montoLimpio).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            const modalDiv = document.getElementById('modalAcuerdoPago');
            const myModal = new bootstrap.Modal(modalDiv);
            myModal.show();
            
            generarCronograma(); 
        }
    } else {
        window.acuerdoPagoGlobal = null;
    }
}

function generarCronograma() {
    const inputTotal = document.getElementById('total_acuerdo');
    if (!inputTotal) return;

    const total = parseFloat(inputTotal.value.replace(/[^\d.]/g, ''));
    const cuotas = parseInt(document.getElementById('cant_cuotas').value) || 1;
    const diasFrecuencia = parseInt(document.getElementById('frecuencia_dias').value) || 15;
    const tbody = document.getElementById('lista_cuotas_acuerdo');
    
    if (!tbody) return;

    tbody.innerHTML = '';
    const montoIndividual = (total / cuotas).toFixed(2);
    let fechaBase = new Date();

    for (let i = 1; i <= cuotas; i++) {
        fechaBase.setDate(fechaBase.getDate() + diasFrecuencia);
        const fechaISO = fechaBase.toISOString().split('T')[0];

        tbody.innerHTML += `
            <tr>
                <td class="fw-bold text-primary">${i}</td>
                <td><input type="number" class="form-control form-control-sm cuota-monto" value="${montoIndividual}"></td>
                <td><input type="date" class="form-control form-control-sm cuota-fecha" value="${fechaISO}"></td>
            </tr>`;
    }
}

function confirmarAcuerdo() {
    const montos = document.querySelectorAll('.cuota-monto');
    const fechas = document.querySelectorAll('.cuota-fecha');
    
    const cuotasArray = [];
    montos.forEach((m, i) => {
        cuotasArray.push({
            nro: i + 1,
            monto: parseFloat(m.value),
            fecha: fechas[i].value
        });
    });

    window.acuerdoPagoGlobal = cuotasArray;
    
    const modalEl = document.getElementById('modalAcuerdoPago');
    const instance = bootstrap.Modal.getInstance(modalEl);
    if (instance) instance.hide();
    
    Swal.fire("Acuerdo Confirmado", "Plan de pago guardado correctamente.", "success");
}