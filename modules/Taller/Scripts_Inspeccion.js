let clientesCache = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarDatosBase();
});

function cargarDatosBase() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Inspeccion.php?action=cargar_datos_iniciales")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            clientesCache = data.data.clientes;
            
            // Llenar Asesores
            const selAsesor = document.getElementById("id_empleado");
            selAsesor.innerHTML = '<option value="">Seleccione Asesor...</option>';
            data.data.empleados.forEach(e => {
                selAsesor.innerHTML += `<option value="${e.id_empleado}">${e.nombre_empleado}</option>`;
            });
        }
    });
}

// --- BUSCADOR DINÁMICO DE CLIENTES ---
const txtBuscar = document.getElementById('txt_buscar_cliente');
const listaResultados = document.getElementById('lista_clientes_busqueda');
const inputIdCliente = document.getElementById('id_cliente');

txtBuscar.addEventListener('input', function() {
    const busqueda = this.value.toLowerCase().trim();
    listaResultados.innerHTML = '';

    if (busqueda.length < 1) {
        listaResultados.classList.add('d-none');
        return;
    }

    const filtrados = clientesCache.filter(c => 
        c.nombre_cliente.toLowerCase().includes(busqueda) || 
        c.cedula.includes(busqueda)
    );

    if (filtrados.length > 0) {
        listaResultados.classList.remove('d-none');
        filtrados.forEach(c => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action cursor-pointer py-1';
            li.textContent = `${c.nombre_cliente} - ${c.cedula}`;
            li.onclick = () => {
                txtBuscar.value = c.nombre_cliente;
                inputIdCliente.value = c.id_cliente;
                listaResultados.classList.add('d-none');
                cargarVehiculosCliente(c.id_cliente); // Cargar vehículos al elegir cliente
            };
            listaResultados.appendChild(li);
        });
    } else {
        listaResultados.classList.add('d-none');
    }
});

function cargarVehiculosCliente(id_cliente) {
    const selVehiculo = document.getElementById("id_vehiculo");
    selVehiculo.innerHTML = '<option value="">Cargando vehículos...</option>';

    // Asegúrate de que id_cliente sea un número válido
    console.log("Cargando vehículos para el cliente ID:", id_cliente);

    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Inspeccion.php?action=cargar_vehiculos_cliente&id_cliente=${id_cliente}`)
    .then(res => res.json())
    .then(data => {
        console.log("Respuesta del servidor:", data); // ESTO ES CLAVE PARA DEBUREAR
        
        selVehiculo.innerHTML = ''; 
        
        // CORRECCIÓN: Verifica si data.success existe y si data.data tiene elementos
        if (data.success && data.data && data.data.length > 0) {
            selVehiculo.innerHTML = '<option value="">Seleccione un vehículo...</option>';
            data.data.forEach(v => {
                let placa = v.placa ? v.placa : 'Sin Placa';
                // Usamos v.id_vehiculo porque en el SQL usamos el alias "AS id_vehiculo"
                selVehiculo.innerHTML += `<option value="${v.id_vehiculo}">${placa} - ${v.marca} ${v.modelo}</option>`;
            });
        } else {
            selVehiculo.innerHTML = '<option value="">El cliente no tiene vehículos activos...</option>';
        }
    })
    .catch(error => {
        console.error("Error en el fetch:", error);
        selVehiculo.innerHTML = '<option value="">Error al cargar datos</option>';
    });
}

// Ocultar lista al hacer clic fuera
document.addEventListener('click', (e) => {
    if (!txtBuscar.contains(e.target)) listaResultados.classList.add('d-none');
});

// Guardar Inspección
document.getElementById("formulario_inspeccion").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Inspeccion.php?action=guardar", { 
        method: "POST", 
        body: formData 
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    });
});