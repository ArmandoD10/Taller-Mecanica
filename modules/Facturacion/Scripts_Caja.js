document.addEventListener("DOMContentLoaded", () => {
    verificarEstadoCaja();
    cargarHistorialCaja();

    // ==========================================
    // APERTURA DE CAJA
    // ==========================================
    document.getElementById("formApertura").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const btn = document.getElementById("btnAbrir");
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando...';

        const formData = new FormData(this);

        fetch('/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Caja.php?action=abrir_caja', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("✅ Caja abierta correctamente");
                verificarEstadoCaja();
                cargarHistorialCaja();
            } else {
                alert("❌ " + data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-unlock-alt me-2"></i> Abrir Caja';
            }
        })
        .catch(err => {
            alert("Error de conexión.");
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-unlock-alt me-2"></i> Abrir Caja';
        });
    });

    // ==========================================
    // CIERRE DE CAJA
    // ==========================================
    document.getElementById("formCierre").addEventListener("submit", function(e) {
        e.preventDefault();
        
        if(!confirm("¿Está seguro de cerrar el turno? Ya no podrá facturar hasta abrir una nueva caja.")) return;

        const btn = document.getElementById("btnCerrarTurno");
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Cerrando...';

        const formData = new FormData(this);

        fetch('/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Caja.php?action=cerrar_caja', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const diff = parseFloat(data.diferencia);
                let msjCuadre = "";

                if (diff === 0) {
                    msjCuadre = "✅ CUADRE PERFECTO. La caja cerró exacta.";
                } else if (diff > 0) {
                    msjCuadre = `⚠️ SOBRANTE DE CAJA. Hay un sobrante de RD$ ${diff.toLocaleString(undefined, {minimumFractionDigits: 2})}.`;
                } else {
                    msjCuadre = `❌ FALTANTE DE CAJA. Faltan RD$ ${Math.abs(diff).toLocaleString(undefined, {minimumFractionDigits: 2})}.`;
                }

                alert(data.message + "\n\n" + msjCuadre);
                
                cerrarModalCierreDefinitivo();
                verificarEstadoCaja();
                cargarHistorialCaja();

            } else {
                alert("❌ Error al cerrar: " + data.message);
                btn.disabled = false;
                btn.innerHTML = 'Confirmar Cierre Definitivo';
            }
        })
        .catch(err => {
            alert("Error de conexión durante el cierre.");
            btn.disabled = false;
            btn.innerHTML = 'Confirmar Cierre Definitivo';
        });
    });
});

function verificarEstadoCaja() {
    const panelCerrada = document.getElementById("panelCajaCerrada");
    const panelAbierta = document.getElementById("panelCajaAbierta");
    const loader = document.getElementById("loaderCaja");

    panelCerrada.classList.add("d-none");
    panelAbierta.classList.add("d-none");
    loader.classList.remove("d-none");

    fetch('/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Caja.php?action=verificar_estado')
    .then(res => res.json())
    .then(data => {
        loader.classList.add("d-none");
        if (data.success) {
            if (data.estado === 'Abierta') {
                document.getElementById("lbl_usuario_caja").innerText = data.data.username;
                document.getElementById("lbl_fecha_caja").innerText = data.data.fecha_apertura_fmt;
                document.getElementById("lbl_monto_caja").innerText = `RD$ ${parseFloat(data.data.monto_inicial).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
                panelAbierta.classList.remove("d-none");
            } else {
                document.getElementById("formApertura").reset();
                const btn = document.getElementById("btnAbrir");
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-unlock-alt me-2"></i> Abrir Caja';
                
                panelCerrada.classList.remove("d-none");
            }
        } else {
            alert("Error consultando estado de caja: " + data.message);
        }
    })
    .catch(err => {
        loader.innerHTML = `<div class="alert alert-danger text-center">Error de comunicación con el servidor.</div>`;
    });
}

function prepararCierre() {
    document.getElementById("formCierre").reset();
    const btn = document.getElementById("btnCerrarTurno");
    btn.disabled = false;
    btn.innerHTML = 'Confirmar Cierre Definitivo';
}

function cerrarModalCierreDefinitivo() {
    const modalEl = document.getElementById('modalCierreCaja');
    if (typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
    
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
}

function cargarHistorialCaja() {
    fetch('/Taller/Taller-Mecanica/modules/Facturacion/Archivo_Caja.php?action=listar_historial')
    .then(res => res.json())
    .then(res => {
        const tbody = document.getElementById("tbodyHistorialCaja");
        if(!tbody) return;
        tbody.innerHTML = "";
        
        if(res.success && res.data.length > 0) {
            res.data.forEach(c => {
                let badgeEst = c.estado === 'Abierta' 
                    ? '<span class="badge bg-success-subtle text-success border border-success">Abierta</span>' 
                    : '<span class="badge bg-secondary">Cerrada</span>';
                
                let badgeDiff = "-";
                if (c.estado === 'Cerrada') {
                    const diff = parseFloat(c.diferencia);
                    if (diff === 0) {
                        badgeDiff = `<span class="badge bg-success"><i class="fas fa-check"></i> Exacto</span>`;
                    } else if (diff > 0) {
                        badgeDiff = `<span class="badge bg-warning text-dark">+ RD$ ${diff.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>`;
                    } else {
                        badgeDiff = `<span class="badge bg-danger">- RD$ ${Math.abs(diff).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>`;
                    }
                }

                let cierreFmt = c.cierre ? c.cierre : '<span class="text-muted small"><i>Turno en curso...</i></span>';
                let contadoFmt = c.monto_cierre ? `RD$ ${parseFloat(c.monto_cierre).toLocaleString(undefined, {minimumFractionDigits: 2})}` : '-';
                let notaIcon = c.notas ? `<i class="fas fa-comment-dots text-info ms-2" title="${c.notas}" style="cursor:help;"></i>` : '';

                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold text-dark">TRN-${c.id_sesion} ${notaIcon}</td>
                        <td class="fw-bold">${c.username}</td>
                        <td class="small">${c.apertura}</td>
                        <td class="small">${cierreFmt}</td>
                        <td class="text-success fw-bold">RD$ ${parseFloat(c.monto_inicial).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td class="fw-bold text-primary">${contadoFmt}</td>
                        <td>${badgeDiff}</td>
                        <td>${badgeEst}</td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="py-4 text-muted">No hay turnos registrados en el historial.</td></tr>';
        }
    })
    .catch(err => console.error("Error al cargar el historial de caja: ", err));
}