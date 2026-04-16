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
    document.getElementById("formTrabajo").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const url = modoEdicion 
            ? "../../modules/Taller/Archivo_TrabajoSolicitado.php?action=actualizar" 
            : "../../modules/Taller/Archivo_TrabajoSolicitado.php?action=guardar";

        document.getElementById("btnGuardar").disabled = true;

        fetch(url, { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            document.getElementById("btnGuardar").disabled = false;
            if (data.success) {
                cerrarModalUI('modalTrabajo');
                listarTrabajos();
                // Limpiar form
                document.getElementById("formTrabajo").reset();
            } else {
                alert("ERROR: " + data.message);
            }
        })
        .catch(err => {
            document.getElementById("btnGuardar").disabled = false;
            alert("Error de conexión al servidor.");
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

function abrirModalEliminar(id) {
    document.getElementById("id_eliminar").value = id;
    abrirModalUI('modalEliminar');
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