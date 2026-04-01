let dataActual = {
    id_cliente: null,
    referencia: null,
    resultado: null
};

function solicitarConsulta() {
    const cedula = document.getElementById('cedula_consulta').value;
    if(!cedula) return alert("Ingrese una cédula");

    document.getElementById('reporte_final').classList.add('d-none');
    document.getElementById('msg_error').classList.add('d-none');
    document.getElementById('loader_datacredito').classList.remove('d-none');

    setTimeout(() => {
        fetch(`/Taller/Taller-Mecanica/modules/Cliente/Archivo_Apicredito.php?action=consultar_simulacion&cedula=${cedula}`)
        .then(res => res.json())
        .then(res => {
            document.getElementById('loader_datacredito').classList.add('d-none');
            
            if(res.success && res.data.length > 0) {
                // --- LINEAS CLAVE: Guardamos los datos para el botón "Guardar" ---
                dataActual.id_cliente = res.id_cliente_real;
                dataActual.referencia = res.referencia;
                
                // Mapeamos el score a los ENUM de tu tabla (Aprobado, Rechazado, Condicional)
                if(res.cliente.score >= 700) {
                    dataActual.resultado = 'Aprobado';
                } else if(res.cliente.score >= 600) {
                    dataActual.resultado = 'Condicional';
                } else {
                    dataActual.resultado = 'Rechazado';
                }
                // ---------------------------------------------------------------

                mostrarReporte(res.cliente, res.data);
            } else {
                // Si el PHP mandó un mensaje de error específico, lo mostramos
                if(res.message) alert(res.message); 
                document.getElementById('msg_error').classList.remove('d-none');
            }
        })
        .catch(err => {
            document.getElementById('loader_datacredito').classList.add('d-none');
            console.error("Error en la petición:", err);
        });
    }, 2000);
}


function mostrarReporte(cliente, cuentas) {
    const reporte = document.getElementById('reporte_final');
    const header = document.getElementById('datos_generales_cliente');
    const contenedor = document.getElementById('contenedor_cuentas');
    
    // 1. Limpiar contenedores previos
    contenedor.innerHTML = '';
    
    // 2. Llenar el Header con los datos de Api_DataCredito (Score y Riesgo)
    header.innerHTML = `
        <div class="col-md-3 border-end">
            <label class="text-muted small text-uppercase">Titular</label>
            <h5 class="fw-bold mb-0">${cliente.nombre}</h5>
            <span class="badge bg-light text-dark border">${cliente.cedula}</span>
        </div>
        <div class="col-md-3 border-end text-center">
            <label class="text-muted small text-uppercase">Score Crediticio</label>
            <h2 class="fw-bold ${cliente.score >= 700 ? 'text-success' : 'text-warning'} mb-0">${cliente.score}</h2>
            <small class="text-muted">Puntaje Actual</small>
        </div>
        <div class="col-md-3 border-end text-center">
            <label class="text-muted small text-uppercase">Nivel de Riesgo</label><br>
            <span class="badge ${cliente.riesgo === 'Bajo' ? 'bg-success' : (cliente.riesgo === 'Medio' ? 'bg-warning' : 'bg-danger')} px-3 py-2 mt-1">
                ${cliente.riesgo.toUpperCase()}
            </span>
        </div>
        <div class="col-md-3 text-center">
            <label class="text-muted small text-uppercase">Consolidado Total</label>
            <h4 class="fw-bold text-primary mb-0">$${parseFloat(cliente.saldo_total).toLocaleString('en-US', {minimumFractionDigits: 2})}</h4>
            <small class="text-muted">${cliente.nacionalidad}</small>
        </div>
    `;

    // 3. Generar las Cards de las cuentas (Detalle_ApiCredito)
    if (cuentas.length > 0) {
        cuentas.forEach(cta => {
            // Manejo dinámico del logo .webp
            // El nombre de la entidad en la DB debe coincidir con el nombre del archivo
            const nombreArchivo = cta.entidad.trim(); 
            const rutaLogo = `/Taller/Taller-Mecanica/img/${nombreArchivo}.webp`;

            const cardHtml = `
                <div class="card mb-3 border-0 shadow-sm overflow-hidden" style="border-left: 5px solid #198754 !important;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center border-end">
                                <img src="${rutaLogo}" 
                                     alt="${cta.entidad}" 
                                     style="width: 80px; height: 60px; object-fit: contain;"
                                     onerror="this.src='/Taller/Taller-Mecanica/assets/img/default_bank.png'">
                                <p class="small fw-bold mt-2 mb-0 text-success">${cta.entidad}</p>
                            </div>

                            <div class="col-md-10">
                                <div class="row px-3">
                                    <div class="col-md-4">
                                        <small class="text-muted">Producto / Servicio</small>
                                        <p class="fw-bold mb-0 text-dark">${cta.producto}</p>
                                        <small class="text-muted"><i class="far fa-calendar-alt me-1"></i>Apertura: ${cta.fecha}</small>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <small class="text-muted">Monto Registrado</small>
                                        <p class="fw-bold mb-0" style="font-size: 1.1rem;">$${parseFloat(cta.monto).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="d-inline-block text-center">
                                            <small class="text-muted d-block mb-1">Estado de Cuenta</small>
                                            <span class="badge ${cta.estado === 'Al Día' ? 'bg-success' : 'bg-danger'} rounded-pill px-3 py-2">
                                                <i class="fas ${cta.estado === 'Al Día' ? 'fa-check-circle' : 'fa-clock'} me-1"></i>
                                                ${cta.estado.toUpperCase()}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedor.innerHTML += cardHtml;
        });
    } else {
        contenedor.innerHTML = `<div class="alert alert-warning text-center">El cliente no posee líneas de crédito activas en el detalle.</div>`;
    }

    // 4. Mostrar el reporte con efecto suave
    reporte.classList.remove('d-none');
    reporte.scrollIntoView({ behavior: 'smooth' });
}

const inputCedula = document.getElementById('cedula_consulta');

if (inputCedula) {
    inputCedula.addEventListener('input', function (e) {
        // 1. Limpiar el valor de cualquier caracter que no sea número
        let valor = e.target.value.replace(/\D/g, '');
        let valorFormateado = '';

        // 2. Insertar guiones según la longitud
        if (valor.length > 0) {
            // Primer bloque (###)
            valorFormateado = valor.substring(0, 3);
            if (valor.length > 3) {
                // Segundo bloque (-#######)
                valorFormateado += '-' + valor.substring(3, 10);
            }
            if (valor.length > 10) {
                // Tercer bloque (-#)
                valorFormateado += '-' + valor.substring(10, 11);
            }
        }

        e.target.value = valorFormateado;
    });
}


function guardarConsultaBackend() {
    if (!dataActual.referencia || !dataActual.id_cliente) {
        alert("No hay una consulta activa para guardar.");
        return;
    }

    const fd = new FormData();
    fd.append('id_cliente', dataActual.id_cliente);
    fd.append('referencia', dataActual.referencia);
    fd.append('estado_consulta', dataActual.resultado);

    fetch('/Taller/Taller-Mecanica/modules/Cliente/Archivo_Apicredito.php?action=guardar_historial', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("✅ Consulta guardada correctamente en el historial.");
            // Opcional: Deshabilitar el botón para evitar duplicados
            // Buscamos el botón
        const btn = document.getElementById('btn_guardar_consulta');
        
        if (btn) {
            btn.disabled = true; // Deshabilita el botón
            btn.innerHTML = '<i class="fas fa-check"></i> Guardado'; // Cambia el texto para que se note
            btn.classList.replace('btn-light', 'btn-secondary'); // Lo pone gris
        }

        // Importante: También limpia la variable global para que no se pueda enviar por consola
        dataActual.id_cliente = null;
        dataActual.referencia = null;
        } else {
            alert("❌ Error: " + data.message);
        }
    })
    .catch(err => console.error("Error:", err));
}