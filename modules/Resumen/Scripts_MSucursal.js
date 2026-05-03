document.addEventListener('DOMContentLoaded', () => {
    cargarGraficosSedes();
    cargarTablaRanking();
});

function cargarGraficosSedes() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MSucursal.php?action=datos_sucursales')
    .then(r => r.json())
    .then(data => {
        // 1. Ventas por Sede (Pie)
        new Chart(document.getElementById('chartVentasSedes'), {
            type: 'pie',
            data: {
                labels: data.ventas.map(s => s.nombre),
                datasets: [{ data: data.ventas.map(s => s.total), backgroundColor: ['#0d6efd', '#6610f2', '#0dcaf0'] }]
            }
        });

        // 2. Órdenes (Barras)
        new Chart(document.getElementById('chartOrdenesSedes'), {
            type: 'bar',
            data: {
                labels: data.ordenes.map(s => s.nombre),
                datasets: [{ label: 'Órdenes', data: data.ordenes.map(s => s.total), backgroundColor: '#0d6efd' }]
            }
        });

        // 3. Ticket Promedio (Radar o Doughnut)
        new Chart(document.getElementById('chartTicketPromedio'), {
            type: 'doughnut',
            data: {
                labels: data.promedios.map(s => s.nombre),
                datasets: [{ label: 'Promedio RD$', data: data.promedios.map(s => s.promedio), backgroundColor: ['#fd7e14', '#20c997', '#ffc107'] }]
            }
        });
    });
}

function cargarTablaRanking() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MSucursal.php?action=listar_tabla_ranking')
    .then(r => r.json())
    .then(res => {
        const tbody = document.getElementById('tbodyRanking');
        tbody.innerHTML = res.data.map(s => {
            const ingresos = parseFloat(s.ingresos_totales || 0);
            return `
            <tr>
                <td class="text-start">
                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                    <span class="fw-bold text-dark">${s.sucursal}</span>
                </td>
                <td><span class="badge bg-light text-dark border">${s.total_facturas}</span></td>
                <td><span class="badge bg-light text-dark border">${s.total_ordenes}</span></td>
                <td class="fw-bold text-primary">RD$ ${ingresos.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                <td>
                    <span class="badge ${ingresos > 0 ? 'bg-success' : 'bg-secondary'}">
                        ${ingresos > 100000 ? 'Alta Producción' : 'Operativa'}
                    </span>
                </td>
            </tr>
        `}).join('');
    });
}