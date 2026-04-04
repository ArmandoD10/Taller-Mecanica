let cacheArticulos = []; 
let carritoArticulos = []; 

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // ==========================================
    // LÓGICA DEL CARRITO: AUTOCOMPLETAR PRECIO
    // ==========================================
    const selectorArticulo = document.getElementById('selector_articulo');
    const inputPrecio = document.getElementById('input_precio');
    const inputCantidad = document.getElementById('input_cantidad');

    selectorArticulo.addEventListener('change', function() {
        const id_art = this.value;
        if (!id_art) {
            inputPrecio.value = "";
            return;
        }
        
        const articulo = cacheArticulos.find(a => a.id_articulo == id_art);
        if (articulo) {
            inputPrecio.value = articulo.precio_compra || 0;
            inputCantidad.value = 1; 
        }
    });

    // ==========================================
    // LÓGICA DEL CARRITO: AGREGAR A LA TABLA
    // ==========================================
    document.getElementById('btnAgregarArticulo').addEventListener('click', function() {
        const id_art = selectorArticulo.value;
        const precio = parseFloat(inputPrecio.value);
        const cantidad = parseInt(inputCantidad.value);

        if (!id_art || isNaN(precio) || isNaN(cantidad) || cantidad <= 0) {
            alert("Seleccione un artículo, verifique el precio y asigne una cantidad válida.");
            return;
        }

        const articulo = cacheArticulos.find(a => a.id_articulo == id_art);
        
        const existeIndex = carritoArticulos.findIndex(item => item.id_articulo == id_art);
        
        if (existeIndex !== -1) {
            carritoArticulos[existeIndex].cantidad += cantidad;
            carritoArticulos[existeIndex].precio = precio; 
        } else {
            carritoArticulos.push({
                id_articulo: id_art,
                nombre: articulo.nombre,
                num_serie: articulo.num_serie,
                precio: precio,
                cantidad: cantidad
            });
        }

        selectorArticulo.value = "";
        inputPrecio.value = "";
        inputCantidad.value = "1";
        
        dibujarCarrito();
    });

    // ==========================================
    // GUARDADO DEL FORMULARIO COMPLETO VIA JSON
    // ==========================================
    document.getElementById("btnGuardarCompra").addEventListener("click", function() {
        const id_proveedor = document.getElementById("id_proveedor").value;
        const id_metodo = document.getElementById("id_metodo").value;
        const id_moneda = document.getElementById("id_moneda").value;
        
        if (!id_proveedor || !id_metodo || !id_moneda) {
            alert("Faltan datos en la Cabecera (Proveedor, Método o Moneda).");
            return;
        }
        
        if (carritoArticulos.length === 0) {
            alert("No puede crear una orden de compra vacía. Agregue artículos.");
            return;
        }

        const payload = {
            id_compra: document.getElementById("id_compra").value,
            id_proveedor: id_proveedor,
            id_metodo: id_metodo,
            id_moneda: id_moneda,
            detalle: document.getElementById("detalle").value,
            estado: document.getElementById("estado").value,
            detalles_articulos: carritoArticulos 
        };

        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Compra.php?action=guardar", {
            method: "POST", 
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) { 
                cerrarModalUI('modalCompra'); 
                listar(); 
                alert(data.message); 
            } else { 
                alert(data.message); 
            }
        })
        .catch(error => {
            console.error("Error al guardar orden:", error);
            alert("Error de conexión con el servidor.");
        });
    });
});

// ==========================================
// FUNCIONES DEL CARRITO (DIBUJAR TABLA)
// ==========================================
function dibujarCarrito() {
    const tbody = document.getElementById("cuerpoTablaDetalles");
    const lblTotal = document.getElementById("lblTotalOrden");
    tbody.innerHTML = "";
    let totalGeneral = 0;

    if (carritoArticulos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-muted py-3">No hay artículos agregados a la orden.</td></tr>`;
        lblTotal.innerText = "$ 0.00";
        return;
    }

    carritoArticulos.forEach((item, index) => {
        const subtotal = item.precio * item.cantidad;
        totalGeneral += subtotal;

        tbody.innerHTML += `
            <tr>
                <td class="text-muted">${item.num_serie || '-'}</td>
                <td class="fw-bold text-start ps-3">${item.nombre}</td>
                <td>$ ${parseFloat(item.precio).toFixed(2)}</td>
                <td class="fw-bold fs-5">${item.cantidad}</td>
                <td class="fw-bold text-primary text-end pe-3">$ ${subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-outline-danger btn-sm" onclick="quitarDelCarrito(${index})" title="Quitar">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    lblTotal.innerText = "$ " + totalGeneral.toFixed(2);
}

function quitarDelCarrito(index) {
    carritoArticulos.splice(index, 1);
    dibujarCarrito();
}

// ==========================================
// CRUD PRINCIPAL DE COMPRAS
// ==========================================
function listar() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Compra.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTabla");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(c => {
                let badgeEstado = c.estado === "activo" ? "bg-success" : "bg-warning text-dark";
                let fecha = c.fecha_creacion.substring(0, 10); 

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-primary">OC-${c.id_compra.toString().padStart(4, '0')}</td>
                    <td>${fecha}</td>
                    <td class="fw-bold">${c.nombre_comercial}</td>
                    <td class="fs-5">${c.cantidad_articulo}</td>
                    <td class="fw-bold text-success">$ ${parseFloat(c.monto).toFixed(2)} <small class="text-muted">${c.moneda}</small></td>
                    <td><span class="badge ${badgeEstado}">${c.estado.toUpperCase()}</span></td>
                    <td>
                        <button class="btn btn-dark btn-sm text-white me-1" onclick="editar(${c.id_compra})" title="Ver Detalles / Editar">
                            <i class="fas fa-list"></i>
                        </button>
                        <button class="btn btn-info btn-sm text-white me-1" onclick="imprimirOrden(${c.id_compra})" title="Imprimir Orden">
                            <i class="fas fa-print"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="eliminar(${c.id_compra})" title="Anular Orden">
                            <i class="fas fa-ban"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No hay órdenes de compra registradas.</td></tr>`;
        }
    })
    .catch(error => console.error("Error al listar compras:", error));
}

function cargarDependencias() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Compra.php?action=cargar_dependencias")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const selectProv = document.getElementById("id_proveedor");
            const selectMetodo = document.getElementById("id_metodo");
            const selectMoneda = document.getElementById("id_moneda");
            const selectArticulo = document.getElementById("selector_articulo");
            
            selectProv.innerHTML = '<option value="">Seleccione proveedor...</option>';
            selectMetodo.innerHTML = '<option value="">Seleccione método...</option>';
            selectMoneda.innerHTML = '<option value="">Seleccione moneda...</option>';
            selectArticulo.innerHTML = '<option value="">Seleccione o busque artículo...</option>';
            
            data.data.proveedores.forEach(p => {
                selectProv.innerHTML += `<option value="${p.id_proveedor}">${p.nombre_comercial} (RNC: ${p.RNC})</option>`;
            });
            
            data.data.metodos.forEach(m => {
                selectMetodo.innerHTML += `<option value="${m.id_metodo}">${m.nombre}</option>`;
            });
            
            data.data.monedas.forEach(mo => {
                selectMoneda.innerHTML += `<option value="${mo.id_moneda}">${mo.codigo_ISO} - ${mo.nombre}</option>`;
            });
            
            cacheArticulos = data.data.articulos;
            cacheArticulos.forEach(a => {
                selectArticulo.innerHTML += `<option value="${a.id_articulo}">[${a.num_serie}] - ${a.nombre}</option>`;
            });
        }
    })
    .catch(error => console.error("Error al cargar dependencias:", error));
}

function nuevaCompra() {
    document.getElementById("formCompraCabecera").reset();
    document.getElementById("id_compra").value = "";
    
    carritoArticulos = [];
    dibujarCarrito();
    
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Orden de Compra';
    abrirModalUI('modalCompra');
}

function editar(id) {
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-list me-2"></i>Detalles y Edición de Orden';
    
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Compra.php?action=obtener&id_compra=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            
            document.getElementById("id_compra").value = d.id_compra;
            document.getElementById("id_proveedor").value = d.id_proveedor;
            document.getElementById("id_metodo").value = d.id_metodo;
            document.getElementById("id_moneda").value = d.id_moneda;
            document.getElementById("detalle").value = d.detalle;
            document.getElementById("estado").value = d.estado;
            
            carritoArticulos = d.detalles_articulos;
            carritoArticulos.forEach(item => {
                item.precio = parseFloat(item.precio);
                item.cantidad = parseInt(item.cantidad);
            });
            
            dibujarCarrito();
            abrirModalUI('modalCompra');
        }
    })
    .catch(error => console.error("Error al obtener datos de la compra:", error));
}

function eliminar(id) {
    if (confirm("¿Está seguro que desea ANULAR esta orden de compra? (Quedará inactiva en el sistema)")) {
        const f = new FormData(); 
        f.append("id_compra", id);
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Compra.php?action=eliminar", {
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
        .catch(error => console.error("Error al anular compra:", error));
    }
}

function imprimirOrden(id_compra) {
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
            let mod = bootstrap.Modal.getInstance(modalElement);
            if (!mod) { mod = new bootstrap.Modal(modalElement); }
            mod.show(); return;
        }
        throw new Error("Librerías de Bootstrap no detectadas");
    } catch (e) {
        modalElement.classList.add('show'); modalElement.style.display = 'block'; document.body.classList.add('modal-open');
        if (!document.getElementById('fondo-oscuro-modal')) {
            const backdrop = document.createElement('div'); backdrop.className = 'modal-backdrop fade show'; backdrop.id = 'fondo-oscuro-modal';
            document.body.appendChild(backdrop);
        }
    }
}

function cerrarModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    if (!modalElement) return;
    modalElement.classList.remove('show'); modalElement.style.display = 'none'; document.body.classList.remove('modal-open');
    const backdrop = document.getElementById('fondo-oscuro-modal'); 
    if (backdrop) { backdrop.remove(); }
    if (typeof $ !== 'undefined' && $.fn.modal) { $('#' + idModal).modal('hide'); }
}