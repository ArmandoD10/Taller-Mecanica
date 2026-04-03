const modalArticulo = new bootstrap.Modal(document.getElementById('modalArticulo'));
const formArticulo = document.getElementById('formArticulo');
const contenedorCards = document.getElementById('contenedorCards');

// Al cargar el documento
document.addEventListener('DOMContentLoaded', () => {
    listarArticulos();
    
    // Lógica de previsualización
    document.getElementById('imagen_file').addEventListener('change', function(e) {
        const reader = new FileReader();
        reader.onload = (event) => {
            document.getElementById('img_preview').src = event.target.result;
        }
        if(e.target.files[0]) reader.readAsDataURL(e.target.files[0]);
    });
});

// Función para listar en Cards
function listarArticulos() {
    fetch('Back_Articulo.php?action=listar')
    .then(res => res.json())
    .then(response => {
        contenedorCards.innerHTML = '';
        if(response.data.length === 0) {
            contenedorCards.innerHTML = '<p class="text-center w-100">No hay repuestos registrados.</p>';
            return;
        }

        response.data.forEach(art => {
            const img = art.imagen ? art.imagen : '/Taller/img/default-part.png';
            const card = `
                <div class="col">
                    <div class="card card-articulo h-100 shadow-sm" onclick="editarArticulo(${art.id_articulo})">
                        <div class="card-body d-flex align-items-center">
                            <div class="img-articulo-container">
                                <img src="${img}" class="img-articulo-lista">
                            </div>
                            <div class="ms-3 overflow-hidden">
                                <h6 class="mb-0 text-dark fw-bold text-truncate">${art.nombre}</h6>
                                <p class="mb-1 small text-muted text-truncate">${art.nombre_proveedor || 'Sin proveedor'}</p>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-precio">$${parseFloat(art.precio_costo).toFixed(2)}</span>
                                    <span class="badge ${art.estado === 'activo' ? 'bg-success' : 'bg-danger'}" style="font-size:0.6rem">
                                        ${art.estado.toUpperCase()}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedorCards.insertAdjacentHTML('beforeend', card);
        });
    });
}

function nuevoArticulo() {
    formArticulo.reset();
    document.getElementById('id_articulo').value = '';
    document.getElementById('img_preview').src = '/Taller/img/default-part.png';
    document.getElementById('tituloModal').innerText = 'Registrar Nuevo Artículo';
    modalArticulo.show();
}

function editarArticulo(id) {
    fetch(`Back_Articulo.php?action=obtener&id=${id}`)
    .then(res => res.json())
    .then(res => {
        const art = res.data;
        document.getElementById('id_articulo').value = art.id_articulo;
        document.getElementById('nombre').value = art.nombre;
        document.getElementById('num_serie').value = art.num_serie;
        document.getElementById('precio_costo').value = art.precio_costo;
        document.getElementById('id_proveedor').value = art.id_proveedor;
        document.getElementById('estado').value = art.estado;
        document.getElementById('img_preview').src = art.imagen ? art.imagen : '/Taller/img/default-part.png';
        
        document.getElementById('tituloModal').innerText = 'Editando: ' + art.nombre;
        modalArticulo.show();
    });
}

// Envío de formulario
formArticulo.onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('Back_Articulo.php?action=guardar', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            alert(res.message);
            modalArticulo.hide();
            listarArticulos();
        } else {
            alert("Error: " + res.message);
        }
    });
}