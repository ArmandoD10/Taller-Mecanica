let deudoresRaw = [];

const formatoMoneda = new Intl.NumberFormat('es-DO', {
    style: 'currency',
    currency: 'DOP',
    minimumFractionDigits: 2
});

document.addEventListener("DOMContentLoaded", () => {
    cargarDeudores();
});

function cargarDeudores() {
    fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_ConsultaDeuda.php?action=cargar_deudores`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            deudoresRaw = data.data;
            
            // Actualizar el gran total en la tarjeta roja
            document.getElementById('txt_gran_total').textContent = formatoMoneda.format(data.gran_total_deuda);
            
            renderizarTabla(deudoresRaw);
        }
    })
    .catch(error => console.error("Error al cargar deudores:", error));
}

function renderizarTabla(lista) {
    const tbody = document.getElementById("cuerpo-tabla");
    tbody.innerHTML = "";

    if (lista.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-success fw-bold py-4"><i class="fas fa-check-circle me-2"></i>No hay clientes con deudas pendientes en este momento.</td></tr>`;
        return;
    }

    lista.forEach(deudor => {
        // Formateamos la fecha para que se vea más amigable
        let fechaVencimiento = deudor.vencimiento_mas_antiguo;
        let badgeFecha = '';

        // Lógica visual para saber si ya se venció el crédito más antiguo
        const hoy = new Date();
        const fechaV = new Date(fechaVencimiento);
        
        if (fechaV < hoy) {
            badgeFecha = `<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Vencido: ${fechaVencimiento}</span>`;
        } else {
            badgeFecha = `<span class="badge bg-warning text-dark">${fechaVencimiento}</span>`;
        }

        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>CLI-${deudor.id_cliente.toString().padStart(4, '0')}</td>
            <td class="fw-bold text-primary">${deudor.nombre_cliente}</td>
            <td>${deudor.cedula}</td>
            <td>${deudor.telefono}</td>
            <td class="text-center"><span class="badge bg-secondary rounded-circle px-2 py-1">${deudor.cantidad_creditos}</span></td>
            <td>${badgeFecha}</td>
            <td class="text-end text-danger fw-bold fs-6">${formatoMoneda.format(deudor.total_adeudado)}</td>
            <td class="text-center">
                <a href="/Taller/Taller-Mecanica/view/Cliente/CHistorialCredito.php" class="btn btn-outline-primary btn-sm" title="Ver detalle en Historial">
                    <i class="fas fa-eye"></i> Detalle
                </a>
            </td>
        `;
        tbody.appendChild(fila);
    });
}

// Filtro de búsqueda local
document.getElementById('filtro').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase().trim();
    
    const resultados = deudoresRaw.filter(deudor => {
        return deudor.nombre_cliente.toLowerCase().includes(busqueda) || 
               deudor.cedula.includes(busqueda);
    });
    
    renderizarTabla(resultados);
});

function imprimirReporte() {
    window.print();
}