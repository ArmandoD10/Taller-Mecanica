let cacheSucursales = [];
let cacheCategorias = [];

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // ==========================================
    // 1. BUSCADOR DINÁMICO DE CATEGORÍAS
    // ==========================================
    const txtCat = document.getElementById('txt_buscar_cat');
    const listaCat = document.getElementById('lista_cat');
    const hiddenCat = document.getElementById('id_categoria');

    txtCat.addEventListener('input', function() {
        const busca = this.value.toLowerCase().trim();
        listaCat.innerHTML = '';
        if (busca.length < 1) { listaCat.classList.add('d-none'); return; }
        
        const filtrados = cacheCategorias.filter(c => c.nombre.toLowerCase().includes(busca));
        if (filtrados.length > 0) {
            listaCat.classList.remove('d-none');
            filtrados.forEach(c => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action py-1';
                li.style.cursor = 'pointer';
                li.textContent = c.nombre;
                li.onclick = () => {
                    txtCat.value = c.nombre;
                    hiddenCat.value = c.id_categoria;
                    listaCat.classList.add('d-none');
                };
                listaCat.appendChild(li);
            });
        } else { listaCat.classList.add('d-none'); }
    });

    // ==========================================
    // 2. BUSCADOR DINÁMICO DE SUCURSALES
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
        if (!txtCat.contains(e.target)) listaCat.classList.add('d-none');
        if (!txtSuc.contains(e.target)) listaSuc.classList.add('d-none');
    });

    // ==========================================
    // 3. GUARDAR FORMULARIO
    // ==========================================
    document.getElementById("formMaquinaria").addEventListener("submit", function(e) {
        e.preventDefault();
        
        if(hiddenCat.value === "" || hiddenSuc.value === "") {
            alert("Por favor, seleccione una Categoría y una Sucursal válida de la lista.");
            return;
        }

        const formData = new FormData(this);

        fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=guardar", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                cerrarModalUI();
                listar();
                alert(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error("Error al guardar:", error);
            alert("Error de conexión al intentar guardar.");
        });
    });

    document.querySelectorAll('#modalMaquinaria [data-bs-dismiss="modal"], #modalMaquinaria .btn-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            cerrarModalUI();
        });
    });
});

function listar() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTabla");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(m => {
                let badgeCondicion = "bg-primary";
                if (m.estado_maquina === "Desgastado") badgeCondicion = "bg-warning text-dark";
                else if (m.estado_maquina === "Usado") badgeCondicion = "bg-info text-dark";

                let badgeEstadoReg = m.estado === "activo" ? "text-success fw-bold" : "text-muted text-decoration-line-through";

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold">${m.nombre}</td>
                    <td>${m.categoria || '<span class="text-muted">N/A</span>'}</td>
                    <td>${m.sucursal}</td>
                    <td>${m.fecha_ingreso || '-'}</td>
                    <td><span class="badge ${badgeCondicion} fs-6">${m.estado_maquina}</span></td>
                    <td class="${badgeEstadoReg}">${m.estado.toUpperCase()}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm text-white" onclick="editar(${m.id_maquinaria})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="eliminar(${m.id_maquinaria})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No hay recursos registrados.</td></tr>`;
        }
    })
    .catch(error => console.error("Error al cargar listado:", error));
}

function cargarDependencias() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=cargar_dependencias")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            cacheSucursales = data.data.sucursales;
            cacheCategorias = data.data.categorias;
        }
    });
}

function nuevoRecurso() {
    document.getElementById("formMaquinaria").reset();
    document.getElementById("id_maquinaria").value = "";
    document.getElementById("id_categoria").value = "";
    document.getElementById("id_sucursal").value = "";
    document.getElementById("estado").value = "activo";
    
    // Por defecto colocamos la fecha de hoy para agilizar
    document.getElementById("fecha_ingreso").value = new Date().toISOString().split('T')[0];
    
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Recurso';
    abrirModalUI();
}

function editar(id) {
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-edit me-2"></i>Editar Recurso';
    
    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=obtener&id_maquinaria=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            document.getElementById("id_maquinaria").value = d.id_maquinaria;
            document.getElementById("nombre").value = d.nombre;
            document.getElementById("id_categoria").value = d.id_categoria;
            document.getElementById("id_sucursal").value = d.id_sucursal;
            document.getElementById("funcionamiento").value = d.funcionamiento;
            document.getElementById("estado_maquina").value = d.estado_maquina;
            document.getElementById("fecha_ingreso").value = d.fecha_ingreso_formato;
            document.getElementById("estado").value = d.estado;

            // Rellenar buscadores
            const cat = cacheCategorias.find(c => c.id_categoria == d.id_categoria);
            const suc = cacheSucursales.find(s => s.id_sucursal == d.id_sucursal);
            document.getElementById("txt_buscar_cat").value = cat ? cat.nombre : "";
            document.getElementById("txt_buscar_suc").value = suc ? suc.nombre : "";
            
            abrirModalUI();
        } else {
            alert("Error al cargar los datos.");
        }
    });
}

function eliminar(id) {
    if (confirm("¿Está seguro que desea eliminar este recurso?")) {
        const f = new FormData(); 
        f.append("id_maquinaria", id);
        
        fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Maquinaria.php?action=eliminar", {
            method: "POST", 
            body: f
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) listar();
        });
    }
}

// ==========================================
// FUNCIONES DEL MODAL (A PRUEBA DE FALLOS)
// ==========================================
function abrirModalUI() {
    const modalElement = document.getElementById('modalMaquinaria');
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#modalMaquinaria').modal('show');
            return;
        }
        if (typeof bootstrap !== 'undefined') {
            let modal = bootstrap.Modal.getInstance(modalElement);
            if (!modal) modal = new bootstrap.Modal(modalElement);
            modal.show();
            return;
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

function cerrarModalUI() {
    const modalElement = document.getElementById('modalMaquinaria');
    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    document.body.classList.remove('modal-open');
    
    const backdrop = document.getElementById('fondo-oscuro-modal');
    if(backdrop) backdrop.remove();
    
    if (typeof $ !== 'undefined' && $.fn.modal) {
        $('#modalMaquinaria').modal('hide');
    }
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
});