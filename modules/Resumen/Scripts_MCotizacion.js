document.addEventListener('DOMContentLoaded', () => {
    cargarGraficosCot();
    cargarTablaCotizaciones();
});

function cargarGraficosCot() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MCotizacion.php?action=datos_cotizaciones')
    .then(r => r.json())
    .then(data => {
        // 1. Montos Altos (Barras)
        new Chart(document.getElementById('chartMontoAlto'), {
            type: 'bar',
            data: {
                labels: data.tops.map(t => t.nombre),
                datasets: [{ label: 'Monto RD$', data: data.tops.map(t => t.monto), backgroundColor: '#ffc107' }]
            }
        });

        // 2. Crecimiento Mensual (Línea)
        new Chart(document.getElementById('chartCrecimiento'), {
            type: 'line',
            data: {
                labels: data.historico.map(h => h.mes),
                datasets: [{ label: 'Cotizaciones', data: data.historico.map(h => h.total), borderColor: '#0d6efd', fill: false, tension: 0.1 }]
            }
        });

        // 3. Sucursales (Pie)
        new Chart(document.getElementById('chartSucursalesCot'), {
            type: 'pie',
            data: {
                labels: data.sucursales.map(s => s.nombre),
                datasets: [{ data: data.sucursales.map(s => s.total), backgroundColor: ['#198754', '#0dcaf0', '#6610f2'] }]
            }
        });
    });
}

function cargarTablaCotizaciones() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MCotizacion.php?action=listar_tabla_cotizaciones')
    .then(r => r.json())
    .then(res => {
        const tbody = document.getElementById('tbodyCotizaciones');
        tbody.innerHTML = res.data.map(c => `
            <tr>
                <!-- Numero de cotización con color de énfasis -->
                <td class="fw-bold text-warning">COT-${c.id_cotizacion}</td>
                
                <!-- Nombre del cliente -->
                <td class="text-start fw-bold text-dark">${c.nombre_cliente}</td>
                
                <!-- Usuario con icono de perfil -->
                <td><i class="fas fa-user-circle me-1 text-secondary"></i>${c.usuario}</td>
                
                <!-- Fecha formateada -->
                <td class="small text-muted">${c.fecha}</td>
                
                <!-- Sucursal con formato IGUAL al de servicios -->
                <td class="text-start">
                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                    <span class="text-dark small fw-bold">${c.sucursal}</span>
                </td>
                
                <!-- Total con formato de moneda -->
                <td class="text-end fw-bold text-dark">
                    RD$ ${parseFloat(c.monto_total).toLocaleString(undefined, {minimumFractionDigits: 2})}
                </td>
            </tr>
        `).join('');
    });
}