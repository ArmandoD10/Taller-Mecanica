let productosEnCombo = [];

document.addEventListener("DOMContentLoaded", () => {
    listarPaquetes();
});

// Buscador Dinámico
const inputBusqueda = document.getElementById('buscar_producto');
const listaResultados = document.getElementById('lista_resultados');

inputBusqueda.addEventListener('input', function() {
    const q = this.value.trim();
    if(q.length < 2) { listaResultados.classList.add('d-none'); return; }

    fetch(`/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Paquetes.php?action=buscar_articulo&q=${q}`)
    .then(res => res.json())
    .then(data => {
        listaResultados.innerHTML = "";
        if(data.data.length > 0) {
            listaResultados.classList.remove('d-none');
            data.data.forEach(p => {
                const li = document.createElement('li');
                li.className = "list-group-item list-group-item-action d-flex align-items-center";
                li.style.cursor = "pointer";
                li.innerHTML = `
                    <img src="${p.imagen || 'default.png'}" style="width:40px; height:40px; object-fit:cover;" class="me-2 rounded">
                    <div>
                        <div class="fw-bold">${p.nombre}</div>
                        <small class="text-success">RD$ ${parseFloat(p.precio_venta).toFixed(2)}</small>
                    </div>
                `;
                li.onclick = () => agregarAlCombo(p);
                listaResultados.appendChild(li);
            });
        }
    });
});

function agregarAlCombo(p) {
    const existe = productosEnCombo.find(item => item.id === p.id_articulo);
    if(existe) {
        existe.cantidad++;
    } else {
        productosEnCombo.push({
            id: p.id_articulo,
            nombre: p.nombre,
            precio: parseFloat(p.precio_venta),
            imagen: p.imagen, // <--- IMPORTANTE: Guardar la imagen aquí
            cantidad: 1
        });
    }
    inputBusqueda.value = "";
    listaResultados.classList.add('d-none');
    renderizarCombo();
}

function renderizarCombo() {
    const contenedor = document.getElementById('contenedor_items_paquete');
    contenedor.innerHTML = "";
    let totalAcumulado = 0;

    if (productosEnCombo.length === 0) {
        contenedor.innerHTML = `<p class="text-center text-muted small my-5">No hay productos añadidos al paquete</p>`;
        document.getElementById('total_paquete').innerText = `RD$ 0.00`;
        return;
    }

    productosEnCombo.forEach((item, index) => {
        const precioConDesc = item.precio * 0.98; // Descuento del 2%
        const subtotal = precioConDesc * item.cantidad;
        totalAcumulado += subtotal;

        // --- RUTA DE LA IMAGEN ---
        const rutaImg = `${item.imagen || 'default.png'}`;

        const div = document.createElement('div');
        div.className = "d-flex justify-content-between align-items-center bg-white p-2 mb-2 rounded border-start border-4 border-primary shadow-sm";
        div.innerHTML = `
            <div class="me-3">
                <img src="${rutaImg}" 
                     style="width:50px; height:50px; object-fit:cover;" 
                     class="rounded border shadow-sm"
                     onerror="this.src='/Taller/Taller-Mecanica/img/default.png';">
            </div>

            <div style="flex:1;">
                <div class="small fw-bold text-dark">${item.nombre}</div>
                <small class="text-muted">
                    RD$ ${item.precio.toLocaleString()} -> 
                    <span class="text-success fw-bold">RD$ ${precioConDesc.toFixed(2)}</span>
                </small>
            </div>

            <div class="d-flex align-items-center">
                <input type="number" class="form-control form-control-sm me-2 text-center" 
                       style="width:60px; fw-bold" 
                       value="${item.cantidad}" 
                       min="1"
                       onchange="cambiarCantidad(${index}, this.value)">
                <button class="btn btn-sm btn-outline-danger" onclick="quitarDelCombo(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        contenedor.appendChild(div);
    });

    document.getElementById('total_paquete').innerText = `RD$ ${totalAcumulado.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
}

function cambiarCantidad(index, valor) {
    productosEnCombo[index].cantidad = parseInt(valor) || 1;
    renderizarCombo();
}

function quitarDelCombo(index) {
    productosEnCombo.splice(index, 1);
    renderizarCombo();
}


/**
 * Elimina un paquete del sistema (Cambiando su estado a 'eliminado')
 * @param {number} id - ID del paquete a eliminar
 */
function eliminarPaquete(id) {
    Swal.fire({
        title: '¿Eliminar este combo?',
        text: "Esta acción marcará el paquete como eliminado y no podrá facturarse.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);

            fetch("/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Paquetes.php?action=eliminar", { 
                method: "POST", 
                body: fd 
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Eliminado', 'El paquete ha sido removido.', 'success');
                    listarPaquetes();
                } else {
                    Swal.fire('Error', 'No se pudo eliminar el registro.', 'error');
                }
            });
        }
    });
}


/**
 * Función auxiliar para limpiar el formulario de creación
 */
function limpiarFormularioPaquete() {
    document.getElementById('nombre_paquete').value = "";
    document.getElementById('buscar_producto').value = "";
    productosEnCombo = [];
    renderizarCombo();
}


/**
 * Cambia el estado entre Activo e Inactivo mediante el Switch
 */
function cambiarEstadoPaquete(id, checkbox) {
    const nuevoEstado = checkbox.checked ? 'activo' : 'inactivo';
    const fd = new FormData();
    fd.append('id', id);
    fd.append('estado', nuevoEstado);

    fetch("/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Paquetes.php?action=cambiar_estado", {
        method: "POST",
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if(!data.success) {
            alert("Error al cambiar estado");
            checkbox.checked = !checkbox.checked; // Revertir si falla
        }
    });
}

function guardarPaquete() {
    const id_edit = document.getElementById('id_paquete_edit').value;
    const nombreInput = document.getElementById('nombre_paquete');
    const nombre = nombreInput.value.trim();
    const radioActivo = document.getElementById('paq_activo');
    const estado = radioActivo.checked ? 'activo' : 'inactivo';

    // --- VALIDACIONES CON SWEETALERT2 ---
    if (nombre.length < 3) {
        Swal.fire('Nombre demasiado corto', 'Por favor, asigne un nombre descriptivo al paquete (mínimo 3 caracteres).', 'warning');
        nombreInput.focus();
        return;
    }

    if (productosEnCombo.length < 2) {
        Swal.fire('¡Combo incompleto!', 'Un combo debe estar integrado por al menos 2 productos para aplicar el descuento.', 'error');
        return;
    }

    const totalTexto = document.getElementById('total_paquete').innerText;
    const totalLimpio = totalTexto.replace(/[^\d.]/g, '');

    const fd = new FormData();
    fd.append('id_paquete', id_edit);
    fd.append('nombre', nombre);
    fd.append('estado', estado);
    fd.append('total', totalLimpio);
    fd.append('items', JSON.stringify(productosEnCombo));

    fetch("/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Paquetes.php?action=guardar", {
        method: "POST",
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Éxito!',
                text: id_edit ? "¡Paquete actualizado correctamente!" : "¡Paquete creado y registrado con éxito!",
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                location.reload(); 
            });
        } else {
            Swal.fire('Error del sistema', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error de conexión', 'No se pudo establecer contacto con el servidor.', 'error');
    });
}

function verDetallePaquete(id, nombrePaquete, estadoActual) {
    fetch(`/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Paquetes.php?action=ver_detalle&id=${id}`)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Cargar datos en el formulario
            document.getElementById('id_paquete_edit').value = id;
            document.getElementById('nombre_paquete').value = nombrePaquete;
            
            // Seleccionar el radio button correcto según el estado
            if(estadoActual === 'activo') {
                document.getElementById('paq_activo').checked = true;
            } else {
                document.getElementById('paq_inactivo').checked = true;
            }

            productosEnCombo = data.items.map(i => ({
                id: i.id_articulo,
                nombre: i.nombre,
                precio: parseFloat(i.precio),
                imagen: i.imagen,
                cantidad: parseInt(i.cantidad)
            }));

            renderizarCombo();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            document.getElementById('btn_principal_paquete').innerHTML = '<i class="fas fa-sync-alt me-2"></i> ACTUALIZAR PAQUETE';
        }
    });
}

function listarPaquetes() {
    fetch("/Taller/Taller-Mecanica/modules/Autoadorno/Archivo_Paquetes.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('tabla_paquetes');
        tbody.innerHTML = "";
        data.data.forEach(p => {
            const ruta = `${p.imagen_portada || 'default.png'}`;
            // El estado ahora solo se muestra como texto pequeño o color en el nombre
            const colorEstado = p.estado === 'activo' ? 'text-success' : 'text-danger';
            
            tbody.innerHTML += `
                <tr>
                    <td class="small fw-bold">
                        <img src="${ruta}" style="width:35px; height:35px; object-fit:cover;" class="rounded-circle border me-2">
                        ${p.nombre_paquete} <br>
                        <small class="${colorEstado} fw-bold">${p.estado.toUpperCase()}</small>
                    </td>
                    <td class="text-success fw-bold small">RD$ ${parseFloat(p.precio_total).toLocaleString()}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="verDetallePaquete(${p.id_paquete}, '${p.nombre_paquete}', '${p.estado}')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarPaquete(${p.id_paquete})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });
    });
}


// Selección del input de nombre
const inputNombre = document.getElementById('nombre_paquete');

if (inputNombre) {
    inputNombre.addEventListener('input', function (e) {
        let valor = e.target.value;

        // 1. Forzar la primera letra a Mayúscula y el resto dejarlo igual
        if (valor.length > 0) {
            e.target.value = valor.charAt(0).toUpperCase() + valor.slice(1);
        }

        // 2. Validación de caracteres permitidos: Letras, Números, Espacios y Paréntesis ()
        // El regex [^a-zA-Z0-9\s\(\)] busca cualquier cosa que NO sea lo permitido y lo elimina
        const regexPemitido = /[^a-zA-Z0-9\s\(\)áéíóúÁÉÍÓÚñÑ]/g;
        
        if (regexPemitido.test(valor)) {
            e.target.value = valor.replace(regexPemitido, '');
            // Opcional: podrías mostrar un pequeño aviso o toast aquí
        }
    });
}