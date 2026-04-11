document.addEventListener("DOMContentLoaded", () => {
    cargarGarantias();
});

function cargarGarantias() {
    const tbody = document.getElementById("cuerpoTablaGarantias");
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Cargando historial...</td></tr>`;

    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Garantia.php?action=listar")
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        let activas = 0, vencidas = 0, anuladas = 0;

        if (data.success && data.data.length > 0) {
            data.data.forEach(g => {
                let badgeEstado = "";
                if (g.estado_real === 'Activa') { badgeEstado = `<span class="badge bg-success shadow-sm">Activa</span>`; activas++; }
                else if (g.estado_real === 'Vencida') { badgeEstado = `<span class="badge bg-warning text-dark shadow-sm">Vencida</span>`; vencidas++; }
                else { badgeEstado = `<span class="badge bg-danger shadow-sm">Anulada</span>`; anuladas++; }

                let btnAnular = (g.estado_real === 'Activa') 
                    ? `<button class="btn btn-sm btn-outline-danger" onclick="prepararAnulacion(${g.id_garantia}, '${g.codigo_certificado}')" title="Anular"><i class="fas fa-ban"></i></button>`
                    : `<button class="btn btn-sm btn-outline-secondary disabled"><i class="fas fa-ban"></i></button>`;

                const tr = document.createElement("tr");
                tr.className = "fila-garantia";
                tr.innerHTML = `
                    <td class="fw-bold text-success">${g.codigo_certificado}</td>
                    <td class="text-start fw-bold">${g.cliente}</td>
                    <td class="text-start small">${g.vehiculo}</td>
                    <td class="small">${g.fecha_emision}</td>
                    <td class="small fw-bold text-danger">${g.fecha_vence_fmt} <br> <span class="text-muted" style="font-size: 10px;">o ${parseFloat(g.kilometraje_limite).toLocaleString()} Km</span></td>
                    <td>${badgeEstado}</td>
                    <td>
                        <div class="btn-group shadow-sm">
                            <button class="btn btn-sm btn-primary" onclick="reimprimirCertificado(${g.id_orden})"><i class="fas fa-print"></i></button>
                            ${btnAnular}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron garantías.</td></tr>`;
        }

        document.getElementById("lbl_activas").innerText = activas;
        document.getElementById("lbl_vencidas").innerText = vencidas;
        document.getElementById("lbl_anuladas").innerText = anuladas;
    })
    .catch(err => {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error al cargar datos.</td></tr>`;
    });
}

function prepararAnulacion(id, codigo) {
    document.getElementById("anular_id_garantia").value = id;
    document.getElementById("lbl_codigo_anular").innerText = codigo;
    document.getElementById("admin_user").value = "";
    document.getElementById("admin_pass").value = "";
    document.getElementById("anular_motivo").value = "";

    const modalEl = document.getElementById('modalAnular');
    try {
        let myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        myModal.show();
    } catch (e) {
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        document.body.classList.add('modal-open');
        if (!document.querySelector('.modal-backdrop')) {
            const b = document.createElement('div'); b.className = 'modal-backdrop fade show'; document.body.appendChild(b);
        }
    }
}

function cerrarModalAnular() {
    const modalEl = document.getElementById('modalAnular');
    try {
        let myModal = bootstrap.Modal.getInstance(modalEl);
        if (myModal) myModal.hide();
    } catch (e) {}
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    document.body.classList.remove('modal-open');
    const b = document.querySelector('.modal-backdrop');
    if (b) b.remove();
}

function confirmarAnulacion() {
    const id = document.getElementById("anular_id_garantia").value;
    const user = document.getElementById("admin_user").value.trim();
    const pass = document.getElementById("admin_pass").value.trim();
    const motivo = document.getElementById("anular_motivo").value.trim();

    if (!user || !pass) return alert("Ingrese credenciales de administrador.");
    if (motivo.length < 10) return alert("Escriba un motivo justificado (mín. 10 caracteres).");

    const fd = new FormData();
    fd.append("id_garantia", id);
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

function filtrarTabla() {
    const texto = document.getElementById("buscador_garantia").value.toLowerCase();
    const filas = document.querySelectorAll(".fila-garantia");
    filas.forEach(f => f.style.display = f.textContent.toLowerCase().includes(texto) ? "" : "none");
}

function reimprimirCertificado(id_orden) {
    window.open(`/Taller/Taller-Mecanica/view/Garantias/CertificadoGarantia.php?id_orden=${id_orden}`, '_blank');
}