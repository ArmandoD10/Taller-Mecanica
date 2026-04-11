let clientesRaw = [];
let modoEdicion = false;

document.addEventListener("DOMContentLoaded", () => {
    cargarTabla();
    cargarSelects();
    
    // Escuchar el cambio de Tipo de Cliente
    document.querySelectorAll('input[name="tipo_persona"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleTipoPersona(this.value);
        });
    });
});

function toggleTipoPersona(tipo) {
    const lblNombre = document.getElementById('lbl_nombre');
    const colApeP = document.getElementById('col_apellido_p');
    const colApeM = document.getElementById('col_apellido_m');
    const lblCedula = document.getElementById('lbl_cedula');
    const lblFecha = document.getElementById('lbl_fecha');
    const colSexo = document.getElementById('col_sexo');
    const cedulaInput = document.getElementById('cedula');

    // Limpiar valores para evitar mezclas de datos
    document.getElementById('nombre').value = '';
    document.getElementById('apellido_p').value = '';
    document.getElementById('apellido_m').value = '';
    document.getElementById('sexo').value = '';
    cedulaInput.value = '';

    if(tipo === 'juridica') {
        lblNombre.textContent = 'Razón Social (Empresa)';
        colApeP.classList.add('d-none');
        colApeM.classList.add('d-none');
        colSexo.classList.add('d-none');
        lblCedula.textContent = 'RNC';
        lblFecha.textContent = 'Fecha de Constitución';
        cedulaInput.placeholder = "123456789";
        cedulaInput.maxLength = 9; 
    } else {
        lblNombre.textContent = 'Primer Nombre';
        colApeP.classList.remove('d-none');
        colApeM.classList.remove('d-none');
        colSexo.classList.remove('d-none');
        lblCedula.textContent = 'Cédula';
        lblFecha.textContent = 'Fecha Nacimiento';
        cedulaInput.placeholder = "000-0000000-0";
        cedulaInput.maxLength = 13; 
    }

    // --- AGREGAR ESTO ---
    // Si no estamos en modo edición (registro nuevo) y no hemos validado la cédula,
    // mantenemos los campos bloqueados al cambiar el tipo.
    if (!modoEdicion) {
        bloquearCampos(true); // Esto bloqueará los campos, pero dejará el toggle libre
    }
}

window.limpiarFormulario = function() {
    // 1. Resetear el HTML del formulario (esto limpia el texto visible)
    const form = document.getElementById("formulario");
    if (form) {
        form.reset();
    }

    // 2. Limpiar MANUALMENTE los IDs ocultos (importante para que no se queden pegados)
    const idsParaVaciar = ['id_oculto', 'id_persona_capturado', 'cedula', 'nombre', 'apellido_p', 'apellido_m', 'correo', 'direccion', 'telefono'];
    idsParaVaciar.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = "";
    });

    // 3. Resetear los Selects (País, Prov, Ciudad)
    const selects = ['pais', 'nacionalidad', 'provincia', 'ciudad', 'sexo'];
    selects.forEach(id => {
        const sel = document.getElementById(id);
        if (sel) sel.selectedIndex = 0; 
    });

    // 4. Restaurar Radio Buttons y Labels
    document.getElementById("fisica").checked = true;
    toggleTipoPersona('fisica'); // Esto resetea los labels de Cédula/Nombre

    // 5. Bloquear todo excepto Cédula y Tipo de Cliente
    bloquearCampos(true); 
    
    // Forzamos el desbloqueo de la cédula y el bloqueo del botón guardar
    document.getElementById('cedula').disabled = false;
    document.getElementById('btnGuardar').disabled = true;
    document.getElementById('btnGuardar').textContent = "Registrar";

    // 6. Ocultar el estado (solo se usa en edición)
    document.getElementById("contenedor-estado").classList.add("d-none");
    
    modoEdicion = false;
    console.log("Formulario reseteado y campos bloqueados.");
};

function cargarTabla() {
    fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cargar`)
    .then(res => res.json())
    .then(data => {
        clientesRaw = data.data; 
        renderizarTabla(clientesRaw);
    });
}

function renderizarTabla(lista) {
    const tbody = document.getElementById("cuerpo-tabla");
    tbody.innerHTML = "";

    lista.forEach(cli => {
        const colorBadge = cli.estado.toLowerCase() === 'activo' ? 'bg-success' : 'bg-danger';
        // Determinar nombre completo si es empresa o persona física
        const esEmpresa = (cli.tipo_persona && cli.tipo_persona.toLowerCase() === 'juridica');
        const nombreCompleto = esEmpresa ? cli.nombre : `${cli.nombre} ${cli.apellido_p} ${cli.apellido_m || ''}`;
        
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${cli.id_cliente}</td>
            <td class="fw-bold">${nombreCompleto}</td>
            <td>${cli.cedula}</td>
            <td>${cli.telefono || 'N/A'}</td>
            <td><span class="badge rounded-pill ${colorBadge}">${cli.estado}</span></td>
            <td>
                <button type="button" class="btn btn-warning btn-sm" onclick="editarRegistro(${cli.id_cliente})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });
}

// Filtro
document.getElementById('filtro').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase();
    const radioSeleccionado = document.querySelector('input[name="criterioFiltro"]:checked');
    const criterio = radioSeleccionado ? radioSeleccionado.value : 'nombre';

    const resultados = clientesRaw.filter(cli => {
        const valorCampo = String(cli[criterio]).toLowerCase();
        return valorCampo.includes(busqueda);
    });
    renderizarTabla(resultados);
});

document.querySelectorAll('input[name="criterioFiltro"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const inputFiltro = document.getElementById('filtro');
        inputFiltro.value = "";
        inputFiltro.focus();
        renderizarTabla(clientesRaw); 
    });
});

// Cascada de Selects
function cargarSelects() {
    fetch("/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cargar_selects")
    .then(res => res.json())
    .then(data => {
        const pais = document.getElementById("pais");
        const nacionalidad = document.getElementById("nacionalidad");
        pais.innerHTML = '<option disabled selected>Seleccione</option>';
        nacionalidad.innerHTML = '<option disabled selected>Seleccione</option>';
        data.data.pais.forEach(p => {
            pais.innerHTML += `<option value="${p.id_pais}">${p.nombre}</option>`;
            nacionalidad.innerHTML += `<option value="${p.id_pais}">${p.nombre}</option>`;
        });
    });
}

document.getElementById("pais").addEventListener("change", function () {
    fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cargar_provincias&id_pais=${this.value}`)
    .then(res => res.json())
    .then(data => {
        const provincia = document.getElementById("provincia");
        provincia.innerHTML = '<option disabled selected>Seleccione</option>';
        data.forEach(p => provincia.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`);
        document.getElementById("ciudad").innerHTML = '<option disabled selected>Seleccione Prov. Primero</option>';
    });
});

document.getElementById("provincia").addEventListener("change", function () {
    fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cargar_ciudades&id_provincia=${this.value}`)
    .then(res => res.json())
    .then(data => {
        const ciudad = document.getElementById("ciudad");
        ciudad.innerHTML = '<option disabled selected>Seleccione</option>';
        data.forEach(c => ciudad.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`);
    });
});

// Editar
window.editarRegistro = function(id) {
    const cli = clientesRaw.find(c => c.id_cliente == id);
    if (!cli) return;

    modoEdicion = true;

    // 1. Mostrar estado y limpiar IDs ocultos de vinculación
    document.getElementById("contenedor-estado").classList.remove("d-none");
    document.getElementById("id_oculto").value = cli.id_cliente;
    const idPersonaCapturado = document.getElementById("id_persona_capturado");
    if(idPersonaCapturado) idPersonaCapturado.value = "";

    // 2. Detectar tipo de persona y ajustar labels
    if (cli.tipo_persona && cli.tipo_persona.toLowerCase() === 'juridica') {
        document.getElementById("juridica").checked = true;
        toggleTipoPersona('juridica');
    } else {
        document.getElementById("fisica").checked = true;
        toggleTipoPersona('fisica');
        document.getElementById("apellido_p").value = cli.apellido_p || "";
        document.getElementById("apellido_m").value = cli.apellido_m || "";
        document.getElementById("sexo").value = cli.sexo || "";
    }

    // 3. Llenar campos básicos
    document.getElementById("nombre").value = cli.nombre;
    document.getElementById("cedula").value = cli.cedula;
    document.getElementById("correo").value = cli.email || "";
    document.getElementById("fecha_nacimiento").value = cli.fecha_nacimiento;
    document.getElementById("nacionalidad").value = cli.nacionalidad;
    document.getElementById("telefono").value = cli.telefono || "";
    document.getElementById("direccion").value = cli.direccion;

    // 4. Estado del radio
    const estado = cli.estado?.toLowerCase();
    if (estado === "activo") document.getElementById("activo").checked = true;
    else if (estado === "inactivo") document.getElementById("inactivo").checked = true;

    // 5. 🔥 CARGAR UBICACIÓN Y DESBLOQUEAR
    document.getElementById("pais").value = cli.id_pais;
    
    // Llamamos a una carga de cascada manual para asegurar que se habiliten después de cargar
    cargarUbicacionEdicionCliente(cli.id_pais, cli.id_provincia, cli.id_ciudad).then(() => {
        // DESBLOQUEO TOTAL
        const todosLosInputs = document.querySelectorAll('#formulario input, #formulario select');
        todosLosInputs.forEach(input => {
            // No habilitamos los radios de tipo de cliente en edición para evitar inconsistencias
            if(input.name !== 'tipo_persona') {
                input.disabled = false;
            }
        });

        // BLOQUEO DE IDENTIDAD
        document.getElementById("cedula").disabled = true;
        const btnLupa = document.getElementById("btnBuscarCedula");
        if(btnLupa) btnLupa.disabled = true;
    });

    document.getElementById("btnGuardar").textContent = "Actualizar";
    document.getElementById("formulario").scrollIntoView({ behavior: "smooth" });
};

// Función auxiliar para la cascada sincronizada
async function cargarUbicacionEdicionCliente(idPais, idProv, idCiud) {
    const resProv = await fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cargar_provincias&id_pais=${idPais}`);
    const provs = await resProv.json();
    const selProv = document.getElementById("provincia");
    selProv.innerHTML = "";
    provs.forEach(p => selProv.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`);
    selProv.value = idProv;

    const resCiud = await fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cargar_ciudades&id_provincia=${idProv}`);
    const ciuds = await resCiud.json();
    const selCiud = document.getElementById("ciudad");
    selCiud.innerHTML = "";
    ciuds.forEach(c => selCiud.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`);
    selCiud.value = idCiud;
}

// Guardar
document.getElementById("formulario").addEventListener("submit", function(e) {
    e.preventDefault();

    // Habilitar temporalmente para capturar los datos
    const camposBloqueados = this.querySelectorAll('input:disabled, select:disabled');
    camposBloqueados.forEach(campo => campo.disabled = false);

    const formData = new FormData(this);

    // Restaurar estado visual
    camposBloqueados.forEach(campo => campo.disabled = true);

    let url = modoEdicion 
        ? "/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=actualizar" 
        : "/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=guardar";

    fetch(url, { method: "POST", body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload(); // Recarga limpia igual que en empleados
        } else {
            alert("Error: " + data.message);
        }
    });
});

// Máscaras de Validación
document.addEventListener("DOMContentLoaded", function() {
    // 1. Nombres: Solo letras. PERO si es empresa, debe permitir números y símbolos
    const camposNombres = document.querySelectorAll('#nombre, #apellido_p, #apellido_m');
    camposNombres.forEach(input => {
        input.addEventListener('input', function(e) {
            const isEmpresa = document.getElementById('juridica').checked;
            let valor = e.target.value;
            
            // Si es persona física (apellidos existen), limpiamos. Si es empresa, dejamos todo.
            if(input.id !== 'nombre' || !isEmpresa) {
                valor = valor.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            }
            if (valor.length > 0) valor = valor.charAt(0).toUpperCase() + valor.slice(1);
            e.target.value = valor;
        });
    });

    // 2. Cédula Inteligente
    const cedulaInput = document.getElementById('cedula');
    if(cedulaInput){
        cedulaInput.addEventListener('input', function(e) {
            const isEmpresa = document.getElementById('juridica').checked;
            let valor = e.target.value.replace(/\D/g, '');
            
            if(isEmpresa) {
                // RNC: 9 dígitos, sin formato
                e.target.value = valor.substring(0, 9);
            } else {
                // Cédula: 000-0000000-0
                valor = valor.substring(0, 11);
                let formateado = "";
                if (valor.length > 0) {
                    formateado += valor.substring(0, 3);
                    if (valor.length > 3) formateado += "-" + valor.substring(3, 10);
                    if (valor.length > 10) formateado += "-" + valor.substring(10, 11);
                }
                e.target.value = formateado;
            }
        });
    }

    // 3. Teléfono
    document.querySelectorAll('.f-telefono').forEach(input => {
        input.addEventListener('input', function(e) {
            let num = e.target.value.replace(/\D/g, '').substring(0, 10);
            let form = "";
            if (num.length > 0) {
                form += "(" + num.substring(0, 3);
                if (num.length > 3) form += ") " + num.substring(3, 6);
                if (num.length > 6) form += "-" + num.substring(6, 10);
            }
            e.target.value = form;
        });
    });
});



//funcion cliente-empleado.
// Evento del botón de la lupa (Buscar Cédula)
document.getElementById('btnBuscarCedula').addEventListener('click', async function() {
    const cedula = document.getElementById('cedula').value;
    
    if (cedula.length < 9) {
        alert("Ingrese una cédula o RNC válido.");
        return;
    }

    try {
        const response = await fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=verificar_cedula&cedula=${cedula}`);
        const res = await response.json();

        if (res.status === 'cliente_existe') {
            alert("Este número ya pertenece a un Cliente registrado.");
            bloquearCampos(true);
            document.getElementById('id_persona_capturado').value = ""; // Limpiar por seguridad
        } 
        else if (res.status === 'persona_existe') {
            alert("Empleado/Persona encontrada. Cargando datos...");
            
            // 1. LLENAMOS EL ID OCULTO CON EL ID DE LA PERSONA ENCONTRADA
            document.getElementById('id_persona_capturado').value = res.data.id_persona;
            
            // 2. Cargamos los datos en los inputs (incluyendo cascada de ciudad)
            await cargarDatosPersona(res.data);
            
            // 3. Bloqueamos campos para que no altere datos del empleado
            bloquearCampos(true);
            
            // 4. Habilitamos el botón de registro para que pueda "convertirlo" a cliente
            document.getElementById('btnGuardar').disabled = false;
        } 
        else {
            alert("No se encontró registro previo. Complete los datos para un nuevo cliente.");
            
            // Es un registro nuevo, el ID oculto debe estar vacío
            document.getElementById('id_persona_capturado').value = "";
            bloquearCampos(false);
        }
    } catch (error) {
        console.error("Error en la búsqueda:", error);
    }
});

// No olvides limpiar el ID oculto en tu función de limpiarFormulario
function limpiarFormulario() {
    // ... tu lógica de limpiar campos ...
    document.getElementById('id_persona_capturado').value = ""; 
    document.getElementById('id_oculto').value = ""; // Este es el del Cliente (edición)
    bloquearCampos(true);
}

function bloquearCampos(estado) {
    // Seleccionamos todos los inputs y selects
    const inputs = document.querySelectorAll('#formulario input, #formulario select');
    
    inputs.forEach(input => {
        // EXCEPCIONES: No bloqueamos la cédula, el filtro, ni los radio buttons
        const esExcepcion = 
            input.id === 'cedula' || 
            input.id === 'filtro' || 
            input.type === 'radio'; // <--- Esta es la clave

        if (!esExcepcion) {
            input.disabled = estado;
        }
    });

    // El botón guardar sigue la lógica del estado (bloqueado si no hay búsqueda)
    document.getElementById('btnGuardar').disabled = estado;
}

async function cargarDatosPersona(p) {
    // Datos de texto básicos
    document.getElementById('nombre').value = p.nombre;
    document.getElementById('apellido_p').value = p.apellido_p;
    document.getElementById('apellido_m').value = p.apellido_m;
    document.getElementById('correo').value = p.email;
    document.getElementById('fecha_nacimiento').value = p.fecha_nacimiento;
    document.getElementById('sexo').value = p.sexo;
    document.getElementById('direccion').value = p.direccion;
    document.getElementById('telefono').value = p.telefono || '';

    // Asignar Nacionalidad por ID
    if (p.nacionalidad) {
        document.getElementById('nacionalidad').value = p.nacionalidad;
    }

    // Ubicación por IDs (Sincronizada)
    if (p.id_pais) {
        document.getElementById('pais').value = p.id_pais;
        
        // Esperar carga de provincias para asignar el ID
        const resProv = await fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cargar_provincias&id_pais=${p.id_pais}`);
        const provincias = await resProv.json();
        const selectProv = document.getElementById("provincia");
        selectProv.innerHTML = '<option disabled>Seleccione</option>';
        provincias.forEach(pr => {
            selectProv.innerHTML += `<option value="${pr.id_provincia}">${pr.nombre}</option>`;
        });
        selectProv.value = p.id_provincia; // Asignamos el ID numérico

        // Esperar carga de ciudades para asignar el ID
        const resCiud = await fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Cliente.php?action=cargar_ciudades&id_provincia=${p.id_provincia}`);
        const ciudades = await resCiud.json();
        const selectCiud = document.getElementById("ciudad");
        selectCiud.innerHTML = '<option disabled>Seleccione</option>';
        ciudades.forEach(c => {
            selectCiud.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`;
        });
        selectCiud.value = p.id_ciudad; // Asignamos el ID numérico
    }
}