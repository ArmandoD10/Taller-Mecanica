document.addEventListener("DOMContentLoaded", () => {
    // =========================================================
    // 1. MOTOR DE PESTAÑAS MANUAL (Bypasea errores de plantilla)
    // =========================================================
    const tabs = document.querySelectorAll('#tabHistoriales .nav-link');
    const panes = document.querySelectorAll('.tab-content .tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault(); // Evita que la página salte
            
            // 1.1 Apagar todas las pestañas y ocultar los contenidos
            tabs.forEach(t => {
                t.classList.remove('active', 'text-dark');
                t.classList.add('text-primary');
            });
            panes.forEach(p => {
                p.classList.remove('show', 'active');
            });
            
            // 1.2 Encender la pestaña a la que le dimos clic
            this.classList.add('active', 'text-dark');
            this.classList.remove('text-primary');
            
            // 1.3 Mostrar el contenido correspondiente buscando su ID
            const targetId = this.getAttribute('href'); // Ejemplo: #tab-mecanicos
            const targetPane = document.querySelector(targetId);
            if(targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });

    // =========================================================
    // 2. CARGA DE DATOS DE LA BASE DE DATOS
    // =========================================================
    cargarHistorialMaquinaria();
    cargarHistorialMecanicos();
});

function cargarHistorialMaquinaria() {
    fetch("../../modules/Taller/Archivo_HistorialMaquinaria.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoMaquinaria");
        if(!tbody) return;
        tbody.innerHTML = "";
        
        if(data.success && data.data && data.data.length > 0) {
            data.data.forEach(r => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold">${r.maquinaria}</td>
                    <td class="text-primary fw-bold">ORD-${r.id_orden}</td>
                    <td>${r.mecanico}</td>
                    <td>${r.servicio}</td>
                    <td><span class="badge bg-info text-dark">${r.minutos_uso} min</span></td>
                    <td class="small">${r.inicio || '--'}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No hay registros de uso de maquinaria.</td></tr>`;
        }
    })
    .catch(err => console.error("Error cargando maquinaria:", err));
}

function cargarHistorialMecanicos() {
    fetch("../../modules/Taller/Archivo_HistorialMecanico.php?action=listar")
    .then(res => {
        if (!res.ok) throw new Error("Error de red o código 500 del servidor.");
        return res.json();
    })
    .then(data => {
        const tbody = document.getElementById("cuerpoMecanicos");
        if(!tbody) return;
        tbody.innerHTML = "";
        
        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Error SQL: ${data.message}</td></tr>`;
            return;
        }

        if (data.data && data.data.length > 0) {
            data.data.forEach(r => {
                let badgeClass = r.estado_asignacion === 'Completado' ? 'bg-success' : 'bg-warning text-dark';
                
                let inicioFmt = r.inicio ? r.inicio : '<span class="text-muted fst-italic">No ha iniciado</span>';
                let finFmt = r.fin ? r.fin : '<span class="text-muted fst-italic">En proceso...</span>';

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="fw-bold"><i class="fas fa-user-circle text-secondary me-2"></i>${r.mecanico}</td>
                    <td class="text-primary fw-bold">ORD-${r.id_orden}</td>
                    <td>${r.servicio}</td>
                    <td><span class="badge ${badgeClass}">${r.estado_asignacion}</span></td>
                    <td class="small">${inicioFmt}</td>
                    <td class="small">${finFmt}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Aún no hay registros de productividad para los mecánicos.</td></tr>`;
        }
    })
    .catch(err => {
        console.error("Error crítico cargando mecánicos:", err);
        const tbody = document.getElementById("cuerpoMecanicos");
        if(tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger fw-bold"><i class="fas fa-times-circle me-2"></i>Error grave de conexión. Revisa la consola (F12).</td></tr>`;
    });
}