<?php
// ======================================================================
// HISTORIAL (STOCK ACTUAL)
// ======================================================================
// MODIFICADO v2.0 - [Asistente] Refactor a Carga con AJAX

// 1. Definir el título
$titulo_pagina = "Stock Actual";

// 2. Cargar el NÚCLEO (Sesión, DB, Funciones, Header, Menú)
require_once 'src/init.php';

// 3. Lógica de la Página
$mensaje_error = "";
$depositos_filtro = [];
$categorias_filtro = [];

// ⭐️ CAMBIO: Solo cargamos los datos para los SELECT
try {
    // Usar el $pdo global
    $filtro_deposito_id = isset($_GET['filtro_deposito_id']) ? (int)$_GET['filtro_deposito_id'] : null;
    
    $depositos_filtro = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);
    $categorias_filtro = obtener_categorias_con_stock_por_deposito($pdo, $filtro_deposito_id);
    
} catch (\PDOException $e) {
    $mensaje_error = "Error Crítico de Conexión: " . $e->getMessage();
}

// 4. Renderizar VISTA (HTML)
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">
    
    <h1 class="mb-4"><i class="bi bi-boxes me-3"></i>Consulta de Stock Actual</h1>

    <div id="alerta_stock_bajo" class="alert alert-danger d-flex align-items-center" role="alert" style="display: none;">
        <div>
            Mostrando solo insumos con <strong>Stock Bajo</strong>. <a href="historial.php" class="alert-link ms-2">Limpiar filtro</a>
        </div>
    </div>
    
    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="#" method="POST" id="form_filtros" class="row g-3 align-items-end">
                <input type="hidden" name="filtro" id="filtro_especial" value="">
                
                <div class="col-md-3">
                    <label for="filtro_deposito_id" class="form-label">Filtrar por Depósito:</label>
                    <select name="filtro_deposito_id" id="filtro_deposito_id" class="form-select">
                        <option value="">-- TODOS (Mis Depósitos) --</option>
                        <?php foreach ($depositos_filtro as $deposito): ?>
                            <option value="<?php echo $deposito['id']; ?>">
                                <?php echo htmlspecialchars($deposito['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtro_categoria_id" class="form-label">Filtrar por Categoría:</label>
                    <select name="filtro_categoria_id" id="filtro_categoria_id" class="form-select">
                        <option value="">-- TODAS --</option>
                        <?php foreach ($categorias_filtro as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>">
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtro_texto_js" class="form-label">Buscar en esta página:</label>
                    <input type="text" class="form-control" id="filtro_texto_js" placeholder="Ej: Resma A4...">
                </div>
                <div class="col-md-3 d-flex">
                    <button type="submit" class="btn btn-primary me-2 flex-grow-1">Filtrar</button>
                    <a href="historial.php" class="btn btn-secondary flex-grow-1">Limpiar</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Stock Actual en Tabla</h2>
            <a href="#" id="link_exportar" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar a Excel (CSV)
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="tabla_stock">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 80px;">Imagen</th>
                            <th>Insumo</th>
                            <th>Categoría</th>
                            <th>Depósito</th>
                            <th>Proveedor</th>
                            <th>Stock</th>
                            <th>Última Actualización</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_stock">
                        <tr id="fila_loading">
                            <td colspan="7" class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando stock...</p>
                            </td>
                        </tr>
                        <tr id="fila_no_resultados" style="display: none;">
                            <td colspan="7">
                                <div class="text-center p-5">
                                    <i class="bi bi-box-seam empty-state-icon"></i>
                                    <h4 class="mt-3 fw-light">No se encontró stock</h4>
                                    <p class="text-muted">No hay stock registrado que coincida con los filtros aplicados.</p>
                                </div>
                            </td>
                        </tr>
                        <tr id="fila_no_resultados_js" style="display: none;">
                            <td colspan="7" class="text-center text-muted">No hay resultados para tu búsqueda en esta página.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <nav aria-label="Navegación de stock" id="nav_paginacion" style="display: none;">
                <ul class="pagination justify-content-center" id="ul_paginacion">
                    </ul>
            </nav>
            </div>
    </div>

</div> </div> 

<?php 
// 5. Cargar el footer
require 'src/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- Referencias a elementos del DOM ---
    const formFiltros = document.getElementById('form_filtros');
    const tbody = document.getElementById('tbody_stock');
    const filaLoading = document.getElementById('fila_loading');
    const filaNoResultados = document.getElementById('fila_no_resultados');
    const navPaginacion = document.getElementById('nav_paginacion');
    const ulPaginacion = document.getElementById('ul_paginacion');
    const linkExportar = document.getElementById('link_exportar');
    const filtroEspecialInput = document.getElementById('filtro_especial');
    const alertaStockBajo = document.getElementById('alerta_stock_bajo');

    // --- Función Principal de Carga de Datos ---
    async function cargarStock(pagina = 1) {
        mostrarLoading(true);
        
        // 1. Construir la URL con los parámetros de filtro
        const params = new URLSearchParams(new FormData(formFiltros));
        params.append('pagina', pagina);
        const url = `api_get_stock.php?${params.toString()}`;

        // Actualizar el link de exportación
        linkExportar.href = `exportar_stock.php?${params.toString()}`;
        
        // Mostrar/ocultar alerta de stock bajo
        alertaStockBajo.style.display = (params.get('filtro') === 'stock_bajo') ? 'flex' : 'none';

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            const data = await response.json();

            if (!data.exito) {
                throw new Error(data.mensaje || 'La API devolvió un error');
            }

            // 2. Renderizar la tabla y la paginación
            renderizarTabla(data.stock);
            renderizarPaginacion(data.paginacion.pagina_actual, data.paginacion.total_paginas);
            
            // 3. Actualizar la URL del navegador (sin recargar)
            const urlNavegador = `historial.php?${params.toString()}`;
            history.pushState({pagina: pagina}, '', urlNavegador);

        } catch (error) {
            console.error('Error al cargar stock:', error);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger p-4">Error al cargar datos: ${error.message}</td></tr>`;
        } finally {
            mostrarLoading(false);
        }
    }

    // --- Función para mostrar/ocultar el Spinner ---
    function mostrarLoading(mostrar) {
        filaLoading.style.display = mostrar ? '' : 'none';
        if (mostrar) {
            filaNoResultados.style.display = 'none';
            tbody.innerHTML = ''; // Limpiar la tabla
            tbody.appendChild(filaLoading);
        }
    }

    // --- Función para Dibujar la Tabla ---
    function renderizarTabla(stockItems) {
        tbody.innerHTML = ''; // Limpiar
        if (stockItems.length === 0) {
            filaNoResultados.style.display = '';
            tbody.appendChild(filaNoResultados);
            return;
        }

        stockItems.forEach(stock => {
            const tr = document.createElement('tr');
            
            const alerta_stock = (stock.stock_minimo > 0 && stock.cantidad <= stock.stock_minimo);
            if (alerta_stock) {
                tr.classList.add('table-danger');
            }

            const cantidadClase = alerta_stock ? 'text-danger' : '';

            tr.innerHTML = `
                <td>
                    <img src="ver_archivo.php?insumo_id=${stock.insumo_id}" 
                         alt="${escapeHTML(stock.insumo_nombre)}" 
                         class="img-thumbnail" 
                         style="width: 70px; height: 70px; object-fit: cover;"
                         onerror="this.src='https://placehold.co/100x100/e9ecef/6c757d?text=N/A';">
                </td>
                <td><strong>${escapeHTML(stock.insumo_nombre)}</strong></td>
                <td>${escapeHTML(stock.categoria_nombre)}</td>
                <td>${escapeHTML(stock.deposito_nombre)}</td>
                <td>${escapeHTML(stock.proveedor || 'N/A')}</td>
                <td>
                    <strong class="fs-5 ${cantidadClase}">
                        ${stock.cantidad}
                    </strong>
                </td>
                <td>${escapeHTML(stock.fecha_actualizacion)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // --- Función para Dibujar la Paginación ---
    function renderizarPaginacion(paginaActual, totalPaginas) {
        ulPaginacion.innerHTML = '';
        if (totalPaginas <= 1) {
            navPaginacion.style.display = 'none';
            return;
        }
        navPaginacion.style.display = 'block';

        // Botón "Anterior"
        ulPaginacion.innerHTML += `
            <li class="page-item ${paginaActual <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-pagina="${paginaActual - 1}">Anterior</a>
            </li>`;

        // Números de página
        for (let i = 1; i <= totalPaginas; i++) {
            ulPaginacion.innerHTML += `
                <li class="page-item ${i == paginaActual ? 'active' : ''}">
                    <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                </li>`;
        }

        // Botón "Siguiente"
        ulPaginacion.innerHTML += `
            <li class="page-item ${paginaActual >= totalPaginas ? 'disabled' : ''}">
                <a class="page-link" href="#" data-pagina="${paginaActual + 1}">Siguiente</a>
            </li>`;
    }

    // --- Función Helper para escapar HTML ---
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // --- EVENT LISTENERS ---
    
    // 1. Al enviar el formulario de filtros
    formFiltros.addEventListener('submit', function(e) {
        e.preventDefault();
        cargarStock(1); // Ir a la página 1 con los nuevos filtros
    });

    // 2. Al hacer clic en un link de paginación
    ulPaginacion.addEventListener('click', function(e) {
        e.preventDefault();
        const link = e.target.closest('.page-link');
        if (link && !e.target.closest('.disabled')) {
            const pagina = parseInt(link.dataset.pagina, 10);
            if (pagina) {
                cargarStock(pagina);
            }
        }
    });

    // 3. Carga inicial: Leer parámetros de la URL
    const paramsURL = new URLSearchParams(window.location.search);
    document.getElementById('filtro_deposito_id').value = paramsURL.get('filtro_deposito_id') || '';
    document.getElementById('filtro_categoria_id').value = paramsURL.get('filtro_categoria_id') || '';
    filtroEspecialInput.value = paramsURL.get('filtro') || '';
    
    cargarStock(parseInt(paramsURL.get('pagina') || '1', 10));
});


// --- Script de Filtro (en vivo, en la página) ---
// (Este script sigue funcionando sobre la tabla ya cargada por AJAX)
document.addEventListener('DOMContentLoaded', function() {
    const filtroInput = document.getElementById('filtro_texto_js');
    const tablaBody = document.getElementById('tbody_stock');
    const filaNoResultadosJS = document.getElementById('fila_no_resultados_js');
    const filaNoResultadosPHP = document.getElementById('fila_no_resultados');

    if (filtroInput) {
        filtroInput.addEventListener('keyup', function() {
            const textoBusqueda = filtroInput.value.toLowerCase().trim();
            let filasVisibles = 0;
            const filas = tablaBody.querySelectorAll('tr:not(#fila_loading):not(#fila_no_resultados):not(#fila_no_resultados_js)');

            filas.forEach(function(fila) {
                const textoFila = fila.textContent.toLowerCase();
                if (textoFila.includes(textoBusqueda)) {
                    fila.style.display = '';
                    filasVisibles++;
                } else {
                    fila.style.display = 'none';
                }
            });
            filaNoResultadosJS.style.display = (filasVisibles === 0 && filas.length > 0) ? '' : 'none';
        });
    }
});
</script>