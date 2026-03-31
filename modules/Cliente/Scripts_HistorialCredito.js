let clientesDisponibles = [];

const formatoMoneda = new Intl.NumberFormat('es-DO', {
    style: 'currency',
    currency: 'DOP',
    minimumFractionDigits: 2
});

document.addEventListener("DOMContentLoaded", () => {
    cargarClientes();
});

// --- LÓGICA DEL BUSCADOR AUTOCOMPLETABLE ---
function cargarClientes() {
    fetch("/Taller/Taller-Mecanica/modules/Cliente/Archivo_HistorialCredito.php?action=cargar_clientes")
    .then(res => res.json())
    .then(data => {
        clientesDisponibles = data.data;
    });
}

const buscador = document.getElementById('buscador_cliente');
const listaClientes = document.getElementById('lista_clientes');
const inputIdCliente = document.getElementById('id_cliente');

buscador.addEventListener('input', function() {
    const texto = this.value.toLowerCase().trim();
    listaClientes.innerHTML = ''; 

    if (texto.length === 0) {
        listaClientes.classList.add('d-none');
        return;
    }

    const filtrados = clientesDisponibles.filter(cli => 
        cli.nombre_cliente.toLowerCase().includes(texto) || 
        cli.cedula.includes(texto)
    );

    if (filtrados.length > 0) {
        listaClientes.classList.remove('d-none');
        filtrados.forEach(cli => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action fw-bold';
            li.style.cursor = 'pointer';
            li.textContent = `${cli.nombre_cliente} - ${cli.cedula}`;
            
            li.onclick = () => {
                inputIdCliente.value = cli.id_cliente; 
                buscador.value = cli.nombre_cliente; 
                listaClientes.classList.add('d-none'); 
                
                // Ejecutar la búsqueda real al hacer clic
                consultarHistorial(cli.id_cliente);
            };
            listaClientes.appendChild(li);
        });
    } else {
        listaClientes.classList.remove('d-none');
        listaClientes.innerHTML = '<li class="list-group-item text-muted">No se encontraron resultados...</li>';
    }
});

document.addEventListener('click', function(e) {
    if (!buscador.contains(e.target) && !listaClientes.contains(e.target)) {
        listaClientes.classList.add('d-none');
    }
});

// --- LÓGICA DE CONSULTA Y PINTADO ---
function consultarHistorial(id_cliente) {
    fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_HistorialCredito.php?action=buscar_historial&id_cliente=${id_cliente}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // 1. Mostrar y actualizar las tarjetas de resumen
            document.getElementById('panel_resumen').classList.remove('d-none');
            document.getElementById('txt_limite').textContent = formatoMoneda.format(data.totales.limite);
            document.getElementById('txt_deuda').textContent = formatoMoneda.format(data.totales.deuda);
            document.getElementById('txt_disponible').textContent = formatoMoneda.format(data.totales.disponible);

            // 2. Llenar la tabla de historial
            const tbody = document.getElementById("cuerpo-tabla");
            tbody.innerHTML = "";

            if (data.historial.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Este cliente no tiene líneas de crédito registradas.</td></tr>`;
                return;
            }

            data.historial.forEach(cr => {
                let colorBadge = 'bg-secondary';
                if(cr.estado_credito === 'Activo') colorBadge = 'bg-success';
                if(cr.estado_credito === 'Vencido') colorBadge = 'bg-danger';
                if(cr.estado_credito === 'Pagado') colorBadge = 'bg-info';

                // Solo mostrar la fecha sin la hora si viene formato datetime
                let fecha_aprob = cr.fecha_aprobacion.split(' ')[0];

                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td class="fw-bold">CR-${cr.id_credito.toString().padStart(4, '0')}</td>
                    <td class="text-primary fw-bold">${formatoMoneda.format(cr.monto_credito)}</td>
                    <td class="text-danger fw-bold">${formatoMoneda.format(cr.saldo_pendiente)}</td>
                    <td>${fecha_aprob}</td>
                    <td>${cr.fecha_vencimiento}</td>
                    <td>${cr.referencia_datacredito || 'N/A'}</td>
                    <td><span class="badge rounded-pill ${colorBadge}">${cr.estado_credito}</span></td>
                `;
                tbody.appendChild(fila);
            });
        } else {
            alert(data.message);
        }
    });
}

function limpiarConsulta() {
    document.getElementById('buscador_cliente').value = '';
    document.getElementById('id_cliente').value = '';
    document.getElementById('panel_resumen').classList.add('d-none');
    
    document.getElementById("cuerpo-tabla").innerHTML = `
        <tr>
            <td colspan="7" class="text-center text-muted py-4">Seleccione un cliente para ver su historial.</td>
        </tr>
    `;
    
    document.getElementById('buscador_cliente').focus();
}