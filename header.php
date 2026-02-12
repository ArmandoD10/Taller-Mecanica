<?php
date_default_timezone_set('America/Santo_Domingo');
?>

<link rel="stylesheet" href="/Taller/Taller-Mecanica/estilos.css">

<div class="header">

    <div class="header-left">
        <div id="fechaHora"></div>
    </div>

    <div class="header-right">
        <img src="img/user.png" class="user-img">
    </div>

</div>

<script>
function actualizarFechaHora() {
    const ahora = new Date();

    const opciones = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };

    const fecha = ahora.toLocaleDateString('es-ES', opciones);
    const hora = ahora.toLocaleTimeString('es-ES');

    document.getElementById("fechaHora").innerHTML =
        fecha.charAt(0).toUpperCase() + fecha.slice(1) + " | " + hora;
}

setInterval(actualizarFechaHora, 1000);
actualizarFechaHora();
</script>
