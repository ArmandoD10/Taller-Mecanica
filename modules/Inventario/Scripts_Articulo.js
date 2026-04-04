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
    fetch('../../modules/Inventario/Archivo_Articulo.php?action=listar')
    .then(res => res.json())
    .then(response => {
        if (!contenedorCards) return;
        contenedorCards.innerHTML = '';
        response.data.forEach(art => {
            const img = art.imagen || '/Taller/Taller-Mecanica/img/default-part.webp';
            // Lógica para el color del estado
            const estadoColor = art.estado === 'activo' ? 'bg-success' : 'bg-danger';
            
            const card = `
                <div class="col">
                    <div class="card card-articulo h-100 shadow-sm position-relative">
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
                                    <span class="text-precio">$${parseFloat(art.precio_venta).toFixed(2)}</span>
                                    <span class="badge ${estadoColor}" style="font-size:0.6rem">
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
    // IMPORTANTE: Asegúrate de que esta ruta sea IDÉNTICA a la que usas en listarArticulos
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Articulo.php?action=obtener&id=' + id)
    .then(res => {
        // Si el servidor responde con 404 o 500, esto atrapará el error antes de intentar leer el JSON
        if (!res.ok) throw new Error('No se encontró el archivo en la ruta especificada');
        return res.json();
    })
    .then(res => {
        if (res.success) {
            const art = res.data;

            // Llenado de campos básicos
            document.getElementById('id_articulo').value = art.id_articulo;
            document.getElementById('nombre').value = art.nombre;
            document.getElementById('descripcion').value = art.descripcion || '';
            document.getElementById('num_serie').value = art.num_serie;
            document.getElementById('precio_compra').value = art.precio_compra; // Este es el que te faltaba
            document.getElementById('precio_venta').value = art.precio_venta;
            document.getElementById('estado').value = art.estado;
            document.getElementById('fecha_caducidad').value = art.fecha_caducidad || '';

            // Sincronizar Buscadores Inteligentes con el caché
            const marca = cacheMarcas.find(m => m.id_marca_producto == art.id_marca_producto);
            document.getElementById('id_marca_producto').value = art.id_marca_producto;
            document.getElementById('txt_buscar_marca').value = marca ? marca.nombre : "";

            const prov = cacheProveedores.find(p => p.id_proveedor == art.id_proveedor);
            document.getElementById('id_proveedor').value = art.id_proveedor;
            document.getElementById('txt_buscar_proveedor').value = prov ? prov.nombre_comercial : "";

            // Imagen
            document.getElementById('img_preview').src = art.imagen ? art.imagen : '/Taller/Taller-Mecanica/img/default-part.webp';
            
            // Título y mostrar modal
            document.getElementById('tituloModal').innerHTML = `<i class="fas fa-edit me-2"></i>Editando: ${art.nombre}`;
            modalArticulo.show();
        }
    })
    .catch(err => {
        console.error("Error detallado:", err);
        alert("Error de conexión. Por favor, revisa la consola (F12) para ver la ruta exacta que está fallando.");
    });
}

function verDetalleArticulo(id) {
    // Usamos la ruta relativa que ya confirmamos que funciona
    fetch(`../../modules/Inventario/Archivo_Articulo.php?action=obtener&id=${id}`)
    .then(res => {
        if (!res.ok) throw new Error('No se pudo conectar con el servidor (404)');
        return res.json();
    })
    .then(res => {
        if (res.success) {
            const art = res.data;

            // 1. Buscamos el nombre de la marca en el caché
            const marca = cacheMarcas.find(m => m.id_marca_producto == art.id_marca_producto);

            // 2. Llenamos los elementos del Modal de Detalle
            document.getElementById('det_nombre').textContent = art.nombre;
            document.getElementById('det_serie').textContent = "No. Serie: " + (art.num_serie || 'N/A');
            document.getElementById('det_precio').textContent = "$" + parseFloat(art.precio_venta).toFixed(2);
            document.getElementById('det_marca').textContent = marca ? marca.nombre : "Sin Marca";
            document.getElementById('det_descripcion').textContent = art.descripcion || "Sin descripción disponible.";
            document.getElementById('det_fecha').textContent = art.fecha_caducidad || "No definida";
            
            // 3. Lógica de colores para el Estado
            const badgeEstado = document.getElementById('det_estado');
            badgeEstado.textContent = art.estado.toUpperCase();
            
            if (art.estado.toLowerCase() === 'activo') {
                badgeEstado.className = "badge bg-success fs-6"; // Verde
            } else {
                badgeEstado.className = "badge bg-danger fs-6";  // Rojo
            }

            // 4. Carga de Imagen
            const imgDetalle = document.getElementById('det_imagen');
            imgDetalle.src = art.imagen ? art.imagen : '/Taller/Taller-Mecanica/img/default-part.webp';

            // 5. Abrir el Modal de Detalle
            const modalDet = new bootstrap.Modal(document.getElementById('modalDetalleArticulo'));
            modalDet.show();
        } else {
            alert("Error: " + res.message);
        }
    })
    .catch(err => {
        console.error("Error al ver detalle:", err);
        alert("Error al conectar con el servidor. Verifique que la ruta del archivo PHP sea correcta.");
    });
}