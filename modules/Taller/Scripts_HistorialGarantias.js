let listaGarantiasGeneral = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarGarantias();
});

function cargarGarantias() {
    const tbody = document.getElementById("cuerpoTablaGarantias");
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Cargando historial...</td></tr>`;

    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Garantia.php?action=listar")
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            listaGarantiasGeneral = data.data;
            renderizarTablaGarantias(listaGarantiasGeneral);
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error: ${data.message}</td></tr>`;
        }
    })
    .catch(err => {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error de conexión al cargar datos.</td></tr>`;
    });
}

function renderizarTablaGarantias(datos) {
    const tbody = document.getElementById("cuerpoTablaGarantias");
    tbody.innerHTML = "";
    
    let activas = 0, anuladas = 0;

    if (datos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron garantías que coincidan con los filtros.</td></tr>`;
    } else {
        datos.forEach(g => {
            let badgeEstado = "";
            let filaAnulada = "";
            
            if (g.estado === 'activo') { 
                badgeEstado = `<span class="badge bg-success shadow-sm"><i class="fas fa-check-circle me-1"></i> Vigente</span>`; 
                activas++; 
            } else { 
                badgeEstado = `<span class="badge bg-danger shadow-sm"><i class="fas fa-ban me-1"></i> Anulada</span>`; 
                anuladas++; 
                filaAnulada = "opacity-50";
            }

            let btnAnular = (g.estado === 'activo') 
                ? `<button class="btn btn-sm btn-outline-danger" onclick="prepararAnulacion(${g.id_garantia}, ${g.id_orden}, '${g.codigo_certificado}')" title="Anular Certificado"><i class="fas fa-ban"></i></button>`
                : `<button class="btn btn-sm btn-outline-secondary disabled"><i class="fas fa-ban"></i></button>`;

            const tr = document.createElement("tr");
            tr.className = `fila-garantia ${filaAnulada}`;
            tr.innerHTML = `
                <td class="fw-bold text-dark">${g.codigo_certificado}</td>
                <td class="text-start fw-bold">${g.cliente}</td>
                <td class="text-start small">${g.vehiculo}</td>
                <td class="small">${g.fecha_emision}</td>
                <td class="fw-bold"><span class="badge bg-primary text-white border">${g.items_amparados} ítems</span></td>
                <td>${badgeEstado}</td>
                <td>
                    <div class="btn-group shadow-sm">
                        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="verDetalles(${g.id_orden}, '${g.codigo_certificado}')" title="Ver Ítems"><i class="fas fa-list"></i></button>
                        <button class="btn btn-sm btn-primary" onclick="reimprimirCertificado(${g.id_orden})" title="Imprimir"><i class="fas fa-print"></i></button>
                        ${btnAnular}
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Actualizar los contadores del Dashboard superior
    document.getElementById("lbl_activas").innerText = activas;
    document.getElementById("lbl_anuladas").innerText = anuladas;
}

function filtrarTabla() {
    const term = document.getElementById('filtroGeneral').value.toLowerCase().trim();
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    
    const filtrados = listaGarantiasGeneral.filter(g => {
        // Filtro por texto en Código, Cliente o Vehículo
        const matchTexto = (g.codigo_certificado || '').toLowerCase().includes(term) ||
                           (g.cliente || '').toLowerCase().includes(term) ||
                           (g.vehiculo || '').toLowerCase().includes(term);
        
        // Filtro por fecha (comparando cadenas YYYY-MM-DD de la base de datos)
        let matchFecha = true;
        if (desde && g.fecha_db < desde) matchFecha = false;
        if (hasta && g.fecha_db > hasta) matchFecha = false;

        return matchTexto && matchFecha;
    });
    
    renderizarTablaGarantias(filtrados);
}

function limpiarFiltros() {
    document.getElementById('filtroGeneral').value = "";
    document.getElementById('fechaDesde').value = "";
    document.getElementById('fechaHasta').value = "";
    renderizarTablaGarantias(listaGarantiasGeneral);
}

function verDetalles(id_orden, codigo) {
    document.getElementById("det_codigo_garantia").innerText = codigo;
    const tbody = document.getElementById("cuerpoDetallesGarantia");
    tbody.innerHTML = `<tr><td colspan="5" class="py-3 text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando coberturas...</td></tr>`;
    
    new bootstrap.Modal(document.getElementById('modalDetalleGarantia')).show();

    fetch(`/Taller/Taller-Mecanica/modules/Taller/Archivo_Garantia.php?action=ver_detalle&id_orden=${id_orden}`)
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        if(data.success && data.data.length > 0) {
            data.data.forEach(d => {
                let badgeLinea = d.estado_linea === 'Activa' 
                    ? `<span class="badge bg-success-subtle text-success">Vigente</span>` 
                    : `<span class="badge bg-danger-subtle text-danger">Vencida</span>`;

                tbody.innerHTML += `
                    <tr>
                        <td class="text-start ps-3 fw-bold small text-dark">${d.descripcion} <br><span class="badge bg-light text-muted border fw-normal">${d.tipo}</span></td>
                        <td class="small"><span class="badge bg-dark">${d.politica}</span></td>
                        <td class="small fw-bold text-danger">${d.vence_fecha}</td>
                        <td class="small fw-bold text-danger">${d.vence_km}</td>
                        <td>${badgeLinea}</td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="py-3 text-muted">Error cargando desglose o no existen ítems garantizados.</td></tr>`;
        }
    });
}

function prepararAnulacion(id_garantia, id_orden, codigo) {
    document.getElementById("anular_id_garantia").value = id_garantia;
    document.getElementById("anular_id_orden").value = id_orden;
    document.getElementById("lbl_codigo_anular").innerText = codigo;
    document.getElementById("admin_user").value = "";
    document.getElementById("admin_pass").value = "";
    document.getElementById("anular_motivo").value = "";

    const modalEl = document.getElementById('modalAnular');
    if (typeof bootstrap !== 'undefined') {
        let myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        myModal.show();
    }
}

function cerrarModalAnular() {
    const modalEl = document.getElementById('modalAnular');
    if (typeof bootstrap !== 'undefined') {
        let myModal = bootstrap.Modal.getInstance(modalEl);
        if (myModal) myModal.hide();
    }
}

function confirmarAnulacion() {
    const id_g = document.getElementById("anular_id_garantia").value;
    const id_o = document.getElementById("anular_id_orden").value;
    const user = document.getElementById("admin_user").value.trim();
    const pass = document.getElementById("admin_pass").value.trim();
    const motivo = document.getElementById("anular_motivo").value.trim();

    if (!user || !pass) return alert("Ingrese credenciales de administrador.");
    if (motivo.length < 10) return alert("Escriba un motivo justificado (mín. 10 caracteres).");

    const fd = new FormData();
    fd.append("id_garantia", id_g);
    fd.append("id_orden", id_o);
    fd.append("admin_user", user);
    fd.append("admin_pass", pass);
    fd.append("motivo", motivo);

    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Garantia.php?action=anular", { method: "POST", body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            cerrarModalAnular();
            cargarGarantias();
        } else {
            alert(data.message);
        }
    });
}

function reimprimirCertificado(id_orden) {
    window.open(`/Taller/Taller-Mecanica/view/Garantias/CertificadoGarantia.php?id_orden=${id_orden}`, '_blank');
}