document.addEventListener("DOMContentLoaded", () => {
    listarImpuestos();

    // Evento para el formulario
    document.getElementById("formImpuesto").addEventListener("submit", function(e) {
        e.preventDefault();
        guardarImpuesto();
    });
});

function listarImpuestos() {
    fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Impuesto.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("tbody_impuestos");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(imp => {
                const badgeEstado = imp.estado === 'activo' 
                    ? '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3">Activo</span>' 
                    : '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3">Inactivo</span>';

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="ps-4 text-muted">#${imp.id_impuesto}</td>
                    <td class="fw-bold">${imp.nombre_impuesto}</td>
                    <td class="text-center"><span class="badge bg-secondary">${imp.porcentaje}%</span></td>
                    <td class="text-center">${badgeEstado}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-info me-1" onclick="editarImpuesto(${imp.id_impuesto})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarImpuesto(${imp.id_impuesto})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No hay impuestos configurados.</td></tr>`;
        }
    });
}

function guardarImpuesto() {
    const form = document.getElementById("formImpuesto");
    const formData = new FormData(form);

    fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Impuesto.php?action=guardar", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Éxito!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                form.reset();
                document.getElementById("id_config_impuesto").value = ""; 
                document.getElementById("tituloForm").innerHTML = '<i class="fas fa-plus me-2 text-success"></i>Gestionar Impuesto';
                listarImpuestos();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error("Error:", error);
        Swal.fire('Error', 'No se pudo conectar con el servidor de impuestos.', 'error');
    });
}

// Validación de caracteres especiales (Solo letras, números y paréntesis)
function validarNombreImpuesto(input) {
    const errorDiv = document.getElementById('error_nombre');
    
    // Regex: Permite letras (incluyendo acentos), números, espacios, ( y )
    const regex = /^[a-zA-Z0-9\s()áéíóúÁÉÍÓÚñÑ]+$/;

    if (input.value.length > 0 && !regex.test(input.value)) {
        // Elimina el último caracter si no cumple
        input.value = input.value.replace(/[^a-zA-Z0-9\s()áéíóúÁÉÍÓÚñÑ]/g, '');
        
        // Mostrar alerta visual
        errorDiv.classList.remove('d-none');
        input.classList.add('is-invalid');
        
        setTimeout(() => {
            errorDiv.classList.add('d-none');
            input.classList.remove('is-invalid');
        }, 2000);
    }
}

// Modificación en la función editarImpuesto para manejar los Radios
function editarImpuesto(id) {
    fetch(`/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Impuesto.php?action=obtener&id_impuesto=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            document.getElementById("id_config_impuesto").value = d.id_config_impuesto;
            document.getElementById("nombre_impuesto").value = d.nombre_impuesto;
            document.getElementById("porcentaje_impuesto").value = d.porcentaje;
            
            // Seleccionar el Radio correspondiente
            if(d.estado === 'activo') {
                document.getElementById("estado_activo").checked = true;
            } else {
                document.getElementById("estado_inactivo").checked = true;
            }
            
            document.getElementById("tituloForm").innerHTML = '<i class="fas fa-edit me-2 text-warning"></i>Editando Impuesto';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
}

function eliminarImpuesto(id) {
    Swal.fire({
        title: '¿Eliminar este impuesto?',
        text: "No aparecerá en nuevas facturas. Esta acción marcará el registro como eliminado.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const f = new FormData();
            f.append("id_config_impuesto", id);

            fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Impuesto.php?action=eliminar", {
                method: "POST",
                body: f
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Eliminado', 'El impuesto ha sido removido.', 'success');
                    listarImpuestos();
                } else {
                    Swal.fire('Error', 'No se pudo eliminar el impuesto.', 'error');
                }
            });
        }
    });
}

// Función extra para limpiar el formulario si el usuario cancela la edición
function cancelarEdicion() {
        document.getElementById("formImpuesto").reset();
        document.getElementById("id_config_impuesto").value = "";
        document.getElementById("tituloForm").innerHTML = '<i class="fas fa-plus me-2 text-success"></i>Gestionar Impuesto';
}