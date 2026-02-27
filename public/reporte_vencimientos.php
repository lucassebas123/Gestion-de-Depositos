<?php
// ======================================================================
// REPORTE DE VENCIMIENTOS
// ======================================================================
// MODIFICADO v1.3 - [Asistente] Añadida Paginación
// MODIFICADO v1.4 - [Asistente] Añadidos Icono y Estado Vacío

// 1. Definir el título
$titulo_pagina = "Reporte de Vencimientos";

// 2. Cargar el NÚCLEO ESTÁNDAR
require_once 'src/init.php'; 

// 3. Lógica de la Página
$mensaje_error = "";
$lotes_a_vencer = [];

// --- Lógica de Paginación y Filtros ---
$items_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;

$dias_limite = isset($_GET['dias']) ? (int)$_GET['dias'] : 30; // 30 días por defecto

// Construir la URL base para la paginación (conservando el filtro)
$url_params = http_build_query(['dias' => $dias_limite]);
$url_base = "reporte_vencimientos.php?" . $url_params;
// --- Fin Lógica Paginación ---

try {
    // Usar el $pdo global
    
    // Obtener el total ANTES de aplicar el límite
    $total_lotes = obtener_total_lotes_proximos_a_vencer($pdo, $dias_limite);
    $total_paginas = ceil($total_lotes / $items_por_pagina);

    // Obtener solo la página actual
    $lotes_a_vencer = obtener_lotes_proximos_a_vencer(
        $pdo, 
        $dias_limite,
        $items_por_pagina, // <-- ⭐️ NUEVO: limit
        $offset            // <-- ⭐️ NUEVO: offset
    );

} catch (\PDOException $e) {
    $mensaje_error = "Error Crítico de Conexión: " . $e->getMessage();
    $total_paginas = 0;
    $lotes_a_vencer = []; // Asegurarse de que esté vacío
}

// 4. Renderizar VISTA (HTML)
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">

    <h1 class="mb-4"><i class="bi bi-calendar-x-fill me-3"></i>Reporte de Vencimientos</h1>
    <p class="text-muted">Mostrando lotes con cantidad positiva que vencen en los próximos <?php echo $dias_limite; ?> días (o ya están vencidos).</p>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="reporte_vencimientos.php" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="pagina" value="1">
                <div class="col-md-4">
                    <label for="dias" class="form-label">Mostrar lotes que vencen en los próximos:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="dias" id="dias" value="<?php echo $dias_limite; ?>" min="0">
                        <span class="input-group-text">días</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="reporte_vencimientos.php" class="btn btn-secondary ms-2">Resetear (30 días)</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header">
            <h2 class="h5 mb-0">Lotes Próximos a Vencer</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha Vencimiento</th>
                            <th>Insumo</th>
                            <th>Depósito</th>
                            <th>N° de Lote</th>
                            <th>Cantidad Actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <?php if (empty($lotes_a_vencer)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="text-center p-5">
                                        <i class="bi bi-shield-check empty-state-icon"></i>
                                        <h4 class="mt-3 fw-light">¡Todo en orden!</h4>
                                        <p class="text-muted">No hay lotes próximos a vencer con los filtros aplicados.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $hoy = new DateTime();
                            foreach ($lotes_a_vencer as $lote): 
                                $fecha_venc = new DateTime($lote['fecha_vencimiento']);
                                $es_vencido = $fecha_venc < $hoy;
                            ?>
                                <tr class="<?php echo $es_vencido ? 'table-danger' : 'table-warning'; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($lote['fecha_vencimiento']); ?></strong>
                                        <?php if($es_vencido): ?>
                                            <span class="badge bg-danger ms-1">VENCIDO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($lote['insumo_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($lote['deposito_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($lote['numero_lote']); ?></td>
                                    <td><strong class="fs-5"><?php echo $lote['cantidad_actual']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegación de vencimientos">
                <ul class="pagination justify-content-center">
                    
                    <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $url_base; ?>&pagina=<?php echo $pagina_actual - 1; ?>">Anterior</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $url_base; ?>&pagina=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $url_base; ?>&pagina=<?php echo $pagina_actual + 1; ?>">Siguiente</a>
                    </li>

                </ul>
            </nav>
            <?php endif; ?>
            </div>
    </div>

</div> </div> <?php 
// 5. Cargar el footer
require 'src/footer.php'; 
?>