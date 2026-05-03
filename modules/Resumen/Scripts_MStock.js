document.addEventListener('DOMContentLoaded', () => {
    cargarGraficos();
    cargarCardsDistribucion();
});

let datosGraficos = {}; // Para guardar los datos y poder ampliarlos
// Almacén global de datos
let chartGrandeInstance = null; // Instancia para el modal

function cargarGraficos() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MStock.php?action=datos_stock')
    .then(r => r.json())
    .then(data => {
        datosGraficos = data;
        
        // 1. Gráfico de Pie (Distribución)[cite: 19]
        new Chart(document.getElementById('chartPieStock'), {
            type: 'pie',
            data: {
                labels: data.pie.map(i => i.nombre),
                datasets: [{ 
                    data: data.pie.map(i => i.total), 
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#0dcaf0'] 
                }]
            },
            options: { 
                plugins: { 
                    legend: { display: false } // Nombres internos se manejan con datalabels si lo tienes[cite: 19]
                } 
            }
        });

        // 2. Gráfico de Barras: TOP 5 MÁS VENDIDOS (Faltante anteriormente)[cite: 19]
        if (data.barras && data.barras.length > 0) {
            new Chart(document.getElementById('chartBarrasVentas'), {
                type: 'bar',
                data: {
                    labels: data.barras.map(i => i.nombre),
                    datasets: [{ 
                        label: 'Unidades Vendidas', 
                        data: data.barras.map(i => i.vendidos), 
                        backgroundColor: '#0dcaf0',
                        borderRadius: 5
                    }]
                },
                options: { 
                    responsive: true,
                    scales: { y: { beginAtZero: true } },
                    onClick: () => ampliarGrafico('barras') // Permite click para ampliar[cite: 19]
                }
            });
        }

        // 3. Gráfico de Precios[cite: 19]
        new Chart(document.getElementById('chartPreciosExtremos'), {
            type: 'bar',
            data: {
                labels: data.precios.map(i => i.tipo + " (" + i.nombre + ")"),
                datasets: [{
                    label: 'Precio de Venta (RD$)',
                    data: data.precios.map(i => i.precio_venta),
                    backgroundColor: ['#dc3545', '#198754']
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
    });
}

// Lógica para el Modal de Visualización Ampliada[cite: 19]
function ampliarGrafico(tipo) {
    const ctx = document.getElementById('chartGrande').getContext('2d');
    const modalEl = document.getElementById('modalGrafico');
    const modal = new bootstrap.Modal(modalEl);
    
    // Destruir instancia previa si existe para evitar superposición[cite: 19]
    if (chartGrandeInstance) chartGrandeInstance.destroy();

    let configuracion = {};

    if (tipo === 'pie') {
        configuracion = {
            type: 'pie',
            data: {
                labels: datosGraficos.pie.map(i => i.nombre),
                datasets: [{ data: datosGraficos.pie.map(i => i.total), backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1'] }]
            },
            options: { plugins: { legend: { display: true, position: 'bottom' } } }
        };
    } else if (tipo === 'barras') {
        configuracion = {
            type: 'bar',
            data: {
                labels: datosGraficos.barras.map(i => i.nombre),
                datasets: [{ label: 'Ventas Históricas', data: datosGraficos.barras.map(i => i.vendidos), backgroundColor: '#0dcaf0' }]
            }
        };
    }

    chartGrandeInstance = new Chart(ctx, configuracion);
    modal.show();
}

function cargarCardsDistribucion() {
    fetch('/Taller/Taller-Mecanica/modules/Resumen/Archivo_MStock.php?action=distribucion_sucursales')
    .then(r => r.json())
    .then(res => {
        const contenedor = document.getElementById('contenedorCardsSucursales');
        const agrupado = res.data.reduce((acc, current) => {
            if (!acc[current.articulo]) acc[current.articulo] = { datos: [], img: current.imagen };
            acc[current.articulo].datos.push(current);
            return acc;
        }, {});

        contenedor.innerHTML = Object.keys(agrupado).map(art => {
            const totalStock = agrupado[art].datos.reduce((sum, s) => sum + parseInt(s.cantidad), 0);
            const imagen = agrupado[art].img || '/Taller/Taller-Mecanica/img/default.png';

            return `
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                        <div class="card-body d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-primary mb-1">${art}</h6>
                                <div class="fs-4 fw-bold mb-2">Total: <span class="text-success">${totalStock}</span></div>
                                <small class="text-muted fw-bold d-block mb-1 text-uppercase" style="font-size: 10px;">Distribución:</small>
                                <ul class="list-unstyled mb-0 small">
                                    ${agrupado[art].datos.map(s => `<li><i class="fas fa-store me-1 text-muted"></i>${s.sucursal}: <b>${s.cantidad}</b></li>`).join('')}
                                </ul>
                            </div>
                            <!-- Imagen a la derecha -->
                            <div class="ms-3 border rounded shadow-sm" style="width: 70px; height: 70px; overflow: hidden; background: #f8f9fa;">
                                <img src="${imagen}" class="w-100 h-100" style="object-fit: cover;">
                            </div>
                        </div>
                    </div>
                </div>`;
        }).join('');
    });
}