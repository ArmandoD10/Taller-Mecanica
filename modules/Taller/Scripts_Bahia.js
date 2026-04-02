let sucursalesCache = []; // Guardamos las sucursales aquí para buscar rápido

document.addEventListener("DOMContentLoaded", () => {
    cargarBahias();
    cargarSucursales();

    // Evento para el buscador dinámico de Sucursales
    const txtBuscar = document.getElementById('txt_buscar_sucursal');
    const listaResultados = document.getElementById('lista_sucursales_busqueda');
    const inputIdSucursal = document.getElementById('id_sucursal');

    txtBuscar.addEventListener('input', function() {
        const busqueda = this.value.toLowerCase().trim();
        listaResultados.innerHTML = '';

        if (busqueda.length < 1) {
            listaResultados.classList.add('d-none');
            return;
        }

        const filtrados = sucursalesCache.filter(s => s.nombre.toLowerCase().includes(busqueda));

        if (filtrados.length > 0) {
            listaResultados.classList.remove('d-none');
            filtrados.forEach(s => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action py-1';
                li.style.cursor = 'pointer';
                li.textContent = s.nombre;
                li.onclick = () => {
                    txtBuscar.value = s.nombre;
                    inputIdSucursal.value = s.id_sucursal;
                    listaResultados.classList.add('d-none');
                };
                listaResultados.appendChild(li);
            });
        } else {
            listaResultados.classList.add('d-none');
        }
    });

    // Ocultar lista al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!txtBuscar.contains(e.target)) listaResultados.classList.add('d-none');
    });

    // Guardar formulario
    document.getElementById("formBahia").addEventListener("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Bahia.php?action=guardar", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                cerrarModalUI();
                cargarBahias();
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

    document.querySelectorAll('#modalBahia [data-bs-dismiss="modal"], #modalBahia .btn-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            cerrarModalUI();
        });
    });
});

function cargarBahias() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Bahia.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaBahias");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(bahia => {
                let badgeOcupacion = "bg-success";
                if (bahia.estado_bahia === "Ocupada") badgeOcupacion = "bg-danger";
                else if (bahia.estado_bahia === "Mantenimiento") badgeOcupacion = "bg-warning text-dark";

                let badgeEstadoReg = bahia.estado === "activo" ? "text-success fw-bold" : "text-muted text-decoration-line-through";

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold text-primary">BH-${bahia.id_bahia.toString().padStart(3, '0')}</td>
                    <td>${bahia.sucursal}</td>
                    <td>${bahia.descripcion}</td>
                    <td><span class="badge ${badgeOcupacion} fs-6">${bahia.estado_bahia}</span></td>
                    <td class="${badgeEstadoReg}">${bahia.estado.toUpperCase()}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm text-white" onclick="editarBahia(${bahia.id_bahia})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="eliminarBahia(${bahia.id_bahia})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No hay bahías registradas.</td></tr>`;
        }
    })
    .catch(error => console.error("Error al cargar bahías:", error));
}

function cargarSucursales() {
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Bahia.php?action=cargar_sucursales")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            sucursalesCache = data.data; // Guardamos en memoria para el buscador
        }
    });
}

function abrirModalNuevo() {
    document.getElementById("formBahia").reset();
    document.getElementById("id_bahia").value = "";
    document.getElementById("id_sucursal").value = "";
    document.getElementById("estado").value = "activo"; // Valor por defecto
    document.getElementById("modalTitulo").innerHTML = '<i class="fas fa-plus me-2"></i>Nueva Bahía';
    
    abrirModalUI();
}

function editarBahia(id) {
    document.getElementById("modalTitulo").innerHTML = '<i class="fas fa-edit me-2"></i>Editar Bahía';
    
    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Bahia.php?action=obtener&id_bahia=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            document.getElementById("id_bahia").value = d.id_bahia;
            document.getElementById("id_sucursal").value = d.id_sucursal;
            document.getElementById("descripcion").value = d.descripcion;
            document.getElementById("estado_bahia").value = d.estado_bahia;
            document.getElementById("estado").value = d.estado; // Cargamos el estado

            // Colocar el nombre de la sucursal en el buscador para que no se vea vacío
            const suc = sucursalesCache.find(s => s.id_sucursal == d.id_sucursal);
            document.getElementById("txt_buscar_sucursal").value = suc ? suc.nombre : '';
            
            abrirModalUI();
        }
    });
}

function eliminarBahia(id) {
    if (confirm("¿Está seguro que desea eliminar (ocultar) esta bahía?")) {
        const formData = new FormData();
        formData.append("id_bahia", id);

        fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Bahia.php?action=eliminar", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) cargarBahias();
        });
    }
}

function abrirModalUI() {
    const modalElement = document.getElementById('modalBahia');
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#modalBahia').modal('show');
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
    const modalElement = document.getElementById('modalBahia');
    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    document.body.classList.remove('modal-open');
    
    const backdrop = document.getElementById('fondo-oscuro-modal');
    if(backdrop) backdrop.remove();
    
    if (typeof $ !== 'undefined' && $.fn.modal) {
        $('#modalBahia').modal('hide');
    }
}