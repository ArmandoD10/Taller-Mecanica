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
// FUNCIÓN: Cambiar Estado (Activa/Inactiva)
function cambiarEstado(id, nuevoEstado) {
    const accionTexto = nuevoEstado === 'activo' ? 'activar' : 'desactivar';
    const colorBtn = nuevoEstado === 'activo' ? '#28a745' : '#f0ad4e';

    Swal.fire({
        title: `¿Desea ${accionTexto} esta política?`,
        text: `La política quedará en estado ${nuevoEstado} para nuevas órdenes.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: colorBtn,
        cancelButtonColor: '#d33',
        confirmButtonText: `Sí, ${accionTexto}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Estado actualizado',
                        toast: true,
                        position: 'top-end',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    listar();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        }
    });
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

// EVENTO: GUARDAR POLÍTICA
document.getElementById("formPolitica").onsubmit = function(e) {
    e.preventDefault();
    
    // Indicador de carga institucional
    Swal.fire({
        title: 'Guardando...',
        text: 'Actualizando términos de garantía del taller',
        target: document.getElementById('modalPolitica'), // Se muestra sobre el modal
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const formData = new FormData(this);
    
    fetch('../../modules/Taller/Archivo_PoliticaGarantia.php?action=guardar', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        Swal.close(); // Cerramos el loading
        if(res.success) {
            cerrarModal(); // Cerramos el modal de Bootstrap
            Swal.fire({
                title: '¡Guardado!',
                text: "La política de garantía ha sido registrada correctamente.",
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                listar();
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: res.message,
                icon: 'error',
                target: document.getElementById('modalPolitica')
            });
        }
    })
    .catch(err => {
        Swal.close();
        Swal.fire('Error Crítico', 'No se pudo conectar con el servidor técnico.', 'error');
    });
};

// FUNCIÓN: Eliminar permanentemente
function eliminar(id) {
    Swal.fire({
        title: '¿Eliminar Permanentemente?',
        text: "¡Esta acción no se puede deshacer! Se borrará el registro de la política del sistema.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar de por vida',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            
            fetch('../../modules/Taller/Archivo_PoliticaGarantia.php?action=eliminar', { 
                method: 'POST', 
                body: fd 
            })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    Swal.fire('¡Eliminado!', 'La política ha sido removida del catálogo.', 'success');
                    listar();
                } else {
                    Swal.fire('Error de eliminación', res.message, 'error');
                }
            });
        }
    });
}

const inputNombre = document.getElementById('nombre');
if (inputNombre) {
    inputNombre.addEventListener('input', function (e) {
        let valor = e.target.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (valor.length > 0) {
            valor = valor.charAt(0).toUpperCase() + valor.slice(1);
        }
        e.target.value = valor;
    });
}