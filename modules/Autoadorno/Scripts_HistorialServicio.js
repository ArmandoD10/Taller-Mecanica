document.addEventListener('DOMContentLoaded', () => {
    // Al cargar la página, los inputs están vacíos, por lo que traerá todo el historial
    listarOrdenesRecientes();
    aplicarFiltrosHistorial(); // Carga inicial sin filtros para mostrar todo el historial
});

function cargarHistorialDetailing() {
    // Obtener valores de los inputs (pueden estar vacíos)
    const idInput = document.getElementById('f_id_orden');
    const inicioInput = document.getElementById('f_fecha_inicio');
    const finInput = document.getElementById('f_fecha_fin');

    const id = idInput ? idInput.value : '';
    const inicio = inicioInput ? inicioInput.value : '';
    const fin = finInput ? finInput.value : '';

    const tbody = document.getElementById('tabla_historial_detailing');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando historial...</td></tr>';

    // Construcción de la URL con parámetros
    const params = new URLSearchParams({
        action: 'consultar_historial_detailing',
        id_orden: id,
        fecha_inicio: inicio,
        fecha_fin: fin
    });

    fetch(`/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Servicio.php?${params.toString()}`)
    .then(r => r.json())
    .then(res => {
        if (!res.success || !Array.isArray(res.data) || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4 text-muted">No se encontraron registros de Autoadorno.</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map(o => `
            <tr>
                <td class="fw-bold text-primary">ORD-${o.id_orden}</td>
                <td><small class="fw-bold text-dark">${o.descripcion}</small></td>
                <td><i class="fas fa-user-circle text-muted me-1"></i> ${o.mecanico}</td>
                <td><span class="badge rounded-pill bg-success px-3">${o.estado}</span></td>
                <td><small class="text-muted">${o.fecha}</small></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-dark shadow-sm" onclick="verDetalleProductos(${o.id_orden})">
                        <i class="fas fa-eye me-1"></i> Detalle
                    </button>
                </td>
            </tr>
        `).join('');
    })
    .catch(err => {
        console.error("Error:", err);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4 text-danger">Error al cargar datos del servidor.</td></tr>';
    });
}

function verDetalleProductos(id) {
    fetch(`/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_HistorialServicio.php?action=detalle_orden_historial&id_orden=${id}`)
    .then(r => r.json()).then(res => {
        let html = '<div class="list-group list-group-flush">';
        if(res.repuestos.length > 0){
            res.repuestos.forEach(r => {
                const img = r.imagen ? `${r.imagen}` : '../../img/no-image.png';
                html += `
                    <div class="list-group-item d-flex align-items-center p-3">
                        <img src="${img}" class="rounded border me-3" style="width:50px; height:50px; object-fit:cover;">
                        <div class="flex-grow-1">
                            <div class="fw-bold small">${r.nombre}</div>
                            <small class="text-muted">Cantidad: ${r.cantidad}</small>
                        </div>
                        <div class="fw-bold">RD$ ${parseFloat(r.precio).toLocaleString()}</div>
                    </div>`;
            });
        } else {
            html += '<div class="p-3 text-center text-muted">No hay insumos registrados.</div>';
        }
        html += '</div>';
        
        document.getElementById('body_detalle_servicio').innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('modalDetalleServicio'));
        modal.show();
    });
}

// 1. Corregir la carga inicial de recientes
function listarOrdenesRecientes() {
    fetch('/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_HistorialServicio.php?action=listar_ordenes')
        .then(r => r.json())
        .then(res => {
            const tbody = document.getElementById('tabla_ordenes_recientes');
            if (!res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay órdenes recientes.</td></tr>';
                return;
            }
            tbody.innerHTML = res.data.map(o => `
                <tr>
                    <td><span class="fw-bold text-primary">ORD-${o.id_orden}</span></td>
                    <td><small class="fw-bold">${o.descripcion}</small></td>
                    <td>RD$ ${parseFloat(o.monto_total).toLocaleString()}</td>
                    <td><span class="badge bg-success">${o.estado}</span></td>
                    <td><small>${o.fecha_creacion}</small></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-dark" onclick="verDetalleProductos(${o.id_orden})">
                            <i class="fas fa-eye"></i> Detalle
                        </button>
                    </td>
                </tr>
            `).join('');
        });
}

// 2. Corregir el Filtro para que use el archivo de Historial y no el de Servicio
document.addEventListener('DOMContentLoaded', () => {
    // Solo llamar a la función principal al cargar
    aplicarFiltrosHistorial(); 
});

function aplicarFiltrosHistorial() {
    const id = document.getElementById('f_id_orden').value.trim();
    const inicio = document.getElementById('f_fecha_inicio').value;
    const fin = document.getElementById('f_fecha_fin').value;
    const tbody = document.getElementById('tabla_ordenes_recientes');

    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4">Filtrando historial...</td></tr>';

    // Construcción limpia de la URL
    let url = `/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_HistorialServicio.php?action=filtrar_historial`;
    
    if (id !== "") {
        url += `&id_orden=${id}`;
    } else if (inicio !== "" && fin !== "") {
        url += `&f_inicio=${inicio}&f_fin=${fin}`;
    }

    fetch(url)
    .then(r => r.json())
    .then(res => {
        if (!res.success || !res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4 text-muted">No se encontraron registros en este rango de fechas.</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map(o => `
            <tr>
                <td class="fw-bold text-primary">ORD-${o.id_orden}</td>
                <td><small class="fw-bold">${o.descripcion}</small></td>
                <td>RD$ ${parseFloat(o.monto_total).toLocaleString()}</td>
                <td><span class="badge ${o.estado === 'Entregado' ? 'bg-success' : 'bg-warning'} px-3">${o.estado}</span></td>
                <td><small class="text-muted">${o.fecha_formateada || o.fecha_creacion}</small></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-dark shadow-sm" onclick="verDetalleProductos(${o.id_orden})">
                        <i class="fas fa-eye me-1"></i> Detalle
                    </button>
                </td>
            </tr>
        `).join('');
    })
    .catch(err => {
        console.error("Error:", err);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4 text-danger">Error de conexión con el servidor.</td></tr>';
    });
}
// Cargar al inicio sin filtros