document.addEventListener("DOMContentLoaded", () => {
    cargarHistorial();

    document.querySelectorAll('[data-bs-dismiss="modal"], .btn-close').forEach(btn => {
        btn.addEventListener('click', cerrarModal);
    });
});

function cargarHistorial() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_HistorialInspeccion.php?action=listar_historial")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpo-tabla-historial");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(insp => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-primary">INSP-${insp.id_inspeccion.toString().padStart(4, '0')}</td>
                    <td>${insp.fecha_formateada}</td>
                    <td class="fw-bold">${insp.cliente}</td>
                    <td>${insp.vehiculo}</td>
                    <td>${insp.asesor}</td>
                    <td class="text-center">
                        <button class="btn btn-outline-primary btn-sm" onclick="verDetalle(${insp.id_inspeccion})" title="Ver Detalles">
                            <i class="fas fa-eye me-1"></i> Ver
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No hay inspecciones registradas.</td></tr>`;
        }
    })
    .catch(error => console.error("Error al cargar historial:", error));
}

function verDetalle(id_inspeccion) {
    // Función de seguridad: Solo escribe si el campo HTML existe
    const setValor = (id, valor) => {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = valor || '';
        }
    };

    setValor("mod_id_titulo", `#${id_inspeccion}`);
    
    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_HistorialInspeccion.php?action=obtener_detalle&id_inspeccion=${id_inspeccion}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            
            // Llenar los campos de texto usando la función segura
            setValor("mod_id", d.id_inspeccion.toString().padStart(4, '0'));
            setValor("mod_fecha", d.fecha);
            setValor("mod_hora", d.hora);
            setValor("mod_asesor", d.asesor);
            setValor("mod_cliente", d.cliente);
            setValor("mod_documento", d.documento_cliente);
            setValor("mod_vehiculo", d.vehiculo_desc);
            setValor("mod_color", d.color || 'N/A');
            setValor("mod_placa", d.placa);
            setValor("mod_chasis", d.vin_chasis); // Si no existe en el HTML, lo ignora y no se cae
            setValor("mod_km", d.kilometraje_recepcion);
            setValor("mod_combustible", d.nivel_combustible);
            setValor("mod_motivo", d.motivo_visita);
            // 1. Limpiar todos los radio buttons del modal antes de llenarlos
            document.querySelectorAll('#modalInspeccion input[type="radio"]').forEach(radio => {
                radio.checked = false;
            });

            // 2. Llenar los radio buttons según la base de datos
            if (d.checklist && d.checklist.length > 0) {
                // Mapeo interno de nuestras listas de PHP a JavaScript
                const categorias = {
                    'Interior': ['Beeper', 'Pito/Bocina', 'Luces int.', 'Aire Cond.', 'Radio', 'Cristales', 'Seguros', 'Retrovisor'],
                    'Exterior': ['Goma Rep.', 'Gato', 'Herram.', 'Llave Rueda', 'Luces Tras.', 'Tapa Comb.', 'Botiquín', 'Triángulo'],
                    'Motor': ['Varilla Aceite', 'Tapón Aceite', 'Radiador', 'Batería', 'Agua L/V', 'Filtro Aire', 'Correas', 'Tapas']
                };
                const prefijos = {'Interior': 'int', 'Exterior': 'ext', 'Motor': 'mot'};

                d.checklist.forEach(check => {
                    const arrayCategoria = categorias[check.categoria];
                    if(arrayCategoria) {
                        // Buscamos el índice (el número) del elemento (ej. 'Radio' es el index 4)
                        const index = arrayCategoria.indexOf(check.elemento);
                        if (index !== -1) {
                            const nameRadio = `${prefijos[check.categoria]}_${index}`; // ej. int_4
                            
                            // AGREGA ESTO:
                            const val = check.estado;
            
            // AGREGA ESTO:
                             console.log(`Buscando Radio: name="${nameRadio}" con valor="${val}"`);
                            // Buscamos el radio button que coincida en nombre y en valor (B, F o D) y lo marcamos
                            const radioBtn = document.querySelector(`#modalInspeccion input[name="${nameRadio}"][value="${check.estado}"]`);
                            if (radioBtn) {
                                radioBtn.checked = true;
                            }
                        }
                    }
                });
            }

            // --- APERTURA DE MODAL A PRUEBA DE FALLOS ---
            const modalElement = document.getElementById('modalInspeccion');
            
            try {
                if (typeof $ !== 'undefined' && $.fn.modal) {
                    $('#modalInspeccion').modal('show');
                    return;
                }
                
                if (typeof bootstrap !== 'undefined') {
                    let modal = bootstrap.Modal.getInstance(modalElement);
                    if (!modal) modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    return;
                }
                
                throw new Error("Librerías web no detectadas");
            } catch (e) {
                modalElement.classList.add('show');
                modalElement.style.display = 'block';
                document.body.classList.add('modal-open');
                
                if(!document.getElementById('fondo-oscuro-modal')){
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.id = 'fondo-oscuro-modal';
                    document.body.appendChild(backdrop);
                }
            }

        } else {
            alert("Error al cargar detalles: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error Fetch:", error);
        alert("Error de conexión al obtener la inspección.");
    });
}

function cerrarModal() {
    const modalElement = document.getElementById('modalInspeccion');
    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    document.body.classList.remove('modal-open');
    
    const backdrop = document.getElementById('fondo-oscuro-modal');
    if(backdrop) backdrop.remove();
    
    if (typeof $ !== 'undefined' && $.fn.modal) {
        $('#modalInspeccion').modal('hide');
    }
}