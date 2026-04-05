// Formateador base genérico (para fallback si falla la moneda)
const formatter = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

document.addEventListener("DOMContentLoaded", () => {
    listar();

    // ==========================================
    // BUSCADOR EN TIEMPO REAL (Filtro de Tabla)
    // ==========================================
    const buscador = document.getElementById("buscadorPagos");
    if (buscador) {
        buscador.addEventListener("keyup", function() {
            const val = this.value.toLowerCase().trim();
            const filas = document.querySelectorAll("#cuerpoTablaPagos tr");
            
            filas.forEach(fila => {
                const textoFila = fila.innerText.toLowerCase();
                fila.style.display = textoFila.includes(val) ? "" : "none";
            });
        });
    }
});

// ==========================================
// CRUD LISTADO PRINCIPAL DE PAGOS
// ==========================================
function listar() {
    const tbody = document.getElementById("cuerpoTablaPagos");
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Cargando historial de pagos...</td></tr>';

    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialPago.php?action=listar")
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(p => {
                let badgeEstado = p.estado === "activo" ? "bg-success" : "bg-danger";
                
                let btnAnular = p.estado === "activo" 
                    ? `<button class="btn btn-outline-danger btn-sm fw-bold" onclick="anular(${p.id_pago_compra})" title="Anular Pago"><i class="fas fa-ban me-1"></i> Anular</button>`
                    : `<button class="btn btn-secondary btn-sm" disabled><i class="fas fa-ban me-1"></i> Anulado</button>`;

                let fecha = p.fecha_pago ? p.fecha_pago.substring(0, 16) : "Sin Fecha"; 
                let referencia = p.referencia_pago ? p.referencia_pago : '<span class="text-muted fst-italic">N/A</span>';
                
                let valorPagado = parseFloat(p.monto_pagado || 0);
                let montoTotalFormateado = "";
                try {
                    montoTotalFormateado = new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: p.moneda || 'DOP'
                    }).format(valorPagado);
                } catch (e) {
                    montoTotalFormateado = (p.moneda || '$') + " " + formatter.format(valorPagado);
                }
                
                let claseTextoMonto = p.estado === "activo" ? "text-success fw-bold fs-6" : "text-muted text-decoration-line-through";

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-muted">#PAG-${p.id_pago_compra.toString().padStart(4, '0')}</td>
                    <td>${fecha}</td>
                    <td class="fw-bold text-start ps-3">${p.nombre_comercial}</td>
                    <td class="text-primary fw-bold">OC-${p.id_compra.toString().padStart(4, '0')}</td>
                    <td><span class="badge bg-light text-dark border border-secondary">${p.metodo_pago}</span></td>
                    <td>${referencia}</td>
                    <td class="${claseTextoMonto}">${montoTotalFormateado}</td>
                    <td><span class="badge ${badgeEstado}">${p.estado.toUpperCase()}</span></td>
                    <td class="col-acciones">
                        <button class="btn btn-dark btn-sm text-white me-1" onclick="verDetallesPago(${p.id_pago_compra})" title="Ver Comprobante">
                            <i class="fas fa-file-invoice-dollar"></i> Detalle
                        </button>
                        ${btnAnular}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-5">No hay pagos a proveedores registrados en el sistema.</td></tr>`;
        }
    })
    .catch(error => {
        console.error("Error al listar pagos:", error);
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-5">Error de conexión con el servidor. Verifique la consola (F12).</td></tr>`;
    });
}

// ==========================================
// MOSTRAR EL VOUCHER DE PAGO EN MODAL
// ==========================================
function verDetallesPago(id_pago) {
    abrirModalUI('modalDetallePago');
    
    document.getElementById("lbl_voucher_id").innerText = "Cargando...";
    document.getElementById("vd_fecha").innerText = "...";
    document.getElementById("vd_proveedor").innerText = "...";
    document.getElementById("vd_rnc").innerText = "...";
    document.getElementById("vd_orden").innerText = "...";
    document.getElementById("vd_metodo").innerText = "...";
    document.getElementById("vd_referencia").innerText = "...";
    document.getElementById("vd_moneda").innerText = "...";
    document.getElementById("vd_usuario").innerText = "...";
    document.getElementById("vd_monto").innerText = "$ 0.00";
    document.getElementById("alerta_estado_pago").innerHTML = "";

    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialPago.php?action=obtener_detalle&id_pago=${id_pago}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const p = data.data;
            
            document.getElementById("lbl_voucher_id").innerText = `#PAG-${p.id_pago_compra.toString().padStart(4, '0')}`;
            document.getElementById("vd_fecha").innerText = p.fecha_pago;
            document.getElementById("vd_proveedor").innerText = p.proveedor;
            document.getElementById("vd_rnc").innerText = p.RNC || 'N/A';
            document.getElementById("vd_orden").innerText = `OC-${p.id_compra.toString().padStart(4, '0')}`;
            document.getElementById("vd_metodo").innerText = p.metodo_pago;
            document.getElementById("vd_referencia").innerText = p.referencia_pago || 'Ninguna';
            document.getElementById("vd_moneda").innerText = `${p.moneda} - ${p.nombre_moneda}`;
            document.getElementById("vd_usuario").innerText = p.usuario_registro.toUpperCase();

            if (p.estado === 'eliminado') {
                document.getElementById("alerta_estado_pago").innerHTML = `
                    <div class="alert alert-danger fw-bold text-center border-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> ESTE PAGO HA SIDO ANULADO
                    </div>`;
            }

            let valorPagado = parseFloat(p.monto_pagado || 0);
            let montoTotalFormateado = "";
            try {
                montoTotalFormateado = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: p.moneda || 'DOP'
                }).format(valorPagado);
            } catch (e) {
                montoTotalFormateado = (p.moneda || '$') + " " + formatter.format(valorPagado);
            }

            document.getElementById("vd_monto").innerText = montoTotalFormateado;

        } else {
            alert("Error al obtener los detalles: " + data.message);
            cerrarModalUI('modalDetallePago');
        }
    })
    .catch(error => {
        console.error("Error al obtener el pago:", error);
        alert("Error de red al consultar el comprobante. Verifique la conexión o contacte al administrador.");
        cerrarModalUI('modalDetallePago');
    });
}

// ==========================================
// ANULACIÓN DEL PAGO (ADMINISTRADOR)
// ==========================================
function anular(id_pago) {
    if (confirm("ATENCIÓN: Solo Administradores.\n\n¿Está seguro que desea ANULAR este pago?\n\nAl hacerlo, el monto de este pago volverá a reflejarse como deuda pendiente en la Orden de Compra original.")) {
        
        const btnGuardar = document.activeElement;
        const textoOriginal = btnGuardar.innerHTML;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btnGuardar.disabled = true;

        const f = new FormData(); 
        f.append("id_pago_compra", id_pago);
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialPago.php?action=anular", { 
            method: "POST", 
            body: f 
        })
        .then(res => res.json())
        .then(data => { 
            if(data.success) {
                alert(data.message); 
                listar(); 
            } else {
                alert("❌ " + data.message);
                btnGuardar.innerHTML = textoOriginal;
                btnGuardar.disabled = false;
            }
        })
        .catch(error => {
            console.error("Error al anular el pago:", error);
            alert("Error de red. Intente nuevamente.");
            btnGuardar.innerHTML = textoOriginal;
            btnGuardar.disabled = false;
        });
    }
}

// ==========================================
// GESTIÓN DE IMPRESIÓN DUAL (VOUCHER Y REPORTE)
// ==========================================
function imprimirVoucher() {
    document.body.classList.add('modo-voucher');
    window.print();
    setTimeout(() => {
        document.body.classList.remove('modo-voucher');
    }, 500);
}

function imprimirReporteGlobal() {
    document.body.classList.add('modo-reporte');
    window.print();
    setTimeout(() => {
        document.body.classList.remove('modo-reporte');
    }, 500);
}

// ==========================================
// FUNCIONES DEL MODAL A PRUEBA DE FALLOS
// ==========================================
function abrirModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) { $('#' + idModal).modal('show'); return; }
        if (typeof bootstrap !== 'undefined') {
            let mod = bootstrap.Modal.getInstance(modalElement);
            if (!mod) { mod = new bootstrap.Modal(modalElement); }
            mod.show(); return;
        } 
        throw new Error("Forzar apertura manual");
    } catch (e) {
        modalElement.classList.add('show'); modalElement.style.display = 'block';
        document.body.classList.add('modal-open');
        if (!document.getElementById('fondo-oscuro-modal')) {
            const b = document.createElement('div'); b.className = 'modal-backdrop fade show'; b.id = 'fondo-oscuro-modal';
            document.body.appendChild(b);
        }
    }
}

function cerrarModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    if (!modalElement) { return; }
    modalElement.classList.remove('show'); modalElement.style.display = 'none'; 
    document.body.classList.remove('modal-open');
    const b = document.getElementById('fondo-oscuro-modal'); if (b) { b.remove(); }
    if (typeof $ !== 'undefined' && $.fn.modal) { $('#' + idModal).modal('hide'); }
}