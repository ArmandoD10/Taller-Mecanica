let vehiculos = [];
let modoEdicion = false;
let currentPage = 1;
const recordsPerPage = 6;

document.addEventListener('DOMContentLoaded', () => {
    cargarSelects();
    cargarTablaVehiculos();
    
    // Convertir a mayúsculas chasis y placa automáticamente
    const chasisInput = document.getElementById('vin_chasis');
    if(chasisInput) chasisInput.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
    
    const placaInput = document.getElementById('placa');
    if(placaInput) placaInput.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
});

// --- CARGAR DROPDOWNS ---
function cargarSelects() {
    fetch('/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Vehiculo.php?action=get_selects')
    .then(res => res.json())
    .then(data => {
        llenarSelect('id_cliente', data.clientes, 'Seleccione un cliente');
        llenarSelect('id_marca', data.marcas, 'Seleccione una marca');
        llenarSelect('id_color', data.colores, 'Seleccione un color');
    })
    .catch(err => console.error("Error al cargar listas:", err));
}

function llenarSelect(id_elemento, array_datos, placeholder) {
    const select = document.getElementById(id_elemento);
    select.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
    array_datos.forEach(item => {
        // Asumimos que la llave primaria del array es la primera propiedad (id_cliente, id_marca...)
        const id = Object.values(item)[0]; 
        const nombre = Object.values(item)[1];
        select.innerHTML += `<option value="${id}">${nombre}</option>`;
    });
}

// --- TABLA Y PAGINACIÓN ---
function cargarTablaVehiculos(page = 1) {
    fetch(`/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Vehiculo.php?action=cargar&page=${page}&limit=${recordsPerPage}`)
    .then(response => response.json())
    .then(data => {
        vehiculos = data.data; 
        const tbody = document.getElementById("cuerpo-tabla");
        tbody.innerHTML = "";

        if (vehiculos.length > 0) {
            vehiculos.forEach(registro => {
                let isActivo = registro.estado.toLowerCase() === 'activo';
                let colorEstado = isActivo ? 'bg-success' : 'bg-danger';
                let colorBtn = isActivo ? 'btn-secondary' : 'btn-success';
                let tituloBtn = isActivo ? 'Desactivar Vehículo' : 'Activar Vehículo';
                let nuevoEstado = isActivo ? 'inactivo' : 'activo';

                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${registro.id_vehiculo}</td>
                    <td>${registro.cliente_nombre}</td>
                    <td>${registro.vin_chasis}</td>
                    <td>${registro.placa || 'S/P'}</td>
                    <td>${registro.marca_nombre} ${registro.modelo} (${registro.color_nombre})</td>
                    <td>${registro.anio} / ${registro.kilometraje_actual} km</td>
                    <td><span class="badge ${colorEstado}">${registro.estado}</span></td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm me-2" onclick="editarRegistro(${registro.id_vehiculo})" title="Editar Vehículo">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn ${colorBtn} btn-sm" onclick="cambiarEstado(${registro.id_vehiculo}, '${nuevoEstado}')" title="${tituloBtn}">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(fila);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center">No hay vehículos registrados.</td></tr>`;
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

window.cambiarPagina = function(page) { if (page > 0) cargarTablaVehiculos(page); };

// --- ACCIONES ---
window.editarRegistro = function(id) {
    const v = vehiculos.find(reg => reg.id_vehiculo == id);
    if (v) {
        document.getElementById('id_oculto').value = v.id_vehiculo;
        document.getElementById('id_cliente').value = v.id_cliente;
        document.getElementById('vin_chasis').value = v.vin_chasis;
        document.getElementById('placa').value = v.placa;
        document.getElementById('id_marca').value = v.id_marca;
        document.getElementById('modelo').value = v.modelo;
        document.getElementById('id_color').value = v.id_color;
        document.getElementById('anio').value = v.anio;
        document.getElementById('kilometraje_actual').value = v.kilometraje_actual;

        document.getElementById('btnMostrar').textContent = 'Actualizar';
        modoEdicion = true;
        document.getElementById('formulario').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

document.getElementById('formulario').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const url = modoEdicion ? "/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Vehiculo.php?action=actualizar" : "/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Vehiculo.php?action=guardar";

    fetch(url, { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            limpiarFormulario();
            cargarTablaVehiculos(currentPage);
        } else {
            alert('Error: ' + data.message);
        }
    });
});

window.cambiarEstado = function(id, estadoDeseado) {
    let accion = estadoDeseado === 'activo' ? 'activar' : 'desactivar';
    if (confirm(`¿Seguro que desea ${accion} este vehículo?`)) {
        const fd = new FormData();
        fd.append('id_vehiculo', id);
        fd.append('estado', estadoDeseado);

        fetch("/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Vehiculo.php?action=cambiar_estado", { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                cargarTablaVehiculos(currentPage);
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
    // Restaurar los placeholder de los selects
    document.getElementById('id_cliente').value = "";
    document.getElementById('id_marca').value = "";
    document.getElementById('id_color').value = "";
    modoEdicion = false;
};

// --- FILTRO DINÁMICO ---
document.getElementById("filtro").addEventListener("keyup", filtrarTabla);
document.getElementById("tipoFiltro").addEventListener("change", filtrarTabla);

function filtrarTabla() {
    const texto = document.getElementById("filtro").value.toLowerCase();
    const usarCliente = document.getElementById("tipoFiltro").checked; // Toggle 
    const filas = document.querySelectorAll("#cuerpo-tabla tr");

    filas.forEach(fila => {
        let valor = usarCliente 
            ? fila.children[1].innerText.toLowerCase()  // Columna Cliente
            : fila.children[2].innerText.toLowerCase() + " " + fila.children[3].innerText.toLowerCase(); // Chasis o Placa
        fila.style.display = valor.includes(texto) ? "" : "none";
    });
}