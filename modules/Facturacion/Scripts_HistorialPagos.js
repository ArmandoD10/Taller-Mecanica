document.addEventListener("DOMContentLoaded", () => {
    listarHistorial();
});

function listarHistorial() {
    fetch("../../modules/Facturacion/Archivo_HistorialPagos.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaPagos");
        if(!tbody) return;
        tbody.innerHTML = "";

        if (data.success && data.data && data.data.length > 0) {
            data.data.forEach(p => {
                let badgeMetodo = "";
                if(p.metodo_pago === 'Efectivo') badgeMetodo = `<span class="badge bg-success"><i class="fas fa-money-bill-wave me-1"></i> Efectivo</span>`;
                else if(p.metodo_pago === 'Tarjeta') badgeMetodo = `<span class="badge bg-primary"><i class="fas fa-credit-card me-1"></i> Tarjeta</span>`;
                else badgeMetodo = `<span class="badge bg-info text-dark"><i class="fas fa-exchange-alt me-1"></i> Transferencia</span>`;

                const tr = document.createElement("tr");
                tr.className = "fila-pago"; 
                tr.setAttribute("data-fecha", p.fecha_raw || ""); 
                tr.setAttribute("data-monto", p.monto || 0); 

                tr.innerHTML = `
                    <td class="fw-bold text-dark recibo-id">REC-${p.id_abono}</td>
                    <td class="small text-muted">${p.fecha}</td>
                    <td class="fw-bold cliente-nombre">${p.cliente}</td>
                    <td>
                        <span class="text-primary fw-bold factura-id">FAC-${p.id_factura}</span><br>
                        <small class="text-muted orden-id">ORD-${p.id_orden}</small>
                    </td>
                    <td class="text-end fw-bold text-success fs-6">RD$ ${parseFloat(p.monto).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                    <td class="text-center">
                        ${badgeMetodo}<br>
                        <small class="text-muted" style="font-size: 0.7rem;">${p.referencia !== 'N/A' ? 'Ref: '+p.referencia : ''}</small>
                    </td>
                    <td class="small"><i class="fas fa-user-circle text-secondary me-1"></i>${p.cajero}</td>
                    <td class="text-center no-print">
                        <button class="btn btn-sm btn-outline-dark" onclick="reimprimirRecibo(${p.id_abono})" title="Reimprimir Recibo">
                            <i class="fas fa-print"></i> Reimprimir
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Forzamos el recálculo inicial si hay filtros activos
            filtrarTabla();
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">Aún no hay pagos registrados en el sistema.</td></tr>`;
        }
    })
    .catch(err => console.error("Error al listar historial:", err));
}

// ==========================================
// MOTOR DE BÚSQUEDA BLINDADO (Anti-Errores)
// ==========================================
function filtrarTabla() {
    try {
        const elInput = document.getElementById("inputBusqueda");
        const elDesde = document.getElementById("fechaDesde");
        const elHasta = document.getElementById("fechaHasta");

        const inputTexto = elInput ? elInput.value.toUpperCase() : "";
        const fechaDesde = elDesde ? elDesde.value : "";
        const fechaHasta = elHasta ? elHasta.value : "";
        
        const filas = document.getElementsByClassName("fila-pago");
        let encontrados = 0;
        let sumaTotal = 0;

        for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];
            
            // Lectura segura de los datos de la fila
            const cliente = fila.querySelector(".cliente-nombre") ? fila.querySelector(".cliente-nombre").innerText.toUpperCase() : "";
            const factura = fila.querySelector(".factura-id") ? fila.querySelector(".factura-id").innerText.toUpperCase() : "";
            const recibo = fila.querySelector(".recibo-id") ? fila.querySelector(".recibo-id").innerText.toUpperCase() : "";
            const orden = fila.querySelector(".orden-id") ? fila.querySelector(".orden-id").innerText.toUpperCase() : "";
            
            const fechaFila = fila.getAttribute("data-fecha") || "";
            const montoFila = parseFloat(fila.getAttribute("data-monto") || 0);

            // Comprobación de Texto
            let cumpleTexto = (cliente.includes(inputTexto) || factura.includes(inputTexto) || recibo.includes(inputTexto) || orden.includes(inputTexto));
            
            // Comprobación de Fechas
            let cumpleFecha = true;
            if (fechaDesde !== "" && fechaFila < fechaDesde) cumpleFecha = false;
            if (fechaHasta !== "" && fechaFila > fechaHasta) cumpleFecha = false;

            // Mostrar u ocultar
            if (cumpleTexto && cumpleFecha) {
                fila.style.display = "";
                encontrados++;
                sumaTotal += montoFila;
            } else {
                fila.style.display = "none";
            }
        }

        // Actualizar UI de totales
        const status = document.getElementById("statusBusqueda");
        const labelTotal = document.getElementById("totalFiltrado");
        
        if (labelTotal) {
            labelTotal.classList.remove("d-none");
            labelTotal.innerText = `Total Recaudado: RD$ ${sumaTotal.toLocaleString(undefined, {minimumFractionDigits:2})}`;
        }

        if (status) {
            if (inputTexto === "" && fechaDesde === "" && fechaHasta === "") {
                status.innerText = `Mostrando todos los registros (${filas.length}).`;
                status.className = "form-text small text-muted";
            } else {
                status.innerText = `Resultados encontrados: ${encontrados}`;
                status.className = encontrados > 0 ? "form-text small text-success fw-bold" : "form-text small text-danger fw-bold";
            }
        }
    } catch (error) {
        console.error("Error ejecutando el filtro de búsqueda:", error);
    }
}

// ==========================================
// REPORTE MASIVO (Con detector de Pop-ups bloqueados)
// ==========================================
function imprimirReportePagos() {
    try {
        const filas = document.getElementsByClassName("fila-pago");
        let filasImprimir = "";
        let totalSuma = 0;
        let cont = 0;

        for (let i = 0; i < filas.length; i++) {
            if (filas[i].style.display !== "none") {
                const celdas = filas[i].getElementsByTagName("td");
                if (celdas.length >= 7) {
                    const monto = parseFloat(filas[i].getAttribute("data-monto") || 0);
                    totalSuma += monto;
                    cont++;
                    
                    filasImprimir += `
                    <tr>
                        <td>${celdas[0].innerText}</td>
                        <td>${celdas[1].innerText}</td>
                        <td>${celdas[2].innerText}</td>
                        <td>${celdas[3].innerText.replace(/\n/g, ' ')}</td>
                        <td style="text-align: right;">${celdas[4].innerText}</td>
                        <td style="text-align: center;">${celdas[5].innerText.replace(/\n/g, ' ')}</td>
                        <td>${celdas[6].innerText}</td>
                    </tr>`;
                }
            }
        }

        if (cont === 0) return alert("No hay datos en pantalla para imprimir. Ajusta el buscador.");

        // Intentamos abrir la ventana
        const ventana = window.open('', '_blank');
        
        // Si la ventana es NULL, el navegador bloqueó el pop-up
        if (!ventana || ventana.closed || typeof ventana.closed == 'undefined') {
            alert("⚠️ ATENCIÓN: Tu navegador bloqueó la ventana de impresión.\n\nPor favor, busca el icono de 'Pop-ups bloqueados' en la barra de direcciones (arriba a la derecha), selecciona 'Permitir siempre' y vuelve a intentarlo.");
            return;
        }

        ventana.document.write(`
            <html>
            <head>
                <title>Reporte de Ingresos</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h2 { text-align: center; margin-bottom: 5px; }
                    .subtitle { text-align: center; color: #555; margin-bottom: 30px; font-size: 14px; }
                    table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 20px; }
                    th { background-color: #f2f2f2; border: 1px solid #ddd; padding: 8px; text-align: left; }
                    td { border: 1px solid #ddd; padding: 8px; }
                    .total-box { float: right; border: 2px solid #000; padding: 10px; font-size: 18px; font-weight: bold; }
                    .fecha-impresion { font-size: 10px; color: #888; margin-top: 50px; }
                </style>
            </head>
            <body>
                <h2>REPORTE DE INGRESOS Y COBROS</h2>
                <div class="subtitle">Mecánica Automotriz Díaz Pantaleón (SIG)</div>
                
                <table>
                    <thead>
                        <tr>
                            <th>N° Recibo</th>
                            <th>Fecha / Hora</th>
                            <th>Cliente</th>
                            <th>Factura/Orden</th>
                            <th style="text-align: right;">Monto</th>
                            <th style="text-align: center;">Método</th>
                            <th>Cajero</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${filasImprimir}
                    </tbody>
                </table>
                
                <div class="total-box">
                    TOTAL RECAUDADO: RD$ ${totalSuma.toLocaleString(undefined, {minimumFractionDigits:2})}
                </div>
                
                <div style="clear: both;"></div>
                <div class="fecha-impresion">Generado el: ${new Date().toLocaleString()} | Registros: ${cont}</div>
            </body>
            </html>
        `);
        ventana.document.close();
        ventana.focus();
        setTimeout(() => { ventana.print(); ventana.close(); }, 500);

    } catch (error) {
        console.error("Error crítico al intentar generar la impresión:", error);
        alert("Ocurrió un error al preparar la impresión. Por favor, revisa la consola (F12).");
    }
}

// ==========================================
// IMPRESIÓN INDIVIDUAL DE RECIBO (Térmico)
// ==========================================
function reimprimirRecibo(id_abono) {
    fetch(`../../modules/Facturacion/Archivo_HistorialPagos.php?action=obtener_recibo&id_abono=${id_abono}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const r = data.data;
            const ventana = window.open('', '_blank', 'width=350,height=600');
            
            if (!ventana) {
                alert("⚠️ ATENCIÓN: Tu navegador bloqueó la ventana del recibo. Por favor permite las ventanas emergentes.");
                return;
            }

            const htmlRecibo = `
            <html>
            <head>
                <title>Copia de Recibo #${r.id_abono}</title>
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
                    <div style="font-size:12px;">** COPIA DE RECIBO **</div>
                    <div style="font-size:12px;">INGRESO / ABONO</div>
                </div>
                <div class="divider"></div>
                <div><b>Recibo N°:</b> ${r.id_abono}</div>
                <div><b>Fecha:</b> ${r.fecha}</div>
                <div><b>Cliente:</b> ${r.cliente}</div>
                <div><b>Cajero:</b> ${r.cajero}</div>
                <div class="divider"></div>
                <div class="text-center fw-bold" style="margin-bottom:5px;">APLICADO A LA FACTURA FAC-${r.id_factura}</div>
                <div class="row-flex"><span>Total Factura:</span><span>RD$ ${parseFloat(r.monto_total).toLocaleString(undefined,{minimumFractionDigits:2})}</span></div>
                <div class="row-flex text-center" style="margin:10px 0;"><span style="border:1px dashed #000; padding:5px; width:100%; font-size:14px;"><b>MONTO PAGADO:<br>RD$ ${parseFloat(r.monto).toLocaleString(undefined,{minimumFractionDigits:2})}</b></span></div>
                <div class="row-flex"><span>Método Pago:</span><span>${r.metodo_pago}</span></div>
                <div class="row-flex"><span>Referencia:</span><span>${r.referencia}</span></div>
                <div class="divider"></div>
                <div class="row-flex fw-bold" style="font-size:14px;"><span>BALANCE RESTANTE:</span><span>RD$ ${parseFloat(r.balance_restante).toLocaleString(undefined,{minimumFractionDigits:2})}</span></div>
                <div class="divider"></div>
                <div class="text-center" style="font-size:11px;">Documento informativo. Copia generada el ${new Date().toLocaleString()}</div>
            </body>
            </html>`;

            ventana.document.write(htmlRecibo);
            ventana.document.close();
            ventana.focus();
            setTimeout(() => { ventana.print(); ventana.close(); }, 500);
        }
    });
}