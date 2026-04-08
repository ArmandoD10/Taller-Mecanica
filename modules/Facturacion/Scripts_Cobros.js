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
                tr.setAttribute("data-fecha", p.fecha_raw); // Fecha oculta YYYY-MM-DD para el filtro
                tr.setAttribute("data-monto", p.monto); // Monto oculto para la sumatoria

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
            
            document.getElementById("inputBusqueda").value = "";
            document.getElementById("fechaDesde").value = "";
            document.getElementById("fechaHasta").value = "";
            filtrarTabla(); // Forzamos el cálculo inicial
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">Aún no hay pagos registrados en el sistema.</td></tr>`;
        }
    })
    .catch(err => console.error("Error al listar historial:", err));
}

// ==========================================
// MOTOR DE BÚSQUEDA Y FILTRADO DE FECHAS
// ==========================================
function filtrarTabla() {
    const inputTexto = document.getElementById("inputBusqueda").value.toUpperCase();
    const fechaDesde = document.getElementById("fechaDesde").value;
    const fechaHasta = document.getElementById("fechaHasta").value;
    
    const filas = document.getElementsByClassName("fila-pago");
    let encontrados = 0;
    let sumaTotal = 0;

    for (let i = 0; i < filas.length; i++) {
        const cliente = filas[i].querySelector(".cliente-nombre").innerText.toUpperCase();
        const factura = filas[i].querySelector(".factura-id").innerText.toUpperCase();
        const recibo = filas[i].querySelector(".recibo-id").innerText.toUpperCase();
        const orden = filas[i].querySelector(".orden-id").innerText.toUpperCase();
        
        const fechaFila = filas[i].getAttribute("data-fecha");
        const montoFila = parseFloat(filas[i].getAttribute("data-monto"));

        // Comprobación de Texto
        let cumpleTexto = (cliente.indexOf(inputTexto) > -1 || factura.indexOf(inputTexto) > -1 || recibo.indexOf(inputTexto) > -1 || orden.indexOf(inputTexto) > -1);
        
        // Comprobación de Rango de Fechas
        let cumpleFecha = true;
        if (fechaDesde !== "" && fechaFila < fechaDesde) cumpleFecha = false;
        if (fechaHasta !== "" && fechaFila > fechaHasta) cumpleFecha = false;

        // Mostrar u ocultar fila
        if (cumpleTexto && cumpleFecha) {
            filas[i].style.display = "";
            encontrados++;
            sumaTotal += montoFila;
        } else {
            filas[i].style.display = "none";
        }
    }

    // Actualizar Textos
    const status = document.getElementById("statusBusqueda");
    const labelTotal = document.getElementById("totalFiltrado");
    
    labelTotal.classList.remove("d-none");
    labelTotal.innerText = `Total Recaudado: RD$ ${sumaTotal.toLocaleString(undefined, {minimumFractionDigits:2})}`;

    if (inputTexto === "" && fechaDesde === "" && fechaHasta === "") {
        status.innerText = `Mostrando todos los registros (${filas.length}).`;
        status.className = "form-text small text-muted";
    } else {
        status.innerText = `Resultados encontrados: ${encontrados}`;
        status.className = encontrados > 0 ? "form-text small text-success fw-bold" : "form-text small text-danger fw-bold";
    }
}

// ==========================================
// REPORTE MASIVO DE INGRESOS (Imprimir Filtro)
// ==========================================
function imprimirReportePagos() {
    const filas = document.getElementsByClassName("fila-pago");
    let filasImprimir = "";
    let totalSuma = 0;
    let cont = 0;

    for (let i = 0; i < filas.length; i++) {
        if (filas[i].style.display !== "none") {
            const celdas = filas[i].getElementsByTagName("td");
            const monto = parseFloat(filas[i].getAttribute("data-monto"));
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

    if (cont === 0) return alert("No hay datos visibles para imprimir.");

    const ventana = window.open('', '_blank');
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

            const ventana = window.open('', '_blank', 'width=350,height=600');
            ventana.document.write(htmlRecibo);
            ventana.document.close();
            ventana.focus();
            setTimeout(() => { ventana.print(); ventana.close(); }, 500);
        }
    });
}