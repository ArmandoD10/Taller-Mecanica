document.addEventListener("DOMContentLoaded", () => {
    cargarGarantias();
});

function cargarGarantias() {
    const tbody = document.getElementById("cuerpoTablaGarantias");
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Cargando datos...</td></tr>`;

    // RUTA ACTUALIZADA A LA CARPETA TALLER
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Garantia.php?action=listar")
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";
        let activas = 0, vencidas = 0, anuladas = 0;

        if (data.success && data.data.length > 0) {
            data.data.forEach(g => {
                let badgeEstado = "";
                
                if (g.estado_real === 'Activa') {
                    badgeEstado = `<span class="badge bg-success shadow-sm"><i class="fas fa-check-circle me-1"></i> Activa</span>`;
                    activas++;
                } else if (g.estado_real === 'Vencida') {
                    badgeEstado = `<span class="badge bg-warning text-dark shadow-sm"><i class="fas fa-clock me-1"></i> Vencida</span>`;
                    vencidas++;
                } else if (g.estado_real === 'Anulada') {
                    badgeEstado = `<span class="badge bg-danger shadow-sm"><i class="fas fa-ban me-1"></i> Anulada</span>`;
                    anuladas++;
                }

                let btnAnular = (g.estado_real === 'Activa') 
                    ? `<button class="btn btn-sm btn-outline-danger" onclick="prepararAnulacion(${g.id_garantia}, '${g.codigo_certificado}')" title="Anular Garantía"><i class="fas fa-ban"></i></button>`
                    : `<button class="btn btn-sm btn-outline-secondary disabled" title="Ya está inactiva"><i class="fas fa-ban"></i></button>`;

                const tr = document.createElement("tr");
                tr.className = "fila-garantia";
                tr.innerHTML = `
                    <td class="fw-bold text-success">${g.codigo_certificado}</td>
                    <td class="text-start fw-bold text-truncate" style="max-width: 150px;" title="${g.cliente}">${g.cliente}</td>
                    <td class="text-start small text-truncate" style="max-width: 180px;" title="${g.vehiculo}">${g.vehiculo}</td>
                    <td class="small">${g.fecha_emision}</td>
                    <td class="small fw-bold text-danger">${g.fecha_vence_fmt} <br> <span class="text-muted" style="font-size: 10px;">o ${parseFloat(g.kilometraje_limite).toLocaleString()} Km</span></td>
                    <td>${badgeEstado}</td>
                    <td>
                        <div class="btn-group shadow-sm">
                            <button class="btn btn-sm btn-primary" onclick="reimprimirCertificado(${g.id_orden})" title="Reimprimir PDF"><i class="fas fa-print"></i></button>
                            ${btnAnular}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted fw-bold">No hay garantías registradas en el sistema.</td></tr>`;
        }

        document.getElementById("lbl_activas").innerText = activas;
        document.getElementById("lbl_vencidas").innerText = vencidas;
        document.getElementById("lbl_anuladas").innerText = anuladas;
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Error de conexión.</td></tr>`;
    });
}

function filtrarTabla() {
    const texto = document.getElementById("buscador_garantia").value.toLowerCase();
    const filas = document.querySelectorAll(".fila-garantia");

    filas.forEach(fila => {
        const contenido = fila.textContent.toLowerCase();
        fila.style.display = contenido.includes(texto) ? "" : "none";
    });
}

// LA VISTA DEL CERTIFICADO PUEDE QUEDARSE DONDE MISMO O MOVERLA A TALLER (AQUÍ ASUMO QUE LA DEJASTE EN Garantias COMO ESTABA)
function reimprimirCertificado(id_orden) {
    // Si moviste el CertificadoGarantia.php a la carpeta Taller, cambia esta ruta también a /view/Taller/...
    window.open(`/Taller/Taller-Mecanica/view/Garantias/CertificadoGarantia.php?id_orden=${id_orden}`, '_blank');
}

function prepararAnulacion(id_garantia, codigo) {
    document.getElementById("anular_id_garantia").value = id_garantia;
    document.getElementById("lbl_codigo_anular").innerText = codigo;
    document.getElementById("anular_motivo").value = "";
    
    new bootstrap.Modal(document.getElementById('modalAnular')).show();
}

function confirmarAnulacion() {
    const id = document.getElementById("anular_id_garantia").value;
    const motivo = document.getElementById("anular_motivo").value.trim();

    if (motivo.length < 10) {
        alert("Por favor, escriba un motivo detallado para la anulación (mínimo 10 caracteres).");
        return;
    }

    const fd = new FormData();
    fd.append("id_garantia", id);
    fd.append("motivo", motivo);

    // RUTA ACTUALIZADA A LA CARPETA TALLER
    fetch("/Taller/Taller-Mecanica/modules/Taller/Archivo_Garantia.php?action=anular", {
        method: "POST",
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalAnular')).hide();
            cargarGarantias(); 
        } else {
            alert(data.message);
        }
    })
    .catch(err => alert("Error al intentar anular la garantía."));
}