document.addEventListener("DOMContentLoaded", () => {
    listar();

    // ==========================================
    // MÁSCARA INTELIGENTE PARA EL TIEMPO (HH:MM)
    // ==========================================
    const inputTiempo = document.getElementById('tiempo_estimado');
    inputTiempo.addEventListener('input', function(e) {
        // Evitamos formatear si el usuario está presionando la tecla borrar (backspace)
        if (e.inputType === 'deleteContentBackward') return;
        
        // Quita todo lo que no sea número
        let val = this.value.replace(/\D/g, ''); 
        
        // Inserta los dos puntos automáticamente
        if (val.length > 2) {
            val = val.substring(0, 2) + ':' + val.substring(2, 4);
        }
        this.value = val;
    });

    // Validar al salir del campo que el formato sea correcto (ej. no poner 99:99)
    inputTiempo.addEventListener('blur', function() {
        const regex = /^([0-9]{2}):([0-5][0-9])$/;
        if (this.value !== "" && !regex.test(this.value)) {
            alert("Formato de tiempo inválido. Use el formato Horas:Minutos (Ej. 01:30, 00:45)");
            this.value = "01:00"; // Restaurar a 1 hora si lo hace mal
        }
    });

    document.getElementById("formServicio").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);

        fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_TipoServicio.php?action=guardar", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                cerrarModalUI();
                listar();
                alert(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error("Error al guardar:", error);
            alert("Error de conexión al intentar guardar.");
        });
    });

    document.querySelectorAll('#modalServicio [data-bs-dismiss="modal"], #modalServicio .btn-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            cerrarModalUI();
        });
    });
});

function listar() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_TipoServicio.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaServicios");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(srv => {
                let badgeEstado = srv.estado === "activo" ? "text-success fw-bold" : "text-muted text-decoration-line-through";
                let desc = srv.descripcion ? srv.descripcion : '<span class="text-muted fst-italic">Sin descripción</span>';
                
                let tiempo = srv.tiempo_estimado ? srv.tiempo_estimado.substring(0, 5) + ' hrs' : 'N/A';

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-primary">SRV-${srv.id_tipo_servicio.toString().padStart(3, '0')}</td>
                    <td class="fw-bold">${srv.nombre}</td>
                    <td>${desc}</td>
                    <td><span class="badge bg-info text-dark fs-6"><i class="far fa-clock me-1"></i>${tiempo}</span></td>
                    <td class="${badgeEstado}">${srv.estado.toUpperCase()}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm text-white" onclick="editar(${srv.id_tipo_servicio})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="eliminar(${srv.id_tipo_servicio})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No hay servicios registrados.</td></tr>`;
        }
    })
    .catch(error => console.error("Error al cargar lista:", error));
}

function nuevoServicio() {
    document.getElementById("formServicio").reset();
    document.getElementById("id_tipo_servicio").value = "";
    document.getElementById("tiempo_estimado").value = "01:00"; 
    document.getElementById("estado").value = "activo";
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Servicio';
    
    abrirModalUI();
}

function editar(id) {
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-edit me-2"></i>Editar Servicio';
    
    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_TipoServicio.php?action=obtener&id_tipo_servicio=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById("id_tipo_servicio").value = data.data.id_tipo_servicio;
            document.getElementById("nombre").value = data.data.nombre;
            document.getElementById("descripcion").value = data.data.descripcion || "";
            document.getElementById("tiempo_estimado").value = data.data.tiempo_estimado ? data.data.tiempo_estimado.substring(0, 5) : "01:00";
            document.getElementById("estado").value = data.data.estado;
            
            abrirModalUI();
        } else {
            alert("Error al cargar los datos.");
        }
    });
}

function eliminar(id) {
    if (confirm("¿Está seguro que desea eliminar este servicio del catálogo?")) {
        const formData = new FormData();
        formData.append("id_tipo_servicio", id);

        fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_TipoServicio.php?action=eliminar", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) listar();
        });
    }
}

// ==========================================
// FUNCIONES A PRUEBA DE FALLOS PARA EL MODAL
// ==========================================
function abrirModalUI() {
    const modalElement = document.getElementById('modalServicio');
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#modalServicio').modal('show');
            return;
        }
        if (typeof bootstrap !== 'undefined') {
            let modal = bootstrap.Modal.getInstance(modalElement);
            if (!modal) modal = new bootstrap.Modal(modalElement);
            modal.show();
            return;
        }
        throw new Error("Frameworks no detectados");
    } catch (e) {
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        document.body.classList.add('modal-open');
        
        if(!document.getElementById('fondo-oscuro-modal')){
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'fondo-oscuro-modal';
            document.body.appendChild(backdrop);
        }
    }
}

function cerrarModalUI() {
    const modalElement = document.getElementById('modalServicio');
    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    document.body.classList.remove('modal-open');
    
    const backdrop = document.getElementById('fondo-oscuro-modal');
    if(backdrop) backdrop.remove();
    
    if (typeof $ !== 'undefined' && $.fn.modal) {
        $('#modalServicio').modal('hide');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    
    const inputField = document.getElementById('nombre');

    // Verificamos que el input exista para evitar errores en consola
    if (inputField) {
        inputField.addEventListener('input', (e) => {
            let value = e.target.value;

            // 1. Filtro de caracteres especiales (Solo letras, números y espacios)
            value = value.replace(/[^a-zA-Z0-9\s]/g, '');

            // 2. Primera letra siempre Mayúscula
            if (value.length > 0) {
                value = value.charAt(0).toUpperCase() + value.slice(1);
            }

            // 3. Actualizamos el valor del input
            e.target.value = value;
        });
    }
});