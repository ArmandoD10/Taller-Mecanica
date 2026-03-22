//---------------------------------------------------------

let usuario = [];
let modoEdicion = false;
let currentPage = 1;
const recordsPerPage = 11;

// --- LÓGICA PARA CARGAR TABLA CON PAGINACIÓN ---
function cargarTablaAreas(page = 1) {
    fetch(`/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Historial_Acceso.php?action=cargar&page=${page}&limit=${recordsPerPage}`)
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

                // Aplicar color según tipo
                if (registro.tipo === "Login") {
                    fila.classList.add("fila-login");
                } else if (registro.tipo === "Logout") {
                    fila.classList.add("fila-logout");
                }

                fila.innerHTML = `
                    <td>${registro.sec_log}</td>
                    <td>${registro.id_usuario}</td>
                    <td>${registro.username}</td>
                    <td>${registro.ip_equipo}</td>
                    <td>${registro.fecha}</td>
                    <td>${registro.tipo}</td>
                `;
                tbody.appendChild(fila);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="10" class="text-center">No hay registros existentes.</td></tr>`;
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

// --- LÓGICA PARA EL FILTRO DE LA TABLA DE PROVEEDORES ---
document.getElementById('filtro').addEventListener('keyup', function() {
    const valorFiltro = this.value.toLowerCase();
    const filas = document.querySelectorAll('#cuerpo-tabla tr');

    filas.forEach(fila => {
        const celdas = fila.getElementsByTagName('td');
        let coincide = false;
        
        // Modificamos el bucle para que solo revise las dos primeras columnas
        // (índice 0 para el ID y el índice 1 para el nombre)
        for (let i = 1; i < 4; i++) {
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

//Funcion para swithc del filtro.
function filtrarTabla() {
    const texto = document.getElementById("filtro").value.toLowerCase();
    const usarUsername = document.getElementById("tipoFiltro").checked;

    const filas = document.querySelectorAll("#cuerpo-tabla tr");

    filas.forEach(fila => {
        let valor;

        if (usarUsername) {
            // Columna USERNAME (col 3 → índice 2)
            valor = fila.children[2].innerText.toLowerCase();
        } else {
            // Columna ID (col 2 → índice 1)
            valor = fila.children[1].innerText.toLowerCase();
        }

        fila.style.display = valor.includes(texto) ? "" : "none";
    });
}

document.getElementById("filtro").addEventListener("keyup", filtrarTabla);
document.getElementById("tipoFiltro").addEventListener("change", filtrarTabla);