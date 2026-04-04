let cacheProveedores = [];
let cacheArticulos = []; 
let carritoArticulos = []; 
let ordenBloqueada = false; 

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // ==========================================
    // BUSCADOR DINÁMICO NATIVO: PROVEEDORES
    // ==========================================
    const inputProv = document.getElementById("buscar_proveedor");
    const listProv = document.getElementById("lista_proveedores");
    const hiddenProv = document.getElementById("id_proveedor");

    inputProv.addEventListener("input", function() {
        const val = this.value.toLowerCase().trim();
        listProv.innerHTML = "";
        hiddenProv.value = ""; 
        
        if (!val) {
            listProv.style.display = "none";
            return;
        }

        const filtrados = cacheProveedores.filter(p => 
            p.nombre_comercial.toLowerCase().includes(val) || 
            (p.RNC && p.RNC.toLowerCase().includes(val))
        );

        if (filtrados.length > 0) {
            filtrados.forEach(p => {
                const item = document.createElement("a");
                item.href = "#";
                item.className = "list-group-item list-group-item-action text-start";
                item.innerHTML = `<span class="fw-bold">${p.nombre_comercial}</span> <br><small class="text-muted">RNC: ${p.RNC || 'N/A'}</small>`;
                
                item.addEventListener("click", function(e) {
                    e.preventDefault();
                    inputProv.value = p.nombre_comercial; 
                    hiddenProv.value = p.id_proveedor;    
                    listProv.style.display = "none";      
                });
                
                listProv.appendChild(item);
            });
            listProv.style.display = "block";
        } else {
            listProv.style.display = "none";
        }
    });

    // ==========================================
    // BUSCADOR DINÁMICO NATIVO: ARTÍCULOS
    // ==========================================
    const inputArt = document.getElementById("buscar_articulo");
    const listArt = document.getElementById("lista_articulos");
    const hiddenArt = document.getElementById("id_articulo_seleccionado");
    const inputPrecio = document.getElementById("input_precio");
    const inputCantidad = document.getElementById("input_cantidad");

    inputArt.addEventListener("input", function() {
        const val = this.value.toLowerCase().trim();
        listArt.innerHTML = "";
        hiddenArt.value = "";
        inputPrecio.value = "";
        
        if (!val) {
            listArt.style.display = "none";
            return;
        }

        const filtrados = cacheArticulos.filter(a => 
            a.nombre.toLowerCase().includes(val) || 
            (a.num_serie && a.num_serie.toLowerCase().includes(val))
        );

        if (filtrados.length > 0) {
            filtrados.forEach(a => {
                const item = document.createElement("a");
                item.href = "#";
                item.className = "list-group-item list-group-item-action text-start";
                item.innerHTML = `<span class="fw-bold">${a.nombre}</span> <br><small class="text-muted">Cód: ${a.num_serie || 'N/A'} | $${a.precio_compra}</small>`;
                
                item.addEventListener("click", function(e) {
                    e.preventDefault();
                    inputArt.value = a.nombre;                
                    hiddenArt.value = a.id_articulo;          
                    inputPrecio.value = a.precio_compra || 0; 
                    inputCantidad.value = 1;                  
                    listArt.style.display = "none";           
                });
                
                listArt.appendChild(item);
            });
            listArt.style.display = "block";
        } else {
            listArt.style.display = "none";
        }
    });

    document.addEventListener("click", function(e) {
        if (e.target !== inputProv) {
            listProv.style.display = "none";
        }
        if (e.target !== inputArt) {
            listArt.style.display = "none";
        }
    });

    // ==========================================
    // LÓGICA DEL CARRITO: AGREGAR A LA TABLA
    // ==========================================
    document.getElementById('btnAgregarArticulo').addEventListener('click', function() {
        if (ordenBloqueada) {
            return;
        }

        const id_art = document.getElementById("id_articulo_seleccionado").value; 
        const precio = parseFloat(inputPrecio.value);
        const cantidad = parseInt(inputCantidad.value);

        if (!id_art || isNaN(precio) || isNaN(cantidad) || cantidad <= 0) {
            alert("Seleccione un artículo válido de la lista, verifique el precio y asigne cantidad.");
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

        inputArt.value = "";
        hiddenArt.value = "";
        inputPrecio.value = ""; 
        inputCantidad.value = "1";
        
        dibujarCarrito();
    });

    // ==========================================
    // GUARDADO DEL FORMULARIO COMPLETO VIA JSON
    // ==========================================
    document.getElementById("btnGuardarCompra").addEventListener("click", function() {
        if (ordenBloqueada) {
            return;
        }

        const id_proveedor = document.getElementById("id_proveedor").value;
        const id_metodo = document.getElementById("id_metodo").value;
        const id_moneda = document.getElementById("id_moneda").value;
        
        if (!id_proveedor || !id_metodo || !id_moneda) {
            alert("Faltan datos en la Cabecera (Proveedor, Método o Moneda). Asegúrese de haber seleccionado un proveedor válido de la lista."); 
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

    let colQuitar = document.getElementById("col_quitar");
    let colQuitarFoot = document.getElementById("col_quitar_foot");
    
    if (colQuitar) {
        colQuitar.style.display = ordenBloqueada ? 'none' : 'table-cell';
    }
    if (colQuitarFoot) {
        colQuitarFoot.style.display = ordenBloqueada ? 'none' : 'table-cell';
    }

    if (carritoArticulos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-muted py-3">No hay artículos agregados a la orden.</td></tr>`;
        lblTotal.innerText = "$ 0.00";
        return;
    }

    carritoArticulos.forEach((item, index) => {
        const subtotal = item.precio * item.cantidad;
        totalGeneral += subtotal;

        let botonQuitar = "";
        
        if (!ordenBloqueada) {
            botonQuitar = `
                <td>
                    <button class="btn btn-outline-danger btn-sm" onclick="quitarDelCarrito(${index})" title="Quitar">
                        <i class="fas fa-times"></i>
                    </button>
                </td>`;
        } else {
            botonQuitar = `<td></td>`;
        }

        tbody.innerHTML += `
            <tr>
                <td class="text-muted">${item.num_serie || '-'}</td>
                <td class="fw-bold text-start ps-3">${item.nombre}</td>
                <td>$ ${parseFloat(item.precio).toFixed(2)}</td>
                <td class="fw-bold fs-5">${item.cantidad}</td>
                <td class="fw-bold text-primary text-end pe-3">$ ${subtotal.toFixed(2)}</td>
                ${botonQuitar}
            </tr>
        `;
    });

    lblTotal.innerText = "$ " + totalGeneral.toFixed(2);
}

function quitarDelCarrito(index) {
    if (ordenBloqueada) {
        return;
    }
    
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
            cacheProveedores = data.data.proveedores;
            cacheArticulos = data.data.articulos;
            
            const selectMetodo = document.getElementById("id_metodo");
            const selectMoneda = document.getElementById("id_moneda");
            
            selectMetodo.innerHTML = '<option value="">Seleccione método...</option>';
            selectMoneda.innerHTML = '<option value="">Seleccione moneda...</option>';
            
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

function nuevaCompra() {
    ordenBloqueada = false;
    document.getElementById("formCompraCabecera").reset();
    document.getElementById("id_compra").value = "";
    
    document.getElementById("buscar_proveedor").value = "";
    document.getElementById("id_proveedor").value = "";
    document.getElementById("buscar_articulo").value = "";
    document.getElementById("id_articulo_seleccionado").value = "";
    
    bloquearFormulario(false, ""); // Reseteamos la alerta
    
    carritoArticulos = [];
    dibujarCarrito();
    
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Orden de Compra';
    abrirModalUI('modalCompra');
}

function editar(id) {
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-list me-2"></i>Detalles y Edición de Orden';
    
    let banner = document.getElementById("contenedorBanner");
    if (banner) {
        banner.innerHTML = "";
    }

    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Compra.php?action=obtener&id_compra=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            
            document.getElementById("id_compra").value = d.id_compra;
            
            const prov = cacheProveedores.find(p => p.id_proveedor == d.id_proveedor);
            document.getElementById("buscar_proveedor").value = prov ? prov.nombre_comercial : "";
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
            
            let pagado = parseFloat(d.total_pagado || 0);
            let recepciones = parseInt(d.total_recepciones || 0);
            
            ordenBloqueada = (pagado > 0 || recepciones > 0);
            
            let mensajeBloqueo = "";
            if (pagado > 0 && recepciones > 0) {
                mensajeBloqueo = "Esta orden ya tiene <strong>pagos registrados y mercancía recibida</strong>. MODO LECTURA.";
            } else if (pagado > 0) {
                mensajeBloqueo = "Esta orden ya tiene <strong>pagos registrados</strong> en contabilidad. MODO LECTURA.";
            } else if (recepciones > 0) {
                mensajeBloqueo = "Esta orden ya tiene <strong>mercancía recibida</strong> en el almacén. MODO LECTURA.";
            }
            
            bloquearFormulario(ordenBloqueada, mensajeBloqueo);
            
            dibujarCarrito();
            abrirModalUI('modalCompra');
        }
    })
    .catch(error => {
        console.error("Error al obtener datos de la compra:", error);
    });
}

function bloquearFormulario(isLocked, mensaje) {
    document.getElementById("buscar_proveedor").disabled = isLocked;
    document.getElementById("id_metodo").disabled = isLocked;
    document.getElementById("id_moneda").disabled = isLocked;
    document.getElementById("detalle").disabled = isLocked;
    document.getElementById("estado").disabled = isLocked;
    
    let seccionArticulos = document.getElementById("seccion_agregar_articulos");
    if (seccionArticulos) {
        seccionArticulos.style.display = isLocked ? 'none' : 'flex';
    }
    
    let btnGuardar = document.getElementById("btnGuardarCompra");
    if (btnGuardar) {
        btnGuardar.style.display = isLocked ? 'none' : 'block';
    }
    
    let contenedor = document.getElementById("contenedorBanner");
    if (contenedor) {
        contenedor.innerHTML = "";
        if (isLocked) {
            contenedor.innerHTML = `
                <div class="alert alert-warning fw-bold mb-3 shadow-sm border-warning">
                    <i class="fas fa-lock me-2 fs-5"></i> 
                    ${mensaje}
                </div>`;
        }
    }
}

function eliminar(id) {
    if (confirm("ATENCIÓN: Solo los administradores pueden realizar esta acción.\n\n¿Está seguro que desea ANULAR esta orden de compra?")) {
        const f = new FormData(); 
        f.append("id_compra", id);
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Compra.php?action=eliminar", { 
            method: "POST", 
            body: f 
        })
        .then(res => res.json())
        .then(data => { 
            if (data.success) {
                alert(data.message); 
                listar(); 
            } else {
                alert("❌ " + data.message);
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
        document.body.classList.add('modal-open');
        
        if (!document.getElementById('fondo-oscuro-modal')) {
            const backdrop = document.createElement('div'); 
            backdrop.className = 'modal-backdrop fade show'; 
            backdrop.id = 'fondo-oscuro-modal';
            document.body.appendChild(backdrop);
        }
    }
}

function cerrarModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    
    if (!modalElement) return;
    
    modalElement.classList.remove('show'); 
    modalElement.style.display = 'none'; 
    document.body.classList.remove('modal-open');
    
    const backdrop = document.getElementById('fondo-oscuro-modal'); 
    
    if (backdrop) {
        backdrop.remove(); 
    }
    
    if (typeof $ !== 'undefined' && $.fn.modal) { 
        $('#' + idModal).modal('hide'); 
    }
}