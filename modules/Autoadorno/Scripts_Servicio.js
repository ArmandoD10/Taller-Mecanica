document.addEventListener("DOMContentLoaded", () => {
    listarOrdenesRecientes();
    cargarCombos();

    // Lógica de búsqueda de inspecciones por placa
   document.getElementById('ins_placa').addEventListener('blur', function() {
    const placa = this.value.trim();
    const select = document.getElementById('ins_id_inspeccion');
    const msg = document.getElementById('msg_busqueda');

    if (placa.length < 3) return;

    fetch(`/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Servicio.php?action=buscar_inspecciones_pendientes&placa=${placa}`)
    .then(r => r.json())
    .then(res => {
        select.innerHTML = '<option value="">-- Seleccione Inspección --</option>';
        if (res.data && res.data.length > 0) {
            res.data.forEach(i => {
                select.innerHTML += `<option value="${i.id_inspeccion}">Inspección #${i.id_inspeccion} (${i.fecha_formateada})</option>`;
            });
            msg.innerHTML = '<span class="text-success small">Inspecciones encontradas.</span>';
        } else {
            msg.innerHTML = '<span class="text-danger small">No hay inspecciones activas para este vehículo.</span>';
        }
    });
});
});

function cargarCombos() {
    fetch('/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Servicio.php?action=listar_combos')
        .then(r => r.json())
        .then(res => {
            const select = document.getElementById('ins_paquete');
            res.data.forEach(c => {
                select.innerHTML += `<option value="${c.id_paquete}" data-precio="${c.precio_total}">${c.nombre_paquete} - RD$ ${parseFloat(c.precio_total).toLocaleString()}</option>`;
            });
        });
}

function actualizarVistaCombo() {
    const select = document.getElementById('ins_paquete');
    const id = select.value;
    const resDiv = document.getElementById('resumen_combo');
    const lblTotal = document.getElementById('lbl_total_orden');
    const inputTotal = document.getElementById('total_oculto');

    if (!id) {
        resDiv.innerHTML = '<small class="text-muted italic">Seleccione un combo para ver los artículos...</small>';
        lblTotal.innerText = 'RD$ 0.00';
        return;
    }

    const option = select.options[select.selectedIndex];
    const precio = option.getAttribute('data-precio');
    lblTotal.innerText = `RD$ ${parseFloat(precio).toLocaleString()}`;
    inputTotal.value = precio;

    // ... dentro de actualizarVistaCombo() ...
fetch(`/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Servicio.php?action=obtener_detalle_paquete&id=${id}`)
    .then(res => res.json()).then(res => {
        let html = '<div class="list-group list-group-flush">';
        res.data.forEach(i => {
            // Ajustamos la ruta para que busque en la carpeta de imágenes del taller
            const imgPath = i.imagen ? `${i.imagen}` : '../../img/no-image.png';
            html += `
                <div class="list-group-item d-flex align-items-center py-2 px-0 border-bottom">
                    <img src="${imgPath}" class="rounded-circle border me-3 shadow-sm" 
                         style="width:45px; height:45px; object-fit:cover;" 
                         onerror="this.src='/Taller/Taller-Mecanica/img/no-image.png'">
                    <div class="flex-grow-1">
                        <h6 class="mb-0 small fw-bold text-dark">${i.nombre}</h6>
                        <small class="text-muted">Cantidad: ${i.cantidad}</small>
                    </div>
                    <span class="badge bg-primary rounded-pill">RD$ ${parseFloat(i.precio).toFixed(2)}</span>
                </div>`;
        });
        html += '</div>';
        resDiv.innerHTML = html;
    });
}

function guardarOrdenAutoadorno() {
    const form = document.getElementById('formNuevaInstalacion');
    const selIns = document.getElementById('ins_id_inspeccion');
    const selPaq = document.getElementById('ins_paquete');

    if (!selIns.value || !selPaq.value) {
        Swal.fire('Atención', 'Debe seleccionar una inspección y un combo.', 'warning');
        return;
    }

    const fd = new FormData(form);
    const nombreCombo = selPaq.options[selPaq.selectedIndex].text.split(' - ')[0];
    fd.append('nombre_combo', nombreCombo);

    Swal.fire({
        title: '¿Confirmar Orden?',
        text: "Se creará la orden de servicio y se rebajará el inventario.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, crear orden'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Servicio.php?action=guardar_orden', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire('Éxito', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
        }
    });
}

function listarOrdenesRecientes() {
    fetch('/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Servicio.php?action=listar_ordenes')
        .then(r => r.json())
        .then(res => {
            const tbody = document.getElementById('tabla_ordenes_recientes');
            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay órdenes registradas recientemente.</td></tr>';
                return;
            }
            tbody.innerHTML = res.data.map(o => `
                <tr>
                    <td><span class="fw-bold text-primary">ORD-${o.id_orden}</span></td>
                    <td><small class="fw-bold">${o.descripcion}</small></td>
                    <td>RD$ ${parseFloat(o.monto_total).toLocaleString()}</td>
                    <td><span class="badge bg-success">${o.estado}</span></td>
                    <td><small>${o.fecha_creacion}</small></td>
                </tr>
            `).join('');
        });
}

