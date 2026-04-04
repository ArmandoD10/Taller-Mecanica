let cacheOrdenesPendientes = []; // Guardamos en memoria las órdenes para calcular balances rápido

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // ==========================================
    // CASCADA: AL CAMBIAR DE PROVEEDOR, BUSCAR SUS ÓRDENES PENDIENTES
    // ==========================================
    const selectProveedor = document.getElementById("id_proveedor");
    const selectOrden = document.getElementById("id_compra");
    const cajaBalance = document.getElementById("cajaBalance");
    const inputMonto = document.getElementById("monto_pagado");

    selectProveedor.addEventListener('change', function() {
        const id_prov = this.value;
        
        // Limpiar y bloquear select de ordenes
        selectOrden.innerHTML = '<option value="">Seleccione orden...</option>';
        selectOrden.disabled = true;
        cajaBalance.classList.add('d-none');
        inputMonto.value = "";
        inputMonto.max = "";
        
        if (!id_prov) return;

        // Fetch de las órdenes con balance para este proveedor
        fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=buscar_ordenes&id_proveedor=${id_prov}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                cacheOrdenesPendientes = data.data;
                selectOrden.disabled = false;
                
                data.data.forEach(o => {
                    let balance = o.total_orden - o.total_pagado;
                    // Format Date
                    let fecha = o.fecha_creacion.substring(0, 10);
                    selectOrden.innerHTML += `<option value="${o.id_compra}">OC-${o.id_compra.toString().padStart(4, '0')} (Deuda: $${balance.toFixed(2)}) - ${fecha}</option>`;
                });
            } else {
                selectOrden.innerHTML = '<option value="">El proveedor no tiene deudas pendientes.</option>';
            }
        })
        .catch(error => console.error("Error al buscar órdenes:", error));
    });

    // ==========================================
    // AL SELECCIONAR UNA ORDEN: MOSTRAR BALANCE Y LIMITAR EL MONTO
    // ==========================================
    selectOrden.addEventListener('change', function() {
        const id_compra = this.value;
        
        if (!id_compra) {
            cajaBalance.classList.add('d-none');
            inputMonto.value = "";
            return;
        }

        const orden = cacheOrdenesPendientes.find(o => o.id_compra == id_compra);
        
        if (orden) {
            let balance = orden.total_orden - orden.total_pagado;
            
            // Formateador de moneda para la vista
            const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
            
            document.getElementById("lbl_total_orden").innerText = formatter.format(orden.total_orden);
            document.getElementById("lbl_total_pagado").innerText = formatter.format(orden.total_pagado);
            document.getElementById("lbl_balance_pendiente").innerText = formatter.format(balance);
            
            // Pre-llenamos el monto a pagar con el balance total (el usuario puede modificarlo si es abono)
            inputMonto.value = balance.toFixed(2);
            inputMonto.max = balance.toFixed(2); // No dejamos que pague de más
            
            cajaBalance.classList.remove('d-none');
        }
    });

    // ==========================================
    // GUARDAR EL PAGO
    // ==========================================
    document.getElementById("formPago").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const btnGuardar = document.getElementById("btnGuardar");
        btnGuardar.disabled = true; // Evitar doble click
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';

        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=guardar", {
            method: "POST", 
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="fas fa-check-circle me-2"></i>Registrar Pago';
            
            if (data.success) { 
                cerrarModalUI('modalPago'); 
                listar(); 
                alert(data.message); 
            } else { 
                alert(data.message); 
            }
        })
        .catch(error => {
            console.error("Error al registrar pago:", error);
            alert("Error de conexión con el servidor.");
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="fas fa-check-circle me-2"></i>Registrar Pago';
        });
    });

    // Eventos para cerrar modal
    document.querySelectorAll('[data-bs-dismiss="modal"], .btn-close').forEach(btn => {
        btn.addEventListener('click', (e) => { 
            e.preventDefault(); 
            cerrarModalUI('modalPago');
        });
    });
});

// ==========================================
// CRUD LISTADO
// ==========================================
function listar() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTabla");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

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
    .catch(error => console.error("Error al listar pagos:", error));
}

function cargarDependencias() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=cargar_dependencias")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const selectProv = document.getElementById("id_proveedor");
            const selectMetodo = document.getElementById("id_metodo");
            const selectMoneda = document.getElementById("id_moneda");
            
            selectProv.innerHTML = '<option value="">Seleccione proveedor...</option>';
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
    .catch(error => console.error("Error al cargar dependencias:", error));
}

function nuevoPago() {
    document.getElementById("formPago").reset();
    document.getElementById("cajaBalance").classList.add('d-none');
    
    // Reseteamos el select de órdenes
    document.getElementById("id_compra").innerHTML = '<option value="">Primero seleccione proveedor...</option>';
    document.getElementById("id_compra").disabled = true;
    
    abrirModalUI('modalPago');
}

function anular(id_pago) {
    if (confirm("¿Está seguro que desea ANULAR este pago? El dinero volverá a aparecer como deuda pendiente en la Orden de Compra.")) {
        const f = new FormData(); 
        f.append("id_pago_compra", id_pago);
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_PagoCompra.php?action=anular", {
            method: "POST", 
            body: f
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                listar();
            }
        })
        .catch(error => console.error("Error al anular pago:", error));
    }
}

// ==========================================
// FUNCIONES DEL MODAL A PRUEBA DE FALLOS
// ==========================================
function abrirModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) { $('#' + idModal).modal('show'); return; }
        if (typeof bootstrap !== 'undefined') {
            let mod = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            mod.show(); return;
        } throw new Error("");
    } catch (e) {
        modalElement.classList.add('show'); modalElement.style.display = 'block'; document.body.classList.add('modal-open');
        if (!document.getElementById('fondo-oscuro-modal')) {
            const b = document.createElement('div'); b.className = 'modal-backdrop fade show'; b.id = 'fondo-oscuro-modal';
            document.body.appendChild(b);
        }
    }
}

function cerrarModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    if (!modalElement) return;
    modalElement.classList.remove('show'); modalElement.style.display = 'none'; document.body.classList.remove('modal-open');
    const b = document.getElementById('fondo-oscuro-modal'); if (b) b.remove();
    if (typeof $ !== 'undefined' && $.fn.modal) { $('#' + idModal).modal('hide'); }
}