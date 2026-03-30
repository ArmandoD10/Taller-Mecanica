let marcas = [];
let modoEdicion = false;
let currentPage = 1;
const recordsPerPage = 6;

document.addEventListener('DOMContentLoaded', () => {
    cargarPaises();
    cargarTablaMarcas();
    
    // Auto Mayúscula para la Marca
    const inputNombre = document.getElementById('nombre');
    if(inputNombre) {
        inputNombre.addEventListener('input', function() { 
            if (this.value.length > 0) {
                this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
            }
        });
    }
});

function cargarPaises() {
    fetch('/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Marca.php?action=get_paises')
    .then(res => res.json())
    .then(data => {
        const select = document.getElementById('id_pais');
        select.innerHTML = `<option value="" disabled selected>Seleccione un País</option>`;
        data.paises.forEach(pais => {
            select.innerHTML += `<option value="${pais.id_pais}">${pais.nombre}</option>`;
        });
    })
    .catch(err => console.error("Error al cargar países:", err));
}

function cargarTablaMarcas(page = 1) {
    fetch(`/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Marca.php?action=cargar&page=${page}&limit=${recordsPerPage}`)
    .then(response => response.json())
    .then(data => {
        marcas = data.data; 
        const tbody = document.getElementById("cuerpo-tabla");
        tbody.innerHTML = "";

        if (marcas.length > 0) {
            marcas.forEach(registro => {
                let isActivo = registro.estado.toLowerCase() === 'activo';
                let colorEstado = isActivo ? 'bg-success' : 'bg-danger';
                let colorBtn = isActivo ? 'btn-secondary' : 'btn-success';
                let tituloBtn = isActivo ? 'Desactivar Marca' : 'Activar Marca';
                let nuevoEstado = isActivo ? 'inactivo' : 'activo';

                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${registro.id_marca}</td>
                    <td class="fw-bold">${registro.marca_nombre}</td>
                    <td>${registro.pais_nombre}</td>
                    <td>${registro.correo || 'N/A'}</td>
                    <td><span class="badge ${colorEstado}">${registro.estado}</span></td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm me-2" onclick="editarRegistro(${registro.id_marca})" title="Editar Marca">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn ${colorBtn} btn-sm" onclick="cambiarEstado(${registro.id_marca}, '${nuevoEstado}')" title="${tituloBtn}">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(fila);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center">No hay marcas registradas.</td></tr>`;
        }
        generarPaginacion(data.total_records, data.page, data.limit);
    });
}

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

window.cambiarPagina = function(page) { if (page > 0) cargarTablaMarcas(page); };

// --- ACCIONES ---
window.editarRegistro = function(id) {
    const m = marcas.find(reg => reg.id_marca == id);
    if (m) {
        document.getElementById('id_oculto').value = m.id_marca;
        document.getElementById('nombre').value = m.marca_nombre;
        document.getElementById('id_pais').value = m.id_pais;
        document.getElementById('correo').value = m.correo;

        document.getElementById('btnMostrar').textContent = 'Actualizar';
        modoEdicion = true;
        document.getElementById('formulario').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

document.getElementById('formulario').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const url = modoEdicion ? "/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Marca.php?action=actualizar" : "/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Marca.php?action=guardar";

    fetch(url, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            limpiarFormulario();
            cargarTablaMarcas(currentPage);
        } else {
            alert('Error: ' + data.message);
        }
    });
});

window.cambiarEstado = function(id, estadoDeseado) {
    let accion = estadoDeseado === 'activo' ? 'activar' : 'desactivar';
    if (confirm(`¿Seguro que desea ${accion} esta marca?`)) {
        const fd = new FormData();
        fd.append('id_marca', id);
        fd.append('estado', estadoDeseado);

        fetch("/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Marca.php?action=cambiar_estado", { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                cargarTablaMarcas(currentPage);
            } else {
                alert("Error: " + data.message);
            }
        });
    }
};

window.limpiarFormulario = function() {
    document.getElementById('formulario').reset();
    document.getElementById('btnMostrar').textContent = 'Registrar';
    document.getElementById('id_oculto').value = '';
    document.getElementById('id_pais').value = ""; // Reset del select
    modoEdicion = false;
};

// --- FILTRO DINÁMICO ---
document.getElementById("filtro").addEventListener("keyup", function() {
    const texto = this.value.toLowerCase();
    const filas = document.querySelectorAll("#cuerpo-tabla tr");

    filas.forEach(fila => {
        // Busca coincidencias en el nombre de la Marca (Índice 1)
        let valor = fila.children[1].innerText.toLowerCase(); 
        fila.style.display = valor.includes(texto) ? "" : "none";
    });
});