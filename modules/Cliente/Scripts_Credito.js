let creditosRaw = [];
let clientesDisponibles = []; 
let modoEdicion = false;
let montoActualCliente = 0; 

document.addEventListener("DOMContentLoaded", () => {
    cargarTabla();
    cargarClientes();

    document.getElementById("formEliminar").addEventListener("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch("/Taller/Taller-Mecanica/modules/Cliente/Archivo_Credito.php?action=eliminar", { method: "POST", body: formData })
        .then(res => {
            if (!res.ok) throw new Error("Error de red o servidor: " + res.status);
            return res.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message);
                cerrarModalEliminar();
                limpiarFormulario();
                cargarTabla(); 
            } else {
                mostrarModalError(data.message);
            }
        })
        .catch(err => {
            console.error("Falla en la petición de eliminar:", err);
            mostrarModalError("Ocurrió un error de conexión o sintaxis en el servidor.");
        });
    });
});

const formatoMoneda = new Intl.NumberFormat('es-DO', {
    style: 'currency',
    currency: 'DOP',
    minimumFractionDigits: 2
});

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
    document.getElementById('referencia_datacredito').value = consulta.referencia_consulta;
    
    const score = parseInt(consulta.score_crediticio);
    let monto = 0;

    if (score < 150) {
        monto = 0;
        alert("El score es demasiado bajo para generar un monto aprobado automáticamente (Menor a 150). Utilice el Bypass de Administrador si desea proceder.");
    } else if (score >= 150 && score <= 200) { monto = 5000; } 
    else if (score >= 201 && score <= 400) { monto = 10000; } 
    else if (score >= 401 && score <= 700) { monto = 25000; } 
    else if (score >= 701 && score <= 1000) { monto = 35000; }

    document.getElementById('monto_credito').value = monto;
    
    document.getElementById('monto_credito').classList.add('is-valid');
    document.getElementById('referencia_datacredito').classList.add('is-valid');
    
    evaluarSeguridad(); 

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
    document.getElementById("admin_username").value = "";
    document.getElementById("admin_password").value = "";
    document.getElementById("lbl_tipo_cliente").innerHTML = "";
    
    document.getElementById("contenedor_estado_credito").classList.add("d-none");
    document.getElementById("sec_bypass").classList.add("d-none");
    document.getElementById("chk_bypass").checked = false;
    
    document.getElementById("monto_credito").readOnly = true;
    document.getElementById("buscador_cliente").disabled = false; 

    document.getElementById("btnGuardar").textContent = "Aprobar Crédito";
    modoEdicion = false;
    montoActualCliente = 0; 

    document.getElementById('contenedor_consultas_api').classList.add('d-none');
    document.getElementById('lista_consultas_api').innerHTML = '';
    
    evaluarSeguridad();
}

function evaluarSeguridad() {
    const divAuth = document.getElementById('div_autorizacion_admin');
    const lblMotivo = document.getElementById('lbl_motivo_autorizacion');
    const chkBypass = document.getElementById('chk_bypass');
    const montoIngresado = parseFloat(document.getElementById('monto_credito').value) || 0;

    let requiereAdmin = false;
    let motivo = "";

    // 1. Activó el Bypass de DataCredito
    if (chkBypass && chkBypass.checked) {
        requiereAdmin = true;
        motivo = "Aprobación Manual (Bypass DataCrédito)";
    } 
    // 2. MODO NUEVO, pero el cliente ya tiene crédito (Es una AMPLIACIÓN por suma)
    else if (!modoEdicion && montoActualCliente > 0 && montoIngresado > 0) {
        requiereAdmin = true;
        motivo = `Ampliación de Límite (+RD$ ${montoIngresado.toFixed(2)} se sumarán a su cuenta actual)`;
    }
    // 3. MODO EDICIÓN (Cualquier modificación exige Admin)
    else if (modoEdicion) {
        requiereAdmin = true;
        motivo = "Modificación de Registro de Crédito";
    }

    if (requiereAdmin) {
        divAuth.classList.remove('d-none');
        lblMotivo.innerHTML = `<i class="fas fa-shield-alt me-1"></i> Autorización Requerida: ${motivo}`;
    } else {
        divAuth.classList.add('d-none');
        document.getElementById('admin_username').value = '';
        document.getElementById('admin_password').value = '';
    }
}

document.getElementById('monto_credito').addEventListener('input', evaluarSeguridad);

document.getElementById('chk_bypass').addEventListener('change', function() {
    const inputMonto = document.getElementById('monto_credito');
    if(this.checked) {
        inputMonto.readOnly = false;
        inputMonto.classList.remove('bg-light');
        document.getElementById('referencia_datacredito').value = 'BYPASS-ADMIN';
    } else {
        inputMonto.readOnly = true;
        inputMonto.classList.add('bg-light');
        document.getElementById('referencia_datacredito').value = '';
    }
    evaluarSeguridad();
});

function cargarClientes() {
    fetch("/Taller/Taller-Mecanica/modules/Cliente/Archivo_Credito.php?action=cargar_clientes")
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
        inputIdCliente.value = ''; 
        document.getElementById("lbl_tipo_cliente").innerHTML = '';
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
            li.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            li.style.cursor = 'pointer';
            
            let badge = cli.tipo_persona === 'Juridica' ? '<span class="badge bg-primary">Empresa</span>' : '<span class="badge bg-secondary">Física</span>';
            li.innerHTML = `<span>${cli.nombre_cliente} (${cli.cedula})</span> ${badge}`;
            
            li.onclick = () => {
                inputIdCliente.value = cli.id_cliente; 
                buscador.value = cli.nombre_cliente; 
                listaClientes.classList.add('d-none'); 

                // Verificamos si ya tiene crédito para saber si será una Suma/Ampliación
                const creditosActivos = creditosRaw.filter(c => c.id_cliente == cli.id_cliente && c.estado_credito === 'Activo');
                montoActualCliente = creditosActivos.length > 0 ? parseFloat(creditosActivos[0].monto_credito) : 0;

                const lbl = document.getElementById("lbl_tipo_cliente");
                const inputMonto = document.getElementById("monto_credito");
                const refDC = document.getElementById("referencia_datacredito");
                const secBypass = document.getElementById("sec_bypass");
                
                if(cli.tipo_persona === 'Juridica') {
                    lbl.innerHTML = '<i class="fas fa-building text-primary"></i> Cliente Empresarial - Exento de DataCrédito';
                    inputMonto.readOnly = false;
                    inputMonto.classList.remove('bg-light');
                    refDC.value = 'EXENTO-JURIDICA';
                    secBypass.classList.add('d-none');
                    document.getElementById('chk_bypass').checked = false;
                    document.getElementById('contenedor_consultas_api').classList.add('d-none');
                } else {
                    lbl.innerHTML = '<i class="fas fa-user text-secondary"></i> Persona Física - Requiere DataCrédito o Aprobación Admin';
                    inputMonto.readOnly = true;
                    inputMonto.classList.add('bg-light');
                    refDC.value = '';
                    secBypass.classList.remove('d-none');
                    buscarConsultasDataCredito(cli.id_cliente);
                }
                evaluarSeguridad();
            };
            listaClientes.appendChild(li);
        });
    } else {
        listaClientes.classList.remove('d-none');
        listaClientes.innerHTML = '<li class="list-group-item text-muted">No se encontraron clientes...</li>';
        inputIdCliente.value = '';
    }
});

document.addEventListener('click', function(e) {
    if (!buscador.contains(e.target) && !listaClientes.contains(e.target)) {
        listaClientes.classList.add('d-none');
    }
});

function mostrarModalError(mensaje) {
    document.getElementById('modal_error_mensaje').innerText = mensaje;
    document.getElementById('custom_modal_error').classList.remove('d-none');
}

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
        if(cr.estado_credito === 'Pagado') colorBadge = 'bg-info text-dark';
        
        const fila = document.createElement("tr");
        fila.innerHTML = `
            <td>${cr.id_credito}</td>
            <td class="fw-bold">${cr.nombre_cliente}</td>
            <td>${cr.cedula}</td>
            <td class="text-primary fw-bold">${formatoMoneda.format(cr.monto_credito)}</td>
            <td class="text-danger fw-bold">${formatoMoneda.format(cr.saldo_pendiente)}</td>
            <td>${cr.fecha_vencimiento}</td>
            <td><span class="badge rounded-pill ${colorBadge}">${cr.estado_credito}</span></td>
            <td class="text-center">
                <button type="button" class="btn btn-warning btn-sm me-1" onclick="editarRegistro(${cr.id_credito})" title="Modificar Condiciones">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="abrirModalEliminar(${cr.id_credito})" title="Eliminar Crédito">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    });
}

document.getElementById('filtro').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase();
    const resultados = creditosRaw.filter(cr => {
        return cr.nombre_cliente.toLowerCase().includes(busqueda) || cr.cedula.includes(busqueda);
    });
    renderizarTabla(resultados);
});

// === ABRIR MODAL DE ELIMINAR ===
window.abrirModalEliminar = function(id) {
    document.getElementById("id_credito_eliminar").value = id;
    document.getElementById("admin_username_eliminar").value = "";
    document.getElementById("admin_password_eliminar").value = "";
    
    let modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
};

function cerrarModalEliminar() {
    let modalEl = document.getElementById('modalEliminar');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if(modal) modal.hide();
}

window.editarRegistro = function(id) {
    const cr = creditosRaw.find(c => c.id_credito == id);
    if (!cr) return;

    document.getElementById("id_oculto").value = cr.id_credito;
    
    document.getElementById("id_cliente").value = cr.id_cliente;
    document.getElementById("buscador_cliente").value = cr.nombre_cliente;
    
    document.getElementById("monto_credito").value = cr.monto_credito;
    document.getElementById("monto_credito").readOnly = false;
    document.getElementById("monto_credito").classList.remove('bg-light');
    
    document.getElementById("referencia_datacredito").value = cr.referencia_datacredito;
    document.getElementById("fecha_vencimiento").value = cr.fecha_vencimiento.split(' ')[0]; 
    
    document.getElementById("contenedor_estado_credito").classList.remove("d-none");
    document.getElementById("estado_credito").value = cr.estado_credito;

    document.getElementById("buscador_cliente").disabled = true;
    document.getElementById("sec_bypass").classList.add("d-none");

    document.getElementById("btnGuardar").textContent = "Actualizar Crédito";
    modoEdicion = true;
    montoActualCliente = parseFloat(cr.monto_credito); 

    evaluarSeguridad();

    document.getElementById("formulario").scrollIntoView({ behavior: "smooth" });
};

document.getElementById("formulario").addEventListener("submit", function(e) {
    e.preventDefault();

    const idCliente = document.getElementById("id_cliente").value;
    if (idCliente === '') {
        alert("Debe seleccionar un cliente de la lista desplegable.");
        document.getElementById('buscador_cliente').focus();
        return;
    }

    document.getElementById("buscador_cliente").disabled = false; 

    const formData = new FormData(this);
    
    if (modoEdicion) document.getElementById("buscador_cliente").disabled = true;

    let url = modoEdicion
        ? "/Taller/Taller-Mecanica/modules/Cliente/Archivo_Credito.php?action=actualizar"
        : "/Taller/Taller-Mecanica/modules/Cliente/Archivo_Credito.php?action=guardar";

    fetch(url, { method: "POST", body: formData })
    .then(res => {
        if (!res.ok) throw new Error("Error de red o servidor: " + res.status);
        return res.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            limpiarFormulario();
            cargarTabla(); 
        } else {
            mostrarModalError(data.message);
        }
    })
    .catch(err => {
        console.error("Falla en la petición de guardar/editar:", err);
        mostrarModalError("Ocurrió un error de conexión en el servidor.");
    });
});

const selectEstado = document.getElementById('estado_credito');
selectEstado.addEventListener('mousedown', (e) => e.preventDefault()); 
selectEstado.addEventListener('keydown', (e) => e.preventDefault());

document.addEventListener("DOMContentLoaded", function() {
    const inputMonto = document.getElementById('monto_credito');
    if (inputMonto) {
        inputMonto.addEventListener('keydown', function(e) {
            if (['e', 'E', '+', '-'].includes(e.key)) { e.preventDefault(); }
        });
        inputMonto.addEventListener('input', function(e) {
            let valor = this.value.replace(/[^0-9.]/g, '');
            const partes = valor.split('.');
            if (partes.length > 2) {
                valor = partes[0] + '.' + partes.slice(1).join('').replace(/\./g, '');
            }
            if (partes.length === 2 && partes[1].length > 2) {
                valor = partes[0] + '.' + partes[1].substring(0, 2);
            }
            this.value = valor;
        });
    }
});

// ==========================================
// FUNCIONES HÍBRIDAS PARA MODALES
// ==========================================
function abrirModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try {
        if (typeof bootstrap !== 'undefined') {
            let m = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
            m.show();
        } else { throw new Error(); }
    } catch (e) {
        el.classList.add('show'); el.style.display = 'block';
        el.setAttribute('aria-modal', 'true'); el.setAttribute('role', 'dialog');
        document.body.classList.add('modal-open'); document.body.style.overflow = 'hidden';
        
        document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
        const b = document.createElement('div'); b.id = 'm-bd-' + id; b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
    }
}

function cerrarModalUI(id) {
    const el = document.getElementById(id);
    if(!el) return;
    try { if (typeof bootstrap !== 'undefined') { let m = bootstrap.Modal.getInstance(el); if (m) m.hide(); } } catch (e) {}
    el.classList.remove('show'); el.style.display = 'none';
    el.removeAttribute('aria-modal'); el.removeAttribute('role');
    document.body.classList.remove('modal-open'); document.body.style.overflow = '';
    
    const b = document.getElementById('m-bd-' + id); if(b) b.remove();
    document.querySelectorAll('.modal-backdrop').forEach(mb => mb.remove());
}