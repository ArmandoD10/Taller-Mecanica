let clientesCache = [];
let catalogoTrabajos = [];
let trabajosAgregados = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarDatosBase();
    revisarContextoOrden();
    cargarCatalogoTrabajos();
    
    // Ocultar resultados al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#buscar_trabajo') && !e.target.closest('#resultados_trabajos')) {
            const resDiv = document.getElementById('resultados_trabajos');
            if(resDiv) resDiv.classList.add('d-none');
        }
        
        // El de clientes que ya tenías
        if (!txtBuscar.contains(e.target)) listaResultados.classList.add('d-none');
    });
});

function cargarDatosBase() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Inspeccion.php?action=cargar_datos_iniciales")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            clientesCache = data.data.clientes;
            
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
                
                const selVehiculo = document.getElementById("id_vehiculo");
                selVehiculo.innerHTML = `<option value="${d.id_vehiculo}" selected>${d.vehiculo_desc}</option>`;
                selVehiculo.disabled = true;

                document.getElementById('msg_contexto').innerHTML = `<div class="alert alert-info py-2 mb-3 fw-bold border-info" style="font-size: 13px;"><i class="fas fa-info-circle me-2"></i>Completando inspección requerida para la Orden ORD-${d.id_orden}</div>`;
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
            li.style.fontSize = "12px";
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

    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Inspeccion.php?action=cargar_vehiculos_cliente&id_cliente=${id_cliente}`)
    .then(res => res.json())
    .then(data => {
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
    }).catch(error => selVehiculo.innerHTML = '<option value="">Error al cargar datos</option>');
}

// --- BUSCADOR DINÁMICO DE TRABAJOS ---
function cargarCatalogoTrabajos() {
    fetch('../../modules/Taller/Archivo_TrabajoSolicitado.php?action=listar_activos')
    .then(res => res.json())
    .then(data => {
        if(data.success) catalogoTrabajos = data.data;
    });
}

function buscarTrabajos(term) {
    const resDiv = document.getElementById("resultados_trabajos");
    if(term.trim().length < 2) {
        resDiv.classList.add("d-none");
        return;
    }

    const filtrados = catalogoTrabajos.filter(t => t.descripcion.toLowerCase().includes(term.toLowerCase()));
    
    resDiv.innerHTML = "";
    if(filtrados.length > 0) {
        filtrados.forEach(t => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "list-group-item list-group-item-action text-dark py-2 fw-bold border-bottom";
            btn.style.fontSize = "12px";
            btn.innerHTML = `<i class="fas fa-plus text-primary me-2"></i>${t.descripcion}`;
            btn.onclick = () => agregarTrabajo(t);
            resDiv.appendChild(btn);
        });
        resDiv.classList.remove("d-none");
    } else {
        resDiv.innerHTML = `<div class="list-group-item text-muted py-2 bg-light" style="font-size: 12px;">No se encontraron coincidencias.</div>`;
        resDiv.classList.remove("d-none");
    }
}

function agregarTrabajo(trabajo) {
    if(trabajosAgregados.find(t => t.id_trabajo === trabajo.id_trabajo)) {
        alert("Este trabajo ya fue añadido a la lista.");
        return;
    }

    trabajosAgregados.push(trabajo);
    renderizarTrabajos();
    
    document.getElementById("buscar_trabajo").value = "";
    document.getElementById("resultados_trabajos").classList.add("d-none");
    document.getElementById("buscar_trabajo").focus();
}

function eliminarTrabajo(id) {
    trabajosAgregados = trabajosAgregados.filter(t => t.id_trabajo !== id);
    renderizarTrabajos();
}

function renderizarTrabajos() {
    const contenedor = document.getElementById("lista_trabajos_agregados");
    contenedor.innerHTML = "";
    
    if(trabajosAgregados.length === 0) {
        contenedor.innerHTML = '<span class="text-muted w-100 text-center pt-1" style="font-style: italic;">No se han añadido trabajos específicos.</span>';
        return;
    }

    trabajosAgregados.forEach(t => {
        contenedor.innerHTML += `
            <span class="badge bg-primary d-flex align-items-center py-1 px-2 fw-normal rounded" style="font-size: 11px;">
                ${t.descripcion}
                <button type="button" class="btn-close btn-close-white ms-2" style="font-size: 8px;" onclick="eliminarTrabajo(${t.id_trabajo})"></button>
                <input type="hidden" name="trabajos[]" value="${t.id_trabajo}">
            </span>
        `;
    });
}

// --- GUARDAR INSPECCIÓN ---
document.getElementById("formulario_inspeccion").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    // Forzamos la captura de datos si vienen deshabilitados por la URL
    if (document.getElementById('id_vehiculo').disabled) {
        formData.append('id_vehiculo', document.getElementById('id_vehiculo').value);
    }

    const btnGuardar = document.getElementById("btnGuardar");
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';

    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Inspeccion.php?action=guardar", { 
        method: "POST", 
        body: formData 
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            window.location.href = "MInspeccion.php"; 
        } else {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Inspección';
        }
    })
    .catch(err => {
        alert("Error de conexión al guardar.");
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Inspección';
    });
});