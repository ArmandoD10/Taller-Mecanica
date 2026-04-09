let datosReporteActual = [];
let resumenActual = { total_ventas: 0, cantidad: 0 };

document.addEventListener("DOMContentLoaded", () => {
    // En lugar de simular un evento submit (que causa el bucle infinito),
    // llamamos a la función de cargar datos directamente.
    ejecutarReporte();
});

document.getElementById("form_filtros").addEventListener("submit", function(e) {
    e.preventDefault(); // Detiene la recarga de la página
    ejecutarReporte();
});

function ejecutarReporte() {
    const form = document.getElementById("form_filtros");
    const formData = new FormData(form);
    
    const btn = form.querySelector('button[type="submit"]');
    const txtOriginal = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
    btn.disabled = true;

    fetch("../../modules/Facturacion/Archivo_ReporteVentas.php?action=generar_reporte", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = txtOriginal;
        btn.disabled = false;

        const tbody = document.getElementById("cuerpoReporte");
        tbody.innerHTML = "";

        if (data.success) {
            datosReporteActual = data.data;
            resumenActual = data.resumen;

            // Actualizar tarjetas
            document.getElementById("lbl_total_ventas").innerText = `RD$ ${parseFloat(resumenActual.total_ventas).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
            document.getElementById("lbl_cantidad_facturas").innerText = resumenActual.cantidad;

            // Llenar tabla
            if (datosReporteActual.length > 0) {
                datosReporteActual.forEach(f => {
                    let badgeClase = "bg-success";
                    if (f.estado === 'inactivo' || f.estado_pago === 'Cancelado') {
                        badgeClase = "bg-danger";
                    }

                    const isCancelada = (f.estado === 'inactivo' || f.estado_pago === 'Cancelado');
                    const filaEstilo = isCancelada ? 'text-decoration-line-through text-muted' : '';

                    const tr = document.createElement("tr");
                    tr.className = filaEstilo;
                    tr.innerHTML = `
                        <td class="fw-bold">FAC-${f.id_factura}</td>
                        <td class="fw-bold text-primary">${f.ncf}</td>
                        <td class="small">${f.fecha}</td>
                        <td class="text-start">${f.cliente}</td>
                        <td>${f.rnc_cedula}</td>
                        <td><span class="badge ${badgeClase}">${f.estado_pago || f.estado}</span></td>
                        <td class="text-end pe-4 fw-bold">RD$ ${parseFloat(f.monto_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="py-5 text-muted text-center fw-bold">No se encontraron ventas en este período.</td></tr>`;
            }
        } else {
            alert("Error: " + data.message);
            tbody.innerHTML = `<tr><td colspan="7" class="py-5 text-danger text-center">Error al cargar datos.</td></tr>`;
        }
    })
    .catch(err => {
        btn.innerHTML = txtOriginal;
        btn.disabled = false;
        console.error(err);
        alert("Error de conexión con el servidor.");
    });
}

function imprimirReporte() {
    if (datosReporteActual.length === 0) {
        return alert("No hay datos generados para imprimir. Por favor, genere un reporte primero.");
    }

    const fechaIni = document.getElementById("fecha_inicio").value;
    const fechaFin = document.getElementById("fecha_fin").value;

    let htmlFilas = "";
    datosReporteActual.forEach(f => {
        const isCancelada = (f.estado === 'inactivo' || f.estado_pago === 'Cancelado');
        const colorFila = isCancelada ? 'color: red;' : '';
        const estadoTexto = isCancelada ? '(ANULADA)' : '';

        htmlFilas += `
            <tr style="${colorFila}">
                <td>FAC-${f.id_factura}</td>
                <td>${f.ncf}</td>
                <td>${f.fecha}</td>
                <td>${f.cliente}</td>
                <td>${f.rnc_cedula}</td>
                <td style="text-align: right;">RD$ ${parseFloat(f.monto_total).toLocaleString(undefined, {minimumFractionDigits: 2})} ${estadoTexto}</td>
            </tr>
        `;
    });

    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
        <head>
            <title>Reporte de Ventas por NCF</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .header h2 { margin: 0 0 5px 0; }
                .header p { margin: 0; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f4f4f4; font-weight: bold; text-transform: uppercase; }
                .resumen { display: flex; justify-content: flex-end; margin-top: 20px; }
                .resumen-box { border: 1px solid #000; padding: 15px; width: 300px; }
                .resumen-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>REPORTE GENERAL DE VENTAS Y NCF</h2>
                <p>Mecánica Automotriz Díaz Pantaleón</p>
                <p>Período: ${fechaIni} hasta ${fechaFin}</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>NCF</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>RNC / Cédula</th>
                        <th style="text-align: right;">Monto Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${htmlFilas}
                </tbody>
            </table>

            <div class="resumen">
                <div class="resumen-box">
                    <div class="resumen-row">
                        <span>Total Facturas Válidas:</span>
                        <span>${resumenActual.cantidad}</span>
                    </div>
                    <div class="resumen-row" style="border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; font-size: 16px;">
                        <span>TOTAL VENTAS:</span>
                        <span>RD$ ${parseFloat(resumenActual.total_ventas).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            </div>
            
            <p style="text-align: center; font-size: 10px; margin-top: 50px; color: #999;">
                Reporte generado el ${new Date().toLocaleString()} por el Sistema Integrado de Gestión (SIG).
            </p>
        </body>
        </html>
    `);
    
    ventana.document.close();
    ventana.focus();
    // Pequeño delay para asegurar que el HTML renderizó antes de lanzar el menú de impresión
    setTimeout(() => { ventana.print(); }, 500);
}