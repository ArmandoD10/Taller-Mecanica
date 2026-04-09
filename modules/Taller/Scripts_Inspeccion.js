let clientesCache = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarDatosBase();
    revisarContextoOrden();
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

function revisarContextoOrden() {
    const urlParams = new URLSearchParams(window.location.search);
    const idOrden = urlParams.get('id_orden');

    if (idOrden) {
        fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Inspeccion.php?action=cargar_datos_orden&id_orden=${idOrden}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                document.getElementById('id_orden').value = d.id_orden;
                document.getElementById('txt_buscar_cliente').value = d.nombre_cliente;
                document.getElementById('txt_buscar_cliente').disabled = true;
                document.getElementById('id_cliente').value = d.id_cliente;
                
                // Pre-cargar el select de vehículo
                const selVehiculo = document.getElementById("id_vehiculo");
                selVehiculo.innerHTML = `<option value="${d.id_vehiculo}" selected>${d.vehiculo_desc}</option>`;
                selVehiculo.disabled = true;

                // Indicar visualmente que es una inspección vinculada
                document.getElementById('msg_contexto').innerHTML = `<div class="alert alert-info py-2 mb-3 fw-bold border-info"><i class="fas fa-info-circle me-2"></i>Completando inspección técnica requerida para la Orden ORD-${d.id_orden}</div>`;
            }
        });
    }
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
                cargarVehiculosCliente(c.id_cliente); 
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

    console.log("Cargando vehículos para el cliente ID:", id_cliente);

    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Inspeccion.php?action=cargar_vehiculos_cliente&id_cliente=${id_cliente}`)
    .then(res => res.json())
    .then(data => {
        console.log("Respuesta del servidor:", data); 
        
        selVehiculo.innerHTML = ''; 
        
        if (data.success && data.data && data.data.length > 0) {
            selVehiculo.innerHTML = '<option value="">Seleccione un vehículo...</option>';
            data.data.forEach(v => {
                let placa = v.placa ? v.placa : 'Sin Placa';
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

    // Si los inputs estaban deshabilitados por la URL, FormData no los captura. Los forzamos:
    if (document.getElementById('id_vehiculo').disabled) {
        formData.append('id_vehiculo', document.getElementById('id_vehiculo').value);
    }

    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Inspeccion.php?action=guardar", { 
        method: "POST", 
        body: formData 
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            window.location.href = "MInspeccion.php"; 
        }
    });
});