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

    elemento.innerHTML = fecha.charAt(0).toUpperCase() + fecha.slice(1) + " | " + hora;
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

// Manejo del menú de notificaciones
const btnNotif = document.getElementById('btnNotificaciones');
const menuNotif = document.getElementById('menu-notificaciones');

if(btnNotif) {
    btnNotif.addEventListener('click', (e) => {
        e.stopPropagation();
        menuNotif.classList.toggle('d-none');
    });
}

// Cerrar si hace clic fuera
document.addEventListener('click', () => {
    if(menuNotif) menuNotif.classList.add('d-none');
});

if(menuNotif){
    menuNotif.addEventListener('click', (e) => e.stopPropagation());
}


document.addEventListener('DOMContentLoaded', () => {
    verificarNotificaciones();
    // Revisar cada 30 segundos automáticamente
    setInterval(verificarNotificaciones, 30000);
});

function verificarNotificaciones() {
    fetch('/Taller/Taller-Mecanica/modules/Inventario/Archivo_Notificaciones.php')
    .then(res => res.json())
    .then(res => {
        const contador = document.getElementById('contador-notificaciones');
        const contenedor = document.getElementById('contenedor-items-notificacion');
        
        if(!contador || !contenedor) return;

        if (res.total > 0) {
            contador.textContent = res.total;
            contador.classList.remove('d-none');
            
            let html = '';
            res.data.forEach(n => {
                // Formatear la fecha
                const fecha = new Date(n.fecha_solicitud);
                const fechaLegible = fecha.toLocaleDateString() + ' ' + fecha.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

                html += `
                <div class="notif-item">
                    <div class="notif-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="notif-content">
                        <p>Nueva solicitud de <b>${n.producto}</b></p>
                        <p class="small text-muted">Sucursal: ${n.sucursal_destino} (Cant: ${n.cantidad})</p>
                        <span class="notif-time">${fechaLegible}</span>
                    </div>
                </div>`;
            });
            contenedor.innerHTML = html;
        } else {
            contador.classList.add('d-none');
            contenedor.innerHTML = '<li class="p-3 text-center text-muted small">No hay pedidos pendientes</li>';
        }
    })
    .catch(err => console.error("Error en notificaciones:", err));
}

// ==========================================
// SISTEMA DE PERSONALIZACIÓN DE WIDGETS
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    const switches = document.querySelectorAll('.switch-widget');
    if(switches.length === 0) return; // Si no hay switches (no es admin), no hace nada

    // 1. Cargar preferencias guardadas en LocalStorage
    switches.forEach(sw => {
        const targetId = sw.getAttribute('data-target');
        const guardado = localStorage.getItem('widget_' + targetId);
        const widgetDOM = document.getElementById(targetId);
        
        // Si hay una preferencia guardada y dice 'oculto', lo apagamos
        if(guardado === 'oculto') {
            sw.checked = false;
            if(widgetDOM) widgetDOM.classList.add('widget-hidden');
        } else {
            // Por defecto vienen encendidos
            sw.checked = true; 
            if(widgetDOM) widgetDOM.classList.remove('widget-hidden');
        }

        // 2. Escuchar cambios en los switches para guardar y ocultar en tiempo real
        sw.addEventListener('change', function() {
            const currentTarget = this.getAttribute('data-target');
            const targetDOM = document.getElementById(currentTarget);
            
            if(this.checked) {
                if(targetDOM) targetDOM.classList.remove('widget-hidden');
                localStorage.setItem('widget_' + currentTarget, 'visible');
            } else {
                if(targetDOM) targetDOM.classList.add('widget-hidden');
                localStorage.setItem('widget_' + currentTarget, 'oculto');
            }
            
            // Si el usuario oculta la gráfica pero la tabla está activa, 
            // la tabla de vehículos se expande.
            ajustarAnchoTablaVehiculos();
        });
    });

    // Validar anchos en la carga inicial
    ajustarAnchoTablaVehiculos();
});

// Función auxiliar para que la tabla principal ocupe todo el ancho si se oculta la gráfica
function ajustarAnchoTablaVehiculos() {
    const tabla = document.getElementById('widget-tabla-vehiculos');
    const graficoSwitch = document.querySelector('[data-target="widget-grafico-ingresos"]');
    
    if(tabla && graficoSwitch) {
        if(!graficoSwitch.checked) {
            // Si el gráfico está oculto, la tabla ocupa 12 columnas
            tabla.classList.remove('col-xl-8');
            tabla.classList.add('col-12');
        } else {
            // Si el gráfico está visible, la tabla vuelve a sus 8 columnas
            tabla.classList.remove('col-12');
            tabla.classList.add('col-xl-8');
        }
    }
}