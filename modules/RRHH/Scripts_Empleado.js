// 🔥 VARIABLES GLOBALES
let empleados = [];
let modoEdicion = false;

//---------------------------------------------------------
// 🧹 LIMPIAR FORMULARIO
//---------------------------------------------------------
function limpiarFormulario() {
    const campos = document.querySelectorAll("input, textarea, select");

    campos.forEach(campo => {
        if (campo.type === "checkbox" || campo.type === "radio") {
            campo.checked = false;
        } else {
            campo.value = "";
        }
    });

    document.getElementById("btnGuardar").textContent = "Registrar";
    modoEdicion = false;

    document.getElementById("contenedor-estado").classList.add("d-none");
}

//---------------------------------------------------------
// 📊 CARGAR TABLA (DATOS BÁSICOS)
//---------------------------------------------------------
let empleadosRaw = [];

function cargarTabla(page = 1) {
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar`)
    .then(res => res.json())
    .then(data => {
        // CORRECCIÓN: Guardamos en 'empleadosRaw', que es la que usa el filtro
        empleadosRaw = data.data; 

        // Usamos la función que ya creaste para no repetir código
        renderizarTabla(empleadosRaw);
    })
    .catch(error => console.error("Error al cargar:", error));
}
// function cargarTabla(page = 1) {
//     fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar`)
//     .then(res => res.json())
//     .then(data => {

//         empleados = data.data;

//         const tbody = document.getElementById("cuerpo-tabla");
//         tbody.innerHTML = "";

//         empleados.forEach(emp => {
//             const fila = document.createElement("tr");

//             fila.innerHTML = `
//                 <td>${emp.id_empleado}</td>
//                 <td>${emp.nombre}</td>
//                 <td>${emp.apellido_p}</td>
//                 <td>${emp.cedula}</td>
//                 <td>${emp.puesto}</td>
//                 <td>${emp.sueldo}</td>
//                 <td>${emp.estado}</td>
//                 <td>
//                     <button class="btn btn-warning btn-sm" onclick="editarRegistro(${emp.id_empleado})">
//                         <i class="fas fa-edit"></i>
//                     </button>
//                 </td>
//             `;

//             tbody.appendChild(fila);
//         });
//     });
// }

//---------------------------------------------------------
// 🔽 Funcion para renderizar tabla en el filtro
//---------------------------------------------------------
function renderizarTabla(lista) {
    const tbody = document.getElementById("cuerpo-tabla");
    tbody.innerHTML = "";

    lista.forEach(emp => {
        // Determinamos el color: verde para activo, rojo para inactivo
        const colorBadge = emp.estado.toLowerCase() === 'activo' ? 'bg-success' : 'bg-danger';
        
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${emp.id_empleado}</td>
            <td>${emp.nombre}</td>
            <td>${emp.apellido_p}</td>
            <td>${emp.cedula}</td>
            <td>${emp.puesto}</td>
            <td>${emp.sueldo}</td>
            <td><span class="badge rounded-pill ${colorBadge}">${emp.estado}</span></td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="editarRegistro(${emp.id_empleado})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });
}

// Evento que detecta cuando escribes en el buscador
document.getElementById('filtro').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase();
    
    // Detectamos cuál radio button está marcado
    const radioSeleccionado = document.querySelector('input[name="criterioFiltro"]:checked');
    const criterio = radioSeleccionado ? radioSeleccionado.value : 'nombre';

    // Filtramos el array global
    const resultados = empleadosRaw.filter(emp => {
        // Obtenemos el valor de la columna correspondiente (id, nombre o cedula)
        const valorCampo = String(emp[criterio]).toLowerCase();
        return valorCampo.includes(busqueda);
    });

    // Dibujamos solo los resultados filtrados
    renderizarTabla(resultados);
});

// Limpiar el buscador si cambian de opción (ID -> Nombre, etc.)
document.querySelectorAll('input[name="criterioFiltro"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const inputFiltro = document.getElementById('filtro');
        inputFiltro.value = ""; // Limpia el texto
        inputFiltro.focus();
        renderizarTabla(empleadosRaw); // Muestra todos otra vez
    });
});

//---------------------------------------------------------
// 🔽 CARGAR SELECTS PRINCIPALES
//---------------------------------------------------------
function cargarSelects() {

    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar_selects")
    .then(res => res.json())
    .then(data => {

        console.log(data);

        // 🔹 PAIS
        const pais = document.getElementById("pais");
        pais.innerHTML = '<option disabled selected>Seleccione</option>';
        data.data.pais.forEach(p => {
            pais.innerHTML += `<option value="${p.id_pais}">${p.nombre}</option>`;
        });

        const nacionalidad = document.getElementById("nacionalidad");
        nacionalidad.innerHTML = '<option disabled selected>Seleccione</option>';
        data.data.pais.forEach(p => {
            nacionalidad.innerHTML += `<option value="${p.id_pais}">${p.nombre}</option>`;
        });

        // 🔹 PUESTO
        const puesto = document.getElementById("puesto");
        puesto.innerHTML = '<option disabled selected>Seleccione</option>';
        data.data.puesto.forEach(p => {
            puesto.innerHTML += `<option value="${p.id_puesto}">${p.nombre}</option>`;
        });

        // 🔹 SUELDO
        const sueldo = document.getElementById("sueldo");
        sueldo.innerHTML = '<option disabled selected>Seleccione</option>';
        data.data.sueldo.forEach(s => {
            sueldo.innerHTML += `<option value="${s.id_sueldo}">${s.sueldo}</option>`;
        });

    });
}

//---------------------------------------------------------
// 🔁 PROVINCIA POR PAIS
//---------------------------------------------------------
document.getElementById("pais").addEventListener("change", function () {

    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar_provincias&id_pais=${this.value}`)
    .then(res => res.json())
    .then(data => {

        const provincia = document.getElementById("provincia");
        provincia.innerHTML = '<option disabled selected>Seleccione</option>';

        data.forEach(p => {
            provincia.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`;
        });

    });
});

//---------------------------------------------------------
// 🔁 CIUDAD POR PROVINCIA
//---------------------------------------------------------
document.getElementById("provincia").addEventListener("change", function () {

    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar_ciudades&id_provincia=${this.value}`)
    .then(res => res.json())
    .then(data => {

        const ciudad = document.getElementById("ciudad");
        ciudad.innerHTML = '<option disabled selected>Seleccione</option>';

        data.forEach(c => {
            ciudad.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`;
        });

    });
});

//---------------------------------------------------------
// ✏️ EDITAR (LLENA TODO)
//---------------------------------------------------------
window.editarRegistro = function(id) {

    const emp = empleadosRaw.find(e => e.id_empleado == id);

    if (!emp) return;

    // --- AGREGAR ESTO ---
    // Quitamos la clase d-none para que el campo de estado aparezca
    document.getElementById("contenedor-estado").classList.remove("d-none");

    document.getElementById("id_oculto").value = emp.id_empleado;

    document.getElementById("nombre1").value = emp.nombre;
    document.getElementById("nombre2").value = emp.nombre_dos;
    document.getElementById("apellido_p").value = emp.apellido_p;
    document.getElementById("apellido_m").value = emp.apellido_m;
    document.getElementById("cedula").value = emp.cedula;
    document.getElementById("correo").value = emp.email;
    document.getElementById("fecha_nacimiento").value = emp.fecha_nacimiento;
    document.getElementById("nacionalidad").value = emp.nacionalidad;
    document.getElementById("sexo").value = emp.sexo;
    console.log(emp.sexo);
    console.log(emp.sexo);

    document.getElementById("telefono").value = emp.telefono;
    document.getElementById("nombre_e").value = emp.contacto_nombre;
    document.getElementById("telefono_e").value = emp.contacto_tel;

    document.getElementById("puesto").value = emp.id_puesto;
    document.getElementById("sueldo").value = emp.id_sueldo;

    const estado = emp.estado?.toLowerCase();

        if (estado === "activo") {
            document.getElementById("activo").checked = true;
        } else if (estado === "inactivo") {
            document.getElementById("inactivo").checked = true;
        }

    // 🔥 IMPORTANTE: CARGAR CASCADA
    document.getElementById("pais").value = emp.id_pais;

    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar_provincias&id_pais=${emp.id_pais}`)
    .then(res => res.json())
    .then(prov => {
        const provincia = document.getElementById("provincia");
        provincia.innerHTML = "";
        prov.forEach(p => {
            provincia.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`;
        });

        provincia.value = emp.id_provincia;

        return fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar_ciudades&id_provincia=${emp.id_provincia}`);
    })
    .then(res => res.json())
    .then(ciudades => {
        const ciudad = document.getElementById("ciudad");
        ciudad.innerHTML = "";
        ciudades.forEach(c => {
            ciudad.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`;
        });

        ciudad.value = emp.id_ciudad;
    });

    document.getElementById("direccion").value = emp.direccion;

    document.getElementById("btnGuardar").textContent = "Actualizar";
    modoEdicion = true;

    document.getElementById("formulario").scrollIntoView({
        behavior: "smooth"
    });
};

//---------------------------------------------------------
// 💾 GUARDAR / ACTUALIZAR
//---------------------------------------------------------
document.getElementById("formulario").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    let url = modoEdicion
        ? "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=actualizar"
        : "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=guardar";

    fetch(url, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {
            alert(data.message);
            limpiarFormulario();
            location.reload();
        } else {
            alert("Error: " + data.message);
        }

    });
});

//---------------------------------------------------------
// 🚀 INIT
//---------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    cargarTabla();
    cargarSelects();
});

//---------------------------------------------------------
// Funcion para caracteres especiales en el nombre
//---------------------------------------------------------
document.addEventListener("DOMContentLoaded", function() {
    // Seleccionamos todos los inputs de nombres y apellidos por su ID o clase
    const camposNombres = document.querySelectorAll('#nombre1, #nombre2, #apellido_p, #apellido_m, #nombre_e');

    camposNombres.forEach(input => {
        input.addEventListener('input', function(e) {
            // 1. Solo permitir letras (incluyendo ñ y acentos)
            // Borra cualquier cosa que NO sea una letra o un espacio
            let valor = e.target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');

            // 2. Capitalizar la primera letra
            if (valor.length > 0) {
                valor = valor.charAt(0).toUpperCase() + valor.slice(1);
            }

            // Devolvemos el valor limpio al input
            e.target.value = valor;
        });
    });
});

//---------------------------------------------------------
// Funcion para caracteres especiales en la cedula
//---------------------------------------------------------
document.addEventListener("DOMContentLoaded", function() {
    const cedulaInput = document.getElementById('cedula');

    cedulaInput.addEventListener('input', function(e) {
        // 1. Eliminamos todo lo que no sea un número
        let valor = e.target.value.replace(/\D/g, '');
        
        // 2. Limitamos a 11 dígitos (el máximo de una cédula)
        valor = valor.substring(0, 11);

        // 3. Aplicamos la máscara 000-0000000-0
        let formateado = "";
        if (valor.length > 0) {
            // Primer bloque (000)
            formateado += valor.substring(0, 3);
            if (valor.length > 3) {
                // Segundo bloque (-0000000)
                formateado += "-" + valor.substring(3, 10);
            }
            if (valor.length > 10) {
                // Tercer bloque (-0)
                formateado += "-" + valor.substring(10, 11);
            }
        }

        e.target.value = formateado;
    });
});

//---------------------------------------------------------
// Funcion para caracteres especiales en el telefono
//---------------------------------------------------------
document.addEventListener("DOMContentLoaded", function() {
    const inputsTelefono = document.querySelectorAll('.f-telefono');

    inputsTelefono.forEach(input => {
        input.addEventListener('input', function(e) {
            // 1. Limpiar: solo números
            let num = e.target.value.replace(/\D/g, '');
            
            // 2. Limitar a 10 dígitos (formato estándar)
            num = num.substring(0, 10);

            // 3. Construir el formato (000) 000-0000
            let formateado = "";
            if (num.length > 0) {
                formateado += "(" + num.substring(0, 3);
                if (num.length > 3) {
                    formateado += ") " + num.substring(3, 6);
                }
                if (num.length > 6) {
                    formateado += "-" + num.substring(6, 10);
                }
            }

            e.target.value = formateado;
        });
    });
});

// Confirmacion antes de guardar o actualizar al inactivar.
document.getElementById("formulario").addEventListener("submit", function(e) {
    e.preventDefault();

    // --- NUEVA LÓGICA DE CONFIRMACIÓN ---
    if (modoEdicion) {
        // Buscamos el radio de inactivo
        const estaInactivo = document.getElementById("inactivo").checked;
        
        if (estaInactivo) {
            const respuesta = confirm("¿Está seguro de que desea inactivar a este empleado? El usuario ya no aparecerá en las listas operativas.");
            if (!respuesta) {
                return; // Si el usuario cancela, detenemos el envío del formulario
            }
        }
    }
    // ------------------------------------

    const formData = new FormData(this);

    let url = modoEdicion
        ? "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=actualizar"
        : "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=guardar";

    fetch(url, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            limpiarFormulario();
            location.reload();
        } else {
            alert("Error: " + data.message);
        }
    });
});