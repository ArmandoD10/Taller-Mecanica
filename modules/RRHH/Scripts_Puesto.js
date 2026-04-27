// 🔥 VARIABLES GLOBALES
let puestosRaw = [];
let modoEdicion = false;

//---------------------------------------------------------
// 🧹 LIMPIAR FORMULARIO
//---------------------------------------------------------
function limpiarFormulario() {
    const formulario = document.getElementById("formulario");
    formulario.reset(); // Resetea inputs y selects

    document.getElementById("id_oculto").value = "";
    document.getElementById("btnGuardar").textContent = "Registrar";
    modoEdicion = false;

    // Ocultar el contenedor de estado
    document.getElementById("contenedor-estado").classList.add("d-none");
    
    // Resetear select de departamento a la opción por defecto
    document.getElementById("departamento").value = "";
}

//---------------------------------------------------------
// 📊 CARGAR TABLA
//---------------------------------------------------------
function cargarTabla() {
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Puesto.php?action=cargar`)
    .then(res => res.json())
    .then(data => {
        puestosRaw = data.data; 
        renderizarTabla(puestosRaw);
    })
    .catch(error => console.error("Error al cargar puestos:", error));
}

function renderizarTabla(lista) {
    const tbody = document.getElementById("cuerpo-tabla");
    tbody.innerHTML = "";

    lista.forEach(puesto => {
        // Color dinámico para el estado
        const colorBadge = puesto.estado.toLowerCase() === 'activo' ? 'bg-success' : 'bg-danger';
        
        // Formatear fecha si es necesario (asumiendo formato YYYY-MM-DD HH:MM:SS)
        const fecha = puesto.fecha_creacion ? puesto.fecha_creacion.substring(0, 10) : "";

        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${puesto.id_puesto}</td>
            <td>${puesto.nombre}</td>
            <td>${puesto.departamento}</td>
            <td>${fecha}</td>
            <td><span class="badge rounded-pill ${colorBadge}">${puesto.estado}</span></td>
            <td>
                <button type="button" class="btn btn-warning btn-sm" onclick="editarRegistro(${puesto.id_puesto})">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });
}

//---------------------------------------------------------
// 🔽 CARGAR SELECT DE DEPARTAMENTOS
//---------------------------------------------------------
function cargarSelects() {
    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Puesto.php?action=cargar_selects")
    .then(res => res.json())
    .then(data => {
        const depSel = document.getElementById("departamento");
        depSel.innerHTML = '<option value="" disabled selected>Selecciona un Departamento</option>';
        
        data.data.departamentos.forEach(d => {
            depSel.innerHTML += `<option value="${d.id_departamento}">${d.nombre}</option>`;
        });
    })
    .catch(error => console.error("Error cargando selects:", error));
}

//---------------------------------------------------------
// 🔍 FILTRO DE BÚSQUEDA (CÓDIGO, NOMBRE, DEPARTAMENTO)
//---------------------------------------------------------
document.getElementById('filtro').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase().trim();
    const radioSeleccionado = document.querySelector('input[name="criterioFiltro"]:checked');
    
    // Criterios posibles: 'id_puesto', 'nombre' (nombre del puesto), 'departamento' (nombre del depto)
    const criterio = radioSeleccionado ? radioSeleccionado.value : 'nombre';

    const resultados = puestosRaw.filter(puesto => {
        const valorABuscar = puesto[criterio] ? String(puesto[criterio]).toLowerCase() : "";
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
        renderizarTabla(puestosRaw);
    });
});

//---------------------------------------------------------
// ✏️ EDITAR (LLENA TODO Y MUESTRA ESTADO)
//---------------------------------------------------------
window.editarRegistro = function(id) {
    const puesto = puestosRaw.find(p => p.id_puesto == id);
    if (!puesto) return;

    modoEdicion = true;
    document.getElementById("btnGuardar").textContent = "Actualizar";
    document.getElementById("contenedor-estado").classList.remove("d-none");

    // Llenar campos
    document.getElementById("id_oculto").value = puesto.id_puesto;
    document.getElementById("nombre").value = puesto.nombre;
    document.getElementById("departamento").value = puesto.id_departamento; // ID numérico para el select

    // Estado
    if (puesto.estado === "activo") {
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

    // 🔴 Confirmación de Inactivación con SweetAlert2
    if (modoEdicion) {
        const estaInactivo = document.getElementById("inactivo").checked;
        if (estaInactivo) {
            Swal.fire({
                title: '¿Inactivar este puesto?',
                text: "Esto podría afectar la asignación de empleados actuales y futuros.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, inactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarDatosPuesto(this);
                }
            });
            return;
        }
    }

    enviarDatosPuesto(this);
});

// Función para procesar la petición fetch
function enviarDatosPuesto(formulario) {
    const formData = new FormData(formulario);
    let url = modoEdicion
        ? "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Puesto.php?action=actualizar"
        : "/Taller/Taller-Mecanica/modules/RRHH/Archivo_Puesto.php?action=guardar";

    fetch(url, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Éxito!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                limpiarFormulario();
                cargarTabla();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error("Error:", error);
        Swal.fire('Error', 'Ocurrió un error al procesar el puesto de trabajo.', 'error');
    });
}

//---------------------------------------------------------
// 🚀 INIT & VALIDACIONES
//---------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    cargarTabla();
    cargarSelects(); // Importante cargar el select de departamentos

    // Validación de Nombre de Puesto (Letras, números y espacios solamente)
    const inputNombre = document.getElementById('nombre');
    if (inputNombre) {
        inputNombre.addEventListener('input', function(e) {
            // Permitimos letras, acentos, ñ, números y espacios
            let valor = e.target.value.replace(/[^a-zA.-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            if (valor.length > 0) {
                valor = valor.charAt(0).toUpperCase() + valor.slice(1);
            }
            e.target.value = valor;
        });
    }
});

window.generarReportePuestosPDF = function() {
    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Puesto.php?action=reporte_pdf")
    .then(r => r.json())
    .then(res => {
        if (!res.success) return alert("Error al obtener datos de los puestos");

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // Cargar Logo (usando la ruta de tu sistema)
        const img = new Image();
        img.src = "/Taller/Taller-Mecanica/img/logo.png"; 
        
        img.onload = function() {
            // Encabezado
            doc.addImage(img, 'PNG', 160, 10, 35, 18);
            
            doc.setFontSize(18);
            doc.setTextColor(13, 71, 161); // Azul Primario
            doc.text("REPORTE ESTRUCTURA DE PUESTOS", 14, 22);
            
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text("Mecánica Automotriz Díaz & Pantaleón", 14, 28);
            doc.text(`Fecha de emisión: ${new Date().toLocaleString()}`, 14, 33);

            // Mapeo de datos para la tabla
            const filas = res.data.map(p => [
                p.id_puesto,
                p.puesto,
                p.departamento,
                p.fecha,
                p.estado.toUpperCase()
            ]);

            // Configuración de la Tabla
            doc.autoTable({
                startY: 40,
                head: [['ID', 'Nombre del Puesto', 'Departamento', 'Fecha Registro', 'Estado']],
                body: filas,
                headStyles: { 
                    fillColor: [13, 71, 161],
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 15, halign: 'center' },
                    3: { halign: 'center' },
                    4: { halign: 'center' }
                },
                styles: { fontSize: 9 },
                alternateRowStyles: { fillColor: [245, 245, 245] },
                margin: { top: 40 }
            });

            // Guardar el archivo
            doc.save('Reporte_Puestos_Trabajo.pdf');
        };
    })
    .catch(err => {
        console.error("Error al generar PDF:", err);
        alert("No se pudo conectar con el servidor para generar el reporte.");
    });
};