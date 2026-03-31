let vehiculosDisponibles = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarVehiculos();
});

// --- LÓGICA DEL BUSCADOR AUTOCOMPLETABLE ---
function cargarVehiculos() {
    fetch("/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_HistorialVehiculo.php?action=cargar_vehiculos")
    .then(res => res.json())
    .then(data => {
        vehiculosDisponibles = data.data;
    })
    .catch(error => console.error("Error al cargar vehículos:", error));
}

const buscador = document.getElementById('buscador_vehiculo');
const listaVehiculos = document.getElementById('lista_vehiculos');
const inputIdVehiculo = document.getElementById('id_vehiculo');

buscador.addEventListener('input', function() {
    const texto = this.value.toUpperCase().trim(); // Convertir a mayúsculas para placas/chasis
    listaVehiculos.innerHTML = ''; 

    if (texto.length === 0) {
        listaVehiculos.classList.add('d-none');
        return;
    }

    // Buscamos coincidencias en Placa o Chasis
    const filtrados = vehiculosDisponibles.filter(v => 
        (v.placa && v.placa.toUpperCase().includes(texto)) || 
        v.vin_chasis.toUpperCase().includes(texto)
    );

    if (filtrados.length > 0) {
        listaVehiculos.classList.remove('d-none');
        filtrados.forEach(v => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action fw-bold';
            li.style.cursor = 'pointer';
            
            // Mostrar Placa (si tiene) o VIN
            let identificador = v.placa ? `Placa: ${v.placa}` : `VIN: ${v.vin_chasis}`;
            
            // Aquí v.modelo ahora viene de tu nueva tabla Modelo gracias al PHP
            li.textContent = `${identificador} - ${v.marca_nombre} ${v.modelo}`;
            
            li.onclick = () => {
                inputIdVehiculo.value = v.id_vehiculo; 
                buscador.value = identificador; 
                listaVehiculos.classList.add('d-none'); 
                
                // Ejecutar la búsqueda real al hacer clic
                consultarHistorial(v.id_vehiculo);
            };
            listaVehiculos.appendChild(li);
        });
    } else {
        listaVehiculos.classList.remove('d-none');
        listaVehiculos.innerHTML = '<li class="list-group-item text-muted">No se encontraron vehículos...</li>';
    }
});

// Ocultar la lista si el usuario hace clic fuera de ella
document.addEventListener('click', function(e) {
    if (!buscador.contains(e.target) && !listaVehiculos.contains(e.target)) {
        listaVehiculos.classList.add('d-none');
    }
});

// --- LÓGICA DE CONSULTA Y PINTADO ---
function consultarHistorial(id_vehiculo) {
    fetch(`/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_HistorialVehiculo.php?action=buscar_historial&id_vehiculo=${id_vehiculo}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // 1. Mostrar y actualizar la tarjeta del vehículo
            document.getElementById('panel_resumen').classList.remove('d-none');
            
            const v = data.vehiculo;
            document.getElementById('txt_vehiculo').textContent = `${v.marca} ${v.modelo}`;
            document.getElementById('txt_anio').textContent = v.anio || 'N/A';
            document.getElementById('txt_color').textContent = v.color;
            document.getElementById('txt_placa').textContent = v.placa || 'Sin Placa';
            document.getElementById('txt_chasis').textContent = v.vin_chasis;
            document.getElementById('txt_km').textContent = v.kilometraje_actual ? `${v.kilometraje_actual} km` : '0 km';
            
            document.getElementById('txt_propietario').textContent = v.propietario;
            document.getElementById('txt_documento').textContent = v.documento_propietario;
            document.getElementById('txt_telefono').textContent = v.telefono_propietario || 'N/A';

            // 2. Llenar la tabla de historial
            const tbody = document.getElementById("cuerpo-tabla");
            tbody.innerHTML = "";

            if (data.historial.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-info-circle me-2"></i>No hay órdenes de servicio registradas para este vehículo todavía.</td></tr>`;
                return;
            }

            // Aquí se llenará la tabla cuando el módulo de taller esté listo
            // data.historial.forEach(orden => { ... });
            
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error("Error al buscar historial:", error));
}

function limpiarConsulta() {
    document.getElementById('buscador_vehiculo').value = '';
    document.getElementById('id_vehiculo').value = '';
    document.getElementById('panel_resumen').classList.add('d-none');
    
    document.getElementById("cuerpo-tabla").innerHTML = `
        <tr>
            <td colspan="6" class="text-center text-muted py-4">Seleccione un vehículo para ver su historial.</td>
        </tr>
    `;
    
    document.getElementById('buscador_vehiculo').focus();
}