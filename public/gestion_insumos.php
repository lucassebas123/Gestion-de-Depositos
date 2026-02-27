<?php
// 1. Definir el título
$titulo_pagina = "Gestionar Catálogo";

// 2. Cargar el NÚCLEO DE SUPERVISOR
require_once 'src/init_supervisor.php'; 

// 3. Lógica de la Página
$mensaje_exito = $_GET['mensaje_exito'] ?? '';
$mensaje_error = $_GET['mensaje_error'] ?? '';

try {
    // Cargar listas para los desplegables
    $categorias = obtener_todas_categorias($pdo, true); 
    $proveedores = obtener_todos_proveedores($pdo, false);
} catch (\PDOException $e) {
    $mensaje_error = "Error de Conexión: " . $e->getMessage();
    $categorias = [];
    $proveedores = [];
}
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold"><i class="bi bi-boxes me-2 text-primary"></i>Catálogo de Insumos</h1>
            <p class="text-muted mb-0 small">Administre todos los productos, categorías y proveedores del sistema.</p>
        </div>
        <button class="btn btn-primary btn-lg shadow-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalCrearInsumo">
            <i class="bi bi-plus-lg me-2"></i>Nuevo Insumo
        </button>
    </div>

    <?php if ($mensaje_exito): ?>
        <div class="alert alert-success shadow-sm border-0 fade show mb-4"><?php echo htmlspecialchars($mensaje_exito); ?></div>
    <?php endif; ?>
    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger shadow-sm border-0 fade show mb-4"><?php echo htmlspecialchars($mensaje_error); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-2">
            <form id="form_filtros" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-0 bg-light rounded" placeholder="Buscar por Nombre, SKU o Proveedor..." name="busqueda" id="input_busqueda">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select border-0 bg-light rounded" id="filtro_categoria">
                        <option value="">-- Todas las Categorías --</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex justify-content-end pe-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="checkInactivos" name="inactivos" value="1">
                        <label class="form-check-label small text-muted" for="checkInactivos">Ver Inactivos</label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaInsumos">
                    <thead class="table-light small text-uppercase text-muted">
                        <tr>
                            <th class="ps-4" style="width: 40%;">Producto</th>
                            <th style="width: 20%;">Categoría</th>
                            <th style="width: 20%;">Proveedor</th>
                            <th style="width: 10%;">Estado</th>
                            <th class="text-end pe-4" style="width: 10%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_insumos">
                        <tr id="fila_loading">
                            <td colspan="5" class="text-center p-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted small">Cargando inventario...</p>
                            </td>
                        </tr>
                        <tr id="fila_no_resultados" style="display: none;">
                            <td colspan="5">
                                <div class="text-center p-5">
                                    <i class="bi bi-box-seam empty-state-icon text-muted opacity-25 mb-3" style="font-size: 3rem;"></i>
                                    <h5 class="fw-light text-muted">No se encontraron resultados</h5>
                                    <p class="text-muted small">Intenta ajustar los filtros de búsqueda.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="p-3 border-top" id="nav_paginacion_container" style="display:none;">
                <nav aria-label="Navegación">
                    <ul class="pagination justify-content-center mb-0 pagination-sm" id="ul_paginacion"></ul>
                </nav>
            </div>
        </div>
    </div> 

</div> </div> 

<div class="modal fade" id="modalCrearInsumo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Nuevo Insumo</h5>
                <button type="button" class="btn-close" -data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="form_crear_insumo" method="POST" enctype="multipart/form-data">
                    
                    <div class="d-flex justify-content-end mb-3">
                         <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="btn-abrir-escaner-insumo">
                            <i class="bi bi-upc-scan me-1"></i> Escanear Código
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="form_nombre" name="nombre" placeholder="Nombre" required>
                                <label for="form_nombre">Nombre del Insumo <span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="form_sku" name="sku" placeholder="SKU">
                                <label for="form_sku">Código (SKU)</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small text-muted mb-1">Categoría <span class="text-danger">*</span></label>
                            <select id="categoria_id" name="categoria_id" class="form-select py-2" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?php echo $c['id']; ?>">
                                        <?php echo htmlspecialchars($c['nombre']); ?>
                                        <?php echo ($c['activo'] == 0) ? ' (Inactiva)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted mb-1">Proveedor</label>
                            <select id="proveedor_id" name="proveedor_id" class="form-select py-2">
                                <option value="">-- Opcional --</option>
                                <?php foreach ($proveedores as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo htmlspecialchars($p['nombre']); ?>
                                        <?php echo ($p['activo'] == 0) ? ' (Inactivo)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Unidad</label>
                            <input type="text" class="form-control" name="unidad_medida" placeholder="Ej: un, kg">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Stock Mín.</label>
                            <input type="number" class="form-control" name="stock_minimo" value="0" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Agrupador</label>
                            <input type="text" class="form-control" name="agrupador" placeholder="Ej: RESMA-A4">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label small text-muted mb-1">Ubicación / Notas / Descripción</label>
                            <input type="text" class="form-control mb-2" name="ubicacion" placeholder="Ubicación Física (Ej: Estante B)">
                            <textarea class="form-control mb-2" name="notas" rows="2" placeholder="Notas internas..."></textarea>
                            <input type="hidden" name="descripcion" id="form_descripcion_hidden">
                        </div>

                        <div class="col-12">
                            <label class="form-label small text-muted mb-1">Imagen</label>
                            <input class="form-control" type="file" name="imagen_producto" accept="image/*">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form_crear_insumo" class="btn btn-primary px-4" id="btn_guardar_insumo">
                    Guardar Insumo
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEscanerInsumo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title">Escanear Código</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="visor-escaner-insumo" style="width: 100%; height: 200px; background:#000; border-radius:8px;"></div>
                <div id="log-escaner-insumo" class="small mt-2 text-muted">Esperando cámara...</div>
            </div>
        </div>
    </div>
</div>

<?php 
// 5. Cargar el footer
require 'src/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Referencias DOM ---
    const tbody = document.getElementById('tbody_insumos');
    const filaLoading = document.getElementById('fila_loading');
    const filaNoResultados = document.getElementById('fila_no_resultados');
    const navPaginacion = document.getElementById('nav_paginacion_container');
    const ulPaginacion = document.getElementById('ul_paginacion');
    
    // Filtros
    const inputBusqueda = document.getElementById('input_busqueda');
    const selectCategoria = document.getElementById('filtro_categoria');
    const checkInactivos = document.getElementById('checkInactivos');
    const formFiltros = document.getElementById('form_filtros');

    // Modal Crear
    const modalCrearEl = document.getElementById('modalCrearInsumo');
    const modalCrear = new bootstrap.Modal(modalCrearEl);
    const formCrear = document.getElementById('form_crear_insumo');
    const btnGuardar = document.getElementById('btn_guardar_insumo');

    // --- Helper: Renderizar Tabla (Estilo Premium) ---
    function renderizarTabla(insumos) {
        tbody.innerHTML = '';
        if (insumos.length === 0) {
            filaNoResultados.style.display = '';
            tbody.appendChild(filaNoResultados);
            return;
        }

        insumos.forEach(insumo => {
            const tr = document.createElement('tr');
            tr.className = 'position-relative'; 
            tr.id = `fila-insumo-${insumo.id}`; // ID único para manipular fila si es necesario
            
            // Datos
            const nombre = escapeHTML(insumo.nombre);
            const sku = insumo.sku ? `<span class="badge bg-light text-secondary border font-monospace ms-2">${escapeHTML(insumo.sku)}</span>` : '';
            const agrupador = insumo.agrupador ? `<div class="small text-muted mt-1"><i class="bi bi-collection me-1"></i>${escapeHTML(insumo.agrupador)}</div>` : '';
            const proveedor = escapeHTML(insumo.proveedor_nombre || '—');
            const categoria = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal px-2 py-1">${escapeHTML(insumo.categoria_nombre)}</span>`;
            
            // Estado Visual (Dot)
            const estadoDot = insumo.activo 
                ? '<span class="text-success small fw-bold"><i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>Activo</span>'
                : '<span class="text-muted small fw-bold"><i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>Inactivo</span>';

            // Acciones (Botones)
            let acciones = `
                <div class="btn-group">
                    <a href="imprimir_etiquetas.php?ids=${insumo.id}" target="_blank" class="btn btn-sm btn-light text-dark border" title="Imprimir Etiqueta">
                        <i class="bi bi-upc-scan"></i>
                    </a>
                    <a href="editar_insumo.php?id=${insumo.id}" class="btn btn-sm btn-light text-primary border" title="Editar">
                        <i class="bi bi-pencil-fill"></i>
                    </a>`;
            
            if (insumo.activo) {
                acciones += `<button class="btn btn-sm btn-light text-danger border" onclick="cambiarEstado(${insumo.id}, '${addslashes(nombre)}', 'desactivar')" title="Desactivar"><i class="bi bi-trash"></i></button>`;
            } else {
                acciones += `<button class="btn btn-sm btn-light text-success border" onclick="cambiarEstado(${insumo.id}, '${addslashes(nombre)}', 'activar')" title="Activar"><i class="bi bi-check-lg"></i></button>`;
            }
            acciones += `</div>`;

            tr.innerHTML = `
                <td class="ps-4 py-3">
                    <div class="d-flex align-items-center">
                        <img src="ver_archivo.php?insumo_id=${insumo.id}" 
                             class="rounded shadow-sm me-3" 
                             style="width: 48px; height: 48px; object-fit: cover;"
                             onerror="this.src='https://placehold.co/48x48/e9ecef/adb5bd?text=Img';">
                        <div>
                            <div class="fw-bold text-dark">${nombre} ${sku}</div>
                            ${agrupador}
                        </div>
                    </div>
                </td>
                <td>${categoria}</td>
                <td class="text-muted small">${proveedor}</td>
                <td>${estadoDot}</td>
                <td class="text-end pe-4">${acciones}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // --- Función Principal: Carga de Datos ---
    let debounceTimer;
    async function cargarDatos(pagina = 1) {
        mostrarLoading(true);
        
        // Construir parámetros
        const params = new URLSearchParams({
            pagina: pagina,
            busqueda: inputBusqueda.value.trim(),
            inactivos: checkInactivos.checked ? '1' : '0',
            categoria_id: selectCategoria.value 
        });

        try {
            const res = await fetch(`api_get_insumos.php?${params.toString()}`);
            const data = await res.json();

            if (data.exito) {
                renderizarTabla(data.insumos);
                renderizarPaginacion(data.paginacion);
                
                // Actualizar URL del navegador sin recargar
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + params.toString();
                window.history.pushState({path:newUrl},'',newUrl);

            } else {
                throw new Error(data.mensaje);
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Error: ${e.message}</td></tr>`;
        } finally {
            mostrarLoading(false);
        }
    }

    // --- Helpers Renderizado ---
    function mostrarLoading(show) {
        filaLoading.style.display = show ? '' : 'none';
        if(show) { filaNoResultados.style.display = 'none'; tbody.innerHTML = ''; tbody.appendChild(filaLoading); }
    }

    function renderizarPaginacion(pag) {
        if (pag.total_paginas <= 1) { navPaginacion.style.display = 'none'; return; }
        navPaginacion.style.display = 'block';
        
        let html = '';
        const prevDisabled = pag.pagina_actual <= 1 ? 'disabled' : '';
        const nextDisabled = pag.pagina_actual >= pag.total_paginas ? 'disabled' : '';
        
        html += `<li class="page-item ${prevDisabled}"><a class="page-link" href="#" onclick="paginar(${pag.pagina_actual - 1}); return false;">&laquo;</a></li>`;
        
        for(let i=1; i<=pag.total_paginas; i++) {
            const active = i === pag.pagina_actual ? 'active' : '';
            html += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="paginar(${i}); return false;">${i}</a></li>`;
        }
        
        html += `<li class="page-item ${nextDisabled}"><a class="page-link" href="#" onclick="paginar(${pag.pagina_actual + 1}); return false;">&raquo;</a></li>`;
        ulPaginacion.innerHTML = html;
    }

    // Hacemos paginar global para el onclick
    window.paginar = (pag) => cargarDatos(pag);

    // Helpers de String
    function escapeHTML(str) { return str ? str.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') : ''; }
    function addslashes(str) { return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0'); }

    // --- Event Listeners (Filtros) ---
    formFiltros.addEventListener('submit', (e) => { e.preventDefault(); cargarDatos(1); });
    inputBusqueda.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => cargarDatos(1), 400);
    });
    selectCategoria.addEventListener('change', () => cargarDatos(1));
    checkInactivos.addEventListener('change', () => cargarDatos(1));


    // --- Acción CREAR INSUMO (Fetch) ---
    if(formCrear) {
        formCrear.addEventListener('submit', async (e) => {
            e.preventDefault();
            const originalText = btnGuardar.innerHTML;
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

            const formData = new FormData(formCrear);
            formData.append('accion', 'crear');
            formData.append('csrf_token', window.CSRF_TOKEN); 

            try {
                const res = await fetch('api_gestion_insumos.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.exito) {
                    mostrarToast(data.mensaje, 'success');
                    modalCrear.hide();
                    formCrear.reset();
                    cargarDatos(1); 
                } else {
                    throw new Error(data.mensaje);
                }
            } catch(err) {
                mostrarToast(err.message, 'danger');
            } finally {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = originalText;
            }
        });
    }

    // --- Acciones de ESTADO (SweetAlert2) ---
    window.cambiarEstado = function(id, nombre, accion) {
        Swal.fire({
            title: `¿${accion === 'desactivar' ? 'Desactivar' : 'Activar'}?`,
            text: `Vas a cambiar el estado de "${nombre}".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar',
            confirmButtonColor: accion === 'desactivar' ? '#d33' : '#198754'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch('api_insumos_acciones.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
                        body: JSON.stringify({accion: accion, insumo_id: id})
                    });
                    const data = await res.json();
                    if(data.exito) {
                        mostrarToast(data.mensaje, 'success');
                        cargarDatos(); // Recargar vista
                    } else { throw new Error(data.mensaje); }
                } catch(err) { mostrarToast(err.message, 'danger'); }
            }
        });
    };

    // --- LÓGICA DE ESCÁNER Y API EXTERNA ---
    const modalEscanerEl = document.getElementById('modalEscanerInsumo');
    const modalEscaner = new bootstrap.Modal(modalEscanerEl);
    const btnAbrirEscaner = document.getElementById('btn-abrir-escaner-insumo');
    const logEscaner = document.getElementById('log-escaner-insumo');
    const html5QrcodeScanner = new Html5Qrcode("visor-escaner-insumo");

    const onScanSuccess = (decodedText, decodedResult) => {
        logEscaner.innerHTML = `<span class="text-success">¡Éxito! Código: ${decodedText}</span>`;
        html5QrcodeScanner.stop().then(ignore => {
            modalEscaner.hide();
            // Llamar a la API de productos externa
            buscarInfoProducto(decodedText);
        }).catch(err => {
            console.error("Error al detener el escáner:", err);
            modalEscaner.hide();
        });
    };
    const onScanFailure = (error) => { /* Silencioso */ };

    btnAbrirEscaner.addEventListener('click', () => {
        // Mostrar modal escáner sobre el modal de creación
        modalEscaner.show();
    });

    modalEscanerEl.addEventListener('shown.bs.modal', () => {
        logEscaner.innerHTML = "Iniciando cámara...";
        html5QrcodeScanner.start(
            { facingMode: "environment" }, 
            { fps: 10, qrbox: { width: 250, height: 100 } },
            onScanSuccess, 
            onScanFailure
        ).catch(err => {
            logEscaner.innerHTML = `<span class="text-danger">Error al iniciar la cámara: ${err}</span>`;
        });
    });

    modalEscanerEl.addEventListener('hidden.bs.modal', () => {
        html5QrcodeScanner.stop().catch(err => {});
        logEscaner.innerHTML = "";
    });

    async function buscarInfoProducto(codigo) {
        // Poner el código en el campo SKU
        document.getElementById('form_sku').value = codigo;
        mostrarToast('Buscando información del producto...', 'info');

        try {
            const response = await fetch(`https://world.openfoodfacts.org/api/v0/product/${codigo}.json`);
            if (!response.ok) throw new Error('Error de red en API externa');
            const data = await response.json();
            
            if (data.status === 1 && data.product) {
                const producto = data.product;
                const nombre = producto.product_name || producto.product_name_es || '';
                const categorias = producto.categories || '';
                
                document.getElementById('form_nombre').value = nombre;
                
                // Usamos el campo oculto o notas
                if(document.getElementById('form_descripcion_hidden')) {
                     document.getElementById('form_descripcion_hidden').value = categorias;
                }
                // Si existe textarea de notas, agregamos info allí
                const notas = document.querySelector('textarea[name="notas"]');
                if(notas) notas.value = "Categorías ext: " + categorias;

                mostrarToast('¡Producto encontrado y rellenado!', 'success');
            } else {
                mostrarToast('SKU rellenado. No se encontró información extra.', 'warning');
            }
        } catch (error) {
            console.error('Error API Externa:', error);
            mostrarToast('Error al buscar info externa. SKU rellenado.', 'danger');
        }
    }

    // --- Carga Inicial (Manejo de Estado de URL) ---
    const paramsURL = new URLSearchParams(window.location.search);
    
    // 1. Filtro de Inactivos
    if (paramsURL.get('inactivos') === '1') {
        checkInactivos.checked = true;
    }
    
    // 2. Filtro de Búsqueda de Texto
    if (paramsURL.get('busqueda')) {
        inputBusqueda.value = paramsURL.get('busqueda');
    }

    // 3. ⭐️ NUEVO: Filtro de Categoría (desde el Buscador Global)
    if (paramsURL.get('categoria_id')) {
        selectCategoria.value = paramsURL.get('categoria_id');
    }
    
    // Cargar datos con los filtros aplicados
    cargarDatos(parseInt(paramsURL.get('pagina') || '1', 10));
});
</script>