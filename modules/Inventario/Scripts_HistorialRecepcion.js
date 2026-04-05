// Formateador base genérico (para cuando no necesitamos símbolo de moneda)
const formatter = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

document.addEventListener("DOMContentLoaded", () => {
    listar();

    // Lógica del buscador rápido
    const buscador = document.getElementById("buscadorRecepciones");
    if (buscador) {
        buscador.addEventListener("keyup", function() {
            const val = this.value.toLowerCase().trim();
            const filas = document.querySelectorAll("#cuerpoTabla tr");
            filas.forEach(fila => {
                const textoFila = fila.innerText.toLowerCase();
                fila.style.display = textoFila.includes(val) ? "" : "none";
            });
        });
    }
});

// ==========================================
// CRUD LISTADO PRINCIPAL DE RECEPCIONES
// ==========================================
function listar() {
    const tbody = document.getElementById("cuerpoTabla");
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Cargando historial...</td></tr>';

    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialRecepcion.php?action=listar")
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(r => {
                let badgeEstado = r.estado === "activo" ? "bg-success" : "bg-danger";
                
                let btnAnular = r.estado === "activo" 
                    ? `<button class="btn btn-outline-danger btn-sm" onclick="anular(${r.id_recepcion}, ${r.id_compra})" title="Anular Recepción de Almacén"><i class="fas fa-ban"></i></button>`
                    : `<button class="btn btn-secondary btn-sm" disabled><i class="fas fa-ban"></i></button>`;

                let fecha = r.fecha_recepcion ? r.fecha_recepcion.substring(0, 10) : "Sin Fecha";
                
                // CÁLCULO SEGURO DEL VALOR RECIBIDO
                let valorReal = parseFloat(r.monto_recibido) > 0 ? parseFloat(r.monto_recibido) : parseFloat(r.monto_orden);
                
                // FORMATEO DINÁMICO DE MONEDA
                let montoTotalFormateado = "";
                try {
                    montoTotalFormateado = new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: r.moneda || 'USD'
                    }).format(valorReal);
                } catch (e) {
                    montoTotalFormateado = (r.moneda || '$') + " " + formatter.format(valorReal);
                }

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-muted">#REC-${r.id_recepcion.toString().padStart(4, '0')}</td>
                    <td>${fecha}</td>
                    <td class="fw-bold text-dark fs-5">${r.num_conduze}</td>
                    <td class="fw-bold">${r.nombre_comercial}</td>
                    <td class="text-primary fw-bold">OC-${r.id_compra.toString().padStart(4, '0')}</td>
                    <td class="fw-bold text-success fs-6">${montoTotalFormateado}</td>
                    <td><span class="badge ${badgeEstado}">${r.estado.toUpperCase()}</span></td>
                    <td class="col-acciones">
                        <button class="btn btn-dark btn-sm text-white me-1" onclick="verDetalles(${r.id_compra}, '${r.moneda}')" title="Ver Artículos Recibidos">
                            <i class="fas fa-list"></i> Detalle
                        </button>
                        ${btnAnular}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-5">No hay recepciones de mercancía registradas en el almacén.</td></tr>`;
        }
    })
    .catch(error => {
        console.error("Error al listar recepciones:", error);
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-5">Error de conexión con el servidor.</td></tr>`;
    });
}

// ==========================================
// VER DETALLES EN MODAL
// ==========================================
function verDetalles(id_compra, codigo_moneda) {
    let tituloOrden = `OC-${id_compra.toString().padStart(4, '0')}`;
    document.getElementById("lbl_submodal_orden").innerText = tituloOrden;
    document.getElementById("lbl_submodal_orden_print").innerText = tituloOrden;
    
    const tbody = document.getElementById("cuerpoTablaArticulos");
    const lblTotal = document.getElementById("lbl_total_valor");
    
    tbody.innerHTML = '<tr><td colspan="5" class="py-3"><i class="fas fa-spinner fa-spin"></i> Obteniendo lista de artículos...</td></tr>';
    lblTotal.innerText = "$ 0.00";
    
    abrirModalUI('modalDetallesRecepcion');

    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialRecepcion.php?action=obtener&id_compra=${id_compra}`)
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data) {
            tbody.innerHTML = "";
            let sumaTotal = 0;
            
            let currencyFormatter;
            try {
                currencyFormatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: codigo_moneda || 'USD' });
            } catch (e) {
                currencyFormatter = { format: (num) => (codigo_moneda || '$') + " " + formatter.format(num) };
            }
            
            data.data.forEach(art => {
                let subtotal = parseFloat(art.subtotal_recibido || 0);
                sumaTotal += subtotal;
                
                let claseAlerta = (parseInt(art.cantidad_recibida) < parseInt(art.cantidad_pedida)) ? 'text-danger fw-bold' : 'text-success fw-bold';
                
                tbody.innerHTML += `
                    <tr>
                        <td class="text-muted">${art.num_serie || 'N/A'}</td>
                        <td class="text-start fw-bold">${art.nombre}</td>
                        <td class="fw-bold fs-5">${art.cantidad_pedida}</td>
                        <td class="${claseAlerta} fs-5 bg-light">${art.cantidad_recibida}</td>
                        <td class="fw-bold">${currencyFormatter.format(subtotal)}</td>
                    </tr>
                `;
            });
            
            lblTotal.innerText = currencyFormatter.format(sumaTotal);
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-danger">No se pudieron cargar los detalles de la recepción.</td></tr>';
        }
    })
    .catch(error => {
        console.error("Error al cargar los detalles:", error);
        tbody.innerHTML = '<tr><td colspan="5" class="text-danger">Error al consultar la base de datos.</td></tr>';
    });
}

// ==========================================
// ANULACIÓN DE LA RECEPCIÓN (ADMINISTRADOR)
// ==========================================
function anular(id_recepcion, id_compra) {
    if (confirm("ATENCIÓN: Solo Administradores.\n\n¿Está seguro que desea ANULAR esta recepción? \nAl hacerlo, las cantidades recibidas en la Orden de Compra volverán a cero y el inventario se revertirá.")) {
        
        const f = new FormData(); 
        f.append("id_recepcion", id_recepcion);
        f.append("id_compra", id_compra);
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_HistorialRecepcion.php?action=anular", { 
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
            }
        })
        .catch(error => {
            console.error("Error al anular la recepción:", error);
            alert("Error de red. Intente nuevamente.");
        });
    }
}

// ==========================================
// IMPRESIONES Y REPORTES
// ==========================================
function imprimirReporteGlobal() {
    document.body.classList.add('modo-reporte');
    window.print();
    setTimeout(() => {
        document.body.classList.remove('modo-reporte');
    }, 500);
}

function imprimirDetalle() {
    document.body.classList.add('modo-detalle');
    window.print();
    setTimeout(() => {
        document.body.classList.remove('modo-detalle');
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