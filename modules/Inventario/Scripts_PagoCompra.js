let cacheOrdenesPendientes = []; 
const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // ==========================================
    // BUSCADOR DINÁMICO (SELECT2)
    // ==========================================
    // Le indicamos al buscador dinámico que debe desplegarse por encima del modalPago
    $('#id_proveedor').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modalPago'),
        placeholder: "Seleccione o escriba el nombre/RNC..."
    });

    // ==========================================
    // CASCADA: AL CAMBIAR DE PROVEEDOR (Evento jQuery por Select2)
    // ==========================================
    $('#id_proveedor').on('change', function() {
        const id_prov = this.value;
        const seccionOrdenes = document.getElementById("seccion_ordenes");
        const formPago = document.getElementById("formPago");
        const tbodyOrdenes = document.getElementById("cuerpoOrdenesPendientes");
        
        // Ocultar elementos hasta que lleguen los datos de la base de datos
        seccionOrdenes.classList.add('d-none');
        formPago.classList.add('d-none');
        tbodyOrdenes.innerHTML = '<tr><td colspan="4"><i class="fas fa-spinner fa-spin me-2"></i>Buscando órdenes pendientes...</td></tr>';
        
        if (!id_prov) {
            return;
        }

        fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=buscar_ordenes&id_proveedor=${id_prov}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                cacheOrdenesPendientes = data.data;
                tbodyOrdenes.innerHTML = "";
                
                data.data.forEach(o => {
                    let balance = o.total_orden - o.total_pagado;
                    let fecha = o.fecha_creacion.substring(0, 10);
                    let numOrden = `OC-${o.id_compra.toString().padStart(4, '0')}`;
                    
                    tbodyOrdenes.innerHTML += `
                        <tr>
                            <td class="fw-bold text-primary fs-5">${numOrden}</td>
                            <td>${fecha}</td>
                            <td class="fw-bold text-danger fs-5">${formatter.format(balance)}</td>
                            <td>
                                <button class="btn btn-dark btn-sm me-1" onclick="verDetallesOrden(${o.id_compra}, '${numOrden}')" title="Ver Artículos de esta Orden">
                                    <i class="fas fa-eye"></i> Detalle
                                </button>
                                <button class="btn btn-success btn-sm fw-bold" onclick="iniciarPago(${o.id_compra}, '${numOrden}')">
                                    <i class="fas fa-dollar-sign me-1"></i> Pagar
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                seccionOrdenes.classList.remove('d-none');
                
            } else {
                tbodyOrdenes.innerHTML = '<tr><td colspan="4" class="text-muted">Este proveedor no tiene facturas pendientes de pago registradas.</td></tr>';
                seccionOrdenes.classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error("Error al buscar órdenes:", error);
        });
    });

    // ==========================================
    // GUARDAR EL PAGO
    // ==========================================
    document.getElementById("formPago").addEventListener("submit", function(e) {
    e.preventDefault();
    
    // Indicador de carga con SweetAlert2
    Swal.fire({
        title: 'Procesando Pago...',
        text: 'Registrando la transacción en contabilidad',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=guardar", {
        method: "POST", 
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        Swal.close(); // Cerramos el loading
        
        if (data.success) { 
            // Cerramos el modal de Bootstrap antes para evitar conflictos de Z-Index
            cerrarModalUI('modalPago'); 
            
            Swal.fire({
                title: '¡Pago Exitoso!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => { 
                listar(); // Recargamos la tabla de pagos realizados
            });
        } else { 
            Swal.fire({
                title: 'Error en Pago',
                text: data.message,
                icon: 'error',
                target: document.getElementById('modalPago') // Lo mostramos sobre el modal
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error("Error:", error);
        Swal.fire({
            title: 'Fallo de Red',
            text: 'No se pudo registrar el pago. Verifique su conexión.',
            icon: 'error',
            target: document.getElementById('modalPago')
        });
    });
});

    // ==========================================
    // EVENTO PARA CERRAR EL MODAL PRINCIPAL
    // ==========================================
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => { 
            e.preventDefault(); 
            cerrarModalUI('modalPago'); 
        });
    });
});

// ==========================================
// DESPLEGAR FORMULARIO DE PAGO PARA UNA ORDEN
// ==========================================
function iniciarPago(id_compra, num_orden) {
    const orden = cacheOrdenesPendientes.find(o => o.id_compra == id_compra);
    
    if (!orden) {
        return;
    }
    
    let balance = orden.total_orden - orden.total_pagado;
    
    document.getElementById("lbl_orden_seleccionada").innerText = num_orden;
    document.getElementById("id_compra_pagar").value = id_compra;
    
    document.getElementById("lbl_total_orden").innerText = formatter.format(orden.total_orden);
    document.getElementById("lbl_total_pagado").innerText = formatter.format(orden.total_pagado);
    document.getElementById("lbl_balance_pendiente").innerText = formatter.format(balance);
    
    const inputMonto = document.getElementById("monto_pagado");
    inputMonto.value = balance.toFixed(2);
    inputMonto.max = balance.toFixed(2); 
    
    // Limpiamos los selectores por si se había elegido otra orden antes
    document.getElementById("id_moneda").value = "";
    document.getElementById("id_metodo").value = "";
    document.getElementById("referencia_pago").value = "";

    document.getElementById("formPago").classList.remove('d-none');
    
    // Hacemos un scroll suave para que el usuario vea el formulario de pago
    document.getElementById("formPago").scrollIntoView({ behavior: 'smooth', block: 'end' });
}

// ==========================================
// VER DETALLES DE LA ORDEN EN EL SUB-MODAL
// ==========================================
function verDetallesOrden(id_compra, num_orden) {
    document.getElementById("lbl_submodal_orden").innerText = num_orden;
    const tbody = document.getElementById("cuerpoTablaArticulos");
    
    tbody.innerHTML = '<tr><td colspan="4"><i class="fas fa-spinner fa-spin"></i> Cargando artículos...</td></tr>';
    
    abrirModalUI('modalDetallesCompra');

    // Usamos el archivo de Compra que ya tiene programada la extracción de los detalles
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Compra.php?action=obtener&id_compra=${id_compra}`)
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data.detalles_articulos) {
            tbody.innerHTML = "";
            
            data.data.detalles_articulos.forEach(art => {
                tbody.innerHTML += `
                    <tr>
                        <td class="text-start fw-bold">${art.nombre} <br> <small class="text-muted">${art.num_serie}</small></td>
                        <td>${formatter.format(art.precio)}</td>
                        <td class="fw-bold fs-5">${art.cantidad}</td>
                        <td class="fw-bold text-success">${formatter.format(art.subtotal)}</td>
                    </tr>
                `;
            });
        }
    })
    .catch(error => {
        console.error("Error al cargar los detalles:", error);
        tbody.innerHTML = '<tr><td colspan="4" class="text-danger">Error al cargar los detalles.</td></tr>';
    });
}

// ==========================================
// CRUD LISTADO PRINCIPAL DE PAGOS
// ==========================================
function listar() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTabla");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(p => {
                let badgeEstado = p.estado === "activo" ? "bg-success" : "bg-danger";
                
                let btnAnular = p.estado === "activo" 
                    ? `<button class="btn btn-danger btn-sm" onclick="anular(${p.id_pago_compra})" title="Anular Pago"><i class="fas fa-ban"></i></button>`
                    : `<button class="btn btn-secondary btn-sm" disabled><i class="fas fa-ban"></i></button>`;

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-muted">#PAG-${p.id_pago_compra.toString().padStart(4, '0')}</td>
                    <td>${p.fecha_pago}</td>
                    <td class="fw-bold">${p.nombre_comercial}</td>
                    <td class="text-primary fw-bold">OC-${p.id_compra.toString().padStart(4, '0')}</td>
                    <td>${p.metodo_pago}</td>
                    <td class="fst-italic">${p.referencia_pago || '-'}</td>
                    <td class="fw-bold text-success">${formatter.format(p.monto_pagado)} <small class="text-muted">${p.moneda}</small></td>
                    <td><span class="badge ${badgeEstado}">${p.estado.toUpperCase()}</span></td>
                    <td>${btnAnular}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">No hay pagos registrados.</td></tr>`;
        }
    })
    .catch(error => {
        console.error("Error al listar pagos:", error);
    });
}

function cargarDependencias() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=cargar_dependencias")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const selectProv = document.getElementById("id_proveedor");
            const selectMetodo = document.getElementById("id_metodo");
            const selectMoneda = document.getElementById("id_moneda");
            
            selectProv.innerHTML = '<option value="">Seleccione o escriba el proveedor...</option>';
            selectMetodo.innerHTML = '<option value="">Seleccione método...</option>';
            selectMoneda.innerHTML = '<option value="">Seleccione moneda...</option>';
            
            data.data.proveedores.forEach(p => { 
                selectProv.innerHTML += `<option value="${p.id_proveedor}">${p.nombre_comercial} (RNC: ${p.RNC})</option>`; 
            });
            
            data.data.metodos.forEach(m => { 
                selectMetodo.innerHTML += `<option value="${m.id_metodo}">${m.nombre}</option>`; 
            });
            
            data.data.monedas.forEach(mo => { 
                selectMoneda.innerHTML += `<option value="${mo.id_moneda}">${mo.codigo_ISO} - ${mo.nombre}</option>`; 
            });
        }
    })
    .catch(error => {
        console.error("Error al cargar dependencias:", error);
    });
}

function nuevoPago() {
    document.getElementById("formPago").reset();
    document.getElementById("formPago").classList.add('d-none');
    document.getElementById("seccion_ordenes").classList.add('d-none');
    
    // Reseteamos el buscador dinámico de Select2
    $('#id_proveedor').val(null).trigger('change');
    
    abrirModalUI('modalPago');
}

function anular(id_pago) {
    Swal.fire({
        title: '¿Anular este pago?',
        text: "ATENCIÓN: Solo los administradores pueden realizar esta acción. El dinero volverá a aparecer como deuda pendiente en la Orden de Compra.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, anular pago',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Anulando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            const f = new FormData(); 
            f.append("id_pago_compra", id_pago);
            
            fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=anular", { 
                method: "POST", 
                body: f 
            })
            .then(res => res.json())
            .then(data => { 
                Swal.close();
                if(data.success) {
                    Swal.fire('¡Anulado!', data.message, 'success');
                    listar(); 
                } else {
                    Swal.fire('Acceso Denegado', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.close();
                Swal.fire('Error', 'No se pudo procesar la anulación.', 'error');
            });
        }
    });
}

// ==========================================
// FUNCIONES DEL MODAL A PRUEBA DE FALLOS
// ==========================================
function abrirModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) { 
            $('#' + idModal).modal('show'); 
            return; 
        }
        
        if (typeof bootstrap !== 'undefined') {
            let mod = bootstrap.Modal.getInstance(modalElement);
            if (!mod) {
                mod = new bootstrap.Modal(modalElement);
            }
            mod.show(); 
            return;
        } 
        
        throw new Error("Forzar apertura manual");
        
    } catch (e) {
        modalElement.classList.add('show'); 
        modalElement.style.display = 'block';
        
        // Solo agregamos el fondo oscuro general si es el modal principal
        if (idModal === 'modalPago') { 
            document.body.classList.add('modal-open');
            if (!document.getElementById('fondo-oscuro-modal')) {
                const b = document.createElement('div'); 
                b.className = 'modal-backdrop fade show'; 
                b.id = 'fondo-oscuro-modal';
                document.body.appendChild(b);
            }
        }
    }
}

function cerrarModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    
    if (!modalElement) {
        return;
    }
    
    modalElement.classList.remove('show'); 
    modalElement.style.display = 'none'; 
    
    // Solo quitamos el fondo oscuro general si estamos cerrando el modal principal
    if (idModal === 'modalPago') { 
        document.body.classList.remove('modal-open');
        const b = document.getElementById('fondo-oscuro-modal'); 
        if (b) {
            b.remove();
        }
    }
    
    if (typeof $ !== 'undefined' && $.fn.modal) { 
        $('#' + idModal).modal('hide'); 
    }
}