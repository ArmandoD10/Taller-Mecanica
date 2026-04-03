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
    // MÁSCARA DE CÉDULA (000-0000000-0)
    // ==========================================
    function formatCedula(e) {
        if (e.inputType === 'deleteContentBackward') return; // Permitir borrar libremente
        let val = this.value.replace(/\D/g, ''); // Quita letras y guiones
        
        if (val.length > 3 && val.length <= 10) {
            val = val.substring(0, 3) + '-' + val.substring(3);
        } else if (val.length > 10) {
            val = val.substring(0, 3) + '-' + val.substring(3, 10) + '-' + val.substring(10, 11);
        }
        this.value = val;
    }

    inputCedula.addEventListener('input', formatCedula);
    
    inputRNC.addEventListener('input', function(e) {
        // Solo aplica la máscara al RNC si es Persona Física (porque el RNC es su cédula)
        if (selectTipoPersona.value === 'Fisica') {
            formatCedula.call(this, e);
            inputCedula.value = this.value; // Sincroniza abajo
        } else {
            // Si es Jurídica, usualmente son 9 dígitos sin formato estricto
            this.value = this.value.replace(/[^0-9-]/g, ''); 
        }
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
    // GUARDADO
    // ==========================================
    document.getElementById("formProveedor").addEventListener("submit", function(e) {
        e.preventDefault();
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=guardar", {
            method: "POST", body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                cerrarModalUI(); listar(); alert(data.message);
            } else {
                alert(data.message);
            }
        });
    });

    document.querySelectorAll('#modalProveedor [data-bs-dismiss="modal"], #modalProveedor .btn-close').forEach(btn => {
        btn.addEventListener('click', (e) => { e.preventDefault(); cerrarModalUI(); });
    });
});

// --- FUNCIONES DE CASCADA ---
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

// --- FUNCIONES VISUALES ---
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
        
        // Aplica máscara si ya hay algo escrito
        let val = inputRNC.value.replace(/\D/g, '');
        if (val.length > 3) val = val.substring(0,3) + '-' + val.substring(3);
        if (val.length > 10) val = val.substring(0,11) + '-' + val.substring(11,12);
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
        if(inputRNC.value === inputCedula.value) inputCedula.value = ""; 
        
        lblFechaNac.innerHTML = "Fecha de Constitución <span class='text-danger'>*</span>";
    }
}

// --- CRUD BASE ---
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

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td><span class="badge ${badgeTipo}">${p.tipo_persona}</span></td>
                    <td class="fw-bold">${p.nombre_comercial}</td>
                    <td>${p.representante}</td>
                    <td>${p.RNC || 'N/A'}</td>
                    <td>${p.correo || 'N/A'}</td>
                    <td class="${badgeEstado}">${p.estado.toUpperCase()}</td>
                    <td class="text-center">
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
    });
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
    });
}

function nuevoProveedor() {
    document.getElementById("formProveedor").reset();
    document.getElementById("id_proveedor").value = "";
    document.getElementById("id_persona").value = "";
    document.getElementById("id_direccion").value = "";
    document.getElementById("estado").value = "activo";
    
    // Reset cascada
    document.getElementById("id_pais_dir").value = "";
    document.getElementById("id_provincia").innerHTML = '<option value="">Primero seleccione país...</option>';
    document.getElementById("id_provincia").disabled = true;
    document.getElementById("id_ciudad").innerHTML = '<option value="">Primero seleccione provincia...</option>';
    document.getElementById("id_ciudad").disabled = true;

    document.getElementById("tipo_persona").value = "Fisica";
    aplicarLogicaTipoPersona("Fisica"); 
    
    document.getElementById("fecha_nacimiento").value = new Date().toISOString().split('T')[0];
    
    document.getElementById("tituloModal").innerHTML = '<i class="fas fa-plus me-2"></i>Nuevo Proveedor';
    abrirModalUI();
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
            
            abrirModalUI();
        }
    });
}

function eliminar(id) {
    if (confirm("¿Está seguro que desea dar de baja este proveedor?")) {
        const f = new FormData(); f.append("id_proveedor", id);
        fetch("/Taller/Taller-Mecanica/modules/Inventario/Archivo_Proveedor.php?action=eliminar", {
            method: "POST", body: f
        }).then(res => res.json()).then(data => {
            alert(data.message);
            if(data.success) listar();
        });
    }
}

// ==========================================
// FUNCIONES DEL MODAL A PRUEBA DE FALLOS
// ==========================================
function abrirModalUI() {
    const m = document.getElementById('modalProveedor');
    try {
        if (typeof $ !== 'undefined' && $.fn.modal) { $('#modalProveedor').modal('show'); return; }
        if (typeof bootstrap !== 'undefined') {
            let mod = bootstrap.Modal.getInstance(m) || new bootstrap.Modal(m); mod.show(); return;
        } throw new Error("");
    } catch (e) {
        m.classList.add('show'); m.style.display = 'block'; document.body.classList.add('modal-open');
        if(!document.getElementById('fondo-oscuro-modal')){
            const b = document.createElement('div'); b.className = 'modal-backdrop fade show'; b.id = 'fondo-oscuro-modal';
            document.body.appendChild(b);
        }
    }
}

function cerrarModalUI() {
    const m = document.getElementById('modalProveedor');
    m.classList.remove('show'); m.style.display = 'none'; document.body.classList.remove('modal-open');
    const b = document.getElementById('fondo-oscuro-modal'); if(b) b.remove();
    if (typeof $ !== 'undefined' && $.fn.modal) { $('#modalProveedor').modal('hide'); }
}