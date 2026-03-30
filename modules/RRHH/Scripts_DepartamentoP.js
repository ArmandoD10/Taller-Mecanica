// 🔥 VARIABLES GLOBALES
let departamentosRaw = [];
let modoEdicion = false;

//---------------------------------------------------------
// 🧹 LIMPIAR FORMULARIO
//---------------------------------------------------------
function limpiarFormulario() {
    const formulario = document.getElementById("formulario");
    formulario.reset(); // Resetea inputs, numbers y times

    document.getElementById("id_oculto").value = "";
    document.getElementById("btnGuardar").textContent = "Registrar";
    modoEdicion = false;

    // Ocultar el contenedor de estado
    document.getElementById("contenedor-estado").classList.add("d-none");
    
    // Valor por defecto para el contador de días
    document.getElementById("cantidad_dias").value = 1;
}

//---------------------------------------------------------
// 📊 CARGAR TABLA
//---------------------------------------------------------
function cargarTabla() {
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_DepartamentoP.php?action=cargar`)
    .then(res => res.json())
    .then(data => {
        departamentosRaw = data.data; 
        renderizarTabla(departamentosRaw);
    })
    .catch(error => console.error("Error al cargar departamentos:", error));
}

function renderizarTabla(lista) {
    const tbody = document.getElementById("cuerpo-tabla");
    tbody.innerHTML = "";

    lista.forEach(dep => {
        // Color dinámico para el estado
        const colorBadge = dep.estado.toLowerCase() === 'activo' ? 'bg-success' : 'bg-danger';
        
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${dep.id_departamento}</td>
            <td>${dep.nombre}</td>
            <td>${dep.dias_lab} días</td>
            <td>${dep.hora_ini}</td>
            <td>${dep.hora_fin}</td>
            <td><span class="badge rounded-pill ${colorBadge}">${dep.estado}</span></td>
            <td>
                <button type="button" class="btn btn-warning btn-sm" onclick="editarRegistro(${dep.id_departamento})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });
}

//---------------------------------------------------------
// 🔍 FILTRO DE BÚSQUEDA (CÓDIGO, NOMBRE)
//---------------------------------------------------------
document.getElementById('filtro').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase().trim();
    const radioSeleccionado = document.querySelector('input[name="criterioFiltro"]:checked');
    const criterio = radioSeleccionado ? radioSeleccionado.value : 'nombre';

    const resultados = departamentosRaw.filter(dep => {
        const valorABuscar = dep[criterio] ? String(dep[criterio]).toLowerCase() : "";
        return valorABuscar.includes(busqueda);
    });

    renderizarTabla(resultados);
});

// Limpiar filtro al cambiar de opción
document.querySelectorAll('input[name="criterioFiltro"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const inputFiltro = document.getElementById('filtro');
        inputFiltro.value = "";
        inputFiltro.focus();
        renderizarTabla(departamentosRaw);
    });
});

//---------------------------------------------------------
// ✏️ EDITAR (LLENA TODO Y MUESTRA ESTADO)
//---------------------------------------------------------
window.editarRegistro = function(id) {
    const dep = departamentosRaw.find(d => d.id_departamento == id);
    if (!dep) return;

    modoEdicion = true;
    document.getElementById("btnGuardar").textContent = "Actualizar";
    document.getElementById("contenedor-estado").classList.remove("d-none");

    // Llenar campos
    document.getElementById("id_oculto").value = dep.id_departamento;
    document.getElementById("nombre").value = dep.nombre;
    document.getElementById("cantidad_dias").value = dep.dias_lab;
    
    // Los inputs de tipo time aceptan el formato HH:MM (cortamos los segundos si vienen de la DB)
    document.getElementById("hora_entrada").value = dep.hora_ini.substring(0, 5);
    document.getElementById("hora_salida").value = dep.hora_fin.substring(0, 5);

    // Estado
    if (dep.estado === "activo") {
        document.getElementById("activo").checked = true;
    } else {
        document.getElementById("inactivo").checked = true;
    }

    document.getElementById("formulario").scrollIntoView({ behavior: "smooth" });
};

//---------------------------------------------------------
// 💾 GUARDAR / ACTUALIZAR CON CONFIRMACIÓN
//---------------------------------------------------------
document.getElementById("formulario").addEventListener("submit", function(e) {
    e.preventDefault();

    // 🔴 Confirmación de Inactivación
    if (modoEdicion) {
        const estaInactivo = document.getElementById("inactivo").checked;
        if (estaInactivo) {
            const seguro = confirm("¿Está seguro de que desea inactivar este departamento? Los puestos asociados podrían verse afectados.");
            if (!seguro) return; 
        }
    }

    const formData = new FormData(this);
    let url = modoEdicion
        ? "/Taller/Taller-Mecanica/modules/RRHH/Archivo_DepartamentoP.php?action=actualizar"
        : "/Taller/Taller-Mecanica/modules/RRHH/Archivo_DepartamentoP.php?action=guardar";

    fetch(url, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            limpiarFormulario();
            cargarTabla(); // Recargamos la tabla dinámicamente
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error en la petición:", error);
        alert("Ocurrió un error al procesar el departamento.");
    });
});

//---------------------------------------------------------
// 🚀 INIT & VALIDACIONES
//---------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    cargarTabla();

    // Validación de Nombre (Letras, números y espacios solamente)
    const inputNombre = document.getElementById('nombre');
    inputNombre.addEventListener('input', function(e) {
        let valor = e.target.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (valor.length > 0) {
            valor = valor.charAt(0).toUpperCase() + valor.slice(1);
        }
        e.target.value = valor;
    });
});