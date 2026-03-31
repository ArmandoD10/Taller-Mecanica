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
        // ❌ Quitamos la línea de id_cliente porque ya no es un select, es un buscador
        // llenarSelect('id_cliente', data.clientes, 'Seleccione un cliente'); 

        // ✅ Solo dejamos estos dos
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
                    <td>${registro.sec_vehiculo}</td>
                    <td>${registro.cliente_nombre}</td>
                    <td>${registro.vin_chasis}</td>
                    <td>${registro.placa || 'S/P'}</td>
                    <td>${registro.marca_nombre} ${registro.modelo} (${registro.color_nombre})</td>
                    <td>${registro.anio} / ${registro.kilometraje_actual} km</td>
                    <td><span class="badge ${colorEstado}">${registro.estado}</span></td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm me-2" onclick="editarRegistro(${registro.sec_vehiculo})" title="Editar Vehículo">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn ${colorBtn} btn-sm" onclick="cambiarEstado(${registro.sec_vehiculo}, '${nuevoEstado}')" title="${tituloBtn}">
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
// --- FUNCIÓN EDITAR REGISTRO (CORREGIDA) ---
window.editarRegistro = function(id) {
    // 1. Buscamos el vehículo en el array global usando la columna 'sec_vehiculo'
    const v = vehiculos.find(reg => reg.sec_vehiculo == id);
    
    if (v) {
        // 2. Llenamos el ID oculto del formulario con la secuencia
        document.getElementById('id_oculto').value = v.sec_vehiculo;

        // 3. SIMULAMOS LA SELECCIÓN DEL CLIENTE
        // Llamamos a la función seleccionarCliente con los datos que ya vienen en el objeto 'v'
        seleccionarCliente({
            id: v.id_cliente,
            nombre: v.cliente_nombre,
            doc: v.cliente_cedula || 'Verificado' // Asegúrate de traer 'cliente_cedula' en tu SQL de cargar()
        });

        // 4. LLENAMOS LOS CAMPOS DE TEXTO Y SELECTS ESTÁNDAR
        document.getElementById('vin_chasis').value = v.vin_chasis;
        document.getElementById('placa').value = v.placa;
        document.getElementById('id_marca').value = v.id_marca;
        document.getElementById('id_color').value = v.id_color;
        document.getElementById('anio').value = v.anio;
        document.getElementById('kilometraje_actual').value = v.kilometraje_actual;

        // 5. DISPARAMOS LA CASCADA DE MODELOS
        // Como el select de modelos se llena solo cuando cambias la marca,
        // obligamos al navegador a creer que el usuario cambió la marca manualmente.
        const selectMarca = document.getElementById('id_marca');
        const event = new Event('change');
        selectMarca.dispatchEvent(event);

        // 6. ESPERAMOS A QUE EL FETCH DE MODELOS TERMINE
        // Le damos un pequeño tiempo (300-500ms) para que el servidor responda
        // y luego seleccionamos el modelo que ya estaba guardado.
        setTimeout(() => {
            const selectModelo = document.getElementById('id_modelo_rel');
            selectModelo.value = v.modelo;
            
            // Si el valor no se asigna (porque el fetch tardó más), intentamos una vez más
            if (selectModelo.value === "" && v.modelo !== "") {
                selectModelo.value = v.modelo;
            }
        }, 500);

        // 7. INTERFAZ: Cambiamos botón y subimos el scroll
        document.getElementById('btnMostrar').textContent = 'Actualizar';
        modoEdicion = true;
        
        // Scroll suave al inicio del formulario
        document.getElementById('formulario').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    } else {
        console.error("No se encontró el vehículo con secuencia:", id);
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
        fd.append('sec_vehiculo', id);
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

//  nuevas funciones.
let timeoutCliente = null; // Variable para controlar el tiempo de espera al escribir

// --- BUSCADOR DINÁMICO DE CLIENTES ---
const inputBusquedaCliente = document.getElementById('buscar_cliente');
if (inputBusquedaCliente) {
    inputBusquedaCliente.addEventListener('input', function() {
        const term = this.value.trim();
        const lista = document.getElementById('lista_clientes_res');
        clearTimeout(timeoutCliente);

        if (term.length < 2) { 
            lista.classList.add('d-none'); 
            return; 
        }

        // Esperamos 300ms después de que el usuario deja de escribir para no saturar el servidor
        timeoutCliente = setTimeout(() => {
            fetch(`/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Vehiculo.php?action=buscar_cliente&term=${term}`)
            .then(res => res.json())
            .then(data => {
                lista.innerHTML = '';
                if(data.length > 0) {
                    lista.classList.remove('d-none');
                    data.forEach(cli => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item list-group-item-action';
                        li.style.cursor = 'pointer';
                        li.innerHTML = `<strong>ID: ${cli.id}</strong> - ${cli.nombre} <small class="text-muted">(${cli.doc})</small>`;
                        li.onclick = () => seleccionarCliente(cli);
                        lista.appendChild(li);
                    });
                } else { 
                    lista.classList.add('d-none'); 
                }
            });
        }, 300);
    });
}

// Función para cuando el usuario hace clic en un resultado de la lista
function seleccionarCliente(cli) {
    document.getElementById('id_cliente').value = cli.id; // Input hidden para el form
    document.getElementById('lbl_cli_id').innerText = cli.id;
    document.getElementById('lbl_cli_nombre').innerText = cli.nombre;
    document.getElementById('lbl_cli_doc').innerText = cli.doc;
    
    document.getElementById('info_cliente_seleccionado').classList.remove('d-none');
    document.getElementById('lista_clientes_res').classList.add('d-none');
    document.getElementById('buscar_cliente').value = ''; // Limpiamos el buscador
}

// Función para quitar al cliente seleccionado (Botón X)
function deseleccionarCliente() {
    document.getElementById('id_cliente').value = '';
    document.getElementById('info_cliente_seleccionado').classList.add('d-none');
}


// --- CASCADA DE MODELOS ---
document.getElementById('id_marca').addEventListener('change', function() {
    const idMarca = this.value;
    const selectModelo = document.getElementById('id_modelo_rel');
    
    // Si no hay marca seleccionada, deshabilitamos el modelo
    if (!idMarca) {
        selectModelo.disabled = true;
        return;
    }

    selectModelo.disabled = false;
    selectModelo.innerHTML = '<option value="" disabled selected>Cargando modelos...</option>';

    fetch(`/Taller/Taller-Mecanica/modules/Vehiculo/Archivo_Vehiculo.php?action=get_modelos_por_marca&id_marca=${idMarca}`)
    .then(res => res.json())
    .then(data => {
        selectModelo.innerHTML = '<option value="" disabled selected>Seleccione Modelo</option>';
        data.forEach(nombreModelo => {
            // Guardamos el NOMBRE del modelo como valor, tal como pediste
            selectModelo.innerHTML += `<option value="${nombreModelo}">${nombreModelo}</option>`;
        });
    })
    .catch(err => console.error("Error al cargar modelos:", err));
});

const inputPlaca = document.getElementById('placa');

if (inputPlaca) {
    inputPlaca.addEventListener('input', function (e) {
        // 1. Convertir todo a mayúsculas automáticamente
        let valor = e.target.value.toUpperCase();

        // 2. Filtrar: Solo permitir letras de la A a la Z y números del 0 al 9
        // Borra cualquier espacio, punto, guion o minúscula
        valor = valor.replace(/[^A-Z0-9]/g, '');

        // 3. Limitar a máximo 7 caracteres (por si el maxlength falla)
        if (valor.length > 7) {
            valor = valor.slice(0, 7);
        }

        e.target.value = valor;
    });

    // Validación extra al perder el foco (blur)
    inputPlaca.addEventListener('blur', function () {
        if (this.value.length > 0 && this.value.length < 7) {
            alert("La placa debe tener exactamente 7 caracteres.");
            this.classList.add('is-invalid'); // Clase de Bootstrap para marcar error
        } else {
            this.classList.remove('is-invalid');
        }
    });
}

const inputAnio = document.getElementById('anio');

if (inputAnio) {
    inputAnio.addEventListener('input', function () {
        // 1. Si escriben más de 4 caracteres, cortamos el excedente
        if (this.value.length > 4) {
            this.value = this.value.slice(0, 4);
        }
    });

    inputAnio.addEventListener('blur', function () {
        const anioVal = parseInt(this.value);
        const anioActual = new Date().getFullYear();

        // 2. Validación lógica: No menor a 1950 ni mayor al año actual + 1
        if (anioVal < 1950 || anioVal > (anioActual + 1)) {
            alert("Por favor, ingrese un año válido (entre 1950 y " + (anioActual + 1) + ")");
            this.value = ""; // Limpiamos si es inválido
            this.focus();
        }
    });
}