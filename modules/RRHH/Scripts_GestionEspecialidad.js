let timeoutBusqueda = null;

document.addEventListener("DOMContentLoaded", () => {
    cargarCatalogoEspecialidades();
    configurarBuscador();
});

function configurarBuscador() {
    const input = document.getElementById('buscar_mecanico');
    const lista = document.getElementById('lista_mecanicos_res');

    input.addEventListener('input', function() {
        clearTimeout(timeoutBusqueda);
        const term = this.value.trim();
        if (term.length < 2) { lista.classList.add('d-none'); return; }

        timeoutBusqueda = setTimeout(() => {
            fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_GestionEspecialidad.php?action=buscar_mecanico&term=${term}`)
            .then(res => res.json())
            .then(data => {
                lista.innerHTML = '';
                if(data.length > 0) {
                    lista.classList.remove('d-none');
                    data.forEach(m => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action';
                        li.innerHTML = `<strong>${m.nombre} ${m.apellido_p}</strong> <br> <small class="text-muted">Cédula: ${m.cedula}</small>`;
                        li.onclick = () => seleccionarMecanico(m);
                        lista.appendChild(li);
                    });
                }
            });
        }, 300);
    });
}

function seleccionarMecanico(m) {
    document.getElementById('lista_mecanicos_res').classList.add('d-none');
    document.getElementById('buscar_mecanico').value = '';
    
    document.getElementById('id_empleado_hidden').value = m.id_empleado;
    
    // CORRECCIÓN: Usamos m.nombre (que ya viene concatenado del PHP) 
    // y m.puesto para evitar el 'undefined'
    document.getElementById('txt_nombre_mecanico').innerText = `${m.nombre} - ${m.puesto}`;
    document.getElementById('txt_cedula_mecanico').innerText = m.cedula;
    
    document.getElementById('card_mecanico').classList.remove('d-none');
    listarEspecialidadesAsignadas(m.id_empleado);
}

function deseleccionarMecanico() {
    document.getElementById('card_mecanico').classList.add('d-none');
    document.getElementById('tbody_asignaciones').innerHTML = '<tr><td colspan="3" class="text-center py-5 text-muted">Seleccione un mecánico</td></tr>';
}

function cargarCatalogoEspecialidades() {
    fetch('/Taller/Taller-Mecanica/modules/RRHH/Archivo_GestionEspecialidad.php?action=cargar_catalogo')
    .then(res => res.json())
    .then(data => {
        const select = document.getElementById('select_especialidad');
        select.innerHTML = '<option value="">-- Seleccionar --</option>';
        data.forEach(e => {
            select.innerHTML += `<option value="${e.id_especialidad}">${e.nombre}</option>`;
        });
    });
}

function listarEspecialidadesAsignadas(idEmp) {
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_GestionEspecialidad.php?action=listar_por_empleado&id_empleado=${idEmp}`)
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('tbody_asignaciones');
        tbody.innerHTML = '';
        
        if(data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No tiene especialidades asignadas.</td></tr>';
            return;
        }

        data.forEach(ee => {
            tbody.innerHTML += `
                <tr>
                    <td class="ps-4 fw-bold">${ee.nombre}</td>
                    <td class="small">${ee.fecha_asignacion}</td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="eliminarAsignacion(${ee.id_empleado}, ${ee.id_especialidad})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });
    });
}

function guardarAsignacion() {
    const idEmp = document.getElementById('id_empleado_hidden').value;
    const idEsp = document.getElementById('select_especialidad').value;

    if(!idEsp) { alert("Seleccione una especialidad"); return; }

    const fd = new FormData();
    fd.append('id_empleado', idEmp);
    fd.append('id_especialidad', idEsp);

    fetch('/Taller/Taller-Mecanica/modules/RRHH/Archivo_GestionEspecialidad.php?action=guardar', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            listarEspecialidadesAsignadas(idEmp);
        } else {
            alert(res.message);
        }
    });
}


function eliminarAsignacion(idEmp, idEsp) {
    if(confirm("¿Quitar esta especialidad?")) {
        const fd = new FormData();
        fd.append('id_empleado', idEmp);
        fd.append('id_especialidad', idEsp);
        
        fetch('/Taller/Taller-Mecanica/modules/RRHH/Archivo_GestionEspecialidad.php?action=eliminar', { method: 'POST', body: fd })
        .then(() => listarEspecialidadesAsignadas(idEmp));
    }
}