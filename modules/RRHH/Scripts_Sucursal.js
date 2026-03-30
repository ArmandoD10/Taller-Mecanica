// 🔥 VARIABLES GLOBALES
let sucursalesRaw = [];
let modoEdicion = false;

//---------------------------------------------------------
// 🧹 LIMPIAR FORMULARIO
//---------------------------------------------------------
function limpiarFormulario() {
    const formulario = document.getElementById("formulario");
    formulario.reset(); // Resetea todos los campos nativos

    document.getElementById("id_oculto").value = "";
    document.getElementById("btnGuardar").textContent = "Registrar";
    modoEdicion = false;

    // Ocultamos el contenedor de estado
    document.getElementById("contenedor-estado").classList.add("d-none");
}

//---------------------------------------------------------
// 📊 CARGAR TABLA
//---------------------------------------------------------
function cargarTabla() {
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Sucursal.php?action=cargar`)
    .then(res => res.json())
    .then(data => {
        sucursalesRaw = data.data; 
        renderizarTabla(sucursalesRaw);
    })
    .catch(error => console.error("Error al cargar:", error));
}

function renderizarTabla(lista) {
    const tbody = document.getElementById("cuerpo-tabla");
    tbody.innerHTML = "";

    lista.forEach(suc => {
        const badgeColor = suc.estado === 'activo' ? 'bg-success' : 'bg-danger';
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${suc.id_sucursal}</td>
            <td>${suc.nombre}</td>
            <td>${suc.telefono}</td>
            <td>${suc.direccion}</td>
            <td>${suc.ciudad}</td>
            <td><span class="badge rounded-pill ${badgeColor}">${suc.estado}</span></td>
            <td>
                <button type="button" class="btn btn-warning btn-sm" onclick="editarRegistro(${suc.id_sucursal})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });
}

//---------------------------------------------------------
// 🔽 CARGAR SELECTS (UBICACIÓN)
//---------------------------------------------------------
function cargarSelects() {
    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Sucursal.php?action=cargar_selects")
    .then(res => res.json())
    .then(data => {
        const pais = document.getElementById("pais");
        pais.innerHTML = '<option disabled selected>Seleccione</option>';
        data.data.pais.forEach(p => {
            pais.innerHTML += `<option value="${p.id_pais}">${p.nombre}</option>`;
        });
    });
}

document.getElementById("pais").addEventListener("change", function() {
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Sucursal.php?action=cargar_provincias&id_pais=${this.value}`)
    .then(res => res.json())
    .then(data => {
        const provincia = document.getElementById("provincia");
        provincia.innerHTML = '<option disabled selected>Seleccione</option>';
        data.forEach(p => {
            provincia.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`;
        });
    });
});

document.getElementById("provincia").addEventListener("change", function() {
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Sucursal.php?action=cargar_ciudades&id_provincia=${this.value}`)
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
// ✏️ EDITAR (LLENA TODO Y MUESTRA ESTADO)
//---------------------------------------------------------
window.editarRegistro = async function(id) {
    const suc = sucursalesRaw.find(s => s.id_sucursal == id);
    if (!suc) return;

    // 1. Mostrar estado y llenar textos básicos
    document.getElementById("contenedor-estado").classList.remove("d-none");
    document.getElementById("btnGuardar").textContent = "Actualizar";
    modoEdicion = true;

    document.getElementById("id_oculto").value = suc.id_sucursal;
    document.getElementById("nombre").value = suc.nombre;
    document.getElementById("telefono").value = suc.telefono;
    document.getElementById("direccion").value = suc.direccion;

    // 2. Marcar Radio de Estado
    if (suc.estado === "activo") {
        document.getElementById("activo").checked = true;
    } else {
        document.getElementById("inactivo").checked = true;
    }

    try {
        // 3. Seleccionar País
        const paisSel = document.getElementById("pais");
        paisSel.value = suc.id_pais;

        // 4. Cargar y seleccionar Provincias
        const respProv = await fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Sucursal.php?action=cargar_provincias&id_pais=${suc.id_pais}`);
        const provincias = await respProv.json();
        
        const provSel = document.getElementById("provincia");
        provSel.innerHTML = '<option disabled>Seleccione</option>';
        provincias.forEach(p => {
            provSel.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`;
        });
        provSel.value = suc.id_provincia;

        // 5. Cargar y seleccionar Ciudades
        const respCiudad = await fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Sucursal.php?action=cargar_ciudades&id_provincia=${suc.id_provincia}`);
        const ciudades = await respCiudad.json();
        
        const ciudadSel = document.getElementById("ciudad");
        ciudadSel.innerHTML = '<option disabled>Seleccione</option>';
        ciudades.forEach(c => {
            ciudadSel.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`;
        });
        ciudadSel.value = suc.id_ciudad;

    } catch (error) {
        console.error("Error al cargar la cascada de ubicación:", error);
    }

    document.getElementById("formulario").scrollIntoView({ behavior: "smooth" });
};

//---------------------------------------------------------
// 💾 GUARDAR / ACTUALIZAR CON CONFIRMACIÓN
//---------------------------------------------------------
document.getElementById("formulario").addEventListener("submit", function(e) {
    e.preventDefault();

    // 🔴 Lógica de Confirmación si se marca INACTIVO
    if (modoEdicion) {
        const estadoSeleccionado = document.querySelector('input[name="estado"]:checked').value;
        if (estadoSeleccionado === "inactivo") {
            const confirmar = confirm("¿Está seguro de que desea desactivar esta sucursal? Esto podría afectar la operatividad en el sistema.");
            if (!confirmar) return; // Cancela el envío si el usuario dice que no
        }
    }

    const formData = new FormData(this);
    let url = modoEdicion
        ? "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Sucursal.php?action=actualizar"
        : "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Sucursal.php?action=guardar";

    fetch(url, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            limpiarFormulario();
            cargarTabla(); // Recargar tabla sin recargar página completa
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => console.error("Error en el envío:", error));
});

//---------------------------------------------------------
// 🚀 INICIALIZACIÓN Y MASCARAS
//---------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    cargarTabla();
    cargarSelects();

    // Máscara para teléfono sucursal
    const telInput = document.getElementById('telefono');
    if(telInput) {
        telInput.addEventListener('input', function(e) {
            let num = e.target.value.replace(/\D/g, '').substring(0, 10);
            let formateado = "";
            if (num.length > 0) {
                formateado += "(" + num.substring(0, 3);
                if (num.length > 3) formateado += ") " + num.substring(3, 6);
                if (num.length > 6) formateado += "-" + num.substring(6, 10);
            }
            e.target.value = formateado;
        });
    }
});

//Funcion para no caracteres en nombre de la sucursal.
const nombreSucursal = document.getElementById('nombre');

nombreSucursal.addEventListener('input', function(e) {
    // 1. Permitir letras (incluyendo ñ y acentos), números y espacios
    // El regex [^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s] busca lo que NO sea eso y lo borra
    let valor = e.target.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');

    // 2. Opcional: Capitalizar la primera letra para mantener orden
    if (valor.length > 0) {
        valor = valor.charAt(0).toUpperCase() + valor.slice(1);
    }

    // Devolvemos el valor limpio al input
    e.target.value = valor;
});


//---------------------------------------------------------
// 🔍 FILTRO DE BÚSQUEDA (CÓDIGO, NOMBRE, CIUDAD)
//---------------------------------------------------------
document.getElementById('filtro').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase().trim();
    
    // Obtenemos el criterio marcado (id_sucursal, nombre o ciudad)
    const radioSeleccionado = document.querySelector('input[name="criterioFiltro"]:checked');
    const criterio = radioSeleccionado ? radioSeleccionado.value : 'nombre';

    // Filtramos sobre el array global sucursalesRaw
    const resultados = sucursalesRaw.filter(suc => {
        // Accedemos a la propiedad dinámicamente: suc['id_sucursal'], suc['nombre'], etc.
        const valorABuscar = suc[criterio] ? String(suc[criterio]).toLowerCase() : "";
        return valorABuscar.includes(busqueda);
    });

    // Renderizamos la tabla con los resultados filtrados
    renderizarTabla(resultados);
});

// Limpiar al cambiar de criterio
document.querySelectorAll('input[name="criterioFiltro"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const inputFiltro = document.getElementById('filtro');
        inputFiltro.value = ""; 
        inputFiltro.focus();
        renderizarTabla(sucursalesRaw); // Mostrar todos al cambiar de filtro
    });
});