document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // ==========================================
    // AUTO-CAPITALIZAR EL NOMBRE DE LA MARCA
    // ==========================================
    const inputNombre = document.getElementById('nombre_marca');
    
    inputNombre.addEventListener('input', function(e) {
        let val = e.target.value;
        
        // Quita símbolos raros, pero permite letras y números (ej: "3M", "WD-40")
        val = val.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s-]/g, '');
        
        // Capitaliza la primera letra de cada palabra
        val = val.toLowerCase().replace(/\b[a-z0-9áéíóúñ]/g, function(letra) {
            return letra.toUpperCase();
        });
        
        e.target.value = val;
    });

    // ==========================================
    // GUARDADO DEL FORMULARIO
    // ==========================================
    document.getElementById("formMarca").addEventListener("submit", function(e) {
        e.preventDefault();
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_MarcaProducto.php?action=guardar", {
            method: "POST", 
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) { 
                cerrarModalUI('modalMarca'); 
                listar(); 
                alert(data.message); 
            } else { 
                alert(data.message); 
            }
        })
        .catch(error => {
            console.error("Error al guardar:", error);
            alert("Error de conexión con el servidor.");
        });
    });

    // ==========================================
    // EVENTOS PARA CERRAR EL MODAL
    // ==========================================
    const botonesCerrar = document.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
    
    botonesCerrar.forEach(btn => {
        btn.addEventListener('click', (e) => { 
            e.preventDefault(); 
            cerrarModalUI('modalMarca');
        });
    });
});

// ==========================================
// CRUD PRINCIPAL DE MARCAS
// ==========================================
function listar() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_MarcaProducto.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTabla");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(m => {
                let badgeEstado = m.estado === "activo" ? "text-success fw-bold" : "text-muted text-decoration-line-through";
                let correoText = m.correo ? m.correo : '<span class="text-muted fst-italic">N/A</span>';

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-primary">MAR-${m.id_marca_producto.toString().padStart(3, '0')}</td>
                    <td class="fw-bold fs-6">${m.nombre}</td>
                    <td>${m.pais_origen}</td>
                    <td>${correoText}</td>
                    <td class="${badgeEstado}">${m.estado.toUpperCase()}</td>
                    <td>
                        <button class="btn btn-warning btn-sm text-white me-1" onclick="editar(${m.id_marca_producto})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="eliminar(${m.id_marca_producto})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No hay marcas registradas.</td></tr>`;
        }
    })
    .catch(error => console.error("Error al listar marcas:", error));
}

function cargarDependencias() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_MarcaProducto.php?action=cargar_dependencias")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const selectPais = document.getElementById("id_pais");
            
            data.data.forEach(p => {
                selectPais.innerHTML += `<option value="${p.id_pais}">${p.nombre}</option>`;
            });
        }
    })
    .catch(error => console.error("Error al cargar países:", error));
}

function nuevaMarca() {
    document.getElementById("formMarca").reset();
    
    document.getElementById("id_marca_producto").value = "";
    document.getElementById("estado").value = "activo";
    
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Marca de Producto';
    
    abrirModalUI('modalMarca');
}

function editar(id) {
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-edit me-2"></i>Editar Marca';
    
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_MarcaProducto.php?action=obtener&id_marca_producto=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            
            document.getElementById("id_marca_producto").value = d.id_marca_producto;
            document.getElementById("nombre_marca").value = d.nombre;
            document.getElementById("id_pais").value = d.id_pais;
            document.getElementById("correo").value = d.correo;
            document.getElementById("estado").value = d.estado;
            
            abrirModalUI('modalMarca');
        }
    })
    .catch(error => console.error("Error al obtener datos de la marca:", error));
}

function eliminar(id) {
    if (confirm("¿Está seguro que desea eliminar esta marca del catálogo?")) {
        const f = new FormData(); 
        f.append("id_marca_producto", id);
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_MarcaProducto.php?action=eliminar", {
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
        .catch(error => console.error("Error al eliminar marca:", error));
    }
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
        
        throw new Error("Librerías de Bootstrap no detectadas");
        
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