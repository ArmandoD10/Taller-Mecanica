let modelos = [];
let modoEdicion = false;
let currentPage = 1;
const recordsPerPage = 6;

document.addEventListener('DOMContentLoaded', () => {
    cargarMarcas();
    cargarTablaModelos();
});

// Cargar el Select de Marcas
function cargarMarcas() {
    fetch('/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Modelo.php?action=get_marcas')
    .then(res => res.json())
    .then(data => {
        const select = document.getElementById('id_marca');
        // Limpiamos el "Cargando..."
        select.innerHTML = `<option value="" disabled selected>Seleccione una Marca</option>`;
        data.marcas.forEach(m => {
            select.innerHTML += `<option value="${m.id_marca}">${m.nombre}</option>`;
        });
    })
    .catch(err => console.error("Error al cargar marcas:", err));
}

// Cargar la tabla con paginación
function cargarTablaModelos(page = 1) {
    currentPage = page;
    fetch(`/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Modelo.php?action=cargar&page=${page}&limit=${recordsPerPage}`)
    .then(res => res.json())
    .then(data => {
        modelos = data.data; 
        const tbody = document.getElementById("cuerpo-tabla");
        tbody.innerHTML = "";

        if (modelos.length > 0) {
            modelos.forEach(reg => {
                let isActivo = reg.estado === 'activo';
                let fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${reg.id_modelo}</td>
                    <td class="fw-bold">${reg.modelo_nombre}</td>
                    <td>${reg.marca_nombre}</td>
                    <td>${reg.fecha_lanzamiento || 'N/A'}</td>
                    <td><span class="badge ${isActivo ? 'bg-success' : 'bg-danger'}">${reg.estado}</span></td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm" onclick="editarRegistro(${reg.id_modelo})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn ${isActivo ? 'btn-secondary' : 'btn-success'} btn-sm" 
                            onclick="cambiarEstado(${reg.id_modelo}, '${isActivo ? 'inactivo' : 'activo'}')">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(fila);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center">No hay modelos registrados.</td></tr>`;
        }
        generarPaginacion(data.total_records, data.page, data.limit);
    });
}

// Cambiar Estado (Activar/Desactivar)
window.cambiarEstado = function(id, nuevoEstado) {
    let accion = nuevoEstado === 'activo' ? 'activar' : 'desactivar';
    let icon = nuevoEstado === 'activo' ? 'question' : 'warning';
    let btnColor = nuevoEstado === 'activo' ? '#28a745' : '#d33';

    Swal.fire({
        title: `¿Desea ${accion} el modelo?`,
        text: `El registro pasará a estado ${nuevoEstado}.`,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: btnColor,
        cancelButtonColor: '#3085d6',
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id_modelo', id);
            fd.append('estado', nuevoEstado);

            fetch("/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Modelo.php?action=cambiar_estado", { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Actualizado!', data.message, 'success');
                    cargarTablaModelos(currentPage);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
};

// Guardar / Actualizar
document.getElementById("formulario").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    let url = modoEdicion 
        ? "/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Modelo.php?action=actualizar" 
        : "/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Modelo.php?action=guardar";

    // Indicador de "Procesando"
    Swal.fire({
        title: 'Guardando modelo...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(url, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({
                title: '¡Éxito!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                limpiarFormulario();
                cargarTablaModelos(currentPage);
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.close();
        console.error("Error:", error);
        Swal.fire('Error Crítico', 'No se pudo establecer conexión con el servidor.', 'error');
    });
});
// Editar Registro
window.editarRegistro = function(id) {
    const mod = modelos.find(reg => reg.id_modelo == id);
    if (mod) {
        document.getElementById('id_oculto').value = mod.id_modelo;
        document.getElementById('nombre').value = mod.modelo_nombre;
        document.getElementById('id_marca').value = mod.id_marca;
        document.getElementById('fecha_lanzamiento').value = mod.fecha_lanzamiento;

        // Ajuste de botón según tu HTML (ID: btnMostrar)
        const btn = document.getElementById('btnMostrar');
        if(btn) btn.textContent = 'Actualizar';
        
        modoEdicion = true;
        document.getElementById('formulario').scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Dentro de window.editarRegistro = function(id) { ... }
// Justo después del scrollIntoView:
Swal.fire({
    icon: 'info',
    title: 'Modo edición activado',
    text: `Cargando datos de ${mod.modelo_nombre}`,
    toast: true,
    position: 'top-end',
    timer: 2000,
    showConfirmButton: false
});
    }
};

// Paginación
function generarPaginacion(total, currentPage, limit) {
    const totalPages = Math.ceil(total / limit);
    const container = document.getElementById('pagination-container');
    container.innerHTML = ''; 
    if (totalPages <= 1) return;

    container.innerHTML += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="cambiarPagina(${currentPage - 1})">Anterior</a></li>`;
    for (let i = 1; i <= totalPages; i++) {
        container.innerHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" onclick="cambiarPagina(${i})">${i}</a></li>`;
    }
    container.innerHTML += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="cambiarPagina(${currentPage + 1})">Siguiente</a></li>`;
}

window.cambiarPagina = function(page) { if (page > 0) cargarTablaModelos(page); };

// Limpiar Formulario
window.limpiarFormulario = function() {
    document.getElementById('formulario').reset();
    document.getElementById('id_oculto').value = '';
    const btn = document.getElementById('btnMostrar');
    if(btn) btn.textContent = 'Registrar';
    modoEdicion = false;
};

// Filtro Dinámico por Nombre del Modelo
document.getElementById("filtro").addEventListener("keyup", function() {
    const texto = this.value.toLowerCase().trim();
    const filas = document.querySelectorAll("#cuerpo-tabla tr");

    filas.forEach(fila => {
        // El nombre del modelo está en la columna 1 (segunda columna)
        let valor = fila.children[1] ? fila.children[1].innerText.toLowerCase() : ""; 
        fila.style.display = valor.includes(texto) ? "" : "none";
    });
});

// Validación de Nombre
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