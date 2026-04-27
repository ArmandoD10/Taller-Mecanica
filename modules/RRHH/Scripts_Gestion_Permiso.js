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
    if (!filtro) {
        Swal.fire('Atención', 'Escriba un nombre, ID, Cédula o usuario para buscar.', 'info');
        return;
    }

    fetch(`/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Permiso.php?action=buscarEmpleado&filtro=${filtro}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.id_empleado) {
                idEmpleadoSeleccionado = data.id_empleado;

                document.getElementById("res_nombre").textContent = data.nombre;
                document.getElementById("res_id").textContent = data.id_empleado;
                document.getElementById("res_user").textContent = data.username || 'Sin usuario';
                
                const puestoLabel = document.querySelector("#resultadoBusqueda b:last-child");
                if(puestoLabel) puestoLabel.textContent = data.nombre_puesto;

                document.getElementById("resultadoBusqueda").classList.remove("d-none");
                document.getElementById("camposSolicitud").classList.remove("d-none");

                // Notificación rápida de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Empleado localizado',
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                Swal.fire('No encontrado', 'No se encontró ningún empleado con esos datos.', 'error');
                document.getElementById("resultadoBusqueda").classList.add("d-none");
                document.getElementById("camposSolicitud").classList.add("d-none");
            }
        })
        .catch(err => {
            console.error("Error:", err);
            Swal.fire('Error', 'Hubo un fallo en la comunicación con el servidor.', 'error');
        });
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
        Swal.fire('Campos incompletos', 'Por favor complete todos los campos obligatorios.', 'warning');
        return;
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
            Swal.fire({
                title: '¡Éxito!',
                text: 'El permiso ha sido registrado correctamente.',
                icon: 'success',
                confirmButtonColor: '#1a73e8'
            }).then(() => {
                location.reload(); 
            });
        } else {
            Swal.fire('Error al guardar', data.detalle, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error crítico', 'No se pudo conectar con el servidor.', 'error');
    });
}

// 📋 LISTAR HISTORIAL EN LA TABLA
// Modifica el renderizado de la tabla para activar el botón
function listarPermisos() {
    fetch('/Taller/Taller-Mecanica/modules/RRHH/Archivo_Gestion_Permiso.php?action=listarPermisos')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById("tbodyPermisos");
            tbody.innerHTML = "";
            
            data.forEach(p => {
                const colorBadge = p.estado_real === 'activo' ? 'bg-success' : 'bg-secondary';
                const textoEstado = p.estado_real === 'activo' ? 'Vigente' : 'Finalizado';
                
                // Aquí pasamos todo el objeto 'p' para tener los datos listos
                tbody.innerHTML += `
                    <tr>
                        <td>${p.id_permiso || p.id_empleado}</td>
                        <td class="fw-bold">${p.emp_nombre} ${p.apellido_p}</td>
                        <td><span class="badge bg-info text-dark">${p.tipo_nombre}</span></td>
                        <td>${p.fecha_inicio}</td>
                        <td>${p.fecha_fin}</td>
                        <td>${p.motivo || 'N/A'}</td>
                        <td><span class="badge rounded-pill ${colorBadge}">${textoEstado}</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger shadow-sm" onclick='generarComprobantePermiso(${JSON.stringify(p)})' title="Imprimir Comprobante">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        });
}

// Nueva función creativa para el Comprobante Tipo Certificado
function generarComprobantePermiso(datos) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const logoUrl = "/Taller/Taller-Mecanica/img/logo.png"; 

    const img = new Image();
    img.src = logoUrl;
    img.onload = function() {
        // --- AJUSTE DEL LOGO ---
        // Calculamos la proporción para que no se comprima
        const logoAnchoOriginal = img.width;
        const logoAltoOriginal = img.height;
        const proporcion = logoAltoOriginal / logoAnchoOriginal;
        
        const anchoDeseado = 20; // Más chico para que quepa bien
        const altoCalculado = anchoDeseado * proporcion;

        // --- ENCABEZADO INSTITUCIONAL ---
        // Posicionamos el logo sin deformarlo
        doc.addImage(img, 'JPEG', 15, 12, anchoDeseado, altoCalculado);
        
        doc.setFont("helvetica", "bold");
        doc.setFontSize(16);
        doc.setTextColor(13, 71, 161);
        doc.text("MECÁNICA AUTOMOTRIZ DÍAZ & PANTALEÓN", 40, 20);
        
        doc.setFontSize(9);
        doc.setTextColor(100);
        doc.text("Departamento de Recursos Humanos", 40, 26);
        doc.text(`Santiago de los Caballeros, Rep. Dom.`, 40, 31);

        // --- CUERPO DEL CERTIFICADO ---
        doc.setDrawColor(13, 71, 161);
        doc.setLineWidth(0.5);
        doc.line(15, 38, 195, 38); 

        doc.setFontSize(15);
        doc.setTextColor(0);
        doc.text("CERTIFICADO DE PERMISO LABORAL", 105, 50, { align: "center" });

        // Ajuste de texto del cuerpo
        doc.setFontSize(11);
        doc.setFont("helvetica", "normal");
        const nombreCompleto = `${datos.emp_nombre} ${datos.apellido_p}`.toUpperCase();
        const textoCuerpo = `Por la presente se certifica que el empleado(a) ${nombreCompleto}, ha sido formalmente autorizado(a) para ausentarse de sus labores bajo el concepto detallado a continuación:`;
        
        const splitText = doc.splitTextToSize(textoCuerpo, 170);
        doc.text(splitText, 20, 65);

        // --- TABLA DE DETALLES ---
        doc.autoTable({
            startY: 80,
            theme: 'grid',
            head: [['DESCRIPCIÓN', 'INFORMACIÓN']],
            body: [
                ['Tipo de Permiso:', datos.tipo_nombre.toUpperCase()],
                ['Desde:', datos.fecha_inicio],
                ['Hasta:', datos.fecha_fin],
                ['Motivo:', datos.motivo || 'No especificado'],
                ['Estado:', datos.estado_real === 'activo' ? 'VIGENTE' : 'FINALIZADO']
            ],
            headStyles: { fillColor: [13, 71, 161], halign: 'left' },
            styles: { fontSize: 10, cellPadding: 4 },
            columnStyles: { 0: { fontStyle: 'bold', width: 50 } }
        });

        // --- SECCIÓN DE FIRMAS ---
        const finalY = doc.lastAutoTable.finalY + 30;
        doc.setFontSize(10);
        doc.line(30, finalY, 85, finalY);
        doc.text("Firma del Empleado", 42, finalY + 5);

        doc.line(125, finalY, 180, finalY);
        doc.text("Recursos Humanos", 140, finalY + 5);

        // --- PIE DE PÁGINA ---
        doc.setFontSize(8);
        doc.setTextColor(150);
        doc.text(`ID: PER-${datos.id_permiso || '00'} | Generado: ${new Date().toLocaleString()}`, 105, 285, { align: "center" });

        doc.save(`Permiso_${datos.emp_nombre}.pdf`);
    };
}


