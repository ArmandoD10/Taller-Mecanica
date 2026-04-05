// Formateador base genérico
const formatter = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
let idOrdenActivaEnModal = 0; // Variable para saber qué orden está abierta en el modal

document.addEventListener("DOMContentLoaded", () => {
    listar();

    // Lógica del buscador rápido en la tabla
    const buscador = document.getElementById("buscadorHistorial");
    if (buscador) {
        buscador.addEventListener("keyup", function() {
            const val = this.value.toLowerCase().trim();
            const filas = document.querySelectorAll("#cuerpoTablaHistorial tr");
            
            filas.forEach(fila => {
                const textoFila = fila.innerText.toLowerCase();
                fila.style.display = textoFila.includes(val) ? "" : "none";
            });
        });
    }
});

// ==========================================
// LISTADO PRINCIPAL DE AUDITORÍA
// ==========================================
function listar() {
    const tbody = document.getElementById("cuerpoTablaHistorial");
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Generando reporte...</td></tr>';

    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialCompra.php?action=listar")
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(c => {
                // Fechas y Estados Generales
                let fecha = c.fecha_creacion ? c.fecha_creacion.substring(0, 10) : "Sin Fecha";
                let badgeEstado = c.estado === "activo" ? "bg-primary" : "bg-danger";
                
                // Análisis Financiero
                let totalOrden = parseFloat(c.total_orden || 0);
                let totalPagado = parseFloat(c.total_pagado || 0);
                let balancePendiente = totalOrden - totalPagado;
                
                // Formateo de Monedas Dinámico
                let currencyFormatter;
                try {
                    currencyFormatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: c.moneda || 'USD' });
                } catch (e) {
                    currencyFormatter = { format: (num) => "$ " + formatter.format(num) };
                }

                // Color del Balance (Rojo si debe, Verde si está pagado)
                let colorBalance = balancePendiente > 0 ? "text-danger" : "text-success";
                let textoBalance = balancePendiente > 0 ? currencyFormatter.format(balancePendiente) : "PAGADA";

                // Estado de la Mercancía (Almacén)
                let recepciones = parseInt(c.cantidad_recepciones || 0);
                let badgeMercancia = recepciones > 0 
                    ? `<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Recibida</span>` 
                    : `<span class="badge bg-warning text-dark"><i class="fas fa-truck me-1"></i>En Tránsito</span>`;

                if (c.estado === 'eliminado') {
                    badgeMercancia = `<span class="badge bg-secondary">Anulada</span>`;
                    textoBalance = "N/A";
                    colorBalance = "text-muted";
                }

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-primary fs-6">OC-${c.id_compra.toString().padStart(4, '0')}</td>
                    <td>${fecha}</td>
                    <td class="fw-bold">${c.nombre_comercial}</td>
                    <td>${badgeMercancia}</td>
                    <td class="fw-bold">${currencyFormatter.format(totalOrden)}</td>
                    <td class="fw-bold ${colorBalance}">${textoBalance}</td>
                    <td><span class="badge ${badgeEstado}">${c.estado.toUpperCase()}</span></td>
                    <td class="col-acciones">
                        <button class="btn btn-dark btn-sm text-white me-1" onclick="verDetalles(${c.id_compra}, '${c.moneda}')" title="Ver Artículos de la Orden">
                            <i class="fas fa-list"></i> Detalle
                        </button>
                        <button class="btn btn-info btn-sm text-white" onclick="imprimirOrdenIndividualTabla(${c.id_compra})" title="Reimprimir Orden Original">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-5">No hay historial de órdenes de compra.</td></tr>`;
        }
    })
    .catch(error => {
        console.error("Error al listar historial:", error);
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-5">Error de conexión con el servidor.</td></tr>`;
    });
}

// ==========================================
// VER DETALLES DE LA ORDEN EN MODAL
// ==========================================
function verDetalles(id_compra, codigo_moneda) {
    idOrdenActivaEnModal = id_compra; // Guardamos el ID para el botón de imprimir del modal
    document.getElementById("lbl_submodal_orden").innerText = `OC-${id_compra.toString().padStart(4, '0')}`;
    const tbody = document.getElementById("cuerpoTablaArticulos");
    const lblTotal = document.getElementById("lbl_total_valor");
    
    tbody.innerHTML = '<tr><td colspan="6" class="py-3"><i class="fas fa-spinner fa-spin"></i> Obteniendo artículos...</td></tr>';
    lblTotal.innerText = "$ 0.00";
    
    abrirModalUI('modalDetallesOrden');

    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialCompra.php?action=obtener_detalles&id_compra=${id_compra}`)
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data) {
            tbody.innerHTML = "";
            let sumaTotal = 0;
            
            // Formateador dinámico
            let currencyFormatter;
            try {
                currencyFormatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: codigo_moneda || 'USD' });
            } catch (e) {
                currencyFormatter = { format: (num) => "$ " + formatter.format(num) };
            }
            
            data.data.forEach(art => {
                let subtotal = parseFloat(art.subtotal || 0);
                sumaTotal += subtotal;
                
                // Color verde si ya se recibió todo lo pedido, amarillo si falta
                let colorRecibido = parseInt(art.cantidad_recibida) >= parseInt(art.cantidad_pedida) ? 'text-success fw-bold' : 'text-warning text-dark fw-bold';
                if (parseInt(art.cantidad_recibida) === 0) colorRecibido = 'text-muted';
                
                tbody.innerHTML += `
                    <tr>
                        <td class="text-muted">${art.num_serie || 'N/A'}</td>
                        <td class="text-start fw-bold">${art.nombre}</td>
                        <td>${currencyFormatter.format(parseFloat(art.precio))}</td>
                        <td class="fw-bold fs-5">${art.cantidad_pedida}</td>
                        <td class="${colorRecibido} fs-5 bg-light">${art.cantidad_recibida}</td>
                        <td class="fw-bold text-dark">${currencyFormatter.format(subtotal)}</td>
                    </tr>
                `;
            });
            
            lblTotal.innerText = currencyFormatter.format(sumaTotal);
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-danger">No se pudieron cargar los detalles de la orden.</td></tr>';
        }
    })
    .catch(error => {
        console.error("Error al cargar los detalles:", error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-danger">Error al consultar la base de datos.</td></tr>';
    });
}

// ==========================================
// IMPRESIONES Y REPORTES
// ==========================================

// Imprimir el reporte tabular (Toda la tabla)
function imprimirReporteGlobal() {
    document.body.classList.add('modo-reporte');
    window.print();
    setTimeout(() => {
        document.body.classList.remove('modo-reporte');
    }, 500);
}

// Imprimir la orden original desde el botón del Modal
function imprimirOrdenIndividual() {
    if (idOrdenActivaEnModal > 0) {
        window.open(`/Taller/Taller-Mecanica/view/Inventario/Imprimir_Compra.php?id=${idOrdenActivaEnModal}`, '_blank');
    }
}

// Imprimir la orden original desde el botón de la Tabla
function imprimirOrdenIndividualTabla(id_compra) {
    window.open(`/Taller/Taller-Mecanica/view/Inventario/Imprimir_Compra.php?id=${id_compra}`, '_blank');
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
    if (!modalElement) return;
    modalElement.classList.remove('show'); modalElement.style.display = 'none'; 
    document.body.classList.remove('modal-open');
    const b = document.getElementById('fondo-oscuro-modal'); if (b) b.remove();
    if (typeof $ !== 'undefined' && $.fn.modal) { $('#' + idModal).modal('hide'); }
}