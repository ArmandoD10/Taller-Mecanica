let listaTrabajos = [];
let modoEdicion = false;

document.addEventListener("DOMContentLoaded", () => {
    listarTrabajos();

    // Lógica del buscador en tiempo real
    const filtro = document.getElementById('filtroTabla');
    if (filtro) {
        filtro.addEventListener('input', function() {
            const val = this.value.toLowerCase().trim();
            const filas = document.querySelectorAll('#cuerpoTablaTrabajos tr');
            filas.forEach(tr => {
                if(tr.cells.length > 1) { // Evitar ocultar el mensaje de "No hay datos"
                    const contenido = tr.innerText.toLowerCase();
                    tr.style.display = contenido.includes(val) ? '' : 'none';
                }
            });
        });
    }

    // Lógica del formulario
   // ==========================================
// GUARDAR O ACTUALIZAR TRABAJO SOLICITADO
// ==========================================
document.getElementById("formTrabajo").addEventListener("submit", function(e) {
    e.preventDefault();
    
    // Indicador de carga institucional
    Swal.fire({
        title: 'Guardando...',
        text: 'Actualizando catálogo de motivos de taller',
        target: document.getElementById('modalTrabajo'), // Para que se vea sobre el modal
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const formData = new FormData(this);
    const url = modoEdicion 
        ? "../../modules/Taller/Archivo_TrabajoSolicitado.php?action=actualizar" 
        : "../../modules/Taller/Archivo_TrabajoSolicitado.php?action=guardar";

    fetch(url, { method: "POST", body: formData })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            // Cerramos el modal antes de mostrar el éxito para limpiar la pantalla
            cerrarModalUI('modalTrabajo');

            Swal.fire({
                title: '¡Operación Exitosa!',
                text: data.message || 'El catálogo ha sido actualizado correctamente.',
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                listarTrabajos();
                document.getElementById("formTrabajo").reset();
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message,
                icon: 'error',
                target: document.getElementById('modalTrabajo')
            });
        }
    })
    .catch(err => {
        Swal.close();
        Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
    });
});
});

function listarTrabajos() {
    fetch("../../modules/Taller/Archivo_TrabajoSolicitado.php?action=listar_todos")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaTrabajos");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            listaTrabajos = data.data;
            listaTrabajos.forEach(t => {
                let badge = t.estado === 'activo' 
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>';

                tbody.innerHTML += `
                    <tr>
                        <td class="ps-4 fw-bold text-muted">${t.id_trabajo}</td>
                        <td class="fw-bold text-dark">${t.descripcion}</td>
                        <td class="text-muted small"><i class="far fa-calendar-alt me-1"></i> ${t.fecha}</td>
                        <td>${badge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-light border text-primary me-1 shadow-sm" onclick="editarTrabajo(${t.id_trabajo})" title="Editar">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-light border text-danger shadow-sm" onclick="abrirModalEliminar(${t.id_trabajo})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay trabajos registrados en el catálogo.</td></tr>';
        }
    })
    .catch(err => console.error("Error al listar trabajos:", err));
}

function abrirModalNuevo() {
    modoEdicion = false;
    document.getElementById("formTrabajo").reset();
    document.getElementById("id_trabajo").value = "";
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-plus-circle me-2 text-primary"></i>Nuevo Trabajo';
    document.getElementById("div_estado").classList.add("d-none"); // Ocultar estado al crear nuevo
    
    abrirModalUI('modalTrabajo');
    setTimeout(() => document.getElementById("descripcion").focus(), 500);
}

function editarTrabajo(id) {
    const t = listaTrabajos.find(x => x.id_trabajo == id);
    if(!t) return;

    modoEdicion = true;
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-edit me-2 text-warning"></i>Editar Trabajo';
    
    document.getElementById("id_trabajo").value = t.id_trabajo;
    document.getElementById("descripcion").value = t.descripcion;
    document.getElementById("estado").value = t.estado;
    
    document.getElementById("div_estado").classList.remove("d-none"); // Mostrar estado al editar
    
    abrirModalUI('modalTrabajo');
}

/**
 * Procesa la eliminación de un trabajo del catálogo con advertencia
 */
function abrirModalEliminar(id) {
    Swal.fire({
        title: '¿Eliminar Trabajo?',
        text: "Esta acción quitará este motivo del catálogo de sugerencias. No afectará inspecciones pasadas.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            const fd = new FormData();
            fd.append('id_trabajo', id);

            fetch("../../modules/Taller/Archivo_TrabajoSolicitado.php?action=eliminar", { method: "POST", body: fd })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if(data.success) {
                    Swal.fire('¡Eliminado!', 'El trabajo ha sido removido del catálogo.', 'success');
                    listarTrabajos();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(() => {
                Swal.close();
                Swal.fire('Error', 'Fallo de conexión al intentar eliminar.', 'error');
            });
        }
    });
}

function confirmarEliminar() {
    const id = document.getElementById("id_eliminar").value;
    const fd = new FormData();
    fd.append('id_trabajo', id);

    fetch("../../modules/Taller/Archivo_TrabajoSolicitado.php?action=eliminar", { method: "POST", body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            cerrarModalUI('modalEliminar');
            listarTrabajos();
        } else {
            alert("ERROR: " + data.message);
        }
    });
}

// Utilidades para manejar Modales de Bootstrap
function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try {
        if (typeof bootstrap !== 'undefined') {
            let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el); m.show();
        }
    } catch(e) { console.error(e); }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { 
        if (typeof bootstrap !== 'undefined') { 
            let m = bootstrap.Modal.getInstance(el); if(m) m.hide(); 
        } 
    } catch(e) { console.error(e); }
}