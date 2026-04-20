document.addEventListener("DOMContentLoaded", () => {
    listar();
    validarNombreLavado("nombre");
});

function listar() {
    fetch('../../modules/RRHH/Archivo_Especialidad.php?action=listar')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById("tbody_especialidades");
        if (!tbody) return;
        tbody.innerHTML = "";
        
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No hay especialidades registradas.</td></tr>';
            return;
        }

        res.data.forEach(e => {
            let badgeEstado = e.estado === 'activo' 
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>`
                : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>`;
            
            let btnEstado = e.estado === 'activo'
                ? `<button class="btn btn-sm btn-outline-warning" onclick="cambiarEstado(${e.id_especialidad}, 'inactivo')"><i class="fas fa-ban"></i></button>`
                : `<button class="btn btn-sm btn-outline-success" onclick="cambiarEstado(${e.id_especialidad}, 'activo')"><i class="fas fa-check"></i></button>`;

            tbody.innerHTML += `
                <tr class="${e.estado === 'inactivo' ? 'opacity-50' : ''}">
                    <td class="ps-4 fw-bold text-dark">${e.nombre}</td>
                    <td class="small text-muted">${e.descripcion || 'Sin descripción'}</td>
                    <td>${badgeEstado}</td>
                    <td class="text-end pe-4">
                        ${btnEstado}
                        <button class="btn btn-sm btn-outline-primary" onclick='editar(${JSON.stringify(e)})' data-bs-toggle="modal" data-bs-target="#modalEspecialidad"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminar(${e.id_especialidad})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
        });
    });
}

function cambiarEstado(id, nuevoEstado) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('estado', nuevoEstado);
    fetch('../../modules/RRHH/Archivo_Especialidad.php?action=cambiar_estado', { method: 'POST', body: fd })
    .then(() => listar());
}

function prepararModalNuevo() {
    document.getElementById("formEspecialidad").reset();
    document.getElementById("id_especialidad").value = 0;
}

function editar(data) {
    document.getElementById("id_especialidad").value = data.id_especialidad;
    document.getElementById("nombre").value = data.nombre;
    document.getElementById("descripcion").value = data.descripcion;
}

document.getElementById("formEspecialidad").onsubmit = function(e) {
    e.preventDefault();
    fetch('../../modules/RRHH/Archivo_Especialidad.php?action=guardar', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEspecialidad')).hide();
            listar();
        }
    });
};

function eliminar(id) {
    if(confirm("¿Eliminar esta especialidad?")) {
        const fd = new FormData();
        fd.append('id', id);
        fetch('../../modules/RRHH/Archivo_Especialidad.php?action=eliminar', { method: 'POST', body: fd })
        .then(() => listar());
    }
}

function validarNombreLavado(idInput) {
    const input = document.getElementById(idInput);
    
    if (input) {
        input.addEventListener('input', function() {
            let valor = this.value;

            // 1. Filtro: Solo permite letras, números, espacios y paréntesis
            // Eliminamos caracteres como: ! @ # $ % * _ + = { } [ ] etc.
            valor = valor.replace(/[^a-zA-Z0-9\s\(\)]/g, '');

            // 2. Mayúscula inicial:
            if (valor.length > 0) {
                valor = valor.charAt(0).toUpperCase() + valor.slice(1);
            }

            // 3. Reasignar valor limpio
            this.value = valor;
        });
    }
}