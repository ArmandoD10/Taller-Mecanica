console.log("Scripts_Historial.js: Módulo de historial cargado.");

let listaHistorialGeneral = [];

document.addEventListener("DOMContentLoaded", () => {
    listar();
});

// ==========================================
// LISTADO GENERAL DEL HISTORIAL Y FILTROS
// ==========================================
function listar() {
    fetch("../../modules/Taller/Archivo_Historial.php?action=listar")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            listaHistorialGeneral = data.data;
            renderizarTablaHistorial(listaHistorialGeneral);
        } else {
            document.getElementById("cuerpoTablaHistorial").innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No hay registros en el historial.</td></tr>`;
        }
    })
    .catch(err => console.error("Error listar historial:", err));
}

function renderizarTablaHistorial(datos) {
    const tbody = document.getElementById("cuerpoTablaHistorial");
    if(!tbody) return;
    tbody.innerHTML = "";

    if (datos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">No se encontraron resultados para su búsqueda.</td></tr>`;
        return;
    }

    datos.forEach(o => {
        let badgeOrden = "";
        switch(o.estado_orden) {
            case 'Entregado': badgeOrden = `<span class="badge bg-success">Entregado</span>`; break;
            case 'Listo': badgeOrden = `<span class="badge bg-primary">Listo</span>`; break;
            case 'Control Calidad': badgeOrden = `<span class="badge bg-info text-dark">Control Calidad</span>`; break;
            case 'Reparación': badgeOrden = `<span class="badge bg-warning text-dark">Reparación</span>`; break;
            case 'Diagnóstico': badgeOrden = `<span class="badge bg-secondary">Diagnóstico</span>`; break;
            default: badgeOrden = `<span class="badge bg-dark">${o.estado_orden}</span>`; break;
        }

        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td class="fw-bold text-primary ps-3">ORD-${o.id_orden}</td>
            <td>${o.fecha_fmt}</td>
            <td>${o.cliente}</td>
            <td class="fw-bold">${o.vehiculo}</td>
            <td><span class="badge bg-secondary">${o.placa}</span></td>
            <td>${badgeOrden}</td>
            <td class="fw-bold">${o.monto_total_fmt}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="verDetalles(${o.id_orden})" title="Ver Detalles">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function filtrarTabla() {
    const term = document.getElementById('filtroGeneral').value.toLowerCase().trim();
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    
    const filtrados = listaHistorialGeneral.filter(o => {
        const matchTexto = (o.vehiculo || '').toLowerCase().includes(term) ||
                           (o.placa || '').toLowerCase().includes(term) ||
                           (o.cliente || '').toLowerCase().includes(term) ||
                           (o.id_orden || '').toString().includes(term);
        
        let matchFecha = true;
        if (desde && o.fecha_db < desde) matchFecha = false;
        if (hasta && o.fecha_db > hasta) matchFecha = false;

        return matchTexto && matchFecha;
    });
    
    renderizarTablaHistorial(filtrados);
}

function limpiarFiltros() {
    document.getElementById('filtroGeneral').value = "";
    document.getElementById('fechaDesde').value = "";
    document.getElementById('fechaHasta').value = "";
    renderizarTablaHistorial(listaHistorialGeneral);
}

// ==========================================
// OBTENER Y MOSTRAR DETALLES (RADIOGRAFÍA)
// ==========================================
function verDetalles(id_orden) {
    // 1. Petición al servidor
    fetch(`../../modules/Taller/Archivo_Historial.php?action=obtener_detalles&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Desestructuración de los datos recibidos
            const d = data.data.cabecera;
            const trabajos = data.data.trabajos;
            const servicios = data.data.servicios;
            const repuestos = data.data.repuestos;

            // 2. Llenar Cabecera del Modal
            document.getElementById("lbl_id_orden_modal").innerText = "ORD-" + d.id_orden;
            document.getElementById("lbl_estado_orden_modal").innerText = d.estado_orden.toUpperCase();
            document.getElementById("lbl_fecha_orden_modal").innerText = d.fecha_fmt;
            
            // Mostrar la sucursal (Asegúrate de tener este ID en tu HTML)
            const elSucursal = document.getElementById("det_sucursal_nombre");
            if(elSucursal) {
                elSucursal.innerText = d.sucursal_nombre || "No especificada";
            }

            // Datos del Cliente
            document.getElementById("det_cliente").innerText = d.cliente;
            document.getElementById("det_cedula").innerText = d.cedula || 'N/A';
            document.getElementById("det_telefono").innerText = d.telefono || 'N/A'; 

            // Datos del Vehículo
            document.getElementById("det_vehiculo").innerText = d.vehiculo;
            document.getElementById("det_placa").innerText = d.placa || 'N/A';
            document.getElementById("det_vin").innerText = d.vin_chasis || 'N/A';
            document.getElementById("det_km").innerText = d.kilometraje + " km";
            
            // Total
            document.getElementById("det_total").innerText = d.monto_total_fmt;

            // 3. Llenar Trabajos Solicitados (Badges)
            const divTrabajos = document.getElementById("det_trabajos");
            divTrabajos.innerHTML = "";
            if(trabajos && trabajos.length > 0) {
                trabajos.forEach(t => {
                    divTrabajos.innerHTML += `
                        <span class="badge bg-info text-dark shadow-sm px-3 py-2">
                            <i class="fas fa-check-circle me-1 opacity-75"></i> ${t.descripcion}
                        </span>`;
                });
            } else {
                divTrabajos.innerHTML = '<span class="text-muted small italic">No se indicaron motivos específicos.</span>';
            }

            // 4. Llenar Tabla de Servicios (Mano de Obra)
            const tbodyS = document.getElementById("cuerpoServiciosDetalle");
            tbodyS.innerHTML = "";
            if(servicios && servicios.length > 0) {
                servicios.forEach(s => {
                    let estadoServ = s.estado_asignacion === 'Completado' 
                        ? '<i class="fas fa-check-circle text-success" title="Completado"></i>' 
                        : '<i class="fas fa-clock text-warning" title="Pendiente/En Curso"></i>';
                    
                    const notas = s.notas_hallazgos ? s.notas_hallazgos : '<em class="text-muted">Sin notas</em>';

                    tbodyS.innerHTML += `
                        <tr>
                            <td class="fw-bold ps-2">${estadoServ} ${s.servicio}</td>
                            <td><small>${s.mecanicos || 'Ninguno'}</small></td>
                            <td><small>${s.hora_inicio || '--:--'}</small></td>
                            <td><small>${s.hora_fin || '--:--'}</small></td>
                            <td style="max-width: 250px; word-wrap: break-word;"><small>${notas}</small></td>
                        </tr>
                    `;
                });
            } else {
                tbodyS.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No hay servicios asociados.</td></tr>`;
            }

            // 5. Llenar Tabla de Repuestos
            const tbodyR = document.getElementById("cuerpoRepuestosDetalle");
            tbodyR.innerHTML = "";
            if(repuestos && repuestos.length > 0) {
                repuestos.forEach(r => {
                    const pUnit = parseFloat(r.precio_base).toFixed(2);
                    const subT = parseFloat(r.subtotal).toFixed(2);
                    tbodyR.innerHTML += `
                        <tr>
                            <td class="fw-bold ps-2 text-secondary">${r.nombre}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">${r.cantidad}</span></td>
                            <td class="text-end">$${pUnit}</td>
                            <td class="text-end fw-bold pe-2">$${subT}</td>
                        </tr>
                    `;
                });
            } else {
                tbodyR.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No se utilizaron repuestos.</td></tr>`;
            }

            // 6. Abrir la interfaz del modal
            abrirModalUI('modalDetalleOrden');

        } else {
            alert("Error al cargar detalles: " + (data.message || "Error desconocido"));
        }
    })
    .catch(err => {
        console.error("Error ver detalles:", err);
        alert("Hubo un fallo en la conexión con el servidor.");
    });
}

// ==========================================
// UTILIDADES: MODAL E IMPRESIÓN
// ==========================================
function imprimirReporte() {
    const contenido = document.getElementById('areaImpresion').innerHTML;
    const ventana = window.open('', '_blank', 'width=900,height=700');
    ventana.document.write(`
        <html>
            <head>
                <title>Reporte de Servicio</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
                    .card { border: 1px solid #000 !important; margin-bottom: 15px; page-break-inside: avoid; }
                    .card-header { background-color: #f8f9fa !important; border-bottom: 1px solid #000 !important; color: #000 !important; font-weight: bold; padding: 5px 10px; }
                    .table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
                    .table th, .table td { border: 1px solid #ccc; padding: 5px; }
                    .table th { background-color: #eee !important; -webkit-print-color-adjust: exact; }
                    .badge { border: 1px solid #000; color: #000 !important; background: transparent !important; padding: 2px 5px; }
                </style>
            </head>
            <body>
                ${contenido}
            </body>
        </html>
    `);
    ventana.document.close();
    ventana.focus();
    setTimeout(() => { ventana.print(); ventana.close(); }, 500);
}

function cerrarModalDetalle() { cerrarModalUI('modalDetalleOrden'); }

function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try {
        if (typeof bootstrap !== 'undefined') {
            let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
            m.show();
        } else { throw new Error(); }
    } catch (e) {
        el.classList.add('show'); el.style.display = 'block';
        el.setAttribute('aria-modal', 'true'); el.setAttribute('role', 'dialog');
        document.body.classList.add('modal-open'); document.body.style.overflow = 'hidden';
        if(!document.getElementById('m-bd-hist')){
            const b = document.createElement('div'); b.id = 'm-bd-hist'; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
        }
    }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { if (typeof bootstrap !== 'undefined') { let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); } } catch (e) {}
    el.classList.remove('show'); el.style.display = 'none';
    el.removeAttribute('aria-modal'); el.removeAttribute('role');
    document.body.classList.remove('modal-open'); document.body.style.overflow = '';
    const b = document.getElementById('m-bd-hist'); if(b) b.remove();
    document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
}


// Añadir al final de Scripts_Historial.js

window.generarReporteHistorialPDF = function() {
    fetch("../../modules/Taller/Archivo_Historial.php?action=reporte_pdf")
    .then(r => r.json())
    .then(res => {
        if (!res.success) return alert("Error al obtener los datos del historial.");

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); // 'l' para horizontal

        const img = new Image();
        img.src = "/Taller/Taller-Mecanica/img/logo.png"; 
        
        img.onload = function() {
            // Encabezado
            doc.addImage(img, 'PNG', 235, 10, 45, 22);
            doc.setFontSize(18);
            doc.setTextColor(13, 71, 161);
            doc.text("HISTORIAL GENERAL DE SERVICIOS", 14, 22);
            
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text("Mecánica Automotriz Díaz & Pantaleón", 14, 28);
            doc.text(`Reporte generado el: ${new Date().toLocaleString()}`, 14, 33);

            // Mapear datos para la tabla
            const filas = res.data.map(o => [
                o.id_orden,
                o.fecha_fmt,
                o.vehiculo,
                o.placa,
                o.cliente,
                o.estado_orden.toUpperCase(),
                "RD$ " + parseFloat(o.monto_total).toLocaleString('en-US', {minimumFractionDigits: 2})
            ]);

            // Generar Tabla
            doc.autoTable({
                startY: 40,
                head: [['N° Orden', 'Fecha', 'Vehículo', 'Placa', 'Cliente', 'Estado', 'Monto Total']],
                body: filas,
                headStyles: { fillColor: [13, 71, 161] },
                alternateRowStyles: { fillColor: [240, 240, 240] },
                styles: { fontSize: 8, cellPadding: 2 },
                columnStyles: {
                    6: { halign: 'right', fontStyle: 'bold' } // Alinear monto a la derecha
                }
            });

            doc.save(`Historial_Servicios_DP.pdf`);
        };

        img.onerror = function() {
            console.error("No se pudo cargar el logo.");
            alert("No se encontró el logo institucional, el reporte se generará solo con texto.");
            // Aquí podrías disparar la generación sin imagen si lo deseas
        };
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Ocurrió un error al conectar con el servidor.");
    });
};