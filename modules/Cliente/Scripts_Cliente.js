// --- VARIABLES GLOBALES ---
let clientes = [];
let modoEdicion = false;
let currentPage = 1;
const recordsPerPage = 6;

// --- INICIALIZACIÓN ---
document.addEventListener('DOMContentLoaded', () => {
    cargarTablaClientes();
    formatearNombre('nombre');
    formatearNombre('apellido');
    formatearNombre('direccion');
});

// --- LÓGICA DE CARGA Y PAGINACIÓN ---
function cargarTablaClientes(page = 1) {
    fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cargar&page=${page}&limit=${recordsPerPage}`)
    .then(response => response.json())
    .then(data => {
        clientes = data.data; 
        const tbody = document.getElementById("cuerpo-tabla");
        tbody.innerHTML = "";

        if (clientes.length > 0) {
            clientes.forEach(registro => {
                
                // Lógica dinámica para el botón de estado
                let isActivo = registro.estado.toLowerCase() === 'activo';
                let colorEstado = isActivo ? 'bg-success' : 'bg-danger';
                let colorBtn = isActivo ? 'btn-secondary' : 'btn-success';
                let tituloBtn = isActivo ? 'Desactivar Cliente' : 'Activar Cliente';
                let nuevoEstado = isActivo ? 'inactivo' : 'activo';

                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${registro.id_cliente}</td>
                    <td>${registro.nombre} ${registro.apellido}</td>
                    <td>${registro.cedula_rnc}</td>
                    <td>${registro.telefono || 'N/A'}</td>
                    <td>${registro.correo || 'N/A'}</td>
                    <td>${registro.fecha_registro}</td>
                    <td><span class="badge ${colorEstado}">${registro.estado}</span></td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm me-2" onclick="editarRegistro(${registro.id_cliente})" title="Editar Cliente">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <button type="button" class="btn ${colorBtn} btn-sm" onclick="cambiarEstado(${registro.id_cliente}, '${nuevoEstado}')" title="${tituloBtn}">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(fila);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center">No hay clientes registrados.</td></tr>`;
        }

        generarPaginacion(data.total_records, data.page, data.limit);
    })
    .catch(error => console.error("Error al obtener los clientes:", error));
}

function generarPaginacion(totalRecords, currentPage, limit) {
    const totalPages = Math.ceil(totalRecords / limit);
    const paginationContainer = document.getElementById('pagination-container');
    paginationContainer.innerHTML = ''; 

    if (totalPages <= 1) return;

    const liPrev = document.createElement('li');
    liPrev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    liPrev.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${currentPage - 1})">Anterior</a>`;
    paginationContainer.appendChild(liPrev);

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${i})">${i}</a>`;
        paginationContainer.appendChild(li);
    }

    const liNext = document.createElement('li');
    liNext.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    liNext.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${currentPage + 1})">Siguiente</a>`;
    paginationContainer.appendChild(liNext);
}

window.cambiarPagina = function(page) {
    if (page > 0) cargarTablaClientes(page);
};

// --- ACCIONES DEL FORMULARIO ---
window.editarRegistro = function(id) {
    const clienteEditar = clientes.find(reg => reg.id_cliente == id);
    if (clienteEditar) {
        document.getElementById('id_oculto').value = clienteEditar.id_cliente;
        document.getElementById('nombre').value = clienteEditar.nombre;
        document.getElementById('apellido').value = clienteEditar.apellido;
        document.getElementById('cedula_rnc').value = clienteEditar.cedula_rnc;
        document.getElementById('telefono').value = clienteEditar.telefono;
        document.getElementById('correo').value = clienteEditar.correo;
        document.getElementById('direccion').value = clienteEditar.direccion;

        document.getElementById('btnMostrar').textContent = 'Actualizar';
        modoEdicion = true;

        document.getElementById('formulario').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

document.getElementById('formulario').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const url = modoEdicion 
        ? "/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=actualizar"
        : "/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=guardar";

    fetch(url, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            limpiarFormulario();
            cargarTablaClientes(currentPage);
        } else {
            alert('Error: ' + data.message);
        }
    });
});

window.cambiarEstado = function(id, estadoDeseado) {
    let accion = estadoDeseado === 'activo' ? 'activar' : 'desactivar';
    
    if (confirm(`¿Seguro que desea ${accion} el cliente con ID ${id}?`)) {
        const formData = new FormData();
        formData.append('id_cliente', id);
        formData.append('estado', estadoDeseado);

        fetch("/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cambiar_estado", {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                cargarTablaClientes(currentPage);
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => console.error("Error al cambiar estado:", error));
    }
};

window.limpiarFormulario = function() {
    document.getElementById('formulario').reset();
    document.getElementById('btnMostrar').textContent = 'Registrar';
    document.getElementById('id_oculto').value = '';
    modoEdicion = false;
};

// --- UTILIDADES Y FILTROS ---

document.getElementById('telefono').addEventListener('input', function(e) {
    let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
    e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
});

function filtrarTabla() {
    const texto = document.getElementById("filtro").value.toLowerCase();
    const usarNombre = !document.getElementById("tipoFiltro").checked; 
    const filas = document.querySelectorAll("#cuerpo-tabla tr");

    filas.forEach(fila => {
        let valor = usarNombre 
            ? fila.children[1].innerText.toLowerCase()  
            : fila.children[2].innerText.toLowerCase(); 
        fila.style.display = valor.includes(texto) ? "" : "none";
    });
}

document.getElementById("filtro").addEventListener("keyup", filtrarTabla);
document.getElementById("tipoFiltro").addEventListener("change", filtrarTabla);

function formatearNombre(idDelInput) {
    const input = document.getElementById(idDelInput);
    if (!input) return;
    input.addEventListener('input', function() {
        if (this.value.length > 0) {
            this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
        }
    });
}