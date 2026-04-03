let cacheSucursales = [];

document.addEventListener("DOMContentLoaded", () => {
    listarAlmacenes();
    cargarSucursales();

    // ==========================================
    // BUSCADOR DINÁMICO DE SUCURSALES
    // ==========================================
    const txtSuc = document.getElementById('txt_buscar_suc');
    const listaSuc = document.getElementById('lista_suc');
    const hiddenSuc = document.getElementById('id_sucursal');

    txtSuc.addEventListener('input', function() {
        const busca = this.value.toLowerCase().trim();
        listaSuc.innerHTML = '';
        if (busca.length < 1) { listaSuc.classList.add('d-none'); return; }
        
        const filtrados = cacheSucursales.filter(s => s.nombre.toLowerCase().includes(busca));
        if (filtrados.length > 0) {
            listaSuc.classList.remove('d-none');
            filtrados.forEach(s => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action py-1';
                li.style.cursor = 'pointer';
                li.textContent = s.nombre;
                li.onclick = () => {
                    txtSuc.value = s.nombre;
                    hiddenSuc.value = s.id_sucursal;
                    listaSuc.classList.add('d-none');
                };
                listaSuc.appendChild(li);
            });
        } else { listaSuc.classList.add('d-none'); }
    });

    document.addEventListener('click', (e) => {
        if (!txtSuc.contains(e.target)) listaSuc.classList.add('d-none');
    });

    // ==========================================
    // GUARDAR ALMACÉN
    // ==========================================
    document.getElementById("formAlmacen").addEventListener("submit", function(e) {
        e.preventDefault();
        if(hiddenSuc.value === "") { alert("Seleccione una Sucursal válida."); return; }

        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Almacen.php?action=guardar_almacen", {
            method: "POST", body: new FormData(this)
        }).then(res => res.json()).then(data => {
            if (data.success) { 
                alert(data.message);
                cerrarModalUI('modalAlmacen'); 
                listarAlmacenes(); 
            } else {
                // Aquí el backend nos avisa si el nombre está duplicado
                alert(data.message); 
            }
        });
    });

    // ==========================================
    // GUARDAR GÓNDOLA (MINI FORM)
    // ==========================================
    document.getElementById("formGondola").addEventListener("submit", function(e) {
        e.preventDefault();
        const id_almacen = document.getElementById("gondola_id_almacen").value;
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Almacen.php?action=guardar_gondola", {
            method: "POST", body: new FormData(this)
        }).then(res => res.json()).then(data => {
            if (data.success) {
                // Limpiar inputs para agregar la siguiente rápido
                document.getElementById("numero_gondola").value = ""; 
                document.getElementById("niveles_gondola").value = "1"; 
                
                listarGondolas(id_almacen); 
                listarAlmacenes(); 
            } else {
                // Aquí el backend nos avisa si el número de góndola está duplicado
                alert(data.message);
            }
        });
    });

    // Eventos de Cerrar
    document.querySelectorAll('[data-bs-dismiss="modal"], .btn-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            cerrarModalUI('modalAlmacen');
            cerrarModalUI('modalGondolas');
        });
    });
});

// --- LÓGICA DE ALMACENES ---
function listarAlmacenes() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Almacen.php?action=listar_almacenes")
    .then(res => res.json()).then(data => {
        const tbody = document.getElementById("cuerpoTablaAlmacen");
        tbody.innerHTML = "";
        if (data.success && data.data.length > 0) {
            data.data.forEach(a => {
                let estadoBadge = a.estado === 'activo' ? 'text-success fw-bold' : 'text-muted text-decoration-line-through';
                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold text-primary">ALM-${a.id_almacen.toString().padStart(3, '0')}</td>
                        <td class="fw-bold">${a.nombre}</td>
                        <td>${a.sucursal}</td>
                        <td><span class="badge bg-dark rounded-pill fs-6">${a.total_gondolas}</span></td>
                        <td class="${estadoBadge}">${a.estado.toUpperCase()}</td>
                        <td class="text-center">
                            <button class="btn btn-dark btn-sm me-1" onclick="abrirGestionGondolas(${a.id_almacen}, '${a.nombre}')" title="Gestionar Góndolas">
                                <i class="fas fa-layer-group"></i> Góndolas
                            </button>
                            <button class="btn btn-warning btn-sm text-white" onclick="editarAlmacen(${a.id_almacen})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="eliminarAlmacen(${a.id_almacen})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No hay almacenes registrados.</td></tr>`;
        }
    });
}

function cargarSucursales() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Almacen.php?action=cargar_sucursales")
    .then(res => res.json()).then(data => { if(data.success) cacheSucursales = data.data; });
}

function nuevoAlmacen() {
    document.getElementById("formAlmacen").reset();
    document.getElementById("id_almacen").value = "";
    document.getElementById("id_sucursal").value = "";
    document.getElementById("tituloModalAlmacen").innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Almacén';
    abrirModalUI('modalAlmacen');
}

function editarAlmacen(id) {
    document.getElementById("tituloModalAlmacen").innerHTML = '<i class="fas fa-edit me-2"></i>Editar Almacén';
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Almacen.php?action=obtener_almacen&id_almacen=${id}`)
    .then(res => res.json()).then(data => {
        if (data.success) {
            const d = data.data;
            document.getElementById("id_almacen").value = d.id_almacen;
            document.getElementById("nombre_almacen").value = d.nombre;
            document.getElementById("id_sucursal").value = d.id_sucursal;
            document.getElementById("estado_almacen").value = d.estado;

            const suc = cacheSucursales.find(s => s.id_sucursal == d.id_sucursal);
            document.getElementById("txt_buscar_suc").value = suc ? suc.nombre : "";
            
            abrirModalUI('modalAlmacen');
        }
    });
}

function eliminarAlmacen(id) {
    if (confirm("Al eliminar este almacén, se eliminarán todas sus góndolas. ¿Desea continuar?")) {
        const f = new FormData(); f.append("id_almacen", id);
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Almacen.php?action=eliminar_almacen", {
            method: "POST", body: f
        }).then(res => res.json()).then(data => {
            alert(data.message); if(data.success) listarAlmacenes();
        });
    }
}

// --- LÓGICA DE GÓNDOLAS ---
function abrirGestionGondolas(id_almacen, nombre_almacen) {
    document.getElementById("gondola_id_almacen").value = id_almacen;
    document.getElementById("tituloAlmacenGondola").textContent = nombre_almacen.toUpperCase();
    document.getElementById("formGondola").reset(); 
    
    listarGondolas(id_almacen);
    abrirModalUI('modalGondolas');
}

function listarGondolas(id_almacen) {
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Almacen.php?action=listar_gondolas&id_almacen=${id_almacen}`)
    .then(res => res.json()).then(data => {
        const tbody = document.getElementById("cuerpoTablaGondolas");
        tbody.innerHTML = "";
        if (data.success && data.data.length > 0) {
            data.data.forEach(g => {
                let colorEstado = g.estado === 'activo' ? 'text-success' : 'text-danger';
                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold text-muted">GND-${g.id_gondola}</td>
                        <td class="fs-5 fw-bold text-dark">${g.numero}</td>
                        <td class="fw-bold text-primary">${g.niveles} Tramos</td>
                        <td class="fw-bold ${colorEstado}">${g.estado.toUpperCase()}</td>
                        <td>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarGondola(${g.id_gondola}, ${id_almacen})" title="Eliminar Góndola">
                                <i class="fas fa-trash"></i> Quitar
                            </button>
                        </td>
                    </tr>`;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-muted py-3">Este almacén aún no tiene góndolas. Agregue una arriba.</td></tr>`;
        }
    });
}

function eliminarGondola(id_gondola, id_almacen) {
    if(confirm("¿Seguro de quitar esta góndola? No podrá usarla para guardar repuestos.")) {
        const f = new FormData(); f.append("id_gondola", id_gondola);
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Almacen.php?action=eliminar_gondola", {
            method: "POST", body: f
        }).then(res => res.json()).then(data => {
            if(data.success) {
                listarGondolas(id_almacen);
                listarAlmacenes(); 
            }
        });
    }
}

// ==========================================
// FUNCIONES DE MODAL CON PARÁMETRO DE ID
// ==========================================
function abrirModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) { $('#'+idModal).modal('show'); return; }
        if (typeof bootstrap !== 'undefined') {
            let modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            modal.show(); return;
        }
        throw new Error("Frameworks no detectados");
    } catch (e) {
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        document.body.classList.add('modal-open');
        if(!document.getElementById('fondo-oscuro-modal')){
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'fondo-oscuro-modal';
            document.body.appendChild(backdrop);
        }
    }
}

function cerrarModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    document.body.classList.remove('modal-open');
    const backdrop = document.getElementById('fondo-oscuro-modal');
    if(backdrop) backdrop.remove();
    if (typeof $ !== 'undefined' && $.fn.modal) { $('#'+idModal).modal('hide'); }
}

document.addEventListener('DOMContentLoaded', () => {
    
    const inputField = document.getElementById('nombre_almacen');

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
});