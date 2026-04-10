document.addEventListener("DOMContentLoaded", () => {
    cargarTipos();
    listarOfertas();

    // Evento de Guardado
    document.getElementById("formOferta").addEventListener("submit", function(e) {
        e.preventDefault();
        
        // Capturamos el estado de los radio buttons manualmente para asegurar el valor
        const formData = new FormData(this);
        const estadoSeleccionado = document.querySelector('input[name="estado_promo"]:checked')?.value || 'activo';
        formData.append("estado_oferta", estadoSeleccionado);

        fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Oferta.php?action=guardar", {
            method: "POST", 
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) { 
                Swal.fire('¡Éxito!', data.message, 'success');
                cancelarEdicionOferta(); 
                listarOfertas(); 
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
    });

    // Validación estricta para el nombre
    const inputNombreOferta = document.getElementById("nombre_oferta");
    if (inputNombreOferta) {
        inputNombreOferta.addEventListener("input", function(e) {
            this.value = this.value.replace(/[^a-zA-Z0-9\s()áéíóúÁÉÍÓÚñÑ]/g, "");
        });
    }
});

function cargarTipos() {
    fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Oferta.php?action=listar_tipos")
    .then(res => res.json()).then(data => {
        const select = document.getElementById("id_tipo");
        select.innerHTML = '<option value="">Seleccione tipo...</option>';
        data.data.forEach(t => select.innerHTML += `<option value="${t.id_tipo}">${t.nombre}</option>`);
    });
}

function listarOfertas() {
    const tbody = document.getElementById("tbody_ofertas");
    if (!tbody) return;

    fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Oferta.php?action=listar_ofertas")
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        data.data.forEach(o => {
            const badgeEstado = o.estado === 'activo' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
            
            tbody.innerHTML += `
                <tr>
                    <td class="ps-4 fw-bold">${o.nombre_oferta}</td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">Descuento</span>
                    </td>
                    <td class="text-center small">${o.fecha_inicio} / ${o.fecha_fin}</td>
                    <td class="text-center fw-bold text-danger">${parseFloat(o.porciento)}%</td>
                    <td class="text-center">
                        <span class="badge rounded-pill ${badgeEstado} border px-3">${o.estado.toUpperCase()}</span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-warning me-1" onclick="editarOferta(${o.id_oferta})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarOferta(${o.id_oferta})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });
    });
}

function editarOferta(id) {
    fetch(`/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Oferta.php?action=obtener&id_oferta=${id}`)
    .then(res => res.json()).then(data => {
        if (data.success) {
            const o = data.data;
            document.getElementById("id_oferta").value = o.id_oferta;
            document.getElementById("nombre_oferta").value = o.nombre_oferta;
            document.getElementById("id_tipo").value = o.id_tipo;
            document.getElementById("fecha_inicio").value = o.fecha_inicio;
            document.getElementById("fecha_fin").value = o.fecha_fin;
            document.getElementById("porciento").value = o.porciento;
            
            // Ajuste para radio buttons de estado
            if(o.estado === 'activo') document.getElementById('est_activo').checked = true;
            else document.getElementById('est_inactivo').checked = true;

            document.getElementById("tituloForm").innerHTML = '<i class="fas fa-edit me-2 text-warning"></i>Editando Oferta';
        }
    });
}

function cancelarEdicionOferta() {
    document.getElementById("formOferta").reset();
    document.getElementById("id_oferta").value = "";
    document.getElementById("tituloForm").innerHTML = '<i class="fas fa-plus-circle me-2 text-success"></i>Nueva Oferta';
}

function eliminarOferta(id) {
    Swal.fire({
        title: '¿Eliminar oferta?',
        text: "Se marcará como eliminado.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append("id_oferta", id);
            fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Oferta.php?action=eliminar", {
                method: "POST", body: formData
            }).then(res => res.json()).then(data => {
                if (data.success) { listarOfertas(); Swal.fire('Eliminado', '', 'success'); }
            });
        }
    });
}

// 2. Validación estricta para el nombre de la oferta
// Agrega este evento dentro de document.addEventListener("DOMContentLoaded", () => { ... });
const inputNombreOferta = document.getElementById("nombre_oferta");
if (inputNombreOferta) {
    inputNombreOferta.addEventListener("input", function(e) {
        // Regex: permite letras (incluye acentos), números, espacios y los paréntesis ( )
        // Remueve cualquier otro carácter especial
        this.value = this.value.replace(/[^a-zA-Z0-9\s()áéíóúÁÉÍÓÚñÑ]/g, "");
    });
}

document.addEventListener("DOMContentLoaded", function() {
    const inputPorcentaje = document.getElementById('porciento');

    if (inputPorcentaje) {
        inputPorcentaje.addEventListener('blur', function(e) {
            let valor = parseFloat(e.target.value);

            // Si el valor es un número válido
            if (!isNaN(valor)) {
                // Forzamos 2 decimales (ej: 2 -> 2.00, 5.5 -> 5.50)
                e.target.value = valor.toFixed(2);
            }
        });

        // Opcional: Evitar que escriban más de 100 si es un descuento
        inputPorcentaje.addEventListener('input', function(e) {
            if (e.target.value > 100) {
                e.target.value = 100;
            }
        });
    }
});