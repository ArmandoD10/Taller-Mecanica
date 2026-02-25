document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.modulo-btn');

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            // No activar acordeón para el botón de cerrar sesión
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