// Función para limpiar el formulario de registro de clientes
function limpiarFormulario() {
    const campos = document.querySelectorAll("input, textarea, select");

    campos.forEach(campo => {
        switch(campo.type) {
            case "checkbox":
            case "radio":
                campo.checked = false;
                break;
            default:
                campo.value = "";
        }
    });
}