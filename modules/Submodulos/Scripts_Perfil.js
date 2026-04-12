document.addEventListener("DOMContentLoaded", () => {
    fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Perfil.php")
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            const d = res.data;
            
            // Datos de Identificación
            document.getElementById("perfil_nombre_completo").innerText = `${d.emp_nombre} ${d.apellido_p} ${d.apellido_m || ''}`;
            document.getElementById("perfil_puesto").innerText = d.puesto;
            
            // Datos de Usuario y Acceso
            document.getElementById("perfil_username").innerText = `@${d.username}`;
            document.getElementById("perfil_nivel").innerText = d.nivel_acceso;
            
            // Datos de Ubicación Laboral
            document.getElementById("perfil_dep").innerText = d.departamento;
            document.getElementById("perfil_sucursal").innerText = d.sucursal;
            
            // Datos de Contacto y Personales
            document.getElementById("perfil_correo_org").innerText = d.correo_org;
            document.getElementById("perfil_tel").innerText = d.telefono || 'Sin registrar';
            document.getElementById("perfil_cedula").innerText = d.cedula;
            
            // Dirección Completa (Calle + Ciudad)
            document.getElementById("perfil_dir").innerText = `${d.calle}, ${d.ciudad}`;
            
        } else {
            alert("Error al cargar perfil: " + res.message);
        }
    })
    .catch(err => {
        console.error("Error en la petición:", err);
    });
});