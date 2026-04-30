let cacheSucursales = [];

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // ==========================================
    // 1. BUSCADOR DINÁMICO DE SUCURSALES
    // ==========================================
    const txtSuc = document.getElementById('txt_buscar_suc');
    const listaSuc = document.getElementById('lista_suc');
    const hiddenSuc = document.getElementById('id_sucursal');

    txtSuc.addEventListener('input', function() {
        const busca = this.value.toLowerCase().trim();
        listaSuc.innerHTML = '';
        if (busca.length < 1) { listaSuc.classList.add('d-none'); return; }
        
        const filtrados = cacheSucursales.filter(s => s.nombre.toLowerCase().includes(busca));
        if (filtrados.length > 0) {
            listaSuc.classList.remove('d-none');
            filtrados.forEach(s => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action py-1';
                li.style.cursor = 'pointer';
                li.textContent = s.nombre;
                li.onclick = () => {
                    txtSuc.value = s.nombre;
                    hiddenSuc.value = s.id_sucursal;
                    listaSuc.classList.add('d-none');
                };
                listaSuc.appendChild(li);
            });
        } else { listaSuc.classList.add('d-none'); }
    });

    document.addEventListener('click', (e) => {
        if (!txtSuc.contains(e.target)) listaSuc.classList.add('d-none');
    });

    // ==========================================
    // 2. GUARDAR FORMULARIO
    // ==========================================
    // ==========================================
// GUARDAR O ACTUALIZAR RECURSO/MAQUINARIA
// ==========================================
document.getElementById("formMaquinaria").addEventListener("submit", function(e) {
    e.preventDefault();
    
    const hiddenSuc = document.getElementById('id_sucursal');

    // 1. Validación de Sucursal seleccionada
    if(hiddenSuc.value === "") {
        Swal.fire({
            title: 'Dato Requerido',
            text: "Por favor, busque y seleccione una Sucursal válida de la lista.",
            icon: 'warning',
            target: document.getElementById('modalMaquinaria') // Aparece sobre el modal
        });
        return;
    }

    // 2. Indicador de carga institucional
    Swal.fire({
        title: 'Guardando Recurso...',
        text: 'Actualizando el inventario de equipamiento técnico',
        target: document.getElementById('modalMaquinaria'),
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const formData = new FormData(this);

    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=guardar", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            // Cerramos modal antes de mostrar el éxito
            cerrarModalUI();

            Swal.fire({
                title: '¡Operación Exitosa!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                listar(); // Refrescar la tabla principal
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message,
                icon: 'error',
                target: document.getElementById('modalMaquinaria')
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            title: 'Fallo de Red',
            text: 'Error de conexión al intentar guardar.',
            icon: 'error',
            target: document.getElementById('modalMaquinaria')
        });
    });
});

    // Eventos para cerrar el modal
    document.querySelectorAll('#modalMaquinaria [data-bs-dismiss="modal"], #modalMaquinaria .btn-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            cerrarModalUI();
        });
    });

    // ==========================================
    // 3. FILTRO DE TEXTO PARA EL NOMBRE
    // ==========================================
    const inputField = document.getElementById('nombre');
    if (inputField) {
        inputField.addEventListener('input', (e) => {
            let value = e.target.value;
            // Solo letras, números y espacios
            value = value.replace(/[^a-zA-Z0-9\s]/g, '');
            // Primera letra siempre Mayúscula
            if (value.length > 0) {
                value = value.charAt(0).toUpperCase() + value.slice(1);
            }
            e.target.value = value;
        });
    }
});

function listar() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTabla");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(m => {
                let badgeCondicion = "bg-primary";
                if (m.estado_maquina === "Desgastado") badgeCondicion = "bg-warning text-dark";
                else if (m.estado_maquina === "Usado") badgeCondicion = "bg-info text-dark";

                let badgeEstadoReg = m.estado === "activo" ? "text-success fw-bold" : "text-muted text-decoration-line-through";

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold">${m.nombre}</td>
                    <td>${m.categoria || '<span class="text-muted">N/A</span>'}</td>
                    <td>${m.sucursal}</td>
                    <td>${m.fecha_ingreso || '-'}</td>
                    <td><span class="badge ${badgeCondicion} fs-6">${m.estado_maquina}</span></td>
                    <td class="${badgeEstadoReg}">${m.estado.toUpperCase()}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm text-white" onclick="editar(${m.id_maquinaria})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="eliminar(${m.id_maquinaria})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No hay recursos registrados.</td></tr>`;
        }
    })
    .catch(error => console.error("Error al cargar listado:", error));
}

function cargarDependencias() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=cargar_dependencias")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Guardamos las sucursales para el buscador dinámico
            cacheSucursales = data.data.sucursales;
            
            // Llenamos el selector de categorías directamente
            const selectCategoria = document.getElementById("id_categoria");
            selectCategoria.innerHTML = '<option value="">Seleccione una categoría...</option>';
            data.data.categorias.forEach(c => {
                selectCategoria.innerHTML += `<option value="${c.id_categoria}">${c.nombre}</option>`;
            });
        }
    });
}

function nuevoRecurso() {
    document.getElementById("formMaquinaria").reset();
    document.getElementById("id_maquinaria").value = "";
    document.getElementById("id_sucursal").value = "";
    document.getElementById("estado").value = "activo";
    
    // Por defecto colocamos la fecha de hoy
    document.getElementById("fecha_ingreso").value = new Date().toISOString().split('T')[0];
    
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Recurso';
    abrirModalUI();
}

function editar(id) {
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-edit me-2"></i>Editar Recurso';
    
    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=obtener&id_maquinaria=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            document.getElementById("id_maquinaria").value = d.id_maquinaria;
            document.getElementById("nombre").value = d.nombre;
            document.getElementById("id_categoria").value = d.id_categoria; // Esto autoselecciona el <select>
            document.getElementById("id_sucursal").value = d.id_sucursal;
            document.getElementById("funcionamiento").value = d.funcionamiento;
            document.getElementById("estado_maquina").value = d.estado_maquina;
            document.getElementById("fecha_ingreso").value = d.fecha_ingreso_formato;
            document.getElementById("estado").value = d.estado;

            // Rellenar visualmente el buscador de la sucursal
            const suc = cacheSucursales.find(s => s.id_sucursal == d.id_sucursal);
            document.getElementById("txt_buscar_suc").value = suc ? suc.nombre : "";
            
            abrirModalUI();
        } else {
            alert("Error al cargar los datos.");
        }
    });
}

/**
 * Procesa la eliminación física o lógica de una maquinaria
 */
function eliminar(id) {
    Swal.fire({
        title: '¿Eliminar Recurso?',
        text: "Esta acción quitará el equipo del catálogo de activos del taller.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            const f = new FormData(); 
            f.append("id_maquinaria", id);
            
            fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=eliminar", {
                method: "POST", 
                body: f
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if(data.success) {
                    Swal.fire('¡Eliminado!', data.message, 'success');
                    listar();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// ==========================================
// FUNCIONES DEL MODAL (A PRUEBA DE FALLOS)
// ==========================================
function abrirModalUI() {
    const modalElement = document.getElementById('modalMaquinaria');
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#modalMaquinaria').modal('show');
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
    const modalElement = document.getElementById('modalMaquinaria');
    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    document.body.classList.remove('modal-open');
    
    const backdrop = document.getElementById('fondo-oscuro-modal');
    if(backdrop) backdrop.remove();
    
    if (typeof $ !== 'undefined' && $.fn.modal) {
        $('#modalMaquinaria').modal('hide');
    }
}