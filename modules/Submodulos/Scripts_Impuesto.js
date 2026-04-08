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
            alert(data.message);
            form.reset();
            document.getElementById("id_config_impuesto").value = ""; // Limpiar ID oculto
            listarImpuestos();
        } else {
            alert("Error: " + data.message);
        }
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
    if (confirm("¿Está seguro de eliminar este impuesto? No aparecerá en nuevas facturas.")) {
        const f = new FormData();
        f.append("id_config_impuesto", id);

        fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Impuesto.php?action=eliminar", {
            method: "POST",
            body: f
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                listarImpuestos();
            }
        });
    }
}

// Función extra para limpiar el formulario si el usuario cancela la edición
function cancelarEdicion() {
        document.getElementById("formImpuesto").reset();
        document.getElementById("id_config_impuesto").value = "";
        document.getElementById("tituloForm").innerHTML = '<i class="fas fa-plus me-2 text-success"></i>Gestionar Impuesto';
}