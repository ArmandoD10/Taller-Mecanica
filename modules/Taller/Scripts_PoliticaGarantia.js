document.addEventListener("DOMContentLoaded", () => {
    listar();
});

function listar() {
    fetch('../../modules/Taller/Archivo_PoliticaGarantia.php?action=listar')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById("tbody_politicas");
        if (!tbody) return;
        
        tbody.innerHTML = "";
        
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay políticas de garantía registradas.</td></tr>';
            return;
        }

        res.data.forEach(p => {
            const kmTexto = p.km !== 'N/A' ? `${p.km} KM` : 'Sin Límite';
            
            // Lógica de colores y botones según el estado
            let badgeEstado = '';
            let btnEstado = '';
            
            if (p.estado === 'activo') {
                badgeEstado = `<span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>`;
                btnEstado = `<button class="btn btn-sm btn-outline-warning shadow-sm" onclick="cambiarEstado(${p.id_politica}, 'inactivo')" title="Desactivar Política"><i class="fas fa-ban"></i></button>`;
            } else {
                badgeEstado = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>`;
                btnEstado = `<button class="btn btn-sm btn-outline-success shadow-sm" onclick="cambiarEstado(${p.id_politica}, 'activo')" title="Activar Política"><i class="fas fa-check"></i></button>`;
            }
            
            tbody.innerHTML += `
                <tr class="${p.estado === 'inactivo' ? 'opacity-50' : ''}">
                    <td class="ps-4 fw-bold text-dark"><i class="fas fa-shield-alt text-primary me-2"></i>${p.nombre}</td>
                    <td>${p.tiempo_cobertura} ${p.unidad_tiempo}</td>
                    <td><span class="badge bg-light text-dark border">${kmTexto}</span></td>
                    <td>${badgeEstado}</td>
                    <td class="text-end pe-4">
                        ${btnEstado}
                        <button class="btn btn-sm btn-outline-primary shadow-sm" onclick='editar(${JSON.stringify(p).replace(/'/g, "&#39;")})' data-bs-toggle="modal" data-bs-target="#modalPolitica" title="Editar Política"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger shadow-sm" onclick="eliminar(${p.id_politica})" title="Eliminar Permanentemente"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
        });
    })
    .catch(err => console.error("Error al listar políticas:", err));
}

// NUEVA FUNCIÓN: Cambiar Estado
function cambiarEstado(id, nuevoEstado) {
    const accionTexto = nuevoEstado === 'activo' ? 'activar' : 'desactivar';
    
    if(confirm(`¿Seguro que desea ${accionTexto} esta política?`)) {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('estado', nuevoEstado);
        
        fetch('../../modules/Taller/Archivo_PoliticaGarantia.php?action=cambiar_estado', { 
            method: 'POST', 
            body: fd 
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                listar(); // Refresca la tabla automáticamente
            } else {
                alert("❌ Error al cambiar el estado: " + res.message);
            }
        })
        .catch(err => console.error("Error cambiando estado:", err));
    }
}

// Limpia el formulario antes de abrirlo para crear uno nuevo
function prepararModalNuevo() {
    document.getElementById("formPolitica").reset();
    document.getElementById("id_politica").value = 0;
}

// Llena el formulario con los datos a editar
function editar(data) {
    document.getElementById("id_politica").value = data.id_politica;
    document.getElementById("nombre").value = data.nombre;
    document.getElementById("tiempo_cobertura").value = data.tiempo_cobertura;
    document.getElementById("unidad_tiempo").value = data.unidad_tiempo;
    document.getElementById("kilometraje_cobertura").value = data.km === 'N/A' ? '' : data.km;
}

// Cierra el modal de forma programática después de guardar
function cerrarModal() {
    const modalEl = document.getElementById('modalPolitica');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
}

document.getElementById("formPolitica").onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../../modules/Taller/Archivo_PoliticaGarantia.php?action=guardar', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            alert("✅ Política guardada correctamente");
            cerrarModal();
            listar();
        } else {
            alert("❌ Error al guardar: " + res.message);
        }
    })
    .catch(err => console.error("Error guardando política:", err));
};

function eliminar(id) {
    if(confirm("¿Seguro que desea eliminar permanentemente esta política?")) {
        const fd = new FormData();
        fd.append('id', id);
        
        fetch('../../modules/Taller/Archivo_PoliticaGarantia.php?action=eliminar', { 
            method: 'POST', 
            body: fd 
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                listar();
            } else {
                alert("❌ Error al eliminar: " + res.message);
            }
        });
    }
}