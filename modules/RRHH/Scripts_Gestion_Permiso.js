function toggleForm() {
        const f = document.getElementById('formRegistroPermiso');
        f.classList.toggle('d-none');
    }
    function simularBusqueda() {
        document.getElementById('resultadoBusqueda').classList.remove('d-none');
        document.getElementById('camposSolicitud').classList.remove('d-none');
    }


let idEmpleadoSeleccionado = null;

// Ejecutar al cargar la página
document.addEventListener("DOMContentLoaded", () => {
    cargarMotivos();
    listarPermisos();
    cargarKPIs();
    // Aquí podrías llamar a una función para cargar los KPIs si la creas en el backend
});

function toggleForm() {
    document.getElementById('formRegistroPermiso').classList.toggle('d-none');
}

function cargarKPIs() {
    fetch('/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Permiso.php?action=obtenerKPIs')
        .then(res => res.json())
        .then(data => {
            // Asignamos los números reales a los IDs del HTML
            document.getElementById("kpi_totales").textContent = data.totales || 0;
            document.getElementById("kpi_activos").textContent = data.activos || 0;
            document.getElementById("kpi_vacaciones").textContent = data.vacaciones || 0;
            document.getElementById("kpi_otros").textContent = data.otros || 0;
        })
        .catch(err => console.error("Error cargando KPIs:", err));
}

// 🔍 BUSQUEDA REAL POR NOMBRE, ID O USERNAME
function buscarEmpleadoReal() {
    const filtro = document.getElementById("busquedaEmp").value.trim();
    if (!filtro) return alert("Escriba un nombre, ID, Cédula o usuario");

    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Permiso.php?action=buscarEmpleado&filtro=${filtro}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.id_empleado) {
                // Guardamos el ID para el insert posterior
                idEmpleadoSeleccionado = data.id_empleado;

                // Llenamos la Card con datos reales
                document.getElementById("res_nombre").textContent = data.nombre;
                document.getElementById("res_id").textContent = data.id_empleado;
                document.getElementById("res_user").textContent = data.username || 'Sin usuario';
                
                // --- AQUÍ ACTUALIZAMOS EL PUESTO ---
                // Buscamos el elemento donde dice "Puesto:" en tu HTML y le ponemos el dato
                const puestoLabel = document.querySelector("#resultadoBusqueda b:last-child");
                if(puestoLabel) puestoLabel.textContent = data.nombre_puesto;

                // Mostrar secciones ocultas
                document.getElementById("resultadoBusqueda").classList.remove("d-none");
                document.getElementById("camposSolicitud").classList.remove("d-none");
            } else {
                alert("No se encontró ningún empleado con esos datos.");
                document.getElementById("resultadoBusqueda").classList.add("d-none");
                document.getElementById("camposSolicitud").classList.add("d-none");
            }
        })
        .catch(err => console.error("Error en búsqueda:", err));
}

// 📋 CARGAR MOTIVOS DESDE LA TABLA Tipo_Motivo
function cargarMotivos() {
    fetch('/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Permiso.php?action=obtenerMotivos')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById("tipoPermiso");
            select.innerHTML = '<option value="">Seleccione motivo...</option>';
            data.forEach(m => {
                let opt = document.createElement("option");
                opt.value = m.id_motivo;
                opt.textContent = m.nombre;
                select.appendChild(opt);
            });
        });
}

// 💾 GUARDAR EN TABLA Permiso_Empleado
function guardarPermisoReal() {
    const idMotivo = document.getElementById("tipoPermiso").value;
    const fIni = document.getElementById("fecha_inicio").value;
    const fFin = document.getElementById("fecha_fin").value;
    const motivo = document.getElementById("motivo_texto").value;

    if (!idEmpleadoSeleccionado || !idMotivo || !fIni || !fFin) {
        return alert("Por favor complete todos los campos obligatorios.");
    }

    const formData = new URLSearchParams();
    formData.append('id_empleado', idEmpleadoSeleccionado);
    formData.append('id_motivo', idMotivo);
    formData.append('fecha_inicio', fIni);
    formData.append('fecha_fin', fFin);
    formData.append('motivo_texto', motivo);

    fetch('/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Permiso.php?action=guardarPermiso', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("✅ Permiso registrado correctamente.");
            location.reload(); // Recargar para actualizar tabla y dashboard
        } else {
            alert("❌ Error al guardar: " + data.detalle);
        }
    });
}

// 📋 LISTAR HISTORIAL EN LA TABLA
function listarPermisos() {
    fetch('/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Permiso.php?action=listarPermisos')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById("tbodyPermisos");
            tbody.innerHTML = "";
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay permisos registrados</td></tr>';
                return;
            }

            data.forEach(p => {

                // Definir color del badge según el estado
                const colorBadge = p.estado_real === 'activo' ? 'bg-success' : 'bg-secondary';
                const textoEstado = p.estado_real === 'activo' ? 'Vigente' : 'Finalizado';
                
                tbody.innerHTML += `
                    <tr>
                        <td>${p.id_empleado}</td>
                        <td class="fw-bold">${p.emp_nombre} ${p.apellido_p}</td>
                        <td><span class="badge bg-info text-dark">${p.tipo_nombre}</span></td>
                        <td>${p.fecha_inicio}</td>
                        <td>${p.fecha_fin}</td>
                        <td>${p.motivo || 'N/A'}</td>
                        <td><span class="badge rounded-pill ${colorBadge}">${textoEstado}</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-light border" title="Ver Comprobante">
                                <i class="fas fa-file-pdf text-danger"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        });
}


