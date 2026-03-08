// Función para limpiar el formulario de registro de clientes
function limpiarFormulario() {
    const campos = document.querySelectorAll("input, textarea, select");

    campos.forEach(campo => {
        switch(campo.type) {
            case "checkbox":
            case "radio":
                campo.checked = false;
                break;
            default:
                campo.value = "";
        }
    });
}

//---------------------------------------------------------

let usuario = [];
let modoEdicion = false;
let currentPage = 1;
const recordsPerPage = 6;

// --- LÓGICA PARA CARGAR TABLA CON PAGINACIÓN ---
function cargarTablaAreas(page = 1) {
    fetch(`/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Usuario.php?action=cargar&page=${page}&limit=${recordsPerPage}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al cargar la tabla: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        usuario = data.data; // Guardamos los datos de la página actual
        const tbody = document.getElementById("cuerpo-tabla");
        tbody.innerHTML = "";

        if (usuario.length > 0) {
            usuario.forEach(registro => {
                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${registro.id_usuario}</td>
                    <td>${registro.username}</td>
                    <td>${registro.nivel}</td>
                    <td>${registro.interfaz_acceso}</td>
                    <td>${registro.correo_org}</td>
                    <td>${registro.fecha_creacion}</td>
                    <td>${registro.estado}</td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm me-2" onclick="editarRegistro(${registro.id_usuario})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarRegistro(${registro.id_usuario})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(fila);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center">No hay registros existentes.</td></tr>`;
        }

        // Generar la paginación
        generarPaginacion(data.total_records, data.page, data.limit);
    })
    .catch(error => {
        console.error("Error al obtener los datos:", error);
    });
}

// Función para generar los botones de paginación
function generarPaginacion(totalRecords, currentPage, limit) {
    const totalPages = Math.ceil(totalRecords / limit);
    const paginationContainer = document.getElementById('pagination-container');
    paginationContainer.innerHTML = ''; // Limpiar botones anteriores

    if (totalPages <= 1) return;

    // Botón "Anterior"
    const liPrev = document.createElement('li');
    liPrev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    liPrev.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${currentPage - 1})">Anterior</a>`;
    paginationContainer.appendChild(liPrev);

    // Botones de las páginas
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${i})">${i}</a>`;
        paginationContainer.appendChild(li);
    }

    // Botón "Siguiente"
    const liNext = document.createElement('li');
    liNext.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    liNext.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${currentPage + 1})">Siguiente</a>`;
    paginationContainer.appendChild(liNext);
}

// Función para cambiar de página
window.cambiarPagina = function(page) {
    if (page > 0) {
        cargarTablaAreas(page);
    }
};

// Llamar a la función al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    cargarTablaAreas();
});

// --- FUNCIONES PARA EL FORMULARIO DE ÁREAS (Se usa el array 'areas') ---
window.editarRegistro = function(id) {
    const proveedorParaEditar = usuario.find(registro => registro.id_usuario == id);
    

    if (proveedorParaEditar) {
        document.getElementById('id_oculto').value = proveedorParaEditar.id_usuario;
        document.getElementById('nombre').value = proveedorParaEditar.username;
        document.getElementById('nivel').value = proveedorParaEditar.nivel;
        document.getElementById('correo').value = proveedorParaEditar.correo_org;
        document.getElementById('interfaz').value = proveedorParaEditar.interfaz_acceso  ;
        
        document.getElementById('btnMostrar').textContent = 'Actualizar';
        modoEdicion = true;
        console.log("gragrabomom");
        // --- Código agregado para hacer scroll al formulario ---
        document.getElementById('formulario').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    } else {
        console.error(`No se encontró el usuario con ID: ${id}`);
    }
};

window.eliminarRegistro = function(id) {
    // ... Tu lógica de eliminación ...
    console.log(`Eliminar registro con ID: ${id}`);
};


function resetearFormulario() {
    document.getElementById('formulario').reset();
    document.getElementById('btnMostrar').textContent = 'Registrar';
    document.getElementById('id_oculto').value = '';
    modoEdicion = false;
};

// --- LÓGICA PARA EL FILTRO DE LA TABLA DE PROVEEDORES ---
document.getElementById('filtro').addEventListener('keyup', function() {
    const valorFiltro = this.value.toLowerCase();
    const filas = document.querySelectorAll('#cuerpo-tabla tr');

    filas.forEach(fila => {
        const celdas = fila.getElementsByTagName('td');
        let coincide = false;
        
        // Modificamos el bucle para que solo revise las dos primeras columnas
        // (índice 0 para el ID y el índice 1 para el nombre)
        for (let i = 0; i < 2; i++) {
            // Aseguramos que la celda exista antes de intentar acceder a ella
            if (celdas[i]) {
                const textoCelda = celdas[i].textContent || celdas[i].innerText;
                if (textoCelda.toLowerCase().includes(valorFiltro)) {
                    coincide = true;
                    break;
                }
            }
        }

        if (coincide) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
});

// --- LÓGICA PARA MODIFICAR Y ELIMINAR proveedores ---
document.getElementById('formulario').addEventListener('submit', function(e) {
    if (modoEdicion) {
        e.preventDefault(); // Evita que el formulario se envíe de forma normal
        const formData = new FormData(this);
        
        fetch("/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Usuario.php?action=actualizar", {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                resetearFormulario();
                location.reload();
            } else {
                alert('Error al actualizar: ' + data.message);
            }
        })
        .catch(error => {
            console.error("Error en la solicitud:", error);
            alert('Hubo un problema al conectar con el servidor.');
        });
    }
});

window.eliminarRegistro = function(id) {
    if (confirm(`¿Estás seguro de que quieres desactivar el registro con ID ${id}?`)) {
        fetch("/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Usuario.php?action=desactivar", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id_usuario=${id}` // <-- Corrección del nombre del parámetro a "id_sala"
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error("Error en la solicitud:", error);
            alert('Hubo un problema al conectar con el servidor.');
        });
    }
};


document.addEventListener('DOMContentLoaded', function() {

    const telefonoInput = document.getElementById('telefono');

    telefonoInput.addEventListener('input', function(e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    });
});

// --- FUNCIÓN PARA MOSTRAR ALERTA DE ÉXITO ---
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('registro') === 'exito') {
        alert('Registro guardado con exito');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

 // Función que pone la primera letra en mayúscula
 function formatearNombre(idDelInput) {
    const input = document.getElementById(idDelInput);

    // Si el input no existe, sal de la función para evitar errores
    if (!input) {
        console.error(`No se encontró un elemento con el ID: ${idDelInput}`);
        return;
    }
    
    input.addEventListener('input', function() {
        let valor = this.value;

        // 2. Convierte la primera letra a mayúscula
        if (valor.length > 0) {
            const primeraLetra = valor.charAt(0).toUpperCase();
            const restoDeLaCadena = valor.slice(1);
            valor = primeraLetra + restoDeLaCadena;
        }

        // 3. Asigna el valor final al input
        this.value = valor;
    });
}

//FUNCION PAR AMANEJAR MENSAJES DE JSON.
document.getElementById('formulario').addEventListener('submit', function(e) {
    e.preventDefault(); // Siempre previene el envío por defecto

    const formData = new FormData(this);
    let url;

    // --- CAMBIO AQUÍ: Lógica para elegir la URL de guardado o actualización ---
    if (modoEdicion) {
        url = "/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Usuario.php?action=actualizar";
    } else {
        url = "/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Usuario.php?action=guardar";
    }

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            resetearFormulario();
            // Recargar la tabla o la página para ver el nuevo registro
            location.reload(); 
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error("Error en la solicitud:", error);
        alert('Hubo un problema al conectar con el servidor.');
    });
});

// // Llama a la función para cada campo que necesites
// document.addEventListener('DOMContentLoaded', function() {
// formatearNombre('nombre');

// });