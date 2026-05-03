document.addEventListener('DOMContentLoaded', () => {
    cargarSugerenciasDepartamentos();
    // No se llama a ejecutarBusqueda() aquí para mantener la tabla vacía al inicio
});

// Función para limpiar campos y tabla
function limpiarTodo() {
    document.getElementById('filtroCodigo').value = '';
    document.getElementById('filtroNombre').value = '';
    document.getElementById('filtroDepto').value = '';
    document.getElementById('tablaEmpleados').innerHTML = '';
}

function cargarSugerenciasDepartamentos() {
    fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Maestro.php?action=listar_departamentos')
    .then(r => r.json())
    .then(res => {
        const datalist = document.getElementById('listaDeptos');
        datalist.innerHTML = res.data.map(d => `<option value="${d.nombre}">`).join('');
    });
}

function ejecutarBusqueda() {
    const cod = document.getElementById('filtroCodigo').value.trim();
    const nom = document.getElementById('filtroNombre').value.trim();
    const dep = document.getElementById('filtroDepto').value.trim();

    // Verificación: No buscar si todos los campos están vacíos
    if (cod === '' && nom === '' && dep === '') {
        Swal.fire('Atención', 'Por favor ingrese al menos un criterio de búsqueda.', 'info');
        return;
    }

    const tbody = document.getElementById('tablaEmpleados');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center p-5"><div class="spinner-border text-primary"></div><br>Buscando...</td></tr>';

    const params = new URLSearchParams({ action: 'buscar_directorio', codigo: cod, nombre: nom, depto: dep });

    fetch(`/Taller/Taller-Mecanica/modules/Submodulos/Archivo_Maestro.php?${params.toString()}`)
    .then(r => r.json())
    .then(res => {
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted"><i class="fas fa-search fa-2x mb-3"></i><br>No se encontraron empleados.</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map(e => `
            <tr onclick='mostrarDetalle(${JSON.stringify(e)})' style="cursor:pointer">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width:38px; height:38px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">${e.nombre} ${e.apellido_p}</div>
                            <small class="text-muted">${e.username || 'SIN CÓDIGO'}</small>
                        </div>
                    </div>
                </td>
                <td class="text-muted">${e.puesto}</td>
                <td><span class="badge bg-light text-dark border fw-normal">${e.departamento}</span></td>
                <td class="small"><i class="fas fa-map-marker-alt text-success me-1"></i>${e.sucursal}</td>
            </tr>
        `).join('');
    });
}

function mostrarDetalle(emp) {
    const contenedor = document.getElementById('detalleEmpleado');
    
    // Construcción del contenido del modal
    contenedor.innerHTML = `
        <div class="text-center mb-4">
            <!-- Logo de usuario / Foto simulada -->
            <div class="position-relative d-inline-block">
                <div class="rounded-circle bg-light d-flex justify-content-center align-items-center mx-auto shadow-sm border" 
                     style="width: 120px; height: 120px; overflow: hidden;">
                    <i class="fas fa-user-tie fa-5x text-primary" style="margin-top: 20px;"></i>
                </div>
                <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2 border border-white">
                    <span class="visually-hidden">Activo</span>
                </span>
            </div>
            <h4 class="fw-bold mt-3 mb-1 text-dark">${emp.nombre} ${emp.apellido_p} ${emp.apellido_m || ''}</h4>
            <p class="text-primary fw-bold mb-0">${emp.puesto}</p>
            <span class="badge bg-light text-muted border">${emp.departamento}</span>
        </div>

        <div class="row g-3">
            <div class="col-6 text-start">
                <label class="small text-muted fw-bold d-block">CÓDIGO DE USUARIO</label>
                <div class="p-2 bg-light rounded"><i class="fas fa-id-badge me-2 text-secondary"></i>${emp.username || 'N/A'}</div>
            </div>
            <div class="col-6 text-start">
                <label class="small text-muted fw-bold d-block">SUCURSAL</label>
                <div class="p-2 bg-light rounded"><i class="fas fa-building me-2 text-secondary"></i>${emp.sucursal}</div>
            </div>
            <div class="col-12 text-start">
                <label class="small text-muted fw-bold d-block">CORREO INSTITUCIONAL</label>
                <div class="p-2 bg-light rounded"><i class="fas fa-envelope me-2 text-secondary"></i>${emp.correo_org || 'no-reply@taller.com'}</div>
            </div>
            <div class="col-12 text-start">
                <label class="small text-muted fw-bold d-block">TELÉFONO / EXTENSIÓN</label>
                <div class="p-2 bg-light rounded"><i class="fas fa-phone-alt me-2 text-secondary"></i>${emp.telefono || 'Sin registrar'}</div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top text-center">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar Ficha</button>
        </div>
    `;

    // Disparar el modal de Bootstrap
    const modal = new bootstrap.Modal(document.getElementById('modalEmpleado'));
    modal.show();
}