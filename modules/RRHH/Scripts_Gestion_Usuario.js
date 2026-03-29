console.log("✅ Script Empleado_Usuario cargado");

let idEmpleado = null;

// 🔍 BUSCAR EMPLEADO
document.getElementById("btnBuscarEmpleado").addEventListener("click", buscarEmpleado);

document.getElementById("buscarEmpleado").addEventListener("keypress", function(e){
    if(e.key === "Enter"){
        e.preventDefault();
        buscarEmpleado();
    }
});

function buscarEmpleado(){
    const filtro = document.getElementById("buscarEmpleado").value.trim();

    if(filtro === ""){
        alert("Escribe algo para buscar");
        return;
    }

    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Usuario.php?action=buscarEmpleado&filtro=${filtro}`)
    .then(res => res.json())
    .then(data => {

        if(!data){
            alert("Empleado no encontrado");
            return;
        }

        // Guardamos ID
        idEmpleado = data.id_empleado;

        // Mostrar card
        document.getElementById("cardEmpleado").classList.remove("d-none");
        document.getElementById("cardUsuarios").classList.remove("d-none");
        document.getElementById("cardAsignar").classList.remove("d-none");

        document.getElementById("emp_id").textContent = data.id_empleado;
        document.getElementById("emp_nombre").textContent = data.nombre;
        console.log("DESPUES DE ASIGNAR:", 
            document.getElementById("emp_id").textContent,
            document.getElementById("emp_nombre").textContent
        );

        // Cargar datos relacionados
        cargarUsuariosEmpleado();
        cargarUsuariosDisponibles();

    })
    .catch(err => console.error(err));
}

// 📋 USUARIOS ASIGNADOS
function cargarUsuariosEmpleado(){
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Usuario.php?action=usuariosEmpleado&id_empleado=${idEmpleado}`)
    .then(res => res.json())
    .then(data => {

        const tbody = document.getElementById("tablaUsuarios");
        tbody.innerHTML = "";

        if(data.length === 0){
            tbody.innerHTML = `<tr><td colspan="5" class="text-center">No hay usuarios asignados</td></tr>`;
            return;
        }

        data.forEach(reg => {
            const fila = document.createElement("tr");

            // 🔥 BOTÓN DINÁMICO
            let btnAccion = "";

            if(reg.estado === "activo"){
                btnAccion = `
                    <button class="btn btn-danger btn-sm" onclick="quitarUsuario(${reg.id_usuario})">
                        Quitar
                    </button>
                `;
            }else{
                btnAccion = `
                    <button class="btn btn-success btn-sm" onclick="activarUsuario(${reg.id_usuario})">
                        Activar
                    </button>
                `;
            }

            fila.innerHTML = `
                <td>${reg.id_usuario}</td>
                <td>${reg.username}</td>
                <td>${reg.nivel}</td>
                <td>
                    <span class="badge rounded-pill bg-success">
                        ${reg.estado}
                    </span>
                </td>
                <td>
                    ${btnAccion}
                </td>
            `;

            tbody.appendChild(fila);
        });

    });
}

function activarUsuario(id_usuario){

    fetch('/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Usuario.php?action=activar', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id_usuario=${id_usuario}&id_empleado=${idEmpleado}`
    })
    .then(res => res.json())
    .then(data => {

        if(data.success){
            cargarUsuariosEmpleado();
            cargarUsuariosDisponibles();
        }else{
            alert(data.msg); // 🔥 AQUÍ EL CAMBIO
        }

    })
    .catch(err => console.error(err));
}

// 📥 USUARIOS DISPONIBLES
function cargarUsuariosDisponibles(){
    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Usuario.php?action=usuariosDisponibles&id_empleado=${idEmpleado}`)
    .then(res => res.json())
    .then(data => {

            
        const combo = document.getElementById("comboUsuarios");
        combo.innerHTML = '<option value="">Selecciona un usuario</option>';

        data.forEach(u => {
            const option = document.createElement("option");
            option.value = u.id_usuario;
            option.textContent = u.username;
            combo.appendChild(option);
        });

    });
}

// 🔄 SWITCH ESTADO
document.getElementById("estadoSwitch").addEventListener("change", function(){
    document.getElementById("labelEstado").textContent = this.checked ? "Activo" : "Inactivo";
});

// ➕ ASIGNAR USUARIO
document.getElementById("btnAsignar").addEventListener("click", function(){

    const id_usuario = document.getElementById("comboUsuarios").value;
    const estado = document.getElementById("estadoSwitch").checked ? "activo" : "inactivo";

    if(!idEmpleado){
        alert("Debes buscar un empleado primero");
        return;
    }

    if(id_usuario === ""){
        alert("Selecciona un usuario");
        return;
    }

    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Usuario.php?action=asignar", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `id_usuario=${id_usuario}&id_empleado=${idEmpleado}&estado=${estado}`
    })
    .then(res => res.json())
    .then(data => {

        if(data.success){
            alert("Usuario asignado correctamente");

            cargarUsuariosEmpleado();
            cargarUsuariosDisponibles();

        } else {
            alert("Error al asignar");
        }

    })
    .catch(err => console.error(err));
});

// ❌ QUITAR USUARIO
window.quitarUsuario = function(id_usuario){

    if(!confirm("¿Deseas quitar este usuario?")) return;

    fetch("/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Usuario.php?action=quitar", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `id_usuario=${id_usuario}&id_empleado=${idEmpleado}`
    })
    .then(res => res.json())
    .then(data => {

        if(data.success){
            alert("Usuario desactivado");

            cargarUsuariosEmpleado();
            cargarUsuariosDisponibles();

        } else {
            alert("Error al quitar");
        }

    })
    .catch(err => console.error(err));
};

//Funcion para la primera letra mayuscula del buscador
document.getElementById("txtBuscar").addEventListener("input", function(){

    let valor = this.value;

    if(valor.length > 0){
        this.value = valor.charAt(0).toUpperCase() + valor.slice(1);
    }

});

//Funcion para limpiar todo.
function limpiarTodo(){
    location.reload();
}