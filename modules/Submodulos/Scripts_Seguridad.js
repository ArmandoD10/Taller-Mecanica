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

    if (nueva !== confirma) {
        alert("La nueva contraseña y la confirmación no coinciden.");
        return;
    }

    if (nueva.length < 4) {
        alert("La contraseña es muy corta.");
        return;
    }

    const fd = new FormData();
    fd.append('pass_actual', actual);
    fd.append('pass_nueva', nueva);

    fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Seguridad.php?action=actualizar_password", {
        method: "POST",
        body: fd
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);
        if(res.success) {
            location.reload();
        }
    });
}