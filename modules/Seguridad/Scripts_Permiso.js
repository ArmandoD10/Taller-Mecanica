let niveles = [];
let currentPage = 1;
const recordsPerPage = 6;

/* =========================
   🚀 INICIO AUTOMÁTICO
========================= */
document.addEventListener("DOMContentLoaded", () => {
    cargarModulos();
    cargarTablaNiveles();
});


/* =========================
   📦 CARGAR MÓDULOS (CHECKBOX)
========================= */
function cargarModulos(){

    fetch("/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Permisos.php?action=modulos")
    .then(res => res.json())
    .then(data => {

        const contenedor = document.getElementById("contenedorModulos");
        contenedor.innerHTML = "";

        data.forEach(mod => {
            contenedor.innerHTML += `
                <div class="col-md-4">
                    <label>
                        <input type="checkbox" value="${mod.id_modulo}">
                        ${mod.nombre}
                    </label>
                </div>
            `;
        });
    });
}

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


/* =========================
   📊 CARGAR TABLA DE NIVELES
========================= */
function cargarTablaNiveles(page = 1){

    fetch(`/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Permisos.php?action=cargar&page=${page}&limit=${recordsPerPage}`)
    .then(res => res.json())
    .then(data => {

        niveles = data.data;

        const tbody = document.getElementById("cuerpo-niveles");
        tbody.innerHTML = "";

        if(niveles.length > 0){

            niveles.forEach(n => {

                const fila = document.createElement("tr");

                fila.innerHTML = `
                    <td>${n.id_nivel}</td>
                    <td>${n.nombre}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editarNivel(${n.id_nivel})">
                            Editar
                        </button>
                    </td>
                `;

                tbody.appendChild(fila);
            });

        } else {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center">No hay registros</td></tr>`;
        }

        generarPaginacion(data.total_records, data.page, data.limit);
    });
}

/* =========================
   💾 GUARDAR NIVEL + PERMISOS
========================= */
function guardarNivel() {
    const nombre = document.getElementById("nombre").value;
    const id = document.getElementById("id_nivel").value;

    if (!nombre) {
        // Reemplazamos el alert de validación
        Swal.fire({
            title: 'Campo requerido',
            text: 'Por favor, ingrese un nombre de nivel',
            icon: 'warning',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    const checks = document.querySelectorAll("#contenedorModulos input:checked");
    let modulos = [];
    checks.forEach(c => modulos.push(parseInt(c.value)));

    const formData = new FormData();
    formData.append("id_nivel", id);
    formData.append("nombre", nombre);
    modulos.forEach(m => formData.append("modulos[]", m));

    fetch("/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Permisos.php?action=guardar", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success || data) { // Ajustado para manejar la respuesta
            // MODAL DE ÉXITO ESTILO MODERNO
            Swal.fire({
                title: '¡Éxito!',
                text: 'El nivel y sus permisos se han guardado correctamente',
                icon: 'success',
                confirmButtonColor: '#28a745'
            }).then(() => {
                limpiar();
                cargarTablaNiveles();
                
                // Restablecer el botón a su estado original
                const btn = document.getElementById("btnGuardar");
                if (btn) {
                    btn.textContent = "Guardar";
                    btn.classList.remove("btn-warning");
                    btn.classList.add("btn-primary");
                }
            });
        } else {
            Swal.fire('Error', 'No se pudo guardar la información', 'error');
        }
    })
    .catch(error => {
        console.error("Error:", error);
        Swal.fire('Error', 'Hubo un fallo en la conexión con el servidor', 'error');
    });
}



/* =========================
   ✏️ EDITAR NIVEL
========================= */
function editarNivel(id){

    console.log("Editar nivel:", id);

    // 🔥 1. Guardar ID
    const inputId = document.getElementById("id_nivel");
    if(inputId) inputId.value = id;

    // 🔥 2. BUSCAR EL NOMBRE DESDE LA TABLA YA CARGADA
    const nivel = niveles.find(n => n.id_nivel == id);

    if(nivel){
        document.getElementById("nombre").value = nivel.nombre;
    } else {
        console.warn("No se encontró el nivel");
    }

    // 🔥 3. CAMBIAR BOTÓN
    const btn = document.getElementById("btnGuardar");
    if(btn){
        btn.textContent = "Modificar";
        btn.classList.remove("btn-primary");
        btn.classList.add("btn-warning");
    }

    // 🔥 4. CARGAR PERMISOS DESDE PHP
    fetch(`/Taller/Taller-Mecanica/modules/Seguridad/Archivo_Permisos.php?action=obtener_permisos&id_nivel=${id}`)
    .then(res => res.json())
    .then(data => {

        console.log("Permisos:", data);

        const checks = document.querySelectorAll("#contenedorModulos input");

        checks.forEach(chk => {

            chk.checked = false;

            const permiso = data.find(p => 
                parseInt(p.id_modulo) === parseInt(chk.value)
            );

            if(permiso && permiso.estado === "activo"){
                chk.checked = true;
            }
        });
    });
}


/* =========================
   🧹 LIMPIAR
========================= */
function limpiar(){

    document.getElementById("nombre").value = "";
    document.getElementById("id_nivel").value = "";

    document.querySelectorAll("#contenedorModulos input")
    .forEach(c => c.checked = false);

        // 🔥 3. CAMBIAR BOTÓN
    const btn = document.getElementById("btnGuardar");
    if(btn){
        btn.textContent = "Guardar";
        btn.classList.remove("btn-warning");
        btn.classList.add("btn-primary");
    }
}


/* =========================
   📄 PAGINACIÓN
========================= */
function generarPaginacion(totalRecords, currentPage, limit) {

    const totalPages = Math.ceil(totalRecords / limit);
    const container = document.getElementById('pagination-container');
    container.innerHTML = '';

    if (totalPages <= 1) return;

    const prev = document.createElement('li');
    prev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prev.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${currentPage - 1})">Anterior</a>`;
    container.appendChild(prev);

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${i})">${i}</a>`;
        container.appendChild(li);
    }

    const next = document.createElement('li');
    next.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    next.innerHTML = `<a class="page-link" href="#" onclick="cambiarPagina(${currentPage + 1})">Siguiente</a>`;
    container.appendChild(next);
}

window.cambiarPagina = function(page){
    if(page > 0){
        cargarTablaNiveles(page);
    }
};

document.getElementById("filtro").addEventListener("keyup", function () {
    cargarNiveles(1); // vuelve a cargar desde la página 1
});