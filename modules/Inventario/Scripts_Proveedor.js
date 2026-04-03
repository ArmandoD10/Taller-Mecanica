// Memoria para los selectores anidados
let cacheProvincias = []; 
let cacheCiudades = []; 

document.addEventListener("DOMContentLoaded", () => {
    listar();
    cargarDependencias();

    // ==========================================
    // LÓGICA DINÁMICA: FÍSICA VS JURÍDICA
    // ==========================================
    const selectTipoPersona = document.getElementById('tipo_persona');
    const inputRNC = document.getElementById('RNC');
    const inputCedula = document.getElementById('cedula');
    
    selectTipoPersona.addEventListener('change', function() {
        aplicarLogicaTipoPersona(this.value);
    });

    // ==========================================
    // MÁSCARAS Y AUTOCOMPLETADO
    // ==========================================
    function formatCedula(e) {
        if (e.inputType === 'deleteContentBackward') {
            return; 
        }
        
        let val = this.value.replace(/\D/g, ''); 
        
        if (val.length > 3 && val.length <= 10) {
            val = val.substring(0, 3) + '-' + val.substring(3);
        } else if (val.length > 10) {
            val = val.substring(0, 3) + '-' + val.substring(3, 10) + '-' + val.substring(10, 11);
        }
        
        this.value = val;
    }

    inputCedula.addEventListener('input', formatCedula);
    
    inputRNC.addEventListener('input', function(e) {
        if (selectTipoPersona.value === 'Fisica') {
            formatCedula.call(this, e);
            inputCedula.value = this.value; 
        } else {
            this.value = this.value.replace(/[^0-9-]/g, ''); 
        }
    });

    // Máscara Teléfono (000)-000-0000
    document.getElementById('numero_telefono').addEventListener('input', function(e) {
        if (e.inputType === 'deleteContentBackward') {
            return; 
        }
        
        let val = this.value.replace(/\D/g, ''); 
        
        if (val.length === 0) {
            this.value = '';
            return;
        }

        if (val.length <= 3) {
            val = '(' + val;
        } else if (val.length <= 6) {
            val = '(' + val.substring(0, 3) + ')-' + val.substring(3);
        } else {
            val = '(' + val.substring(0, 3) + ')-' + val.substring(3, 6) + '-' + val.substring(6, 10);
        }
        
        this.value = val;
    });

    // ==========================================
    // LÓGICA: SELECTOR ANIDADO DE 3 NIVELES
    // ==========================================
    document.getElementById('id_pais_dir').addEventListener('change', function() {
        cargarProvinciasSelect(this.value);
    });

    document.getElementById('id_provincia').addEventListener('change', function() {
        cargarCiudadesSelect(this.value);
    });

    // ==========================================
    // GUARDADO (PROVEEDOR Y TELÉFONOS)
    // ==========================================
    document.getElementById("formProveedor").addEventListener("submit", function(e) {
        e.preventDefault();
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=guardar", {
            method: "POST", 
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) { 
                cerrarModalUI('modalProveedor'); 
                listar(); 
                alert(data.message); 
            } else { 
                alert(data.message); 
            }
        })
        .catch(error => {
            console.error("Error al guardar:", error);
            alert("Error de conexión con el servidor.");
        });
    });

    document.getElementById("formTelefono").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const id_proveedor = document.getElementById("prov_tel_id").value;
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=guardar_telefono", {
            method: "POST", 
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                cancelarEdicionTelefono();
                listarTelefonos(id_proveedor); 
                listar(); 
            } else { 
                alert(data.message); 
            }
        })
        .catch(error => {
            console.error("Error al guardar teléfono:", error);
            alert("Error de conexión con el servidor.");
        });
    });

    // ==========================================
    // EVENTOS PARA CERRAR LOS MODALES
    // ==========================================
    const botonesCerrar = document.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
    
    botonesCerrar.forEach(btn => {
        btn.addEventListener('click', (e) => { 
            e.preventDefault(); 
            cerrarModalUI('modalProveedor');
            cerrarModalUI('modalTelefonos');
        });
    });
});

// ==========================================
// FUNCIONES DE CASCADA PARA GEOGRAFÍA
// ==========================================
function cargarProvinciasSelect(id_pais) {
    const selectProv = document.getElementById("id_provincia");
    const selectCiu = document.getElementById("id_ciudad");
    
    selectProv.innerHTML = '<option value="">Seleccione provincia...</option>';
    selectCiu.innerHTML = '<option value="">Primero seleccione provincia...</option>';
    selectCiu.disabled = true;
    
    if (!id_pais) {
        selectProv.disabled = true;
        selectProv.innerHTML = '<option value="">Primero seleccione país...</option>';
        return;
    }
    
    selectProv.disabled = false;
    
    const filtradas = cacheProvincias.filter(p => p.id_pais == id_pais);
    
    filtradas.forEach(p => {
        selectProv.innerHTML += `<option value="${p.id_provincia}">${p.nombre}</option>`;
    });
}

function cargarCiudadesSelect(id_provincia) {
    const selectCiudad = document.getElementById("id_ciudad");
    selectCiudad.innerHTML = '<option value="">Seleccione ciudad...</option>';
    
    if (!id_provincia) {
        selectCiudad.disabled = true;
        selectCiudad.innerHTML = '<option value="">Primero seleccione provincia...</option>';
        return;
    }
    
    selectCiudad.disabled = false;
    
    const filtradas = cacheCiudades.filter(c => c.id_provincia == id_provincia);
    
    filtradas.forEach(c => {
        selectCiudad.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`;
    });
}

// ==========================================
// FUNCIONES VISUALES DEL FORMULARIO
// ==========================================
function aplicarLogicaTipoPersona(tipo) {
    const tituloPersona = document.getElementById('titulo_seccion_persona');
    const lblNombreComercial = document.getElementById('lbl_nombre_comercial');
    const inputNombreComercial = document.getElementById('nombre_comercial');
    const lblRNC = document.getElementById('lbl_rnc');
    const inputRNC = document.getElementById('RNC');
    const lblCedulaRep = document.getElementById('lbl_cedula_rep');
    const inputCedula = document.getElementById('cedula');
    const lblFechaNac = document.getElementById('lbl_fecha_nac');

    if (tipo === 'Fisica') {
        tituloPersona.innerHTML = "2. Datos del Proveedor (Persona Física)";
        lblNombreComercial.innerHTML = "Nombre Comercial <span class='text-muted fw-normal'>(Opcional)</span>";
        inputNombreComercial.required = false;
        lblRNC.innerHTML = "RNC (Cédula Registrada) <span class='text-danger'>*</span>";
        
        lblCedulaRep.innerHTML = "Cédula Identidad";
        inputCedula.setAttribute("readonly", true);
        inputCedula.classList.add("bg-light"); 
        
        let val = inputRNC.value.replace(/\D/g, '');
        if (val.length > 3) {
            val = val.substring(0,3) + '-' + val.substring(3);
        }
        if (val.length > 10) {
            val = val.substring(0,11) + '-' + val.substring(11,12);
        }
        inputRNC.value = val;
        inputCedula.value = val;
        
        lblFechaNac.innerHTML = "Fecha Nacimiento <span class='text-danger'>*</span>";
        
    } else {
        tituloPersona.innerHTML = "2. Datos del Representante Legal";
        lblNombreComercial.innerHTML = "Nombre de la Empresa <span class='text-danger'>*</span>";
        inputNombreComercial.required = true;
        lblRNC.innerHTML = "RNC de la Empresa <span class='text-danger'>*</span>";
        
        lblCedulaRep.innerHTML = "Cédula Representante Legal";
        inputCedula.removeAttribute("readonly");
        inputCedula.classList.remove("bg-light");
        
        if (inputRNC.value === inputCedula.value) {
            inputCedula.value = ""; 
        }
        
        lblFechaNac.innerHTML = "Fecha de Constitución <span class='text-danger'>*</span>";
    }
}

// ==========================================
// CRUD PRINCIPAL DE PROVEEDOR
// ==========================================
function listar() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=listar")
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTabla");
        tbody.innerHTML = "";

        if (data.success && data.data.length > 0) {
            data.data.forEach(p => {
                let badgeEstado = p.estado === "activo" ? "text-success fw-bold" : "text-muted text-decoration-line-through";
                let badgeTipo = p.tipo_persona === "Fisica" ? "bg-info text-dark" : "bg-primary text-white";
                let badgeTel = p.total_telefonos > 0 ? "bg-dark" : "bg-secondary text-white-50";

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td><span class="badge ${badgeTipo}">${p.tipo_persona}</span></td>
                    <td class="fw-bold">${p.nombre_comercial}</td>
                    <td>${p.representante}</td>
                    <td>${p.RNC || 'N/A'}</td>
                    <td><span class="badge ${badgeTel} rounded-pill fs-6"><i class="fas fa-phone-alt me-1"></i>${p.total_telefonos}</span></td>
                    <td class="${badgeEstado}">${p.estado.toUpperCase()}</td>
                    <td class="text-center">
                        <button class="btn btn-dark btn-sm me-1" onclick="abrirGestionTelefonos(${p.id_proveedor}, '${p.nombre_comercial}')" title="Teléfonos">
                            <i class="fas fa-phone"></i>
                        </button>
                        <button class="btn btn-warning btn-sm text-white" onclick="editar(${p.id_proveedor})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="eliminar(${p.id_proveedor})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No hay proveedores registrados.</td></tr>`;
        }
    })
    .catch(error => console.error("Error al listar proveedores:", error));
}

function cargarDependencias() {
    fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=cargar_dependencias")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const selectPais = document.getElementById("nacionalidad");
            const selectPaisDir = document.getElementById("id_pais_dir");
            
            data.data.paises.forEach(p => {
                selectPais.innerHTML += `<option value="${p.id_pais}">${p.nombre}</option>`;
                selectPaisDir.innerHTML += `<option value="${p.id_pais}">${p.nombre}</option>`;
            });
            
            cacheProvincias = data.data.provincias;
            cacheCiudades = data.data.ciudades;
        }
    })
    .catch(error => console.error("Error al cargar dependencias:", error));
}

function nuevoProveedor() {
    document.getElementById("formProveedor").reset();
    
    // Limpiamos los IDs ocultos
    document.getElementById("id_proveedor").value = "";
    document.getElementById("id_persona").value = "";
    document.getElementById("id_direccion").value = "";
    document.getElementById("estado").value = "activo";
    
    // Reseteamos las opciones de cascada
    document.getElementById("id_pais_dir").value = "";
    
    document.getElementById("id_provincia").innerHTML = '<option value="">Primero seleccione país...</option>';
    document.getElementById("id_provincia").disabled = true;
    
    document.getElementById("id_ciudad").innerHTML = '<option value="">Primero seleccione provincia...</option>';
    document.getElementById("id_ciudad").disabled = true;

    // Forzamos visualmente a que inicie en "Fisica"
    document.getElementById("tipo_persona").value = "Fisica";
    aplicarLogicaTipoPersona("Fisica"); 
    
    // Ponemos la fecha de hoy por defecto
    document.getElementById("fecha_nacimiento").value = new Date().toISOString().split('T')[0];
    
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Proveedor';
    
    abrirModalUI('modalProveedor');
}

function editar(id) {
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-edit me-2"></i>Editar Proveedor';
    
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=obtener&id_proveedor=${id}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const d = data.data;
            
            document.getElementById("id_proveedor").value = d.id_proveedor;
            document.getElementById("id_persona").value = d.id_persona;
            document.getElementById("id_direccion").value = d.id_direccion;
            
            document.getElementById("tipo_persona").value = d.tipo_persona;
            aplicarLogicaTipoPersona(d.tipo_persona);

            document.getElementById("nombre_comercial").value = d.nombre_comercial;
            document.getElementById("RNC").value = d.RNC;
            document.getElementById("correo").value = d.correo;
            
            document.getElementById("nombre_persona").value = d.nombre;
            document.getElementById("apellido_p").value = d.apellido_p;
            document.getElementById("cedula").value = d.cedula;
            document.getElementById("nacionalidad").value = d.nacionalidad;
            document.getElementById("fecha_nacimiento").value = d.fecha_nacimiento;
            
            // Selección en cascada
            document.getElementById("id_pais_dir").value = d.id_pais_dir;
            cargarProvinciasSelect(d.id_pais_dir);
            
            document.getElementById("id_provincia").value = d.id_provincia;
            cargarCiudadesSelect(d.id_provincia);
            
            document.getElementById("id_ciudad").value = d.id_ciudad; 
            
            document.getElementById("descripcion_dir").value = d.descripcion_dir;
            document.getElementById("estado").value = d.estado;
            
            abrirModalUI('modalProveedor');
        }
    })
    .catch(error => console.error("Error al obtener los datos del proveedor:", error));
}

function eliminar(id) {
    if (confirm("¿Está seguro que desea dar de baja este proveedor? Sus registros de compras se mantendrán en el historial.")) {
        const f = new FormData(); 
        f.append("id_proveedor", id);
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=eliminar", {
            method: "POST", 
            body: f
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                listar();
            }
        })
        .catch(error => console.error("Error al eliminar proveedor:", error));
    }
}

// ==========================================
// FUNCIONES DE TELÉFONOS
// ==========================================
function abrirGestionTelefonos(id_proveedor, nombre_proveedor) {
    document.getElementById("prov_tel_id").value = id_proveedor;
    document.getElementById("tituloProveedorTel").textContent = nombre_proveedor.toUpperCase();
    
    cancelarEdicionTelefono(); 
    listarTelefonos(id_proveedor);
    
    abrirModalUI('modalTelefonos');
}

function listarTelefonos(id_proveedor) {
    fetch(`/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=listar_telefonos&id_proveedor=${id_proveedor}`)
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById("cuerpoTablaTelefonos");
        tbody.innerHTML = "";
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(t => {
                let colorEstado = t.estado === 'activo' ? 'text-success' : 'text-danger';
                
                tbody.innerHTML += `
                    <tr>
                        <td class="fs-5 fw-bold text-dark"><i class="fas fa-phone-alt me-2 text-muted fs-6"></i>${t.numero}</td>
                        <td class="fw-bold ${colorEstado}">${t.estado.toUpperCase()}</td>
                        <td>
                            <button type="button" class="btn btn-outline-warning btn-sm me-1" onclick="editarTelefono(${t.id_telefono}, '${t.numero}', '${t.estado}')" title="Modificar">
                                <i class="fas fa-edit"></i> Modificar
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarTelefono(${t.id_telefono}, ${id_proveedor})" title="Desvincular">
                                <i class="fas fa-unlink"></i> Quitar
                            </button>
                        </td>
                    </tr>`;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="3" class="text-muted py-3">Este proveedor no tiene teléfonos registrados.</td></tr>`;
        }
    })
    .catch(error => console.error("Error al listar teléfonos:", error));
}

function editarTelefono(id_telefono, numero, estado) {
    document.getElementById('id_telefono_edit').value = id_telefono;
    document.getElementById('numero_telefono').value = numero;
    document.getElementById('estado_telefono').value = estado;
    
    const btnGuardar = document.getElementById('btnGuardarTelefono');
    btnGuardar.innerHTML = '<i class="fas fa-save me-1"></i>Actualizar';
    btnGuardar.classList.replace('btn-primary', 'btn-success');
    
    document.getElementById('btnCancelarEdicionTel').classList.remove('d-none');
}

function cancelarEdicionTelefono() {
    document.getElementById('id_telefono_edit').value = '';
    document.getElementById('numero_telefono').value = '';
    document.getElementById('estado_telefono').value = 'activo';
    
    const btnGuardar = document.getElementById('btnGuardarTelefono');
    btnGuardar.innerHTML = '<i class="fas fa-plus me-1"></i>Guardar';
    btnGuardar.classList.replace('btn-success', 'btn-primary');
    
    document.getElementById('btnCancelarEdicionTel').classList.add('d-none');
}

function eliminarTelefono(id_telefono, id_proveedor) {
    if(confirm("¿Seguro de quitar este teléfono del proveedor?")) {
        const f = new FormData(); 
        f.append("id_telefono", id_telefono);
        f.append("id_proveedor", id_proveedor);
        
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=eliminar_telefono", {
            method: "POST", 
            body: f
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                listarTelefonos(id_proveedor);
                listar(); 
            } else {
                alert(data.message);
            }
        })
        .catch(error => console.error("Error al eliminar el teléfono:", error));
    }
}

// ==========================================
// FUNCIONES DEL MODAL A PRUEBA DE FALLOS
// ==========================================
function abrirModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) { 
            $('#' + idModal).modal('show'); 
            return; 
        }
        
        if (typeof bootstrap !== 'undefined') {
            let mod = bootstrap.Modal.getInstance(modalElement);
            if (!mod) {
                mod = new bootstrap.Modal(modalElement); 
            }
            mod.show(); 
            return;
        }
        
        throw new Error("Librerías de Bootstrap no detectadas");
        
    } catch (e) {
        modalElement.classList.add('show'); 
        modalElement.style.display = 'block'; 
        document.body.classList.add('modal-open');
        
        if (!document.getElementById('fondo-oscuro-modal')) {
            const backdrop = document.createElement('div'); 
            backdrop.className = 'modal-backdrop fade show'; 
            backdrop.id = 'fondo-oscuro-modal';
            document.body.appendChild(backdrop);
        }
    }
}

function cerrarModalUI(idModal) {
    const modalElement = document.getElementById(idModal);
    
    if (!modalElement) return;
    
    modalElement.classList.remove('show'); 
    modalElement.style.display = 'none'; 
    document.body.classList.remove('modal-open');
    
    const backdrop = document.getElementById('fondo-oscuro-modal'); 
    
    if (backdrop) {
        backdrop.remove(); 
    }
    
    if (typeof $ !== 'undefined' && $.fn.modal) { 
        $('#' + idModal).modal('hide'); 
    }
}