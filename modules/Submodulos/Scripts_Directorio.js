document.addEventListener("DOMContentLoaded", () => {
    cargarTiposDocumentos();
    listarDocumentos(); // Carga las cards existentes
});

/**
 * Abre el modal del editor y resetea los campos
 */
function abrirModalDocumento() {
    const modalEl = document.getElementById('modalEditor');
    
    // Reseteamos campos
    document.getElementById("sel_tipo").value = "";
    document.getElementById("editor_texto").value = "";
    document.getElementById("preview_contenido").innerHTML = "";
    document.getElementById("titulo_dinamico_hoja").innerText = ""; // Limpiar título dinámico
    document.getElementById("panel_empleado").classList.add("d-none");

    // Obtener instancia existente o crear una nueva
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }
    modal.show();
}

/**
 * Carga los tipos de documentos (Carta trabajo, Solicitud, etc.) en el select
 */
function cargarTiposDocumentos() {
    fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=listar_tipos')
    .then(res => res.json())
    .then(res => {
        const sel = document.getElementById('sel_tipo');
        sel.innerHTML = '<option value="" selected disabled>Seleccione un tipo...</option>';
        if(res.success) {
            res.data.forEach(t => {
                sel.innerHTML += `<option value="${t.id_tipo}" data-cat="${t.categoria}">${t.nombre}</option>`;
            });
        }
    });
}

/**
 * Lista los documentos guardados en formato de Cards A4
 */


/**
 * Guarda el documento y su detalle (HTML del Canvas)
 */
function guardarDocumento() {
    const id_tipo = document.getElementById('sel_tipo').value;
    const cuerpo = document.getElementById('editor_texto').value;
    const titulo = document.getElementById('editor_texto').value.substring(0, 30) + "..."; // Título automático

    if (!id_tipo || !cuerpo) {
        Swal.fire('Campos vacíos', 'Debe seleccionar un tipo y escribir el contenido.', 'warning');
        return;
    }

    Swal.fire({
        title: '¿Guardar Documento?',
        text: "Se registrará en el directorio de la empresa.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1a73e8',
        confirmButtonText: 'Sí, guardar'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id_tipo', id_tipo);
            fd.append('titulo', titulo);
            fd.append('cuerpo_doc', cuerpo);
            // Si hay un empleado seleccionado, podrías enviarlo aquí
            const idEmp = document.getElementById('id_empleado_hidden')?.value;
            if(idEmp) fd.append('id_empleado', idEmp);

            fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=guardar', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    Swal.fire('¡Éxito!', res.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalEditor')).hide();
                    listarDocumentos();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        }
    });
}


function dibujarDocumento() {
    const editor = document.getElementById('editor_texto');
    const preview = document.getElementById('preview_contenido');
    
    let texto = editor.value;

    // Si el editor está vacío, mostrar un mensaje guía
    if (texto.trim() === "") {
        preview.innerHTML = '<span style="color: #ccc;">Comience a escribir el contenido del documento...</span>';
        return;
    }

    // PROCESAMIENTO DEL TEXTO PARA LA HOJA:
    // 1. Reemplazamos los marcadores **texto** por etiquetas <b> (Negritas)
    // 2. Reemplazamos los saltos de línea (\n) por etiquetas <br>
    let textoFormateado = texto
        .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>') 
        .replace(/\n/g, "<br>");

    // Inyectamos el HTML resultante en el cuerpo de la hoja
    preview.innerHTML = textoFormateado;
}

/**
 * Detecta el cambio de tipo y actualiza el título en la hoja
 */
function verificarTipo() {
    const selector = document.getElementById('sel_tipo');
    const tituloHoja = document.getElementById('titulo_dinamico_hoja');
    const panelEmpleado = document.getElementById('panel_empleado');

    // Obtenemos el texto del tipo seleccionado (ej: "Carta de Trabajo")
    const nombreTipo = selector.options[selector.selectedIndex].text;
    const categoria = selector.options[selector.selectedIndex].getAttribute('data-cat');

    // Pintamos el título en el centro de la hoja
    tituloHoja.innerText = nombreTipo;

    // Si es personal, mostramos el buscador de empleados
    if(categoria === 'Personal') {
        panelEmpleado.classList.remove('d-none');
    } else {
        panelEmpleado.classList.add('d-none');
    }
}

// Lógica para incrustar datos de empleado
function seleccionarEmpleado(emp) {
    const editor = document.getElementById('editor_texto');
    const plantilla = `Por la presente se certifica que el Sr(a). ${emp.nombre_completo}, portador de la cédula ${emp.cedula}, labora en nuestra institución...`;
    editor.value = plantilla;
    dibujarDocumento();
}

function imprimirPDF(id) {
    // Aquí abrirías una ventana nueva con el ID para generar el PDF con DomPDF o FPDF
    window.open(`../../modules/Documentos/Generar_PDF.php?id=${id}`, '_blank');
}

// Variable para el ID del empleado seleccionado
let idEmpleadoSeleccionado = null;

document.getElementById('busc_emp').addEventListener('input', function() {
    const q = this.value.trim();
    const lista = document.getElementById('res_emp');
    
    if (q.length < 2) { lista.innerHTML = ""; return; }

    fetch(`/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=buscar_empleado&q=${q}`)
    .then(res => res.json())
    .then(res => {
        lista.innerHTML = "";
        if (res.success && res.data.length > 0) {
            res.data.forEach(emp => {
                const item = document.createElement('button');
                item.className = "list-group-item list-group-item-action small py-1";
                item.innerHTML = `<i class="fas fa-user me-2"></i>${emp.nombre_completo} - ${emp.cedula}`;
                item.onclick = () => {
                    idEmpleadoSeleccionado = emp.id_empleado;
                    document.getElementById('busc_emp').value = emp.nombre_completo;
                    lista.innerHTML = "";
                    // Inyectar datos en el documento automáticamente
                    inyectarEmpleadoEnTexto(emp);
                };
                lista.appendChild(item);
            });
        }
    });
});

function inyectarEmpleadoEnTexto(emp) {
    const editor = document.getElementById('editor_texto');
    const textoActual = editor.value;
    
    // Preparar los datos con formato de negritas (usando asteriscos para el editor)
    // El nombre viene ya concatenado desde el backend (nombre_completo)
    const datosEmp = `De: **${emp.nombre_completo.toUpperCase()}**\nCÉDULA: **${emp.cedula}**\n\n`;
    
    // Insertar al inicio del documento
    editor.value = datosEmp + textoActual;
    
    // Ejecutar el dibujado para que se vea el cambio inmediatamente en la hoja A4
    dibujarDocumento();
}

function editarDocumento(id) {
    fetch(`/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=obtener_detalle&id=${id}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const doc = res.data;
            // 1. Cargamos el modal
            abrirModalDocumento();
            
            // 2. Llenamos los datos
            document.getElementById('sel_tipo').value = doc.id_tipo;
            document.getElementById('editor_texto').value = doc.contenido_html;
            
            // 3. Si tiene empleado, lo mostramos
            if (doc.id_empleado) {
                document.getElementById('panel_empleado').classList.remove('d-none');
                document.getElementById('busc_emp').value = doc.nombre_empleado;
                idEmpleadoSeleccionado = doc.id_empleado;
            }

            // 4. Actualizamos el título dinámico y dibujamos
            verificarTipo();
            dibujarDocumento();
        }
    });
}

function abrirModalTipos() {
    listarTiposTabla();
    const modal = new bootstrap.Modal(document.getElementById('modalGestionTipos'));
    modal.show();
}

function listarTiposTabla() {
    fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=listar_tipos')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById('tbody_tipos');
        tbody.innerHTML = "";
        if(res.success) {
            res.data.forEach(t => {
                tbody.innerHTML += `
                    <tr>
                        <td class="small">${t.nombre}</td>
                        <td><span class="badge bg-light text-dark">${t.categoria}</span></td>
                        <td>
                            <button class="btn btn-sm btn-link text-warning" onclick="editarTipo(${t.id_tipo}, '${t.nombre}', '${t.categoria}')"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-link text-danger" onclick="eliminarTipo(${t.id_tipo})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
            });
        }
    });
}

document.getElementById('formTipoDoc').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=guardar_tipo', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    // Dentro del eventListener de 'submit' del formTipoDoc que hicimos antes:
.then(res => {
    if(res.success) {
        Swal.fire('¡Listo!', res.message, 'success');
        this.reset();
        document.getElementById('id_tipo_doc').value = "";
        
        // Volver el botón a modo "Guardar"
        const btn = this.querySelector('button[type="submit"]');
        btn.classList.replace('btn-warning', 'btn-success');
        btn.innerHTML = 'Guardar Tipo';
        
        listarTiposTabla();
        cargarTiposDocumentos();
    }
});
});

function listarDocumentos() {
    const contenedor = document.getElementById("contenedor_directorio");
    contenedor.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><p>Actualizando Directorio...</p></div>`;

    fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=listar')
    .then(res => res.json())
    .then(res => {
        contenedor.innerHTML = `
            <div class="col-md-3 mb-4">
                <div class="card card-a4-preview shadow-sm border-primary h-100" onclick="abrirModalDocumento()" style="border-style: dashed !important; min-height: 280px; background: #f8f9fa; cursor: pointer;">
                    <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                        <h6 class="fw-bold text-primary">Nuevo Documento</h6>
                        <small class="text-muted">Crear carta o solicitud</small>
                    </div>
                </div>
            </div>`;

        if (res.success && res.data.length > 0) {
            res.data.forEach(doc => {
                const badgeClass = doc.categoria === 'Personal' ? 'bg-info' : 'bg-secondary';
                contenedor.innerHTML += `
                    <div class="col-md-3 mb-4">
                        <div class="card card-a4-preview shadow-sm h-100 border-0" style="min-height: 280px;">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge ${badgeClass} text-white shadow-sm" style="font-size: 9px;">${doc.tipo_nombre}</span>
                                    <div class="btn-group shadow-sm bg-white rounded border">
                                        <button class="btn btn-sm btn-outline-danger border-0" onclick="eliminarDocumento(${doc.id_documento})"><i class="fas fa-times"></i></button>
                                        <button class="btn btn-sm btn-outline-warning border-0" onclick="editarDocumento(${doc.id_documento})"><i class="fas fa-pen"></i></button>
                                       
<button class="btn btn-sm btn-outline-primary border-0" 
        onclick="imprimirDesdeListado(${doc.id_documento})" 
        title="Imprimir PDF">
    <i class="fas fa-print"></i>
</button>
                                    </div>
                                </div>
                                <div class="flex-grow-1 overflow-hidden mb-3">
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 14px;">${doc.titulo}</h6>
                                    <div class="text-muted" style="font-size: 11px; opacity: 0.7;">Click para editar cuerpo.</div>
                                </div>
                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                    <small class="text-muted" style="font-size: 10px;">${doc.fecha_creacion}</small>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });
        }
    })
    .catch(err => console.error("Error listando:", err));
}

function eliminarDocumento(id) {
    Swal.fire({
        title: '¿Eliminar documento?',
        text: "El expediente se marcará como eliminado y no aparecerá en el directorio.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id_documento', id);

            fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=eliminar', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    Swal.fire('Eliminado', res.message, 'success');
                    listarDocumentos(); // Refresca la vista
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error'));
        }
    });
}

function imprimirDesdeListado(id) {
    fetch(`/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=obtener_detalle&id=${id}`)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const doc = res.data;
            // Llamamos a la función de imprimir pasando los datos recuperados
            imprimirDocumentoA4(doc.tipo_nombre, doc.contenido_html);
        } else {
            Swal.fire("Error", "No se pudo recuperar el contenido del documento", "error");
        }
    });
}

/**
 * Carga los datos de un tipo en el formulario del modal para editarlos
 */
function editarTipo(id, nombre, categoria) {
    // Seteamos los valores en los inputs del formulario de tipos
    document.getElementById('id_tipo_doc').value = id;
    document.getElementById('nombre_tipo').value = nombre;
    document.getElementById('cat_tipo').value = categoria;
    
    // Cambiamos el estilo del botón para indicar edición
    const btn = document.querySelector('#formTipoDoc button[type="submit"]');
    btn.classList.replace('btn-success', 'btn-warning');
    btn.innerHTML = '<i class="fas fa-sync me-2"></i>Actualizar Tipo';
}

/**
 * Realiza la eliminación lógica de un tipo de documento
 */
function eliminarTipo(id) {
    Swal.fire({
        title: '¿Eliminar este tipo?',
        text: "Los documentos existentes de este tipo no se borrarán, pero ya no podrás crear nuevos usando esta categoría.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id_tipo', id);

            fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Directorio.php?action=eliminar_tipo', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    Swal.fire('Eliminado', 'El tipo de documento ha sido removido.', 'success');
                    listarTiposTabla(); // Refresca la tabla del modal
                    cargarTiposDocumentos(); // Refresca el select del editor principal
                }
            });
        }
    });
}

const inputNombre = document.getElementById('nombre_tipo');
if (inputNombre) {
    inputNombre.addEventListener('input', function (e) {
        let valor = e.target.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
        if (valor.length > 0) {
            valor = valor.charAt(0).toUpperCase() + valor.slice(1);
        }
        e.target.value = valor;
    });
}

window.imprimirDocumentoA4 = function(tipoManual = null, contenidoManual = null) {
    // Si vienen datos manuales (desde el listado) los usamos, sino leemos el editor
    const tipoDoc = tipoManual || document.getElementById('sel_tipo').options[document.getElementById('sel_tipo').selectedIndex].text;
    const contenidoRaw = contenidoManual || document.getElementById('editor_texto').value;
    
    if (!contenidoRaw || contenidoRaw.trim() === "") {
        return Swal.fire("Hoja vacía", "No hay contenido para generar el PDF", "warning");
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'p', unit: 'mm', format: 'a4' });
    const img = new Image();
    img.src = "../../img/logo.png"; 
    
    img.onload = function() {
        doc.addImage(img, 'PNG', 15, 15, 25, 25);
        doc.setFont("helvetica", "bold");
        doc.setFontSize(16);
        doc.text("MECÁNICA DÍAZ & PANTALEÓN", 45, 25);
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.text("Servicio Automotriz Profesional", 45, 30);
        doc.text(`Fecha: ${new Date().toLocaleDateString()}`, 45, 35);
        doc.line(15, 45, 195, 45);

        // Título
        doc.setFont("helvetica", "bold");
        doc.setFontSize(14);
        const titulo = tipoDoc.toUpperCase();
        doc.text(titulo, 105, 60, { align: "center" });

        // Cuerpo (Limpiamos negritas de Markdown para el PDF)
        doc.setFont("helvetica", "normal");
        doc.setFontSize(12);
        const textoLimpio = contenidoRaw.replace(/\*\*/g, ''); 
        const textoAjustado = doc.splitTextToSize(textoLimpio, 170);
        doc.text(textoAjustado, 20, 80);

        // Firma
        doc.line(65, 250, 145, 250);
        doc.text("Taller Mecánica Díaz & Pantaleón", 105, 257, { align: "center" });

        window.open(doc.output('bloburl'), '_blank');
    };
};