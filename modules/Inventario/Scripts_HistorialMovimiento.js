document.addEventListener('DOMContentLoaded', () => {
    cargarHistorialStock();
});

function cargarHistorialStock() {
    const inicio = document.getElementById('f_inicio').value;
    const fin = document.getElementById('f_fin').value;
    const tbody = document.getElementById('tabla_historial_stock');

    if(!tbody) return;

    tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4">Cargando movimientos...</td></tr>';

    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialMovimiento.php?action=listar_movimientos&f_inicio=${inicio}&f_fin=${fin}`)
    .then(r => r.json())
    .then(res => {
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4 text-muted">No hay movimientos.</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map(m => `
            <tr>
                <td><span class="fw-bold">#${m.id}</span></td>
                <td><span class="badge bg-info text-dark">${m.tipo}</span></td>
                <td>${m.almacen_destino}</td>
                <td>${m.sucursal_nombre}</td> <td><small class="text-muted">${m.fecha_movimiento}</small></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-dark" onclick="verDetalleStock(${m.id})">
                        <i class="fas fa-list"></i> Detalle
                    </button>
                </td>
            </tr>
        `).join('');
    }).catch(err => {
        console.error("Error cargando tabla:", err);
    });
}

function verDetalleStock(id) {
    const modalDiv = document.getElementById('body_detalle_stock');
    modalDiv.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary"></div></div>';
    
    // Mostramos el modal primero
    const modalInst = new bootstrap.Modal(document.getElementById('modalDetalleStock'));
    modalInst.show();

    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialMovimiento.php?action=detalle_movimiento&id=${id}`)
    .then(r => r.json())
    .then(res => {
        if(res.data.length === 0) {
            modalDiv.innerHTML = '<p class="p-3 text-center">Sin detalles.</p>';
            return;
        }
        let html = '<ul class="list-group list-group-flush">';
        res.data.forEach(i => {
            const img = i.imagen ? `${i.imagen}` : '../../img/no-image.png';
            html += `
                <li class="list-group-item d-flex align-items-center">
                    <img src="${img}" class="rounded border me-3 shadow-sm" style="width:50px; height:50px; object-fit:cover;" onerror="this.src='../../img/no-image.png'">
                    <div class="flex-grow-1">
                        <div class="fw-bold small">${i.nombre}</div>
                        <small class="text-muted">Cantidad: ${i.cantidad}</small>
                    </div>
                </li>`;
        });
        html += '</ul>';
        modalDiv.innerHTML = html;
    }).catch(err => {
        modalDiv.innerHTML = '<p class="p-3 text-danger">Error al cargar detalle.</p>';
    });
}