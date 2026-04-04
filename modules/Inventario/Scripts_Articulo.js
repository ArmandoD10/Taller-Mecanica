// 1. Declaración de variables globales
let modalArticulo;
let formArticulo;
let contenedorCards;
let cacheMarcas = [];
let cacheProveedores = [];

document.addEventListener("DOMContentLoaded", () => {
    // 2. Captura de elementos del DOM
    const modalElem = document.getElementById('modalArticulo');
    formArticulo = document.getElementById('formArticulo');
    contenedorCards = document.getElementById('contenedorCards');

    // 3. Inicialización de instancia de Bootstrap
    if (modalElem) {
        modalArticulo = new bootstrap.Modal(modalElem);
    }

    // 4. Carga de datos iniciales
    cargarCacheBusqueda();
    listarArticulos();

    // 5. Configuración de Buscadores Inteligentes
    // Nota: Se envían las referencias de las variables de caché
    configurarBuscador('txt_buscar_marca', 'lista_marcas', 'id_marca_producto', 'marca');
    configurarBuscador('txt_buscar_proveedor', 'lista_proveedores', 'id_proveedor', 'proveedor');

    // 6. Previsualización de imagen
    const inputImg = document.getElementById('imagen_file');
    if (inputImg) {
        inputImg.addEventListener('change', function(e) {
            const reader = new FileReader();
            reader.onload = (event) => document.getElementById('img_preview').src = event.target.result;
            if (e.target.files[0]) reader.readAsDataURL(e.target.files[0]);
        });
    }

    // 7. Envío de formulario
    if (formArticulo) {
        formArticulo.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Articulo.php?action=guardar', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    modalArticulo.hide();
                    listarArticulos();
                } else {
                    alert("Error: " + res.message);
                }
            })
            .catch(err => console.error("Error al guardar:", err));
        }
    }


    const filtroBusqueda = document.getElementById('filtroBusqueda');

if (filtroBusqueda) {
    filtroBusqueda.addEventListener('input', function() {
        const busqueda = this.value.toLowerCase().trim();
        const columnas = document.querySelectorAll('#contenedorCards .col');

        columnas.forEach(col => {
            const card = col.querySelector('.card-articulo');
            if (!card) return;

            // Leemos el nombre y el ID directamente del atributo que creamos
            const nombre = card.querySelector('h6').textContent.toLowerCase();
            const id = card.getAttribute('data-id'); // Aquí está el código del producto

            // Si el nombre contiene la búsqueda O el ID es exactamente igual
            if (nombre.includes(busqueda) || id === busqueda || id.startsWith(busqueda)) {
                col.style.display = ""; // Mostrar
            } else {
                col.style.display = "none"; // Ocultar
            }
        });
    });
}
});

// --- FUNCIONES CORE ---

function configurarBuscador(inputId, listaId, hiddenId, tipo) {
    const input = document.getElementById(inputId);
    const lista = document.getElementById(listaId);
    const hidden = document.getElementById(hiddenId);

    if (!input || !lista || !hidden) return;

    input.addEventListener('input', function() {
        const busqueda = this.value.toLowerCase().trim();
        const fuenteDatos = (tipo === 'marca') ? cacheMarcas : cacheProveedores;
        const campoMostrar = (tipo === 'marca') ? 'nombre' : 'nombre_comercial';
        
        lista.innerHTML = '';
        
        if (busqueda.length < 1) { 
            lista.classList.add('d-none'); 
            hidden.value = ''; 
            return; 
        }

        const filtrados = fuenteDatos.filter(item => 
            item[campoMostrar].toLowerCase().includes(busqueda)
        );

        if (filtrados.length > 0) {
            lista.classList.remove('d-none');
            filtrados.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action py-2';
                li.style.cursor = 'pointer';
                li.innerHTML = `<i class="fas fa-check-circle me-2 text-muted"></i>${item[campoMostrar]}`;
                
                li.onclick = () => {
                    input.value = item[campoMostrar];
                    hidden.value = item[Object.keys(item)[0]]; // Toma el ID
                    lista.classList.add('d-none');
                };
                lista.appendChild(li);
            });
        } else {
            lista.classList.add('d-none');
        }
    });

    document.addEventListener('click', (e) => {
        if (!input.contains(e.target)) lista.classList.add('d-none');
    });
}

function cargarCacheBusqueda() {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Articulo.php?action=cargar_marcas')
        .then(res => res.json()).then(data => { if(data.success) cacheMarcas = data.data; });

    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Articulo.php?action=cargar_proveedores')
        .then(res => res.json()).then(data => { if(data.success) cacheProveedores = data.data; });
}

function listarArticulos() {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Articulo.php?action=listar')
    .then(res => res.json())
    .then(response => {
        if (!contenedorCards) return;
        contenedorCards.innerHTML = '';

        response.data.forEach(art => {
            const img = art.imagen || '/Taller/Taller-Mecanica/img/default-part.webp';
            const precioVenta = parseFloat(art.precio_venta).toLocaleString('en-US', { minimumFractionDigits: 2 });

            const card = `
                <div class="col">
                    <div class="card card-articulo h-100 shadow-sm position-relative" data-id="${art.id_articulo}">
                        <button class="btn btn-sm btn-light shadow-sm position-absolute" 
                                style="top: 10px; right: 10px; z-index: 10;" 
                                onclick="editarArticulo(${art.id_articulo})">
                            <i class="fas fa-edit text-warning"></i>
                        </button>

                        <div class="card-body d-flex align-items-center" onclick="verDetalleArticulo(${art.id_articulo})">
                            <div class="img-articulo-container">
                                <img src="${img}" class="img-articulo-lista">
                            </div>
                            <div class="ms-3 overflow-hidden">
                                <h6 class="mb-0 text-dark fw-bold text-truncate">${art.nombre}</h6>
                                <p class="mb-1 small text-muted text-truncate">${art.nombre_proveedor || 'Sin proveedor'}</p>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-precio">$${precioVenta}</span>
                                    <span class="badge ${art.estado === 'activo' ? 'bg-success' : 'bg-danger'}" style="font-size:0.6rem">
                                        ${art.estado.toUpperCase()}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            contenedorCards.insertAdjacentHTML('beforeend', card);
        });
    });
}

function nuevoArticulo() {
    if (formArticulo) formArticulo.reset();
    document.getElementById('id_articulo').value = '';
    document.getElementById('id_marca_producto').value = '';
    document.getElementById('id_proveedor').value = '';
    document.getElementById('img_preview').src = '/Taller/Taller-Mecanica/img/default-part.webp';
    document.getElementById('tituloModal').innerHTML = `<i class="fas fa-plus me-2"></i>Registrar Nuevo Artículo`;
    
    if (modalArticulo) modalArticulo.show();
}


function editarArticulo(id) {
    const url = '/Taller/Taller-Mecanica/modules/Inventario/Archivo_Articulo.php?action=obtener&id=' + id;

    fetch(url)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const art = res.data;

            document.getElementById('id_articulo').value = art.id_articulo;
            document.getElementById('nombre').value = art.nombre;
            document.getElementById('num_serie').value = art.num_serie;
            document.getElementById('descripcion').value = art.descripcion || '';
            
            // ASIGNACIÓN CORRECTA: precio_compra (DB) -> precio_costo (HTML ID)
            document.getElementById('precio_compra').value = art.precio_compra;
            document.getElementById('precio_venta').value = art.precio_venta;

            document.getElementById('estado').value = art.estado;
            document.getElementById('estado_articulo').value = art.estado_articulo; 
            document.getElementById('fecha_caducidad').value = art.fecha_caducidad || '';

            // Buscadores Inteligentes
            const marca = cacheMarcas.find(m => m.id_marca_producto == art.id_marca_producto);
            document.getElementById('id_marca_producto').value = art.id_marca_producto;
            document.getElementById('txt_buscar_marca').value = marca ? marca.nombre : "";

            const prov = cacheProveedores.find(p => p.id_proveedor == art.id_proveedor);
            document.getElementById('id_proveedor').value = art.id_proveedor;
            document.getElementById('txt_buscar_proveedor').value = prov ? prov.nombre_comercial : "";

            document.getElementById('img_preview').src = art.imagen || '/Taller/Taller-Mecanica/img/default-part.webp';
            document.getElementById('tituloModal').innerHTML = `<i class="fas fa-edit me-2"></i>Modificar Artículo #${art.id_articulo}`;

            modalArticulo.show();
        }
    })
    .catch(err => alert("Error al conectar con el servidor: " + err.message));
}


function verDetalleArticulo(id) {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Articulo.php?action=obtener&id=' + id)
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const art = res.data;
            const marca = cacheMarcas.find(m => m.id_marca_producto == art.id_marca_producto);

            // Formato de precio para el detalle
            const precioDetalle = parseFloat(art.precio_venta).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            document.getElementById('det_nombre').textContent = art.nombre;
            document.getElementById('det_serie').textContent = "Serie/Parte: " + (art.num_serie || 'N/A');
            document.getElementById('det_precio').textContent = "$" + precioDetalle;
            document.getElementById('det_marca').textContent = marca ? marca.nombre : "Sin Marca";
            document.getElementById('det_descripcion').textContent = art.descripcion || "Sin descripción.";
            document.getElementById('det_fecha').textContent = art.fecha_caducidad || "N/A";
            document.getElementById('det_imagen').src = art.imagen || '/Taller/Taller-Mecanica/img/default-part.webp';
            
            // MOSTRAR ID Y ESTADOS
            document.getElementById('det_id_visual').textContent = art.id_articulo;

            const badgeAdmin = document.getElementById('det_estado_admin');
            badgeAdmin.textContent = art.estado.toUpperCase();
            badgeAdmin.className = `badge ${art.estado === 'activo' ? 'bg-success' : 'bg-danger'}`;

            const badgeFisico = document.getElementById('det_estado_fisico');
            badgeFisico.textContent = art.estado_articulo.toUpperCase();
            const colores = { 'nuevo': 'bg-info', 'usado': 'bg-warning text-dark', 'reparado': 'bg-secondary' };
            badgeFisico.className = `badge ${colores[art.estado_articulo] || 'bg-dark'} ms-2 text-uppercase`;

            const modalDet = new bootstrap.Modal(document.getElementById('modalDetalleArticulo'));
            modalDet.show();
        }
    })
    .catch(err => console.error("Error al cargar detalle:", err));
}

document.addEventListener('DOMContentLoaded', () => {
    
    const inputField = document.getElementById('nombre');

    // Verificamos que el input exista para evitar errores en consola
    if (inputField) {
        inputField.addEventListener('input', (e) => {
            let value = e.target.value;

            // 1. Filtro de caracteres especiales (Solo letras, números y espacios)
            value = value.replace(/[^a-zA-Z0-9\s]/g, '');

            // 2. Primera letra siempre Mayúscula
            if (value.length > 0) {
                value = value.charAt(0).toUpperCase() + value.slice(1);
            }

            // 3. Actualizamos el valor del input
            e.target.value = value;
        });
    }

    const inputField2 = document.getElementById('descripcion');

    // Verificamos que el input exista para evitar errores en consola
    if (inputField2) {
        inputField2.addEventListener('input', (e) => {
            let value = e.target.value;

            // 1. Filtro de caracteres especiales (Solo letras, números y espacios)
            value = value.replace(/[^a-zA-Z0-9\s]/g, '');

            // 2. Primera letra siempre Mayúscula
            if (value.length > 0) {
                value = value.charAt(0).toUpperCase() + value.slice(1);
            }

            // 3. Actualizamos el valor del input
            e.target.value = value;
        });
    }
});