document.addEventListener("DOMContentLoaded", () => {
    listar();

    // --- GUARDAR NUEVO RECLAMO ---
document.getElementById("formNuevoReclamo").addEventListener("submit", function(e) {
    e.preventDefault();
    
    // 1. Validación de ítem seleccionado
    if(!document.querySelector('input[name="item_afectado"]:checked')) {
        Swal.fire({
            title: 'Ítem no seleccionado',
            text: "Debe marcar un repuesto o servicio de la lista de cobertura vigente.",
            icon: 'warning',
            target: document.getElementById('modalNuevoReclamo')
        });
        return;
    }

    // 2. Confirmación de apertura de expediente
    Swal.fire({
        title: '¿Abrir Expediente de Reclamo?',
        text: "Se iniciará el proceso de evaluación técnica para este caso.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1a73e8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, registrar reclamo',
        cancelButtonText: 'Cancelar',
        target: document.getElementById('modalNuevoReclamo')
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Generando número de expediente',
                allowOutsideClick: false,
                target: document.getElementById('modalNuevoReclamo'),
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData(this);
            fetch('../../modules/Taller/Archivo_ReclamoGarantia.php?action=guardar_reclamo', {
                method: 'POST', body: formData
            })
            .then(res => res.json())
            .then(res => {
                Swal.close();
                if(res.success) {
                    // Cerramos modal de Bootstrap
                    bootstrap.Modal.getInstance(document.getElementById('modalNuevoReclamo')).hide();
                    
                    Swal.fire('¡Éxito!', res.message, 'success').then(() => { listar(); });
                } else { 
                    Swal.fire('Error', res.message, 'error'); 
                }
            })
            .catch(() => Swal.fire('Error de Conexión', 'No se pudo registrar el reclamo.', 'error'));
        }
    });
});

    // --- EVALUAR RECLAMO (APROBAR/RECHAZAR) ---
// --- EVALUAR RECLAMO (CORREGIDO) ---
document.getElementById("formEvaluar").addEventListener("submit", function(e) {
    e.preventDefault();
    
    // Búsqueda flexible: por nombre o primer select disponible
    const selectEstado = this.querySelector('[name="estado_evaluacion"]') || this.querySelector('select');
    
    if (!selectEstado || !selectEstado.value) {
        Swal.fire({
            title: 'Dato faltante',
            text: "Por favor, seleccione el veredicto (Aprobado/Rechazado).",
            icon: 'warning',
            target: document.getElementById('modalEvaluar')
        });
        return;
    }

    const estado = selectEstado.value;
    const titulo = estado === 'Aprobado' ? '¿Confirmar Aprobación?' : '¿Confirmar Rechazo?';
    
    Swal.fire({
        title: titulo,
        text: "Esta decisión actualizará el estado del expediente y notificará al cliente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: estado === 'Aprobado' ? '#28a745' : '#d33',
        confirmButtonText: 'Confirmar Decisión',
        target: document.getElementById('modalEvaluar')
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ 
                title: 'Actualizando...', 
                allowOutsideClick: false, 
                target: document.getElementById('modalEvaluar'),
                didOpen: () => { Swal.showLoading(); } 
            });

            const formData = new FormData(this);
            fetch('../../modules/Taller/Archivo_ReclamoGarantia.php?action=evaluar_reclamo', {
                method: 'POST', body: formData
            })
            .then(res => res.json())
            .then(res => {
                Swal.close();
                if(res.success) {
                    // Cerramos el modal de Bootstrap
                    const modalElement = document.getElementById('modalEvaluar');
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) modalInstance.hide();

                    Swal.fire('Estado Actualizado', 'El reclamo ha sido procesado.', 'success')
                        .then(() => { listar(); });
                } else { 
                    Swal.fire('Error', res.message, 'error', { target: document.getElementById('modalEvaluar') }); 
                }
            })
            .catch(() => {
                Swal.close();
                Swal.fire('Error', 'Fallo de conexión.', 'error');
            });
        }
    });
});
});

function listar() {
    fetch('../../modules/Taller/Archivo_ReclamoGarantia.php?action=listar')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById("tbody_reclamos");
        tbody.innerHTML = "";
        
        if (res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="py-4 text-muted">No hay expedientes de reclamo registrados.</td></tr>';
            return;
        }

        res.data.forEach(r => {
            let badgeEst = "";
            let btnEval = "";
            if (r.estado_reclamo === 'En Evaluacion') {
                badgeEst = `<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En Evaluación</span>`;
                btnEval = `<button class="btn btn-sm btn-dark fw-bold shadow-sm" onclick="evaluar(${r.id_reclamo})">Evaluar</button>`;
            } else if (r.estado_reclamo === 'Aprobado') {
                badgeEst = `<span class="badge bg-success"><i class="fas fa-check me-1"></i>Aprobado</span>`;
            } else {
                badgeEst = `<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Rechazado</span>`;
            }

            tbody.innerHTML += `
                <tr>
                    <td class="fw-bold text-danger">EXP-${r.id_reclamo}</td>
                    <td class="small">${r.fecha}</td>
                    <td class="fw-bold text-primary">ORD-${r.id_orden_original}</td>
                    <td class="small">
                        <div class="fw-bold text-dark">${r.cliente}</div>
                        <div class="text-muted" style="font-size: 11px;">${r.vehiculo}</div>
                    </td>
                    <td class="small fw-bold">${r.item_afectado} <br><span class="badge bg-light text-dark border fw-normal">${r.tipo_item}</span></td>
                    <td>${badgeEst}</td>
                    <td>${btnEval}</td>
                </tr>`;
        });
    });
}

function abrirModalNuevo() {
    document.getElementById("formNuevoReclamo").reset();
    document.getElementById("area_resultados").classList.add("d-none");
    document.getElementById("btn_guardar_reclamo").disabled = true;
    new bootstrap.Modal(document.getElementById('modalNuevoReclamo')).show();
}

function buscarOrdenGarantia() {
    const id_orden = document.getElementById("buscar_id_orden").value;
    const km_actual = parseInt(document.getElementById("buscar_km").value);

    if(!id_orden || isNaN(km_actual) || km_actual <= 0) {
        return alert("Ingrese un número de orden válido y el kilometraje actual del vehículo.");
    }

    const btn = document.getElementById("btn_guardar_reclamo");
    const area = document.getElementById("area_resultados");
    const lista = document.getElementById("lista_items_garantia");
    
    lista.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Analizando garantías...</div>';
    area.classList.remove("d-none");
    btn.disabled = true;

    fetch(`../../modules/Taller/Archivo_ReclamoGarantia.php?action=buscar_orden&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(res => {
        if(!res.success) {
            lista.innerHTML = `<div class="alert alert-danger m-0 border-0">${res.message}</div>`;
            return;
        }

        // Llenar datos ocultos y visuales
        document.getElementById("rg_id_orden").value = res.cabecera.id_orden;
        document.getElementById("rg_id_sucursal").value = res.cabecera.id_sucursal;
        document.getElementById("rg_id_cliente").value = res.cabecera.id_cliente;
        document.getElementById("rg_id_vehiculo").value = res.cabecera.id_vehiculo;
        document.getElementById("rg_km_actual").value = km_actual;
        
        document.getElementById("rg_lbl_cliente").innerText = res.cabecera.cliente;
        document.getElementById("rg_lbl_vehiculo").innerText = res.cabecera.vehiculo;

        lista.innerHTML = "";
        const fechaHoy = new Date();
        fechaHoy.setHours(0,0,0,0);

        let hayDisponibles = false;

        res.items.forEach(item => {
            // Lógica de validación
            let vencido = false;
            let motivoVencimiento = [];

            // 1. Validar Fecha
            if(item.fecha_vencimiento) {
                const fechaVen = new Date(item.fecha_vencimiento + "T00:00:00");
                if (fechaHoy > fechaVen) {
                    vencido = true;
                    motivoVencimiento.push(`Venció fecha: ${item.fecha_vencimiento}`);
                }
            }

            // 2. Validar KM
            if(item.kilometraje_vencimiento) {
                if (km_actual > parseInt(item.kilometraje_vencimiento)) {
                    vencido = true;
                    motivoVencimiento.push(`Excedió límite KM (${item.kilometraje_vencimiento})`);
                }
            }

            const valRadio = `${item.tipo}_${item.id_item}`;
            const bgClass = vencido ? 'bg-danger-subtle opacity-75' : 'bg-white border-success';
            const disableProp = vencido ? 'disabled' : '';
            const txtMotivo = vencido ? `<span class="badge bg-danger ms-2">${motivoVencimiento.join(' | ')}</span>` : `<span class="badge bg-success ms-2">Cobertura Vigente</span>`;

            if(!vencido) hayDisponibles = true;

            lista.innerHTML += `
                <label class="list-group-item d-flex gap-3 align-items-center p-3 ${bgClass}" style="cursor: ${vencido ? 'not-allowed' : 'pointer'}">
                    <input class="form-check-input flex-shrink-0" type="radio" name="item_afectado" value="${valRadio}" ${disableProp}>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark">${item.descripcion}</div>
                        <div class="small text-muted">Política Aplicada: <b>${item.politica}</b></div>
                    </div>
                    <div>${txtMotivo}</div>
                </label>
            `;
        });

        if (hayDisponibles) {
            btn.disabled = false;
            
            // Activar evento para habilitar el botón solo si seleccionan uno válido
            document.querySelectorAll('input[name="item_afectado"]').forEach(radio => {
                radio.addEventListener('change', () => { btn.disabled = false; });
            });
        } else {
            lista.innerHTML += `<div class="p-2 text-center text-danger fw-bold bg-danger-subtle border-top border-danger">Todos los ítems de esta orden han expirado su garantía.</div>`;
        }
    })
    .catch(err => {
        lista.innerHTML = `<div class="alert alert-danger m-0">Error crítico buscando la orden.</div>`;
    });
}

function evaluar(id_reclamo) {
    document.getElementById("formEvaluar").reset();
    document.getElementById("ev_id_reclamo").value = id_reclamo;
    new bootstrap.Modal(document.getElementById('modalEvaluar')).show();
}