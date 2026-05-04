function buscarOrdenServicio() {
    const input = document.getElementById('input_orden');
    const id = input.value;
    
    if (!id) {
        Swal.fire('Atención', 'Por favor ingrese un número de orden.', 'warning');
        return;
    }

    // Feedback visual en el botón de búsqueda[cite: 3, 4]
    const btn = document.querySelector('#formBuscarOrden button[type="submit"]');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
    btn.disabled = true;

    fetch(`/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Evaluacion.php?action=buscar_orden&id=${id}`)
    .then(res => res.json())
    .then(res => {
        // Restaurar estado del botón[cite: 3, 4]
        btn.innerHTML = originalHTML;
        btn.disabled = false;

        if (res.success) {
            // Caso 1: Orden encontrada y lista para evaluar[cite: 3, 4]
            document.getElementById('txt_cliente_nombre').innerText = res.data.cliente;
            document.getElementById('txt_vehiculo_detalle').innerText = `${res.data.modelo} - Placa: ${res.data.placa}`;
            document.getElementById('txt_orden_id').innerText = `ORD-${res.data.id_orden}`;
            
            // Asignar IDs a los campos ocultos para el guardado final[cite: 3, 4]
            document.getElementById('hidden_id_orden').value = res.data.id_orden;
            document.getElementById('hidden_id_cliente').value = res.data.id_cliente;

            // Mostrar el panel de la encuesta y cargar preguntas[cite: 3, 4]
            document.getElementById('panel_encuesta').classList.remove('d-none');
            cargarPreguntasDinamicas();
            
        } else {
            // Caso 2: Manejo de errores (Orden ya evaluada o no existe)[cite: 3, 4]
            document.getElementById('panel_encuesta').classList.add('d-none');
            
            if (res.is_evaluated) {
                // Si la orden ya tiene una encuesta registrada
                Swal.fire({
                    title: 'Orden ya evaluada',
                    text: res.message,
                    icon: 'warning',
                    confirmButtonColor: '#f8bb86'
                });
            } else {
                // Si la orden no se encuentra en el sistema[cite: 3, 4]
                Swal.fire('No encontrado', res.message, 'error');
            }
        }
    })
    .catch(err => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
        console.error("Error en la petición:", err);
        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
    });
}

function cargarPreguntasDinamicas() {
    fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Evaluacion.php?action=listar_preguntas')
    .then(res => res.json())
    .then(res => {
        const contenedor = document.getElementById('contenedor_preguntas');
        contenedor.innerHTML = "";
        
        res.data.forEach((p, index) => {
            let controlHtml = "";
            
            if (p.tipo_respuesta === 'Escala') {
                controlHtml = `<div class="d-flex gap-3 mt-2">`;
                for (let i = 1; i <= 5; i++) {
                    controlHtml += `
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="respuesta[${p.id_pregunta}]" value="${i}" id="p${p.id_pregunta}_${i}" required>
                            <label class="form-check-label" for="p${p.id_pregunta}_${i}">${i}★</label>
                        </div>`;
                }
                controlHtml += `</div>`;
            } else {
                controlHtml = `
                    <select class="form-select mt-2" name="respuesta[${p.id_pregunta}]" required>
                        <option value="Si">Sí</option>
                        <option value="No">No</option>
                    </select>`;
            }

            contenedor.innerHTML += `
                <div class="mb-4 p-3 border-bottom">
                    <h6 class="fw-bold mb-1">${index + 1}. ${p.pregunta}</h6>
                    ${controlHtml}
                </div>`;
        });
    });
}

// Usamos delegación de eventos para capturar el submit del formulario
document.addEventListener('submit', function (e) {
    // Verificamos si el formulario que se intenta enviar es el de satisfacción
    if (e.target && e.target.id === 'formSatisfaccion') {
        e.preventDefault(); // Evitamos que la página se recargue[cite: 3]

        const fd = new FormData(e.target);

        // Mostramos un indicador de carga para que el usuario sepa que algo pasa
        Swal.fire({
            title: 'Procesando...',
            text: 'Guardando tu evaluación',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Evaluacion.php?action=guardar_evaluacion', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: res.message
                }).then(() => {
                    location.reload(); // Recargamos para limpiar todo[cite: 3]
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        })
        .catch(err => {
            console.error("Error completo:", err);
            Swal.fire('Error de Red', 'No se pudo conectar con el servidor', 'error');
        });
    }
});

// Abrir modal y cargar lista
function abrirModalConfiguracion() {
    listarPreguntasConfig();
    new bootstrap.Modal(document.getElementById('modalConfiguracion')).show();
}

function listarPreguntasConfig() {
    fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Evaluacion.php?action=listar_preguntas_completo')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById('tbody_preguntas_config');
        tbody.innerHTML = res.data.map(p => `
            <tr>
                <td class="small">${p.pregunta}</td>
                <td><span class="badge bg-info text-dark">${p.tipo_respuesta}</span></td>
                <td><span class="badge ${p.estado === 'activo' ? 'bg-success' : 'bg-secondary'}">${p.estado}</span></td>
                <td>
                    <button class="btn btn-sm text-warning" onclick="editarPregunta(${p.id_pregunta}, '${p.pregunta}', '${p.tipo_respuesta}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm text-danger" onclick="eliminarPregunta(${p.id_pregunta})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    });
}

document.addEventListener("DOMContentLoaded", () => {
    // Escuchar el formulario de búsqueda para evitar recarga de página
    const formBuscar = document.getElementById('formBuscarOrden');
    if (formBuscar) {
        formBuscar.addEventListener('submit', function(e) {
            e.preventDefault(); // DETIENE LA RECARGA[cite: 3]
            buscarOrdenServicio(); // Llama a tu función existente
        });
    }

    // El mantenimiento de preguntas ya lo tienes, pero asegúrate de que el ID coincida
    const formPregunta = document.getElementById('formPregunta');
    if (formPregunta) {
        formPregunta.onsubmit = function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Evaluacion.php?action=guardar_pregunta', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    this.reset();
                    document.getElementById('id_pregunta').value = "";
                    document.getElementById('btnGuardarPregunta').innerText = "Guardar";
                    listarPreguntasConfig();
                    Swal.fire('¡Listo!', 'Pregunta actualizada', 'success');
                }
            });
        };
    }
});

/**
 * Limpia el formulario de mantenimiento de preguntas[cite: 3, 4]
 */
function limpiarFormPregunta() {
    // 1. Reseteamos el formulario completo
    document.getElementById('formPregunta').reset();
    
    // 2. Vaciamos el ID oculto para que no sea una edición[cite: 3, 4]
    document.getElementById('id_pregunta').value = "";
    
    // 3. Restauramos el texto del botón principal[cite: 3]
    document.getElementById('btnGuardarPregunta').innerText = "Guardar";
    
    // 4. (Opcional) Enfocamos el campo de texto para mayor rapidez[cite: 3]
    document.getElementById('txt_pregunta').focus();
}

/**
 * Al editar, el botón cambia su texto para avisar que está en modo actualización[cite: 3, 4]
 */
function editarPregunta(id, texto, tipo) {
    document.getElementById('id_pregunta').value = id;
    document.getElementById('txt_pregunta').value = texto;
    document.getElementById('sel_tipo_res').value = tipo;
    document.getElementById('btnGuardarPregunta').innerText = "Actualizar";
}

function eliminarPregunta(id) {
    Swal.fire({
        title: '¿Eliminar permanentemente?',
        text: "¡Esta acción no se puede deshacer y borrará el registro de la base de datos!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, borrar del sistema',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);

            // Cambiamos la acción a la que ejecuta el DELETE[cite: 3]
            fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Evaluacion.php?action=eliminar_pregunta_fisico', { 
                method: 'POST', 
                body: fd 
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire('Eliminado', res.message, 'success');
                    listarPreguntasConfig(); // Refresca la tabla del modal[cite: 3]
                } else {
                    // Muestra el error si la pregunta está siendo usada en encuestas[cite: 3]
                    Swal.fire('Atención', res.message, 'info');
                }
            });
        }
    });
}