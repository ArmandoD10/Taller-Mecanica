document.addEventListener('DOMContentLoaded', () => {
    cargarGraficosFact();
    cargarTablaFacturas();
});

function cargarGraficosFact() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MFactura.php?action=datos_facturas')
    .then(r => r.json())
    .then(data => {
        // 1. Top Facturas (Barras)
        new Chart(document.getElementById('chartFacturasAltas'), {
            type: 'bar',
            data: {
                labels: data.tops.map(t => t.id),
                datasets: [{ label: 'Total FAC (RD$)', data: data.tops.map(t => t.monto), backgroundColor: '#198754' }]
            }
        });

        // 2. Crecimiento Mensual (Línea de Área)
        new Chart(document.getElementById('chartCrecimientoIngresos'), {
            type: 'line',
            data: {
                labels: data.historico.map(h => h.mes),
                datasets: [{ 
                    label: 'Ingresos Totales', 
                    data: data.historico.map(h => h.total), 
                    borderColor: '#198754', 
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true 
                }]
            }
        });

        // 3. Distribución por Sucursal (Doughnut)
        new Chart(document.getElementById('chartSucursalesFact'), {
            type: 'doughnut',
            data: {
                labels: data.sucursales.map(s => s.nombre),
                datasets: [{ data: data.sucursales.map(s => s.total), backgroundColor: ['#198754', '#20c997', '#0d6efd'] }]
            }
        });
    });
}

function cargarTablaFacturas() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MFactura.php?action=listar_tabla_facturas')
    .then(r => r.json())
    .then(res => {
        const tbody = document.getElementById('tbodyFacturas');
        tbody.innerHTML = res.data.map(f => `
            <tr>
                <td class="fw-bold text-success">FAC-${f.id_factura}</td>
                <td>
                    <span class="badge ${f.origen === 'Taller' ? 'bg-primary' : 'bg-info text-dark'} shadow-sm">
                        ${f.origen}
                    </span>
                </td>
                <td><i class="fas fa-user-circle me-1 text-secondary"></i>${f.usuario}</td>
                <td class="small text-muted">${f.fecha}</td>
                <td>
                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                    <span class="text-dark small fw-bold">${f.sucursal}</span>
                </td>
                <td class="fw-bold text-dark">RD$ ${parseFloat(f.monto_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            </tr>
        `).join('');
    });
}