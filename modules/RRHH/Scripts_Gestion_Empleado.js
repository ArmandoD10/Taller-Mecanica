let timeoutEmpleado = null;

document.addEventListener("DOMContentLoaded", () => {
    // Ya no cargamos empleados al inicio en el select
    cargarSucursales(); 
    configurarBuscadorEmpleado();
    cargarTabla();
});

function configurarBuscadorEmpleado() {
    const inputBusqueda = document.getElementById('buscar_empleado');
    const lista = document.getElementById('lista_empleados_res');

    inputBusqueda.addEventListener('input', function() {
        const term = this.value.trim();
        clearTimeout(timeoutEmpleado);

        // CAMBIO AQUÍ: Ahora permite buscar con 1 solo carácter (como el ID 1)
        if (term.length < 1) { 
            lista.classList.add('d-none'); 
            return; 
        }

        timeoutEmpleado = setTimeout(() => {
            fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Empleado.php?action=buscar_empleado&term=${term}`)
            .then(res => res.json())
            .then(data => {
                lista.innerHTML = '';
                if(data.length > 0) {
                    lista.classList.remove('d-none');
                    data.forEach(emp => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action';
                        li.style.cursor = 'pointer';
                        
                        // Mejora visual para mostrar el ID en los resultados
                        li.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${emp.nombre}</strong><br>
                                    <small class="text-muted">Cédula: ${emp.cedula}</small>
                                </div>
                                <span class="badge bg-secondary">ID: ${emp.id}</span>
                            </div>`;
                            
                        li.onclick = () => seleccionarEmpleado(emp);
                        lista.appendChild(li);
                    });
                } else {
                    lista.classList.add('d-none');
                }
            });
        }, 300);
    });
}

function seleccionarEmpleado(emp) {
    document.getElementById('id_empleado').value = emp.id;
    document.getElementById('lbl_emp_nombre').innerText = emp.nombre;
    document.getElementById('lbl_emp_id').innerText = emp.id;
    
    document.getElementById('info_empleado_seleccionado').classList.remove('d-none');
    document.getElementById('lista_empleados_res').classList.add('d-none');
    document.getElementById('buscar_empleado').value = ''; 
}

function deseleccionarEmpleado() {
    document.getElementById('id_empleado').value = '';
    document.getElementById('info_empleado_seleccionado').classList.add('d-none');
}

// Función modificada para cargar solo sucursales
function cargarSucursales() {
    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Empleado.php?action=cargar_datos_iniciales")
    .then(res => res.json())
    .then(data => {
        const selSuc = document.getElementById("id_sucursal");
        selSuc.innerHTML = '<option value="">Seleccione Sucursal...</option>';
        data.sucursales.forEach(s => {
            selSuc.innerHTML += `<option value="${s.id_sucursal}">${s.nombre}</option>`;
        });
    });
}

// Agrega esta función al final de tu archivo .js
function cargarTabla() {
    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Empleado.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpo-asignaciones");
        if(!tbody) return;
        
        tbody.innerHTML = "";
        data.forEach(asig => {
            // Preparamos el nombre completo para el onclick
            const nombreCompleto = `${asig.nombre} ${asig.apellido_p}`;
            
            tbody.innerHTML += `
                <tr>
                    <td>${nombreCompleto}</td>
                    <td><span class="badge bg-primary text-white">${asig.sucursal}</span></td>
                    <td>${asig.fecha_inicio}</td>
                    <td><span class="badge bg-success">${asig.estado}</span></td>
                    <td>
                        <button class="btn btn-sm btn-warning" 
                                onclick="moverEmpleado(${asig.id_empleado}, '${nombreCompleto}', '${asig.cedula}')">
                            <i class="fas fa-exchange-alt"></i> Mover
                        </button>
                    </td>
                </tr>`;
        });
    });
}

// También asegúrate de que la función guardar llame a cargarTabla() al terminar
function guardarAsignacion() {
    const idEmp = document.getElementById("id_empleado").value;
    const idSuc = document.getElementById("id_sucursal").value;

    if (!idEmp || !idSuc) {
        Swal.fire('Campos incompletos', 'Debe seleccionar un empleado y una sucursal destino.', 'warning');
        return;
    }

    const fd = new FormData();
    fd.append("id_empleado", idEmp);
    fd.append("id_sucursal", idSuc);

    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Empleado.php?action=guardar", { method: "POST", body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            Swal.fire({
                title: '¡Asignación Exitosa!',
                text: 'El empleado ha sido vinculado a la sucursal correctamente.',
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                deseleccionarEmpleado();
                cargarTabla(); 
            });
        } else {
            Swal.fire('Error', data.message || 'No se pudo realizar la asignación.', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Hubo un fallo al conectar con el servidor.', 'error');
    });
}

// Función para activar el movimiento desde la tabla
window.moverEmpleado = function(id, nombre, cedula) {
    // 1. LLAMAMOS a la función que ya existe arriba, pasándole los datos
    // No la definas aquí, solo ÚSALA.
    seleccionarEmpleado({
        id: id,
        nombre: nombre,
        cedula: cedula
    });

    // 2. Ponemos el foco en el select de sucursal para que el usuario elija rápido
    const selectSucursal = document.getElementById('id_sucursal');
    if (selectSucursal) {
        selectSucursal.focus();
    }

    // 3. Scroll suave hacia el formulario para que no se pierda el usuario
    const formulario = document.getElementById('formAsignacion');
    if (formulario) {
        formulario.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }
};