document.addEventListener('DOMContentLoaded', () => {
    cargarTablas();
    inicializarBuscadorArticulos();
});

function cargarTablas() {
    cargarMisPedidos();
    cargarPorDespachar();
}

function cargarMisPedidos() {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Transferencia.php?action=listar_mis_pedidos')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById('tbody_mis_pedidos');
        tbody.innerHTML = '';
        res.data.forEach(t => {
            const btnRecibir = t.estado === 'en_transito' 
                ? `<button class="btn btn-success btn-sm fw-bold" onclick="recibirTransferencia(${t.id_transferencia})"><i class="fas fa-check me-1"></i> Recibir</button>`
                : `<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Esperando Envío</span>`;

            tbody.innerHTML += `
                <tr>
                    <td class="ps-4 fw-bold">#${t.id_transferencia}</td>
                    <td><img src="${t.imagen || '/Taller/Taller-Mecanica/img/default.png'}" class="rounded border me-2" style="width:30px; height:30px;"> ${t.producto}</td>
                    <td>${t.sucursal_origen}</td>
                    <td class="text-center fw-bold">${t.cantidad}</td>
                    <td><span class="badge bg-info text-white text-uppercase" style="font-size:0.6rem">${t.estado}</span></td>
                    <td class="text-center">${btnRecibir}</td>
                </tr>`;
        });
    });
}

function cargarPorDespachar() {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Transferencia.php?action=listar_por_despachar')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById('tbody_por_despachar');
        tbody.innerHTML = '';
        res.data.forEach(t => {
            tbody.innerHTML += `
                <tr>
                    <td class="ps-4 fw-bold">#${t.id_transferencia}</td>
                    <td>${t.producto}</td>
                    <td>${t.sucursal_destino}</td>
                    <td class="text-center fw-bold">${t.cantidad}</td>
                    <td><span class="badge bg-secondary text-white text-uppercase" style="font-size:0.6rem">PENDIENTE</span></td>
                    <td class="text-center">
                        <button class="btn btn-primary btn-sm fw-bold" onclick="despacharTransferencia(${t.id_transferencia})">
                            <i class="fas fa-truck me-1"></i> Despachar
                        </button>
                    </td>
                </tr>`;
        });
    });
}

// FUNCIONES DE ACCIÓN
function despacharTransferencia(id) {
    if(!confirm("¿Confirmar salida de mercancía? Se descontará del stock actual.")) return;
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Transferencia.php?action=despachar&id=${id}`)
    .then(res => res.json())
    .then(res => res.success ? cargarTablas() : alert(res.message));
}

function recibirTransferencia(id) {
    if(!confirm("¿Ha llegado el repuesto a sucursal?")) return;
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Transferencia.php?action=recibir&id=${id}`)
    .then(res => res.json())
    .then(res => res.success ? cargarTablas() : alert("Error al recibir"));
}

function abrirModalSolicitud() {
    new bootstrap.Modal(document.getElementById('modalNuevaSolicitud')).show();
}

function inicializarBuscadorArticulos() {
    const inputBusqueda = document.getElementById('busqueda_art');
    const listaResultados = document.getElementById('res_articulos');

    if(!inputBusqueda) return;

    inputBusqueda.addEventListener('input', function() {
        const term = this.value.trim();
        if (term.length < 2) {
            listaResultados.classList.add('d-none');
            return;
        }

        // Reutilizamos el buscador que ya tienes en otros módulos
        fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Articulo.php?action=listar`)
        .then(res => res.json())
        .then(response => {
            const filtrados = response.data.filter(a => 
                a.nombre.toLowerCase().includes(term.toLowerCase()) || 
                a.id_articulo.toString().includes(term)
            );

            listaResultados.innerHTML = '';
            if (filtrados.length > 0) {
                listaResultados.classList.remove('d-none');
                filtrados.forEach(art => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action small py-2 cursor-pointer';
                    li.innerHTML = `<i class="fas fa-box me-2 text-muted"></i>${art.nombre} <span class="float-end badge bg-light text-dark">ID: ${art.id_articulo}</span>`;
                    li.onclick = () => seleccionarArticuloParaPedido(art);
                    listaResultados.appendChild(li);
                });
            } else {
                listaResultados.classList.add('d-none');
            }
        });
    });
}

function seleccionarArticuloParaPedido(art) {
    // Mostrar el contenedor con la info del producto
    document.getElementById('art_seleccionado').classList.remove('d-none');
    document.getElementById('res_articulos').classList.add('d-none');
    document.getElementById('busqueda_art').value = art.nombre;

    document.getElementById('sel_img').src = art.imagen || '/Taller/Taller-Mecanica/img/default.png';
    document.getElementById('sel_nombre').textContent = art.nombre;
    document.getElementById('sel_id').textContent = 'ID: ' + art.id_articulo;
    
    // Guardar el ID en una variable global o campo oculto
    window.articuloSeleccionadoId = art.id_articulo;

    // Cargar las sucursales que tienen este artículo
    cargarDisponibilidadSucursales(art.id_articulo);
}


function cargarDisponibilidadSucursales(idArticulo) {
    const lista = document.getElementById('lista_disponibilidad');
    // Obtenemos el nombre de mi sucursal desde el badge
    const miSucursalElemento = document.getElementById('txt_sucursal_actual');
    const miSucursalNombre = miSucursalElemento ? miSucursalElemento.textContent.trim() : "";

    lista.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Articulo.php?action=obtener&id=${idArticulo}`)
    .then(res => res.json())
    .then(res => {
        lista.innerHTML = '';
        
        // FILTRO DINÁMICO: Si el nombre de la sucursal en la lista es igual al mío, lo salto.
        const sucursalesAjenas = res.stock_lista.filter(s => {
            const nombreSucursalLista = s.sucursal.trim();
            // Solo incluimos si el nombre NO es igual al mío y NO es "Cargando..."
            return nombreSucursalLista !== miSucursalNombre && nombreSucursalLista !== "Cargando...";
        });

        if (sucursalesAjenas.length > 0) {
            sucursalesAjenas.forEach(suc => {
                lista.innerHTML += `
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" 
                            onclick="seleccionarOrigenTransferencia(this, '${suc.sucursal}', ${suc.cantidad})">
                        <span><i class="fas fa-store me-2 text-primary"></i>${suc.sucursal}</span>
                        <span class="badge bg-success">${suc.cantidad} disp.</span>
                    </button>`;
            });
        } else {
            lista.innerHTML = `<div class="alert alert-warning small p-2 mb-0">No hay stock disponible en otras sucursales.</div>`;
        }
    });
}

// Variable global para las sucursales elegidas
let sucursalesSeleccionadas = []; 

function seleccionarOrigenTransferencia(btn, sucursalNombre, stockMax) {
    // Buscamos si ya está en nuestro array
    const index = sucursalesSeleccionadas.findIndex(s => s.nombre === sucursalNombre);

    if (index > -1) {
        // SI YA ESTÁ: Lo quitamos (desmarcar)
        sucursalesSeleccionadas.splice(index, 1);
        btn.classList.remove('active', 'bg-primary', 'text-white');
    } else {
        // NO ESTÁ: Lo agregamos (marcar)
        sucursalesSeleccionadas.push({
            nombre: sucursalNombre,
            stockMax: stockMax
        });
        btn.classList.add('active', 'bg-primary', 'text-white');
    }

    // Habilitar/Deshabilitar el input de cantidad según si hay algo elegido
    const inputCant = document.getElementById('cant_pedir');
    inputCant.disabled = sucursalesSeleccionadas.length === 0;
    
    console.log("Sucursales marcadas:", sucursalesSeleccionadas);
}

function guardarSolicitud() {
    const idArticulo = window.articuloSeleccionadoId;
    const cantidad = parseInt(document.getElementById('cant_pedir').value);

    if (!idArticulo || sucursalesSeleccionadas.length === 0 || isNaN(cantidad) || cantidad <= 0) {
        alert("⚠️ Por favor, seleccione al menos una sucursal y defina una cantidad válida.");
        return;
    }

    const payload = {
        id_articulo: idArticulo,
        sucursales: sucursalesSeleccionadas.map(s => s.nombre),
        cantidad: cantidad
    };

    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Transferencia.php?action=crear_solicitud', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            // Si el servidor manda un error HTML, lo veremos aquí
            console.error("Respuesta cruda del servidor:", text);
            throw new Error("El servidor no devolvió JSON válido.");
        }
    })
    .then(res => {
        if (res.success) {
            alert("✅ Solicitudes de transferencia creadas correctamente.");
            location.reload();
        } else {
            alert("❌ Error: " + res.message);
        }
    })
    .catch(err => {
        console.error("Error en la petición:", err);
        alert("❌ Error crítico: Revise la consola del navegador.");
    });
}

// Añade esto a tu función cargarTablas()
function cargarTablas() {
    cargarMisPedidos();
    cargarPorDespachar();
    cargarHistorial(); // <--- Nueva llamada
}

function cargarHistorial() {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Transferencia.php?action=listar_historial')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById('tbody_historial');
        if(!tbody) return;
        
        tbody.innerHTML = '';
        if(res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay transferencias finalizadas aún.</td></tr>';
            return;
        }

        res.data.forEach(h => {
            tbody.innerHTML += `
                <tr>
                    <td class="ps-4 fw-bold">#${h.id_transferencia}</td>
                    <td>${h.producto}</td>
                    <td>
                        <small class="text-muted d-block">${h.sucursal_origen} <i class="fas fa-arrow-right mx-1 text-primary"></i></small>
                        <span class="fw-bold text-dark">${h.sucursal_destino}</span>
                    </td>
                    <td class="text-center"><span class="badge bg-light text-dark border">${h.cantidad}</span></td>
                    <td>${h.fecha_final}</td>
                </tr>`;
        });
    })
    .catch(err => console.error("Error al cargar historial:", err));
}