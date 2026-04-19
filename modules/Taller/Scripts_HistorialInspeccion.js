let listaHistorial = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarHistorial();
});

function cargarHistorial() {
    fetch('../../modules/Taller/Archivo_Inspeccion.php?action=listar_historial')
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("tbody_historial");
        if (!tbody) return;
        
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            listaHistorial = data.data;
            renderizarTabla(listaHistorial);
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No se encontraron registros previos.</td></tr>';
        }
    })
    .catch(err => console.error("Error cargando historial:", err));
}

function renderizarTabla(datos) {
    const tbody = document.getElementById("tbody_historial");
    if (!tbody) return;
    
    tbody.innerHTML = "";
    
    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No hay inspecciones que coincidan con los filtros.</td></tr>';
        return;
    }

    datos.forEach(i => {
        let badgeEstado = i.estado === 'activo' 
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Activa</span>' 
            : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">${i.estado}</span>`;

        tbody.innerHTML += `
            <tr>
                <td class="ps-4 fw-bold text-muted">#${i.id_inspeccion}</td>
                <td class="small text-dark"><i class="far fa-calendar-alt text-muted me-1"></i> ${i.fecha}</td>
                <td>
                    <div class="fw-bold">${i.vehiculo}</div>
                    <small class="badge bg-dark" style="font-size: 0.65rem;">${i.placa || 'N/A'}</small>
                </td>
                <td class="text-dark small">${i.cliente}</td>
                <td class="text-center">${badgeEstado}</td>
                <td class="text-end pe-4">
                    <button class="btn btn-sm btn-outline-primary shadow-sm fw-bold" onclick="abrirDetalle(${i.id_inspeccion})">
                        <i class="fas fa-eye me-1"></i> Ver Hoja
                    </button>
                </td>
            </tr>
        `;
    });
}

function filtrarTabla() {
    const term = document.getElementById('filtroGeneral').value.toLowerCase().trim();
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    
    const filtrados = listaHistorial.filter(i => {
        // Filtro por texto
        const matchTexto = (i.vehiculo || '').toLowerCase().includes(term) ||
                           (i.placa || '').toLowerCase().includes(term) ||
                           (i.cliente || '').toLowerCase().includes(term) ||
                           (i.id_inspeccion || '').toString().includes(term);
        
        // Filtro por fecha (comparando strings YYYY-MM-DD)
        let matchFecha = true;
        if (desde && i.fecha_db < desde) matchFecha = false;
        if (hasta && i.fecha_db > hasta) matchFecha = false;

        return matchTexto && matchFecha;
    });
    
    renderizarTabla(filtrados);
}

function limpiarFiltros() {
    document.getElementById('filtroGeneral').value = "";
    document.getElementById('fechaDesde').value = "";
    document.getElementById('fechaHasta').value = "";
    renderizarTabla(listaHistorial);
}

function abrirDetalle(id) {
    fetch(`../../modules/Taller/Archivo_Inspeccion.php?action=ver_detalle&id=${id}`)
    .then(res => res.json())
    .then(res => {
        if (!res.success) return;

        const info = res.info;
        
        // Función auxiliar para inyectar texto de forma segura (evita colapsos de JS si falta un ID)
        const setTexto = (id_elemento, texto) => {
            const el = document.getElementById(id_elemento);
            if (el) el.textContent = texto;
        };

        // Llenar metadata
        setTexto('det_id', `#${info.id_inspeccion}`);
        setTexto('det_fecha', info.fecha);
        setTexto('det_asesor', info.asesor);
        setTexto('det_cliente', info.cliente);
        setTexto('det_vehiculo', info.vehiculo);
        setTexto('det_placa', info.placa || 'N/A');
        setTexto('det_km', info.kilometraje_recepcion + " KM/MI");
        setTexto('det_comb', info.nivel_combustible);
        setTexto('det_observacion', info.observacion || 'Ninguna nota adicional.');

        // Llenar trabajos solicitados (Etiquetas azules)
        const contT = document.getElementById('det_trabajos');
        if (contT) {
            contT.innerHTML = '';
            if (res.trabajos && res.trabajos.length > 0) {
                res.trabajos.forEach(t => {
                    contT.innerHTML += `<span class="badge bg-primary px-2 py-1" style="font-size: 10px;">${t.descripcion}</span>`;
                });
            } else {
                contT.innerHTML = '<span class="text-muted small italic">No se especificaron trabajos del catálogo.</span>';
            }
        }

        // Construir checklist dinámico
        renderChecklistSection('table_int', res.checklist, 'Interior');
        renderChecklistSection('table_ext', res.checklist, 'Exterior');
        renderChecklistSection('table_mot', res.checklist, 'Motor');

        // Mostrar Modal de forma segura
        const modalEl = document.getElementById('modalDetalleInspeccion');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    })
    .catch(err => console.error("Error cargando detalle de inspección:", err));
}

function renderChecklistSection(tableId, checklist, categoria) {
    const table = document.getElementById(tableId);
    if (!table) return; // Evita errores si la tabla no existe en el HTML
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    const items = checklist.filter(c => c.categoria === categoria);
    
    items.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td class="text-start">${item.elemento}</td>
                <td class="check-cell ${item.estado === 'B' ? 'active-check' : ''}">${item.estado === 'B' ? 'X' : ''}</td>
                <td class="check-cell ${item.estado === 'F' ? 'active-check' : ''}">${item.estado === 'F' ? 'X' : ''}</td>
                <td class="check-cell ${item.estado === 'D' ? 'active-check' : ''}">${item.estado === 'D' ? 'X' : ''}</td>
            </tr>
        `;
    });
}

function imprimirHoja() {
    const printContent = document.getElementById('hoja_a_ver');
    if (!printContent) return;

    const htmlContent = printContent.innerHTML;
    
    // Abrir ventana temporal de impresión
    const win = window.open('', '_blank');
    win.document.write(`
        <html>
            <head>
                <title>Hoja de Inspección Técnica</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    :root { --form-border-color: #0b2a70; --form-bg-header: #e6edff; }
                    body { padding: 20px; font-family: Arial; }
                    .hoja-preview { border: 1px solid #000; padding: 10px; font-size: 10px; }
                    .preview-title { background: var(--form-border-color); color: white; padding: 3px; font-weight:bold; text-align:center; }
                    .preview-sub-title { background: var(--form-bg-header); border: 1px solid #000; font-weight:bold; text-align:center; }
                    .table-preview { width: 100%; border-collapse: collapse; }
                    .table-preview th, .table-preview td { border: 1px solid #000; padding: 2px; }
                    .active-check { background: #ffff99 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .img-ref { max-width: 100%; height: 80px; object-fit: contain; }
                </style>
            </head>
            <body>${htmlContent}</body>
        </html>
    `);
    win.document.close();
    setTimeout(() => { win.print(); win.close(); }, 500);
}