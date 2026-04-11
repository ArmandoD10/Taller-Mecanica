// 🔥 VARIABLES GLOBALES
let empleados = [];
let modoEdicion = false;

//---------------------------------------------------------
// 🧹 LIMPIAR FORMULARIO
//---------------------------------------------------------
window.limpiarFormulario = function() {
    // 1. Resetear los valores del formulario (texto, selects, etc.)
    const form = document.getElementById("formulario");
    if (form) {
        form.reset();
    }

    // 2. Limpiar manualmente los campos ocultos (IDs)
    // Esto es vital para que no se quede pegado un ID de edición o de cliente previo
    document.getElementById("id_oculto").value = "";
    const idPersonaCapturado = document.getElementById("id_persona_capturado");
    if (idPersonaCapturado) {
        idPersonaCapturado.value = "";
    }

    // 3. Resetear el botón de Guardar a su estado original
    const btnGuardar = document.getElementById("btnGuardar");
    if (btnGuardar) {
        btnGuardar.textContent = "Registrar";
        btnGuardar.disabled = true; // Se bloquea hasta que la búsqueda valide a alguien
    }

    // 4. Ocultar el selector de estado (solo para edición)
    document.getElementById("contenedor-estado").classList.add("d-none");
    modoEdicion = false;

    // 5. 🔒 ESTADO DE BLOQUEO INICIAL
    // Bloqueamos todos los campos personales y laborales
    bloquearCamposEmpleado(true);

    // 6. 🔓 EXCEPCIÓN: Habilitar Cédula y su botón de búsqueda
    // Estos deben ser los únicos activos para empezar un proceso nuevo
    const cedulaInput = document.getElementById("cedula");
    if (cedulaInput) {
        cedulaInput.disabled = false;
        cedulaInput.value = ""; // Aseguramos que esté vacío
        cedulaInput.focus();    // Ponemos el cursor ahí para comodidad del usuario
    }

    const btnLupa = document.getElementById("btnBuscarCedula");
    if (btnLupa) {
        btnLupa.disabled = false;
    }

    console.log("Formulario reiniciado: Listo para nueva búsqueda.");
};

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
                <button type="button" class="btn btn-warning btn-sm" onclick="editarRegistro(${emp.id_empleado})">
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

    // 1. Mostrar estado y llenar IDs
    document.getElementById("contenedor-estado").classList.remove("d-none");
    document.getElementById("id_oculto").value = emp.id_empleado;
    // Limpiamos el ID de persona capturado para evitar conflictos con la lógica de "nuevo cliente"
    const idPersonaCapturado = document.getElementById("id_persona_capturado");
    if(idPersonaCapturado) idPersonaCapturado.value = "";

    // 2. Llenar campos de texto
    document.getElementById("nombre1").value = emp.nombre;
    document.getElementById("nombre2").value = emp.nombre_dos || "";
    document.getElementById("apellido_p").value = emp.apellido_p;
    document.getElementById("apellido_m").value = emp.apellido_m || "";
    document.getElementById("cedula").value = emp.cedula;
    document.getElementById("correo").value = emp.email || "";
    document.getElementById("fecha_nacimiento").value = emp.fecha_nacimiento;
    document.getElementById("nacionalidad").value = emp.nacionalidad;
    document.getElementById("sexo").value = emp.sexo;
    document.getElementById("telefono").value = emp.telefono;
    document.getElementById("nombre_e").value = emp.contacto_nombre;
    document.getElementById("telefono_e").value = emp.contacto_tel;
    document.getElementById("puesto").value = emp.id_puesto;
    document.getElementById("sueldo").value = emp.id_sueldo;
    document.getElementById("direccion").value = emp.direccion;

    // 3. Radio de Estado
    const estado = emp.estado?.toLowerCase();
    if (estado === "activo") {
        document.getElementById("activo").checked = true;
    } else {
        document.getElementById("inactivo").checked = true;
    }

    // 4. Cargar Ubicación y DESBLOQUEAR CAMPOS
    document.getElementById("pais").value = emp.id_pais;
    
    // Función de cascada para edición
    cargarCascadaEdicion(emp.id_pais, emp.id_provincia, emp.id_ciudad).then(() => {
        // DESBLOQUEO TOTAL tras cargar la ubicación
        const todosLosInputs = document.querySelectorAll('#formulario input, #formulario select');
        todosLosInputs.forEach(input => {
            input.disabled = false;
        });

        // 5. BLOQUEO ESPECÍFICO DE IDENTIDAD
        // Bloqueamos la cédula y el botón de búsqueda para evitar cambiar el dueño del registro
        document.getElementById("cedula").disabled = true;
        const btnLupa = document.getElementById("btnBuscarCedula");
        if(btnLupa) btnLupa.disabled = true;
    });

    document.getElementById("btnGuardar").textContent = "Actualizar";
    modoEdicion = true;

    document.getElementById("formulario").scrollIntoView({ behavior: "smooth" });
};

// Función auxiliar para cargar ubicación sin chocar con los eventos change
async function cargarCascadaEdicion(idPais, idProv, idCiud) {
    const resProv = await fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar_provincias&id_pais=${idPais}`);
    const provs = await resProv.json();
    const selProv = document.getElementById("provincia");
    selProv.innerHTML = "";
    provs.forEach(p => selProv.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`);
    selProv.value = idProv;

    const resCiud = await fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar_ciudades&id_provincia=${idProv}`);
    const ciuds = await resCiud.json();
    const selCiud = document.getElementById("ciudad");
    selCiud.innerHTML = "";
    ciuds.forEach(c => selCiud.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`);
    selCiud.value = idCiud;
}

//---------------------------------------------------------
// 💾 GUARDAR / ACTUALIZAR
//---------------------------------------------------------
// document.getElementById("formulario").addEventListener("submit", function(e) {
//     e.preventDefault();

//     const formData = new FormData(this);

//     let url = modoEdicion
//         ? "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=actualizar"
//         : "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=guardar";

//     fetch(url, {
//         method: "POST",
//         body: formData
//     })
//     .then(res => res.json())
//     .then(data => {

//         if (data.success) {
//             alert(data.message);
//             limpiarFormulario();
//             location.reload();
//         } else {
//             alert("Error: " + data.message);
//         }

//     });
// });

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

// BORRA CUALQUIER OTRO "formulario.addEventListener('submit'..." 
// Y DEJA SOLO ESTE:
window.enviarFormulario = function() {
    const form = document.getElementById("formulario");
    
    // 1. Validar campos básicos antes de hacer nada
    if (document.getElementById("nombre1").value === "" || document.getElementById("cedula").value === "") {
        alert("Por favor, complete los campos obligatorios.");
        return;
    }

    // 2. HABILITAR TODO temporalmente para capturar los datos
    const elementos = form.querySelectorAll('input, select, textarea');
    const estadosPrevios = [];

    elementos.forEach(el => {
        estadosPrevios.push({ el: el, wasDisabled: el.disabled });
        el.disabled = false; 
    });

    // 3. Crear el FormData
    const formData = new FormData(form);

    // 4. Restaurar bloqueos inmediatamente para que la interfaz no parpadee
    estadosPrevios.forEach(item => {
        item.el.disabled = item.wasDisabled;
    });

    // 5. Determinar URL
    let url = modoEdicion 
        ? "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=actualizar" 
        : "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=guardar";

    // 6. Enviar vía FETCH
    fetch(url, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Recarga limpia
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => {
        console.error("Error en la petición:", err);
        alert("Error crítico al conectar con el servidor.");
    });
};
// Función de bloqueo selectivo
function bloquearCamposEmpleado(estado) {
    const inputs = document.querySelectorAll('#formulario input, #formulario select');
    const camposLaborales = ['puesto', 'sueldo', 'telefono_e', 'nombre_e']; // IDs de campos laborales

    inputs.forEach(input => {
        // Excepciones que siempre están habilitadas tras la búsqueda
        const esLaboral = camposLaborales.includes(input.id);
        const esBusqueda = input.id === 'cedula' || input.id === 'filtro';

        if (!esBusqueda && !esLaboral) {
            input.disabled = estado;
        } else if (esLaboral) {
            input.disabled = false; // Siempre habilitar laborales si se va a registrar
        }
    });
    document.getElementById('btnGuardar').disabled = estado;
}

// Evento de búsqueda por cédula
document.addEventListener("DOMContentLoaded", () => {
    // Vincular el botón de búsqueda
    const btnBuscar = document.getElementById('btnBuscarCedula');
    if (btnBuscar) {
        btnBuscar.addEventListener('click', ejecutarBusquedaEmpleado);
    }
});

/**
 * Ejecuta la búsqueda de cédula en el módulo de RRHH.
 * Si es empleado: Bloqueo total.
 * Si es cliente: Carga datos personales, activa correo y datos laborales.
 * Si es nuevo: Desbloquea todo.
 */
async function ejecutarBusquedaEmpleado() {
    const cedula = document.getElementById('cedula').value;
    
    // Validación mínima de longitud para cédula dominicana con guiones
    if (cedula.length < 13) { 
        alert("Por favor, ingrese una cédula válida (000-0000000-0).");
        return;
    }

    try {
        // Mostramos un indicador visual si lo deseas o cambiamos el cursor
        document.body.style.cursor = 'wait';

        const url = `/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=verificar_cedula_empleado&cedula=${cedula}`;
        const response = await fetch(url);
        const res = await response.json();

        document.body.style.cursor = 'default';

        if (res.status === 'empleado_existe') {
            alert(res.message); 
            bloquearCamposEmpleado(true);
            document.getElementById('id_persona_capturado').value = "";
        } 
        else if (res.status === 'persona_existe') {
            alert("Cliente encontrado. Cargando datos personales...");
            
            // 1. Asignar el ID de persona para que el PHP no inserte una nueva
            document.getElementById('id_persona_capturado').value = res.data.id_persona;
            
            // 2. Cargar datos en los inputs (incluye la espera de provincia y ciudad)
            await cargarDatosPersona(res.data);
            
            // 3. Bloquear datos personales generales
            bloquearCamposEmpleado(true); 
            
            // 4. EXCEPCIÓN: Habilitar Correo (Los clientes no suelen tenerlo registrado)
            const correoInput = document.getElementById('correo');
            if (correoInput) {
                correoInput.disabled = false;
                correoInput.placeholder = "Ingrese el correo del nuevo empleado";
            }

            // 5. Habilitar campos que SIEMPRE deben llenarse para un empleado
            habilitarLaborales(true);
            
            console.log("Datos de cliente vinculados correctamente.");
        } 
        else {
            alert("Nueva persona. Por favor, complete todos los campos.");
            document.getElementById('id_persona_capturado').value = "";
            bloquearCamposEmpleado(false);
            
            // Asegurarse de que el correo esté habilitado para nuevos
            document.getElementById('correo').disabled = false;
        }
    } catch (error) {
        document.body.style.cursor = 'default';
        console.error("Error en la petición:", error);
        alert("Error de conexión: No se pudo verificar la cédula.");
    }
}

/**
 * Funciones de apoyo para el control de la interfaz
 */
function bloquearCamposEmpleado(estado) {
    const formulario = document.getElementById("formulario");
    const inputs = formulario.querySelectorAll('input, select');
    
    inputs.forEach(i => {
        // No bloqueamos nunca el buscador, la cédula ni los radios de filtro
        if(i.id !== 'cedula' && i.id !== 'filtro' && i.type !== 'radio' && i.name !== 'criterioFiltro') {
            i.disabled = estado;
        }
    });
}

function habilitarLaborales(estado) {
    // Campos que el usuario DEBE llenar manualmente aunque la persona ya exista
    const idsLaborales = ['puesto', 'sueldo', 'telefono_e', 'nombre_e'];
    idsLaborales.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.disabled = !estado;
    });
    
    // Habilitar el botón de guardado
    const btnGuardar = document.getElementById('btnGuardar');
    if (btnGuardar) btnGuardar.disabled = !estado;
}

// Función para cargar los datos en cascada (País -> Provincia -> Ciudad)
async function cargarDatosPersona(p) {
    // --- Lógica de separación de Nombres ---
    const nombres = (p.nombre || "").trim().split(" ");
    const nombre2Input = document.getElementById('nombre2');

    if (nombres.length > 1) {
        document.getElementById('nombre1').value = nombres[0];
        nombre2Input.value = nombres.slice(1).join(" ");
        nombre2Input.disabled = true; 
    } else {
        document.getElementById('nombre1').value = p.nombre;
        nombre2Input.value = p.nombre_dos || '';
        // Si está vacío, habilitamos para completar
        nombre2Input.disabled = (nombre2Input.value !== "");
    }

    // --- Lógica de separación de Apellidos ---
    const apeP = (p.apellido_p || "").trim().split(" ");
    const apeMInput = document.getElementById('apellido_m');

    if (apeP.length > 1 && !p.apellido_m) {
        document.getElementById('apellido_p').value = apeP[0];
        apeMInput.value = apeP.slice(1).join(" ");
        apeMInput.disabled = true;
    } else {
        document.getElementById('apellido_p').value = p.apellido_p;
        apeMInput.value = p.apellido_m || '';
        // Si está vacío, habilitamos para completar
        apeMInput.disabled = (apeMInput.value !== "");
    }

    // --- Carga de datos generales ---
    document.getElementById('correo').value = p.email || '';
    document.getElementById('correo').disabled = false; // Siempre habilitado para empleados
    document.getElementById('fecha_nacimiento').value = p.fecha_nacimiento;
    document.getElementById('sexo').value = p.sexo;
    document.getElementById('direccion').value = p.direccion;
    document.getElementById('telefono').value = p.telefono || '';
    document.getElementById('nacionalidad').value = p.nacionalidad;

    // --- Carga de IDs de Ubicación (Sincronizada) ---
    if (p.id_pais) {
        document.getElementById('pais').value = p.id_pais;
        
        const resProv = await fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar_provincias&id_pais=${p.id_pais}`);
        const provincias = await resProv.json();
        const selProv = document.getElementById("provincia");
        selProv.innerHTML = '<option disabled>Seleccione</option>';
        provincias.forEach(pr => selProv.innerHTML += `<option value="${pr.id_provincia}">${pr.nombre}</option>`);
        selProv.value = p.id_provincia;

        const resCiud = await fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Empleado.php?action=cargar_ciudades&id_provincia=${p.id_provincia}`);
        const ciudades = await resCiud.json();
        const selCiud = document.getElementById("ciudad");
        selCiud.innerHTML = '<option disabled>Seleccione</option>';
        ciudades.forEach(c => selCiud.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`);
        selCiud.value = p.id_ciudad;
    }
}