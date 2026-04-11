let filtroActual = 'todas'; 

document.addEventListener("DOMContentLoaded", () => {
    cargarReporte(); 
});

document.getElementById("form_filtros_ventas").addEventListener("submit", function(e) {
    e.preventDefault();
    cargarReporte();
});

function cambiarTab(tipo) {
    filtroActual = tipo;
    
    const titulos = {
        'todas': 'Mostrando: Todas las Ventas Registradas',
        'pos': 'Mostrando: Ventas POS (Mostrador)',
        'taller': 'Mostrando: Órdenes de Servicio (Taller)',
        'credito': 'Mostrando: Facturas a Crédito (Pendientes de Cobro)'
    };
    document.getElementById('lbl_titulo_tabla').innerText = titulos[tipo];
    
    // Limpiar buscador al cambiar de pestaña
    document.getElementById("buscador_cliente").value = "";
    
    cargarReporte();
}

function cargarReporte() {
    const form = document.getElementById("form_filtros_ventas");
    const formData = new FormData(form);
    formData.append('tipo_filtro', filtroActual); 

    const tbody = document.getElementById("cuerpoTablaVentas");
    tbody.innerHTML = `<tr><td colspan="7" class="py-5 text-muted fw-bold"><i class="fas fa-spinner fa-spin me-2"></i>Analizando datos...</td></tr>`;

    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_ReporteVentas.php?action=generar_reporte", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        
        if (data.success && data.data.length > 0) {
            document.getElementById('lbl_total_facturas').innerText = data.resumen.total_facturas;
            document.getElementById('lbl_monto_total').innerText = `RD$ ${parseFloat(data.resumen.total_monto).toLocaleString(undefined, {minimumFractionDigits: 2})}`;

            data.data.forEach(f => {
                let badgeEstado = f.estado_pago === 'Pagado' ? "bg-success" : "bg-danger";
                
                // CAMBIO 1 LÓGICO EN JS PARA COLORES
                let badgeOrigen = f.origen === 'Venta POS' ? "bg-info text-dark" : "bg-warning text-dark";

                const tr = document.createElement("tr");
                tr.className = "fila-venta"; // Clase agregada para el buscador
                tr.innerHTML = `
                    <td class="fw-bold text-dark">FAC-${f.id_factura}</td>
                    <td class="small">${f.fecha}</td>
                    <td class="text-start fw-bold text-truncate nombre-cliente-celda" style="max-width: 200px;">${f.cliente}</td>
                    <td class="small text-muted">${f.ncf || 'N/A'}</td>
                    <td><span class="badge ${badgeOrigen}">${f.origen}</span></td>
                    <td><span class="badge ${badgeEstado}">${f.estado_pago}</span></td>
                    <td class="text-end fw-bold text-primary pe-4">RD$ ${parseFloat(f.monto_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(tr);
            });
            
            // Reaplicar filtro por si alguien escribe mientras carga
            filtrarPorCliente();
            
        } else {
            document.getElementById('lbl_total_facturas').innerText = "0";
            document.getElementById('lbl_monto_total').innerText = "RD$ 0.00";
            tbody.innerHTML = `<tr><td colspan="7" class="py-5 text-muted fw-bold text-center">No se encontraron ventas para este filtro y fecha.</td></tr>`;
        }
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="7" class="py-5 text-danger fw-bold text-center"><i class="fas fa-exclamation-triangle me-2"></i>Error de conexión. Revisa la consola.</td></tr>`;
    });
}

// CAMBIO 2: FUNCIÓN PARA FILTRAR POR CLIENTE EN TIEMPO REAL
function filtrarPorCliente() {
    const textoBusqueda = document.getElementById("buscador_cliente").value.toLowerCase();
    const filas = document.querySelectorAll(".fila-venta");

    filas.forEach(fila => {
        const nombreCliente = fila.querySelector(".nombre-cliente-celda").textContent.toLowerCase();
        if (nombreCliente.includes(textoBusqueda)) {
            fila.style.display = "";
        } else {
            fila.style.display = "none";
        }
    });
}

function imprimirTabla() {
    const divToPrint = document.getElementById("tabla_ventas_print");
    const titulo = document.getElementById('lbl_titulo_tabla').innerText;
    const fechaIn = document.getElementById('fecha_inicio').value;
    const fechaOut = document.getElementById('fecha_fin').value;
    const totalDinero = document.getElementById('lbl_monto_total').innerText;

    const newWin = window.open("");
    newWin.document.write(`
        <html>
            <head>
                <title>Reporte de Ventas</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h2 { text-align: center; color: #333; margin-bottom: 5px;}
                    .sub-header { text-align: center; font-size: 14px; color: #666; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                    th { background-color: #f4f4f4; }
                    .resumen { text-align: right; margin-top: 20px; font-size: 16px; font-weight: bold; border-top: 2px solid #000; padding-top: 10px; }
                </style>
            </head>
            <body>
                <h2>MECÁNICA DÍAZ & PANTALEÓN</h2>
                <div class="sub-header">
                    <b>${titulo}</b><br>
                    Período: ${fechaIn} al ${fechaOut}
                </div>
                ${divToPrint.outerHTML}
                <div class="resumen">TOTAL ACUMULADO: ${totalDinero}</div>
            </body>
        </html>
    `);
    newWin.document.close();
    setTimeout(() => { newWin.print(); newWin.close(); }, 500);
}