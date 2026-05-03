document.addEventListener('DOMContentLoaded', () => {
    renderizarGraficosServicios();
    cargarTablaServicios();
});

function renderizarGraficosServicios() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MServicio.php?action=datos_servicios')
    .then(r => r.json())
    .then(data => {
        // 1. Servicios Más Realizados (Barras Horizontales)
        new Chart(document.getElementById('chartMasRealizados'), {
            type: 'bar',
            data: {
                labels: data.servicios.map(s => s.nombre),
                datasets: [{ label: 'Cantidad', data: data.servicios.map(s => s.total), backgroundColor: '#0d6efd' }]
            },
            options: { indexAxis: 'y' }
        });

        // 2. Daños Frecuentes (Doughnut)
        new Chart(document.getElementById('chartDanosFrecuentes'), {
            type: 'doughnut',
            data: {
                labels: data.danos.map(d => d.nombre),
                datasets: [{ data: data.danos.map(d => d.total), backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#0dcaf0'] }]
            }
        });

        // 3. Sucursales (Polar Area)
        new Chart(document.getElementById('chartSucursales'), {
            type: 'polarArea',
            data: {
                labels: data.sucursales.map(s => s.nombre),
                datasets: [{ data: data.sucursales.map(s => s.total), backgroundColor: ['#6610f2', '#6f42c1', '#d63384'] }]
            }
        });
    });
}

function cargarTablaServicios() {
    const tbody = document.getElementById('tbodyServicios');
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MServicio.php?action=listar_tabla_servicios')
    .then(r => r.json())
    .then(res => {
        tbody.innerHTML = res.data.map(fila => `
            <tr>
                <td class="fw-bold text-primary">ORD-${fila.id_orden}</td>
                <td><span class="badge bg-info-subtle text-info border border-info-subtle">${fila.servicio}</span></td>
                <td><i class="fas fa-user-cog me-2 text-secondary"></i>${fila.mecanico}</td>
                <td class="small">${fila.fecha}</td>
                <td><i class="fas fa-map-marker-alt text-danger me-1"></i>${fila.sucursal}</td>
            </tr>
        `).join('');
    });
}