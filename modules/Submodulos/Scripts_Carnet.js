document.addEventListener("DOMContentLoaded", () => {
    // Reutilizamos el mismo Archivo_Perfil.php que ya creamos
    fetch("/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Carnet.php")
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            const d = res.data;
            // Solo llenamos los 3 campos solicitados
            document.getElementById("carnet_nombre").innerText = `${d.emp_nombre} ${d.apellido_p}`;
            document.getElementById("carnet_puesto").innerText = d.puesto;
            
            // Suponiendo que el ID viene del servidor (necesitarás traer el id_empleado en el SQL)
            // Si el query anterior no lo tenía, asegúrate de agregarlo como 'id_empleado'
            document.getElementById("carnet_id").innerText = String(d.id_empleado).padStart(4, '0');
        }
    });
});

/**
 * Captura el div del carnet y lo descarga como imagen
 */
function imprimirCarnet() {
    const areaCarnet = document.getElementById('carnet_para_captura');
    
    html2canvas(areaCarnet, {
        scale: 3, // Mayor calidad
        backgroundColor: null,
        logging: false
    }).then(canvas => {
        // Creamos un link temporal para descargar la imagen
        const link = document.createElement('a');
        link.download = `Carnet_${document.getElementById("carnet_id").innerText}.png`;
        link.href = canvas.toDataURL("image/png");
        link.click();
        
        // Opcional: Abrir la imagen en una pestaña nueva para imprimir
        const imgWin = window.open('', '_blank');
        imgWin.document.write(`<img src="${link.href}" style="width:100%;">`);
        imgWin.document.title = "Imprimir Carnet";
        setTimeout(() => imgWin.print(), 500);
    });
}