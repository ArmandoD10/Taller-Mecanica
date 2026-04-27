// 1. VARIABLE GLOBAL: Fundamental para que 'confirmarDevolucion' sepa qué ID usar
let currentFacturaId = null; 

/**
 * Busca una factura por ID, valida la garantía de 30 días
 * y renderiza el resultado con los botones de acción.
 */
function buscarFacturaDevolucion() {
    const idInput = document.getElementById("txt_buscar_fac");
    const id = idInput.value;

    if (!id) {
        Swal.fire('Campo requerido', 'Por favor, ingrese un número de factura para validar.', 'warning');
        idInput.focus();
        return;
    }

    // Indicador de carga para la búsqueda
    Swal.fire({
        title: 'Buscando factura...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const formData = new FormData();
    formData.append('id_factura', id);

    fetch('/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Devolucion.php?action=buscar_factura', { 
        method: 'POST', 
        body: formData 
    })
    .then(res => res.json())
    .then(res => {
        Swal.close(); // Cerramos el cargando

        if (res.success) {
            const f = res.data;
            currentFacturaId = f.id_factura; 

            const contenedor = document.getElementById("resultado_busqueda");
            contenedor.classList.remove("d-none");
            
            document.getElementById("info_fac_nro").innerText = "FACTURA N° " + f.id_factura;
            document.getElementById("info_fac_cliente").innerText = f.cliente_nombre;
            document.getElementById("info_fac_fecha").innerText = "Emitida el: " + f.fecha_emision;
            
            const montoFormateado = "RD$ " + parseFloat(f.monto_total).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById("info_fac_monto").innerText = montoFormateado;
            
            const btnDevolucion = document.getElementById("btn_procesar_dev");
            const alertaExpirada = document.getElementById("alerta_expirada");
            const alertaValida = document.getElementById("alerta_valida");

            const dias = parseInt(f.dias_transcurridos);
            const estaInactiva = (f.estado === 'inactivo');

            if (dias > 30 || estaInactiva) {
                btnDevolucion.disabled = true;
                btnDevolucion.classList.replace("btn-danger", "btn-secondary");
                alertaExpirada.classList.remove("d-none");
                alertaValida.classList.add("d-none");
                
                if(estaInactiva) {
                    alertaExpirada.innerHTML = '<i class="fas fa-info-circle me-2"></i> Esta factura ya ha sido devuelta anteriormente.';
                }
            } else {
                btnDevolucion.disabled = false;
                btnDevolucion.classList.replace("btn-secondary", "btn-danger");
                alertaExpirada.classList.add("d-none");
                alertaValida.classList.remove("d-none");

                btnDevolucion.onclick = function() {
                    const modalDev = new bootstrap.Modal(document.getElementById('modalDevolucion'));
                    modalDev.show();
                };
            }

            document.getElementById("btn_ver_detalle").onclick = () => verDetalleFactura(f.id_factura);

        } else {
            Swal.fire('No encontrada', res.message, 'error');
            document.getElementById("resultado_busqueda").classList.add("d-none");
            currentFacturaId = null;
        }
    })
    .catch(err => {
        Swal.close();
        Swal.fire('Error', 'Error de conexión al buscar la factura.', 'error');
    });
}

/**
 * Función para cargar los artículos en el modal de detalle
 */
function verDetalleFactura(id) {
    const tbody = document.getElementById("det_fac_items");
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>Cargando ítems...</td></tr>';
    
    const modal = new bootstrap.Modal(document.getElementById('modalDetalleFactura'));
    modal.show();

    // Reutilizamos tu endpoint de HistorialFacturas
    fetch(`../../modules/Facturacion/Archivo_HistorialFacturas.php?action=obtener_detalle&id_factura=${id}`)
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        if (data.success && data.data.length > 0) {
            data.data.forEach(item => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="text-start ps-4">${item.descripcion}</td>
                    <td>${item.cantidad}</td>
                    <td class="text-end">RD$ ${parseFloat(item.precio).toLocaleString()}</td>
                    <td class="text-end pe-4 fw-bold">RD$ ${parseFloat(item.subtotal).toLocaleString()}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No se encontraron ítems en esta factura.</td></tr>';
        }
    });
}

function confirmarDevolucion() {
    // Validaciones de Políticas de Devolución
    if (document.getElementById("chk_destapado").checked || document.getElementById("chk_uso").checked) {
        Swal.fire('Devolución Rechazada', 'No se permite la devolución de productos abiertos o usados según las políticas del taller.', 'error');
        return;
    }
    
    const userAdmin = document.getElementById("admin_user").value;
    const passAdmin = document.getElementById("admin_pass").value;

    if (!userAdmin || !passAdmin) {
        Swal.fire('Autorización Requerida', 'Las credenciales de administrador son obligatorias para procesar reversiones.', 'warning');
        return;
    }

    // Alerta de confirmación final
    Swal.fire({
        title: '¿Confirmar Devolución?',
        text: "Se generará una nota de crédito y se anulará la factura original.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, procesar devolución',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            const form = new FormData();
            form.append('id_factura', currentFacturaId);
            form.append('user_admin', userAdmin);
            form.append('pass_admin', passAdmin);
            form.append('motivo', document.getElementById("txt_motivo").value);
            form.append('buen_estado', document.getElementById("chk_buen_estado").checked);
            
            const montoLimpio = document.getElementById("info_fac_monto").innerText.replace(/[^\d.-]/g, '');
            form.append('monto', montoLimpio);
            
            fetch('/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Devolucion.php?action=procesar_devolucion', { 
                method: 'POST', 
                body: form 
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire('¡Éxito!', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error Crítico', 'Fallo en la comunicación con el servidor.', 'error');
            });
        }
    });
}

/**
 * Limpia la pantalla de devoluciones y resetea el estado global
 */
function limpiarPantallaDevolucion() {
    // 1. Limpiar el buscador
    document.getElementById("txt_buscar_fac").value = "";
    
    // 2. Ocultar el resultado de la búsqueda
    document.getElementById("resultado_busqueda").classList.add("d-none");
    
    // 3. Resetear la variable de seguridad
    currentFacturaId = null;

    // 4. Limpiar los campos del modal (por si se abrieron)
    document.getElementById("txt_motivo").value = "";
    document.getElementById("chk_buen_estado").checked = false;
    document.getElementById("chk_destapado").checked = false;
    document.getElementById("chk_uso").checked = false;
    document.getElementById("admin_user").value = "";
    document.getElementById("admin_pass").value = "";

    console.log("Módulo de devoluciones reiniciado.");
}