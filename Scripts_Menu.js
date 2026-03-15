console.log("✅ Scripts_Menu.js cargado correctamente");
document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.modulo-btn');
    console.log("🔎 Botones encontrados:", buttons.length);

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            // No activar acordeón para el botón de cerrar sesión
            console.log("🖱️ Click en:", this.innerText);
            if (this.id === 'logoutBtn') return;

            const modulo = this.parentElement;
            const isActive = modulo.classList.contains('active');

            // Cierra todos los módulos para efecto de acordeón limpio
            document.querySelectorAll('.modulo').forEach(m => m.classList.remove('active'));

            // Si no estaba activo, lo abre
            if (!isActive) {
                modulo.classList.add('active');
            }
        });
    });

    // Confirmación para cerrar sesión
    const logout = document.getElementById('logoutBtn');
    if (logout) {
        logout.addEventListener('click', (e) => {
            if (!confirm("¿Está seguro de que desea salir del sistema?")) {
                e.preventDefault();
            }
        });
    }
});

// Función para actualizar la fecha y hora en el menú
function actualizarFechaHora() {

    const elemento = document.getElementById("fechaHora");

    if(!elemento) return; // si no existe el elemento, no hace nada

    const ahora = new Date();

    const opciones = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };

    const fecha = ahora.toLocaleDateString('es-ES', opciones);
    const hora = ahora.toLocaleTimeString('es-ES');

    elemento.innerHTML =
        fecha.charAt(0).toUpperCase() + fecha.slice(1) + " | " + hora;
}

setInterval(actualizarFechaHora, 1000);
actualizarFechaHora();

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

document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('btnConfig');
    const menu = document.getElementById('menuConfig');

    if (btn && menu) {
        // Abrir/Cerrar al hacer clic
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('show');
        });

        // Cerrar si haces clic fuera del menú
        document.addEventListener('click', function(e) {
            if (!menu.contains(e.target) && e.target !== btn) {
                menu.classList.remove('show');
            }
        });
    }
});