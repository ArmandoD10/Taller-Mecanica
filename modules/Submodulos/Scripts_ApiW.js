document.addEventListener('DOMContentLoaded', () => {
    cargarListaWhatsApp();
});

function cargarListaWhatsApp() {
    const tbody = document.getElementById('tabla_api_whatsapp');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border text-success"></div></td></tr>';

    fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_ApiW.php?action=listar_clientes_aceite')
    .then(r => r.json())
    .then(res => {
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-muted">No hay recordatorios pendientes.</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map(c => `
            <tr>
                <td>
                    <div class="fw-bold text-dark">${c.nombre} ${c.apellido_p}</div>
                    <small class="text-muted"><i class="fas fa-phone-alt me-1"></i>${c.telefono}</small>
                </td>
                <td>
                    <span class="badge bg-dark px-2">${c.placa}</span>
                    <div class="small text-muted mt-1">Servicio Aceite</div>
                </td>
                <td>
                    <div class="small fw-bold">${c.ultima_visita}</div>
                </td>
                <td>
                    <span class="badge bg-danger">Vencido: ${Math.floor(c.dias_transcurridos / 30)} meses</span>
                </td>
                <td class="text-center">
                    <button class="btn btn-wa btn-sm px-4" onclick="enviarMensaje(${c.id_telefono}, '${c.nombre}', '${c.placa}')">
                        <i class="fab fa-whatsapp me-2"></i>Notificar
                    </button>
                </td>
            </tr>
        `).join('');
    });
}

function enviarMensaje(idTel, nombre, placa) {
    // IMPORTANTE: Los nombres aquí deben coincidir exactamente con el $_POST del PHP
    const datos = new FormData();
    datos.append('id_telefono', idTel);
    datos.append('nombre', nombre);
    datos.append('placa', placa);

    Swal.fire({
        title: '¿Enviar Recordatorio?',
        text: `Se notificará a ${nombre} sobre su mantenimiento.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#25D366',
        confirmButtonText: 'Sí, enviar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Asegúrate de que la ruta al archivo sea la correcta
            fetch('/Taller/Taller-Mecanica/modules/Submodulos/Archivo_ApiW.php?action=registrar_envio_whatsapp', {
                method: 'POST',
                body: datos
            })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    Swal.fire('¡Éxito!', 'Mensaje registrado correctamente.', 'success');
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            })
            .catch(err => console.error("Error crítico:", err));
        }
    });
}