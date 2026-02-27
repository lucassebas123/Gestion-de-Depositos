<?php
// ======================================================================
// HISTORIAL DE MOVIMIENTOS
// ======================================================================
// MODIFICADO v3.2 - [Asistente] Refactor a Carga AJAX + Seguridad CSRF + SweetAlert2

// 1. Definir el título
$titulo_pagina = "Historial de Movimientos";

// 2. Cargar el NÚCLEO
require_once 'src/init.php';

// 3. Lógica de la Página
$mensaje_error = $_GET['error'] ?? '';
$mensaje_exito = $_GET['exito'] ?? '';
$depositos_filtro = [];
$usuarios_filtro = [];

// ⭐️ CAMBIO: Solo cargamos los datos para los SELECT
try {
    $depositos_filtro = obtener_depositos_por_usuario($pdo, $USUARIO_ID, $USUARIO_ROL);
    $usuarios_filtro = obtener_usuarios_con_movimientos($pdo);
} catch (\PDOException $e) {
    $mensaje_error = "Error Crítico de Conexión: " . $e->getMessage();
}

// 4. Renderizar VISTA (HTML)
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">
    
    <h1 class="mb-4 text-center"><i class="bi bi-list-task me-3"></i>Historial de Movimientos</h1>

    <?php if ($mensaje_exito): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje_exito); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            
            <form action="#" method="POST" id="form_filtros" class="row g-3 justify-content-center align-items-end">
                
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

                <div class="col-md-2">
                    <label for="filtro_tipo" class="form-label">Por Tipo:</label>
                    <select name="filtro_tipo" id="filtro_tipo" class="form-select">
                        <option value="">-- TODOS --</option>
                        <option value="ENTRADA">ENTRADA</option>
                        <option value="SALIDA">SALIDA</option>
                        <option value="AJUSTE">AJUSTE</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="filtro_usuario_id" class="form-label">Por Usuario:</label>
                    <select name="filtro_usuario_id" id="filtro_usuario_id" class="form-select">
                        <option value="">-- TODOS --</option>
                        <?php foreach ($usuarios_filtro as $usuario): ?>
                            <option value="<?php echo $usuario['id']; ?>">
                                <?php echo htmlspecialchars($usuario['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filtro_texto_js" class="form-label">Buscar en esta página:</label>
                    <input type="text" class="form-control" id="filtro_texto_js" placeholder="Ej: Birome, LOTE-A123...">
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    <a href="historial_movimientos.php" class="btn btn-secondary w-100 mt-1">Limpiar</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Movimientos en Tabla</h2>
            <a href="#" id="link_exportar" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar a Excel (CSV)
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha (Efectiva)</th>
                            <th>Estado</th> 
                            <th>Insumo</th>
                            <th>Depósito</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Usuario</th>
                            <th>Observaciones</th>
                            <th style="width: 120px;">Acción</th> 
                        </tr>
                    </thead>
                    <tbody id="tbody_movimientos">
                        <tr id="fila_loading">
                            <td colspan="9" class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 text-muted">Cargando movimientos...</p>
                            </td>
                        </tr>
                        <tr id="fila_no_resultados" style="display: none;">
                            <td colspan="9">
                                <div class="text-center p-5">
                                    <i class="bi bi-search empty-state-icon"></i>
                                    <h4 class="mt-3 fw-light">No se encontraron movimientos</h4>
                                    <p class="text-muted">No se han registrado movimientos que coincidan con tus filtros.</p>
                                </div>
                            </td>
                        </tr>
                        <tr id="fila_no_resultados_js" style="display: none;">
                            <td colspan="9" class="text-center text-muted">No hay resultados para tu búsqueda.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <nav aria-label="Navegación de historial" id="nav_paginacion" style="display: none;">
                <ul class="pagination justify-content-center" id="ul_paginacion">
                    </ul>
            </nav>
            </div>
    </div>

</div> </div> 

<?php 
require 'src/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Referencias a elementos del DOM ---
    const formFiltros = document.getElementById('form_filtros');
    const tbody = document.getElementById('tbody_movimientos');
    const filaLoading = document.getElementById('fila_loading');
    const filaNoResultados = document.getElementById('fila_no_resultados');
    const navPaginacion = document.getElementById('nav_paginacion');
    const ulPaginacion = document.getElementById('ul_paginacion');
    const linkExportar = document.getElementById('link_exportar');
    
    // ⭐️ CAMBIO: Leemos la variable global window.USUARIO_ROL (inyectada en header.php)
    const puedeGestionar = (window.USUARIO_ROL === 'admin' || window.USUARIO_ROL === 'supervisor');

    // --- ⭐️ NUEVO: Helper de Confirmación (SweetAlert2) ⭐️ ---
    function mostrarConfirmacion(titulo, texto, callback) {
        Swal.fire({
            title: titulo,
            text: texto,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // Rojo para eliminar
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, ¡eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    }

    // --- Función Principal de Carga de Datos ---
    async function cargarMovimientos(pagina = 1) {
        mostrarLoading(true);
        
        const params = new URLSearchParams(new FormData(formFiltros));
        params.append('pagina', pagina);
        const url = `api_get_movimientos.php?${params.toString()}`;
        
        linkExportar.href = `exportar_movimientos.php?${params.toString()}`;

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            const data = await response.json();

            if (!data.exito) {
                throw new Error(data.mensaje || 'La API devolvió un error');
            }

            renderizarTabla(data.movimientos);
            renderizarPaginacion(data.paginacion.pagina_actual, data.paginacion.total_paginas);
            
            const urlNavegador = `historial_movimientos.php?${params.toString()}&pagina=${pagina}`;
            history.pushState({pagina: pagina}, '', urlNavegador);

        } catch (error) {
            console.error('Error al cargar movimientos:', error);
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger p-4">Error al cargar datos: ${error.message}</td></tr>`;
        } finally {
            mostrarLoading(false);
        }
    }

    // --- Función para mostrar/ocultar el Spinner ---
    function mostrarLoading(mostrar) {
        filaLoading.style.display = mostrar ? '' : 'none';
        if (mostrar) {
            filaNoResultados.style.display = 'none';
            tbody.innerHTML = ''; 
            tbody.appendChild(filaLoading);
        }
    }

    // --- Función para Dibujar la Tabla ---
    function renderizarTabla(movimientos) {
        tbody.innerHTML = ''; 
        if (movimientos.length === 0) {
            filaNoResultados.style.display = '';
            tbody.appendChild(filaNoResultados);
            return;
        }

        movimientos.forEach(mov => {
            const tr = document.createElement('tr');
            tr.id = `fila-mov-${mov.id}`;
            if (mov.anulado_por_id != null) {
                tr.classList.add('text-muted');
            }

            // --- Estado ---
            let estadoHtml = '';
            if (mov.anulado_por_id != null) {
                estadoHtml = '<span class="badge bg-secondary">Anulado</span>';
            } else if (mov.estado == 'PROGRAMADO') {
                estadoHtml = '<span class="badge bg-info text-dark">Programado</span>';
            } else {
                estadoHtml = '<span class="badge bg-success">Efectivo</span>';
            }

            // --- Tipo Movimiento ---
            let tipoHtml = '';
            if (mov.tipo_movimiento == 'ENTRADA') {
                tipoHtml = '<span class="badge bg-success">ENTRADA</span>';
            } else if (mov.tipo_movimiento == 'SALIDA') {
                tipoHtml = '<span class="badge bg-danger">SALIDA</span>';
            } else {
                tipoHtml = '<span class="badge bg-warning text-dark">AJUSTE</span>';
            }
            
            // --- Cantidad ---
            const cantidadClase = mov.cantidad_movida > 0 ? 'text-success' : 'text-danger';
            const cantidadSigno = mov.cantidad_movida > 0 ? '+' : '';

            // --- Fecha Efectiva ---
            let fechaHtml = '';
            if (mov.estado == 'PROGRAMADO') {
                fechaHtml = `<small>Programado para:</small><br><strong>${escapeHTML(mov.fecha_efectiva.substring(0, 10))}</strong>`;
            } else {
                fechaHtml = escapeHTML(mov.fecha_efectiva.substring(0, 16));
            }
            
            // --- Acciones ---
            let accionesHtml = `
                <a href="ver_movimiento_detalle.php?id=${mov.id}" 
                   class="btn btn-info btn-sm w-100" 
                   target="_blank" 
                   title="Ver Detalles">
                    Detalles
                </a>
            `;
            
            if (mov.anulado_por_id != null) {
                if (mov.anulado_por_username) {
                    accionesHtml += `
                        <div style="font-size: 0.75rem;" class="text-muted text-center mt-1">
                            Por: <strong>${escapeHTML(mov.anulado_por_username)}</strong>
                        </div>`;
                }
            } else if (mov.estado == 'PROGRAMADO') {
                // ⭐️ CAMBIO: Ahora usa la variable JS 'puedeGestionar' ⭐️
                if (puedeGestionar) {
                    accionesHtml += `
                        <button 
                            class="btn btn-warning btn-sm w-100 mt-1 btn-eliminar-programado" 
                            title="Eliminar movimiento programado"
                            data-id="${mov.id}"
                            data-nombre="${escapeHTML(addslashes(mov.insumo_nombre))}">
                            Eliminar
                        </button>`;
                }
            } else {
                // ⭐️ CAMBIO: Ahora usa la variable JS 'puedeGestionar' ⭐️
                if (puedeGestionar && mov.tipo_movimiento != 'AJUSTE') {
                    accionesHtml += `
                        <a href="anular_movimiento.php?id=${mov.id}" 
                           class="btn btn-danger btn-sm w-100 mt-1" 
                           title="Anular">
                            Anular
                        </a>`;
                }
                if (mov.recibo_path) {
                    accionesHtml += `
                        <a href="ver_archivo.php?movimiento_id=${mov.id}" 
                           class="btn btn-secondary btn-sm w-100 mt-1" 
                           target="_blank" 
                           title="Ver Recibo">
                            Recibo
                        </a>`;
                }
            }
            
            // --- Llenar la fila ---
            tr.innerHTML = `
                <td>${fechaHtml}</td>
                <td>${estadoHtml}</td>
                <td>${escapeHTML(mov.insumo_nombre)}</td>
                <td>${escapeHTML(mov.deposito_nombre)}</td>
                <td>${tipoHtml}</td>
                <td><strong class="${cantidadClase}">${cantidadSigno}${mov.cantidad_movida}</strong></td>
                <td><span class="badge bg-secondary">${escapeHTML(mov.username || 'Sistema')}</span></td>
                <td>${escapeHTML(mov.observaciones)}</td>
                <td>${accionesHtml}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // --- Función para Dibujar la Paginación (se mantiene) ---
    function renderizarPaginacion(paginaActual, totalPaginas) {
        ulPaginacion.innerHTML = '';
        if (totalPaginas <= 1) {
            navPaginacion.style.display = 'none';
            return;
        }
        navPaginacion.style.display = 'block';

        ulPaginacion.innerHTML += `
            <li class="page-item ${paginaActual <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-pagina="${paginaActual - 1}">Anterior</a>
            </li>`;

        for (let i = 1; i <= totalPaginas; i++) {
            ulPaginacion.innerHTML += `
                <li class="page-item ${i == paginaActual ? 'active' : ''}">
                    <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                </li>`;
        }

        ulPaginacion.innerHTML += `
            <li class="page-item ${paginaActual >= totalPaginas ? 'disabled' : ''}">
                <a class="page-link" href="#" data-pagina="${paginaActual + 1}">Siguiente</a>
            </li>`;
    }

    // --- Función Helper para escapar HTML (se mantiene) ---
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // --- Función Helper para evitar problemas en strings pasados a JS (se mantiene) ---
    function addslashes(str) {
         return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
    }

    // --- Función para Manejar la Eliminación (API con CSRF y SweetAlert2) ---
    async function manejarEliminacion(movimientoId, insumoNombre) {
        // ⭐️ CAMBIO: Usar SweetAlert2 ⭐️
        mostrarConfirmacion(
            '¿Eliminar Movimiento?',
            `Estás a punto de eliminar permanentemente el movimiento programado #${movimientoId} (${insumoNombre}). Esta acción no se puede deshacer.`,
            async () => {
                // --- Inicio de la lógica de acción ---
                try {
                    const response = await fetch('api_movimientos.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': window.CSRF_TOKEN 
                        },
                        body: JSON.stringify({
                            accion: 'eliminar_programado',
                            movimiento_id: movimientoId
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.exito) {
                        mostrarToast(data.mensaje, 'success');
                        const params = new URLSearchParams(window.location.search);
                        window.cargarMovimientos(parseInt(params.get('pagina') || '1', 10));
                    } else {
                        mostrarToast(data.mensaje || 'Error desconocido', 'danger');
                    }

                } catch (error) {
                    console.error('Error en fetch:', error);
                    mostrarToast('Error de conexión con la API.', 'danger');
                }
                // --- Fin de la lógica de acción ---
            }
        );
    }
    window.manejarEliminacion = manejarEliminacion; // Hacerla global

    // --- EVENT LISTENERS (se mantienen) ---
    
    formFiltros.addEventListener('submit', function(e) {
        e.preventDefault();
        cargarMovimientos(1);
    });

    ulPaginacion.addEventListener('click', function(e) {
        e.preventDefault();
        const link = e.target.closest('.page-link');
        if (link && !e.target.closest('.disabled')) {
            const pagina = parseInt(link.dataset.pagina, 10);
            if (pagina) {
                cargarMovimientos(pagina);
            }
        }
    });
    
    tbody.addEventListener('click', function(e) {
        const boton = e.target.closest('.btn-eliminar-programado');
        if (boton) {
            e.preventDefault();
            const id = boton.dataset.id;
            const nombre = boton.dataset.nombre;
            manejarEliminacion(id, nombre);
        }
    });

    const paramsURL = new URLSearchParams(window.location.search);
    document.getElementById('filtro_deposito_id').value = paramsURL.get('filtro_deposito_id') || '';
    document.getElementById('filtro_tipo').value = paramsURL.get('filtro_tipo') || '';
    document.getElementById('filtro_usuario_id').value = paramsURL.get('filtro_usuario_id') || '';
    
    window.cargarMovimientos = cargarMovimientos; 
    cargarMovimientos(parseInt(paramsURL.get('pagina') || '1', 10));
    
});

// --- Script de Filtro (en vivo, en la página) ---
document.addEventListener('DOMContentLoaded', function() {
    const filtroInput = document.getElementById('filtro_texto_js');
    const tablaBody = document.getElementById('tbody_movimientos');
    const filaNoResultadosJS = document.getElementById('fila_no_resultados_js');

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