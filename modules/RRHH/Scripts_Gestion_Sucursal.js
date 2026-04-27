let sucursales = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarCheckboxesDeptos();
    cargarTablaSucursales();
});

function cargarCheckboxesDeptos() {
    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Sucursal.php?action=departamentos")
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById("contenedorDeptos");
        container.innerHTML = "";
        data.forEach(d => {
            container.innerHTML += `
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input class="form-check-input chk-depto" type="checkbox" value="${d.id_departamento}" id="dep_${d.id_departamento}">
                        <label class="form-check-label" for="dep_${d.id_departamento}">${d.nombre}</label>
                    </div>
                </div>`;
        });
    });
}

function cargarTablaSucursales(page = 1) {
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Sucursal.php?action=cargar&page=${page}&limit=6`)
    .then(res => res.json())
    .then(data => {
        sucursales = data.data;
        const tbody = document.getElementById("cuerpo-sucursales");
        tbody.innerHTML = "";
        sucursales.forEach(s => {
            tbody.innerHTML += `
                <tr>
                    <td>${s.id_sucursal}</td>
                    <td>${s.nombre}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editarSucursal(${s.id_sucursal})">Editar</button>
                    </td>
                </tr>`;
        });
        // Aquí llamarías a tu función de generarPaginacion (puedes reusar la que ya tienes)
    });
}

function guardarSucursal() {
    const id = document.getElementById("id_sucursal").value;
    const nombre = document.getElementById("nombre_sucursal").value;
    
    if(!nombre) {
        Swal.fire('Atención', 'El nombre de la sucursal es obligatorio.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append("id_sucursal", id);
    formData.append("nombre", nombre);

    const checks = document.querySelectorAll(".chk-depto:checked");
    checks.forEach(c => formData.append("deptos[]", c.value));

    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Sucursal.php?action=guardar", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            Swal.fire({
                title: '¡Actualizado!',
                text: 'La sucursal y sus departamentos se han guardado correctamente.',
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                limpiar();
                cargarTablaSucursales();
            });
        } else {
            Swal.fire('Error', 'No se pudieron guardar los cambios.', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
    });
}

function editarSucursal(id) {
    const suc = sucursales.find(s => s.id_sucursal == id);
    if(!suc) return;

    document.getElementById("id_sucursal").value = suc.id_sucursal;
    document.getElementById("nombre_sucursal").value = suc.nombre;
    document.getElementById("btnGuardar").textContent = "Modificar Sucursal";

    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Sucursal.php?action=obtener_asignados&id_sucursal=${id}`)
    .then(res => res.json())
    .then(asignados => {
        document.querySelectorAll(".chk-depto").forEach(c => c.checked = false);
        asignados.forEach(a => {
            const check = document.getElementById(`dep_${a.id_departamento}`);
            if(check && a.estado === 'activo') check.checked = true;
        });

        // Notificación Toast de carga exitosa
        Swal.fire({
            icon: 'info',
            title: 'Asignaciones cargadas',
            toast: true,
            position: 'top-end',
            timer: 1500,
            showConfirmButton: false
        });
    });
}

function limpiar() {
    document.getElementById("formSucursal").reset();
    document.getElementById("id_sucursal").value = "";
    document.getElementById("btnGuardar").textContent = "Guardar Sucursal";
    document.querySelectorAll(".chk-depto").forEach(c => c.checked = false);
}

