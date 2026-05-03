document.addEventListener('DOMContentLoaded', () => {
    renderizarGraficosClientes();
    cargarCardsClientes();
});

function renderizarGraficosClientes() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MCliente.php?action=datos_clientes')
    .then(r => r.json())
    .then(data => {
        // 1. Top Clientes (Barras Horizontales)
        new Chart(document.getElementById('chartTopClientes'), {
            type: 'bar',
            data: {
                labels: data.clientes.map(c => c.nombre),
                datasets: [{ label: 'Órdenes Totales', data: data.clientes.map(c => c.total), backgroundColor: '#0dcaf0' }]
            },
            options: { indexAxis: 'y' }
        });

        // 2. Gasto por Vehículo (Barras)
        new Chart(document.getElementById('chartGastoVehiculos'), {
            type: 'bar',
            data: {
                labels: data.gastos.map(v => v.placa),
                datasets: [{ label: 'Inversión RD$', data: data.gastos.map(v => v.total_gastado), backgroundColor: '#17a2b8' }]
            }
        });

        // 3. Marcas (Pie)
        new Chart(document.getElementById('chartMarcas'), {
            type: 'pie',
            data: {
                labels: data.marcas.map(m => m.nombre),
                datasets: [{ data: data.marcas.map(m => m.total), backgroundColor: ['#0dcaf0', '#007bff', '#6610f2', '#6f42c1', '#e83e8c'] }]
            }
        });
    });
}

function cargarCardsClientes() {
    const contenedor = document.getElementById('contenedorCardsClientes');
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MCliente.php?action=listar_cards_clientes')
    .then(r => r.json())
    .then(res => {
        contenedor.innerHTML = res.data.map(item => `
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-body position-relative">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info-subtle text-info rounded-circle p-2 me-3">
                                <i class="fas fa-user-circle fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">${item.cliente}</h6>
                                <span class="badge bg-dark-subtle text-dark small" style="font-size: 10px;">PROPIETARIO</span>
                            </div>
                        </div>
                        <div class="bg-light p-2 rounded border">
                            <div class="small fw-bold text-primary text-uppercase mb-1 border-bottom pb-1">
                                <i class="fas fa-car me-1"></i>Detalles del Vehículo
                            </div>
                            <div class="row g-0 small text-dark">
                                <div class="col-6 mb-1"><b>Marca:</b> ${item.marca}</div>
                                <div class="col-6 mb-1"><b>Modelo:</b> ${item.modelo}</div>
                                <div class="col-6 mb-1"><b>Año:</b> ${item.anio}</div>
                                <div class="col-6 mb-1"><b>Color:</b> ${item.color}</div>
                            </div>
                            <div class="mt-2 text-center bg-white border border-info-subtle py-1 rounded">
                                <span class="fw-bold text-info"><i class="fas fa-id-card me-1"></i>PLACA: ${item.placa}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    });
}