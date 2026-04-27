document.addEventListener("DOMContentLoaded", () => {
    cargarDatosUsuario();

    document.getElementById("form_cambio_pass").addEventListener("submit", function(e) {
        e.preventDefault();
        actualizarPassword();
    });
});

function cargarDatosUsuario() {
    fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Seguridad.php?action=cargar_datos")
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            document.getElementById("txt_id_user").innerText = res.data.id_usuario;
            document.getElementById("txt_username").innerText = res.data.username;
            document.getElementById("txt_correo").innerText = res.data.correo_org;
        }
    });
}

function actualizarPassword() {
    const actual = document.getElementById("pass_actual").value;
    const nueva = document.getElementById("pass_nueva").value;
    const confirma = document.getElementById("pass_confirma").value;

    // Validación de coincidencia
    if (nueva !== confirma) {
        Swal.fire({
            title: 'Las contraseñas no coinciden',
            text: 'Asegúrese de que la nueva contraseña y su confirmación sean idénticas.',
            icon: 'warning',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    // Validación de longitud
    if (nueva.length < 4) {
        Swal.fire({
            title: 'Contraseña muy corta',
            text: 'Por razones de seguridad, utilice al menos 4 caracteres.',
            icon: 'info'
        });
        return;
    }

    // Indicador de "Procesando" para mayor seguridad
    Swal.fire({
        title: 'Actualizando contraseña...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const fd = new FormData();
    fd.append('pass_actual', actual);
    fd.append('pass_nueva', nueva);

    fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Seguridad.php?action=actualizar_password", {
        method: "POST",
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        Swal.close(); // Cerramos el cargando

        if(res.success) {
            Swal.fire({
                title: '¡Cambio Exitoso!',
                text: res.message,
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                location.reload();
            });
        } else {
            // Error de contraseña actual incorrecta u otro fallo del servidor
            Swal.fire('Error', res.message, 'error');
        }
    })
    .catch(error => {
        Swal.close();
        console.error("Error:", error);
        Swal.fire('Error Crítico', 'No se pudo conectar con el servidor de seguridad.', 'error');
    });
}