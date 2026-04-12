document.addEventListener("DOMContentLoaded", () => {
    cargarDevoluciones();
});

document.getElementById("form_filtros_devoluciones").addEventListener("submit", function(e) {
    e.preventDefault();
    cargarDevoluciones();
});

/**
 * Carga el historial de devoluciones. 
 * Si las fechas están vacías, el PHP traerá todos los registros.
 */
function cargarDevoluciones() {
    const form = document.getElementById("form_filtros_devoluciones");
    const formData = new FormData(form);
    const tbody = document.getElementById("cuerpoTablaDevoluciones");

    // Mensaje de carga profesional
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="py-5 text-muted fw-bold">
                <i class="fas fa-spinner fa-spin me-2"></i> Cargando historial de devoluciones...
            </td>
        </tr>`;

    fetch("/Taller/Taller-Mecanica/modules/Facturacion/Archivo_HistorialDevolucion.php?action=listar_devoluciones", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = ""; // Limpiar el spinner

        if (data.success && data.data.length > 0) {
            data.data.forEach(d => {
                const tr = document.createElement("tr");
                tr.className = "fila-devolucion"; // Clase para el buscador dinámico

                // Definir color del badge según el estado del producto
                const badgeColor = (d.estado_producto === 'Buen Estado') ? 'bg-success' : 'bg-warning text-dark';

                tr.innerHTML = `
                    <td class="small fw-bold">#${d.id_devolucion}</td>
                    <td class="text-primary fw-bold">FAC-${d.id_factura}</td>
                    <td class="small">${d.fecha}</td>
                    <td class="text-start fw-bold">${d.cliente}</td>
                    <td>
                        <span class="badge rounded-pill ${badgeColor}">
                            ${d.estado_producto}
                        </span>
                    </td>
                    <td class="text-end fw-bold text-danger">
                        RD$ ${parseFloat(d.monto_devuelto).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger shadow-sm fw-bold" 
                                onclick="verDetalleDev(${d.id_factura}, '${d.motivo}', '${d.admin_autorizo}')">
                            <i class="fas fa-eye me-1"></i> DETALLE
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="py-5 text-muted fw-bold text-center">
                        <i class="fas fa-info-circle me-2"></i> No se encontraron registros de devoluciones.
                    </td>
                </tr>`;
        }
    })
    .catch(err => {
        console.error("Error al cargar devoluciones:", err);
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="py-5 text-danger fw-bold text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i> Error crítico al conectar con el servidor.
                </td>
            </tr>`;
    });
}

function verDetalleDev(id_factura, motivo, admin) {
    document.getElementById("det_dev_motivo").innerText = motivo;
    document.getElementById("det_dev_admin").innerText = admin;
    
    const tbody = document.getElementById("det_dev_items");
    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-2"><i class="fas fa-sync fa-spin"></i></td></tr>';

    const modal = new bootstrap.Modal(document.getElementById('modalDetalleDevolucion'));
    modal.show();

    // Reutilizamos el endpoint de detalle de facturas que ya tienes funcional
    fetch(`/Taller/Taller-Mecanica/modules/Facturacion/Archivo_HistorialFacturas.php?action=obtener_detalle&id_factura=${id_factura}`)
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        if(data.success) {
            data.data.forEach(i => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${i.descripcion}</td>
                    <td class="text-center">${i.cantidad}</td>
                    <td class="text-end">RD$ ${parseFloat(i.subtotal).toLocaleString()}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    });
}

function filtrarTablaDevoluciones() {
    const texto = document.getElementById("buscador_dinamico").value.toLowerCase();
    const filas = document.querySelectorAll(".fila-devolucion");
    filas.forEach(f => {
        f.style.display = f.innerText.toLowerCase().includes(texto) ? "" : "none";
    });
}