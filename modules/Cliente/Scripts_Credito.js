let creditosRaw = [];
let clientesDisponibles = []; // Nuevo array para guardar clientes para el buscador
let modoEdicion = false;

document.addEventListener("DOMContentLoaded", () => {
    cargarTabla();
    cargarClientes();
});

const formatoMoneda = new Intl.NumberFormat('es-DO', {
    style: 'currency',
    currency: 'DOP',
    minimumFractionDigits: 2
});

// --- NUEVAS FUNCIONES ---

function buscarConsultasDataCredito(id_cliente) {
    const contenedor = document.getElementById('contenedor_consultas_api');
    const lista = document.getElementById('lista_consultas_api');
    
    fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Credito.php?action=cargar_consultas_api&id_cliente=${id_cliente}`)
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data.length > 0) {
            contenedor.classList.remove('d-none');
            lista.innerHTML = '';
            
            res.data.forEach(con => {
                const item = document.createElement('button');
                item.type = "button";
                item.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
                item.innerHTML = `
                    <div>
                        <small class="d-block text-muted">${con.fecha_consulta}</small>
                        <strong>Ref: ${con.referencia_consulta}</strong> | Score: <span class="badge bg-primary">${con.score_crediticio}</span>
                    </div>
                    <span class="btn btn-sm btn-outline-success">Usar Datos</span>
                `;
                
                item.onclick = () => aplicarDatosConsulta(con);
                lista.appendChild(item);
            });
        } else {
            contenedor.classList.add('d-none');
        }
    });
}

function aplicarDatosConsulta(consulta) {
    // 1. Llenar la referencia
    document.getElementById('referencia_datacredito').value = consulta.referencia_consulta;
    
    // 2. Calcular monto aprobado según el Score
    const score = parseInt(consulta.score_crediticio);
    let monto = 0;

    if (score < 150) {
        monto = 0;
        alert("El score es demasiado bajo para generar un monto aprobado automáticamente (Menor a 150).");
    } else if (score >= 150 && score <= 200) {
        monto = 5000;
    } else if (score >= 201 && score <= 400) {
        monto = 10000;
    } else if (score >= 401 && score <= 700) {
        monto = 25000;
    } else if (score >= 701 && score <= 1000) {
        monto = 35000;
    }

    document.getElementById('monto_credito').value = monto;
    
    // Efecto visual de resaltado
    document.getElementById('monto_credito').classList.add('is-valid');
    document.getElementById('referencia_datacredito').classList.add('is-valid');
    
    setTimeout(() => {
        document.getElementById('monto_credito').classList.remove('is-valid');
        document.getElementById('referencia_datacredito').classList.remove('is-valid');
    }, 2000);
}

function limpiarFormulario() {
    document.getElementById("id_oculto").value = "";
    document.getElementById("id_cliente").value = "";
    document.getElementById("buscador_cliente").value = "";
    document.getElementById("monto_credito").value = "";
    document.getElementById("referencia_datacredito").value = "";
    document.getElementById("fecha_vencimiento").value = "";
    
    document.getElementById("contenedor_estado_credito").classList.add("d-none");
    
    // Reactivar buscador por si estaba bloqueado en edición
    document.getElementById("buscador_cliente").disabled = false; 

    document.getElementById("btnGuardar").textContent = "Aprobar Crédito";
    modoEdicion = false;

    document.getElementById('contenedor_consultas_api').classList.add('d-none');
    document.getElementById('lista_consultas_api').innerHTML = '';
}

// --- LÓGICA DEL BUSCADOR AUTOCOMPLETABLE ---
function cargarClientes() {
    fetch("/Taller/Taller-Mecanica/modules/Cliente/Archivo_Credito.php?action=cargar_clientes")
    .then(res => res.json())
    .then(data => {
        clientesDisponibles = data.data; // Los guardamos todos en memoria
    });
}

const buscador = document.getElementById('buscador_cliente');
const listaClientes = document.getElementById('lista_clientes');
const inputIdCliente = document.getElementById('id_cliente');

buscador.addEventListener('input', function() {
    const texto = this.value.toLowerCase().trim();
    listaClientes.innerHTML = ''; // Limpiar lista

    // Si está vacío, ocultamos la lista y borramos el ID
    if (texto.length === 0) {
        listaClientes.classList.add('d-none');
        inputIdCliente.value = ''; 
        return;
    }

    // Filtramos buscando coincidencias en nombre o cédula
    const filtrados = clientesDisponibles.filter(cli => 
        cli.nombre_cliente.toLowerCase().includes(texto) || 
        cli.cedula.includes(texto)
    );

    if (filtrados.length > 0) {
        listaClientes.classList.remove('d-none'); // Mostrar lista
        filtrados.forEach(cli => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action';
            li.style.cursor = 'pointer';
            li.textContent = `${cli.nombre_cliente} (${cli.cedula})`;
            
            // Evento al hacer clic en una opción
            li.onclick = () => {
                inputIdCliente.value = cli.id_cliente; // Guardamos ID oculto
                buscador.value = cli.nombre_cliente; // Mostramos nombre bonito
                listaClientes.classList.add('d-none'); // Ocultamos lista

                // NUEVO: Al seleccionar un cliente, buscar sus consultas de DataCrédito
                buscarConsultasDataCredito(cli.id_cliente);
            };
            listaClientes.appendChild(li);
        });
    } else {
        // Si no hay resultados
        listaClientes.classList.remove('d-none');
        listaClientes.innerHTML = '<li class="list-group-item text-muted">No se encontraron clientes...</li>';
        inputIdCliente.value = '';
    }
});

// Ocultar la lista si el usuario hace clic fuera de ella
document.addEventListener('click', function(e) {
    if (!buscador.contains(e.target) && !listaClientes.contains(e.target)) {
        listaClientes.classList.add('d-none');
    }
});
// -------------------------------------------

// Función para mostrar el modal con el mensaje que venga del Backend
function mostrarModalError(mensaje) {
    document.getElementById('modal_error_mensaje').innerText = mensaje;
    document.getElementById('custom_modal_error').classList.remove('d-none');
}

// Función para cerrar
function cerrarModalError() {
    document.getElementById('custom_modal_error').classList.add('d-none');
}

function cargarTabla() {
    fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Credito.php?action=cargar`)
    .then(res => res.json())
    .then(data => {
        creditosRaw = data.data; 
        renderizarTabla(creditosRaw);
    });
}

function renderizarTabla(lista) {
    const tbody = document.getElementById("cuerpo-tabla");
    tbody.innerHTML = "";

    lista.forEach(cr => {
        let colorBadge = 'bg-secondary';
        if(cr.estado_credito === 'Activo') colorBadge = 'bg-success';
        if(cr.estado_credito === 'Vencido') colorBadge = 'bg-danger';
        if(cr.estado_credito === 'Pagado') colorBadge = 'bg-info';
        
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${cr.id_credito}</td>
            <td class="fw-bold">${cr.nombre_cliente}</td>
            <td>${cr.cedula}</td>
            <td class="text-primary fw-bold">${formatoMoneda.format(cr.monto_credito)}</td>
            <td class="text-danger">${formatoMoneda.format(cr.saldo_pendiente)}</td>
            <td>${cr.fecha_vencimiento}</td>
            <td><span class="badge rounded-pill ${colorBadge}">${cr.estado_credito}</span></td>
            <td>
                <button type="button" class="btn btn-warning btn-sm" onclick="editarRegistro(${cr.id_credito})" title="Modificar Condiciones">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });
}

// Filtro general de la tabla inferior
document.getElementById('filtro').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase();
    const resultados = creditosRaw.filter(cr => {
        return cr.nombre_cliente.toLowerCase().includes(busqueda) || cr.cedula.includes(busqueda);
    });
    renderizarTabla(resultados);
});

// Editar
window.editarRegistro = function(id) {
    const cr = creditosRaw.find(c => c.id_credito == id);
    if (!cr) return;

    document.getElementById("id_oculto").value = cr.id_credito;
    
    // Llenar el ID oculto y el nombre visible en el buscador
    document.getElementById("id_cliente").value = cr.id_cliente;
    document.getElementById("buscador_cliente").value = cr.nombre_cliente;
    
    document.getElementById("monto_credito").value = cr.monto_credito;
    document.getElementById("referencia_datacredito").value = cr.referencia_datacredito;
    document.getElementById("fecha_vencimiento").value = cr.fecha_vencimiento.split(' ')[0]; 
    
    document.getElementById("contenedor_estado_credito").classList.remove("d-none");
    document.getElementById("estado_credito").value = cr.estado_credito;

    // Bloqueamos el buscador visual en vez del select para evitar cambiar de cliente
    document.getElementById("buscador_cliente").disabled = true;

    document.getElementById("btnGuardar").textContent = "Actualizar Crédito";
    modoEdicion = true;

    document.getElementById("formulario").scrollIntoView({ behavior: "smooth" });
};

// Guardar / Actualizar
document.getElementById("formulario").addEventListener("submit", function(e) {
    e.preventDefault();

    // Validar que realmente seleccionó un cliente de la lista y no solo escribió algo
    const idCliente = document.getElementById("id_cliente").value;
    if (idCliente === '') {
        alert("Debe seleccionar un cliente de la lista desplegable.");
        document.getElementById('buscador_cliente').focus();
        return;
    }

    // Habilitar para enviar
    document.getElementById("buscador_cliente").disabled = false; 

    const formData = new FormData(this);
    
    // Volver a bloquear
    if (modoEdicion) document.getElementById("buscador_cliente").disabled = true;

    let url = modoEdicion
        ? "/Taller/Taller-Mecanica/modules/Cliente/Archivo_Credito.php?action=actualizar"
        : "/Taller/Taller-Mecanica/modules/Cliente/Archivo_Credito.php?action=guardar";

    fetch(url, { method: "POST", body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            limpiarFormulario();
            cargarTabla(); 
        } else {
            mostrarModalError(data.message);
        }
    });
});

const selectEstado = document.getElementById('estado_credito');

// Bloqueo total: guarda el valor y si cambia, lo regresa al original
selectEstado.addEventListener('mousedown', (e) => e.preventDefault()); // Evita clic
selectEstado.addEventListener('keydown', (e) => e.preventDefault());
// --- MÁSCARA DE VALIDACIÓN PARA MONTO (SOLO NÚMEROS Y DECIMALES) ---
document.addEventListener("DOMContentLoaded", function() {
    const inputMonto = document.getElementById('monto_credito');

    if (inputMonto) {
        // 1. Evitar que se tecleen letras 'e', signos '+' o '-'
        inputMonto.addEventListener('keydown', function(e) {
            if (['e', 'E', '+', '-'].includes(e.key)) {
                e.preventDefault();
            }
        });

        // 2. Limpiar el texto si el usuario copia y pega algo con letras
        inputMonto.addEventListener('input', function(e) {
            // Reemplaza cualquier cosa que no sea número o un punto
            let valor = this.value.replace(/[^0-9.]/g, '');

            // Asegurar que solo exista UN punto decimal
            const partes = valor.split('.');
            if (partes.length > 2) {
                // Si hay más de un punto, dejamos el primero y borramos los demás
                valor = partes[0] + '.' + partes.slice(1).join('').replace(/\./g, '');
            }

            // (Opcional) Limitar a máximo 2 decimales
            if (partes.length === 2 && partes[1].length > 2) {
                valor = partes[0] + '.' + partes[1].substring(0, 2);
            }

            this.value = valor;
        });
    }
});