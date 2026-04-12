let cacheVehiculos = [];
let vehiculoActual = null;
let historialActual = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarVehiculos();
});

function cargarVehiculos() {
    fetch("../../modules/Vehiculo/Archivo_HistorialVehiculo.php?action=cargar_vehiculos")
    .then(res => res.json())
    .then(data => cacheVehiculos = data.data);
}

const buscador = document.getElementById('buscador_vehiculo');
const lista = document.getElementById('lista_vehiculos');

buscador.addEventListener('input', function() {
    const texto = this.value.toLowerCase().trim();
    lista.innerHTML = '';
    if (texto.length < 1) { lista.classList.add('d-none'); return; }

    const filtrados = cacheVehiculos.filter(v => 
        v.placa.toLowerCase().includes(texto) || 
        v.vin_chasis.toLowerCase().includes(texto) || 
        v.cliente.toLowerCase().includes(texto)
    );

    if (filtrados.length > 0) {
        lista.classList.remove('d-none');
        filtrados.forEach(v => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action py-2';
            li.innerHTML = `<strong>${v.placa}</strong> - ${v.marca} ${v.modelo} <br> <small class="text-muted">${v.cliente}</small>`;
            li.onclick = () => {
                buscador.value = v.placa;
                lista.classList.add('d-none');
                consultarHistorial(v.sec_vehiculo);
            };
            lista.appendChild(li);
        });
    } else {
        lista.classList.remove('d-none');
        lista.innerHTML = '<li class="list-group-item text-muted">No se encontraron vehículos.</li>';
    }
});

function consultarHistorial(id) {
    fetch(`../../modules/Vehiculo/Archivo_HistorialVehiculo.php?action=buscar_historial&id_vehiculo=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            vehiculoActual = data.vehiculo;
            historialActual = data.historial;
            mostrarUI();
        }
    });
}

function mostrarUI() {
    document.getElementById('panel_resumen').classList.remove('d-none');
    document.getElementById('btn_imprimir_full').classList.remove('d-none');
    
    document.getElementById('txt_vehiculo').innerText = `${vehiculoActual.marca} ${vehiculoActual.modelo}`;
    document.getElementById('txt_placa').innerText = vehiculoActual.placa;
    document.getElementById('txt_chasis').innerText = vehiculoActual.vin_chasis;
    document.getElementById('txt_km').innerText = vehiculoActual.kilometraje_actual + ' KM';
    document.getElementById('txt_propietario').innerText = vehiculoActual.propietario;
    document.getElementById('txt_documento').innerText = vehiculoActual.cedula;
    document.getElementById('txt_telefono').innerText = vehiculoActual.telefono;

    const tbody = document.getElementById('cuerpo-tabla');
    tbody.innerHTML = '';

    historialActual.forEach(h => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="fw-bold">ORD-${h.id_orden}</td>
            <td class="small">${h.fecha_creacion}</td>
            <td>${h.falla_reportada}</td>
            <td><strong class="text-primary">${h.servicio_realizado}</strong><br><small class="text-muted">${h.diagnostico_tecnico || ''}</small></td>
            <td>${h.mecanicos}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-info text-white" onclick="imprimirRegistroUnico(${h.id_orden})">
                    <i class="fas fa-print"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function prepararImpresion() {
    const area = document.getElementById('area_impresion');
    let tablaH = '';
    historialActual.forEach(h => {
        tablaH += `<tr>
            <td>ORD-${h.id_orden}</td>
            <td>${h.servicio_realizado}</td>
            <td>${h.mecanicos}</td>
            <td>RD$ ${h.monto_total}</td>
        </tr>`;
    });

    area.innerHTML = `
        <div style="text-align:center; border-bottom: 2px solid #000; padding-bottom:10px;">
            <h3>MECÁNICA DÍAZ PANTALEÓN</h3>
            <p>Reporte Histórico de Mantenimiento</p>
        </div>
        <div style="margin: 20px 0;">
            <p><strong>Vehículo:</strong> ${vehiculoActual.marca} ${vehiculoActual.modelo} (${vehiculoActual.placa})</p>
            <p><strong>Propietario:</strong> ${vehiculoActual.propietario}</p>
        </div>
        <table class="table table-bordered table-sm">
            <thead><tr><th>Orden</th><th>Servicio</th><th>Técnicos</th><th>Monto</th></tr></thead>
            <tbody>${tablaH}</tbody>
        </table>
    `;
    const m = new bootstrap.Modal(document.getElementById('modalImpresion'));
    m.show();
}

function ejecutarImpresion() {
    const contenido = document.getElementById('area_impresion').innerHTML;
    const ventana = window.open('', '_blank');
    ventana.document.write(`<html><head><title>Historial SIG</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head><body onload="window.print()"> ${contenido} </body></html>`);
    ventana.document.close();
}

function limpiarConsulta() {
    location.reload();
}

function imprimirRegistroUnico() {
    window.print();
}