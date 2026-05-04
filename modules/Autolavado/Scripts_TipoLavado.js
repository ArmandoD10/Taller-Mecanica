document.addEventListener("DOMContentLoaded", () => {
    listarTipos();

   document.getElementById("formTipoLavado").addEventListener("submit", function(e) {
    e.preventDefault();
    
    // Indicador de carga institucional
    Swal.fire({
        title: 'Guardando...',
        text: 'Actualizando catálogo de servicios',
        target: document.getElementById('modalTipoLavado'), // Fuerza al frente
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const formData = new FormData(this);

    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_TipoLavado.php?action=guardar", {
        method: "POST", 
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            // Cerramos modal antes de mostrar éxito para evitar conflictos de Z-Index
            cerrarModalUI('modalTipoLavado');

            Swal.fire({
                title: '¡Operación Exitosa!',
                text: data.message || 'El tipo de lavado ha sido guardado.',
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                listarTipos();
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message,
                icon: 'error',
                target: document.getElementById('modalTipoLavado')
            });
        }
    })
    .catch(err => {
        Swal.close();
        Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
    });
});
});

function listarTipos() {
    fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_TipoLavado.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaTipos");
        tbody.innerHTML = "";
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(t => {
                let badgeEstado = t.estado === 'activo' 
                    ? `<span class="badge bg-success"><i class="fas fa-check-circle"></i> Activo</span>` 
                    : `<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Inactivo</span>`;

                let btnEstado = t.estado === 'activo'
                    ? `<button class="btn btn-sm btn-outline-danger" title="Desactivar" onclick="cambiarEstadoTipo(${t.id_tipo}, 'inactivo')"><i class="fas fa-power-off"></i></button>`
                    : `<button class="btn btn-sm btn-outline-success" title="Activar" onclick="cambiarEstadoTipo(${t.id_tipo}, 'activo')"><i class="fas fa-check"></i></button>`;

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-muted">${t.id_tipo}</td>
                    <td class="text-start fw-bold text-dark">${t.nombre}</td>
                    <td>${t.fecha}</td>
                    <td>${badgeEstado}</td>
                    <td>
                        <button class="btn btn-sm btn-info text-dark shadow-sm me-1" title="Editar" onclick="editarTipo(${t.id_tipo})">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        ${btnEstado}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-5">No hay tipos de lavado registrados.</td></tr>`;
        }
    });
}

function abrirModalTipo() {
    document.getElementById("formTipoLavado").reset();
    document.getElementById("id_tipo").value = "";
    document.getElementById("tituloModalTipo").innerHTML = '<i class="fas fa-plus-circle me-2"></i>Nuevo Tipo de Lavado';
    abrirModalUI('modalTipoLavado');
}

function editarTipo(id) {
    fetch(`/Taller/Taller-Mecanica/modules/Autolavado/Archivo_TipoLavado.php?action=obtener&id_tipo=${id}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById("id_tipo").value = data.data.id_tipo;
            document.getElementById("nombre_tipo").value = data.data.nombre;
            document.getElementById("tituloModalTipo").innerHTML = '<i class="fas fa-edit me-2"></i>Editar Tipo de Lavado';
            abrirModalUI('modalTipoLavado');
        }
    });
}

function cambiarEstadoTipo(id, nuevo_estado) {
    let accion = nuevo_estado === 'activo' ? 'activar' : 'desactivar';
    let color = nuevo_estado === 'activo' ? '#28a745' : '#d33';

    Swal.fire({
        title: `¿Desea ${accion} este servicio?`,
        text: `El tipo de lavado aparecerá como ${nuevo_estado} en el sistema.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: color,
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append("id_tipo", id);
            fd.append("estado", nuevo_estado);

            fetch("/Taller/Taller-Mecanica/modules/Autolavado/Archivo_TipoLavado.php?action=cambiar_estado", {
                method: "POST", 
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Estado actualizado',
                        toast: true,
                        position: 'top-end',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    listarTipos();
                } else {
                    Swal.fire('Error', 'No se pudo cambiar el estado.', 'error');
                }
            });
        }
    });
}

const inputNombre = document.getElementById('nombre_tipo');
if (inputNombre) {
    inputNombre.addEventListener('input', function (e) {
        let valor = e.target.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (valor.length > 0) {
            valor = valor.charAt(0).toUpperCase() + valor.slice(1);
        }
        e.target.value = valor;
    });
}

// ==========================================
// UTILIDADES MODALES ROBUSTAS
// ==========================================
function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try {
        if (typeof bootstrap !== 'undefined') {
            let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el); m.show();
        } else throw new Error();
    } catch (e) {
        if (typeof jQuery !== 'undefined') { $('#' + id).modal('show'); } 
        else {
            el.classList.add('show'); el.style.display = 'block'; document.body.classList.add('modal-open');
            document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
            const b = document.createElement('div'); b.id = 'm-bd-' + id; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
        }
    }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { 
        if (typeof bootstrap !== 'undefined') { let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); } 
        else throw new Error();
    } catch (e) {
        if (typeof jQuery !== 'undefined') { $('#' + id).modal('hide'); } 
        else {
            el.classList.remove('show'); el.style.display = 'none'; document.body.classList.remove('modal-open');
            const b = document.getElementById('m-bd-' + id); if(b) b.remove();
            document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
        }
    }
}