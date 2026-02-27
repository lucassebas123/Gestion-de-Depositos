<?php
// ======================================================================
// INICIO (DASHBOARD)
// ======================================================================
// MODIFICADO v2.2 - [Asistente] Reemplazada tarjeta 'Movimientos' por 'Vencimientos'.

// 1. Definir el título de la página
$titulo_pagina = "Inicio (Dashboard)";

// 2. Cargar el NÚCLEO (Sesión, DB, Funciones, Header, Menú)
require_once 'src/init.php';

// 3. Lógica de la Página (usando el $pdo global)
$mensaje_error = "";
$conteo_stock_bajo = 0;
$conteo_insumos_total = 0;
$conteo_vencimientos = 0;
$ultimos_movimientos = [];

try {
    // Obtener métricas
    $conteo_stock_bajo = obtener_conteo_stock_bajo($pdo);
    $conteo_insumos_total = obtener_conteo_total_insumos($pdo);
    
    // ⭐️ NUEVO: Contar vencimientos en próximos 30 días
    $conteo_vencimientos = obtener_conteo_vencimientos_proximos($pdo, 30);
    
    // Obtener la tabla de movimientos (para la lista inferior)
    $ultimos_movimientos = obtener_ultimos_movimientos($pdo, 5);
    
} catch (\PDOException $e) {
    $mensaje_error = "Error Crítico de Conexión: " . $e->getMessage();
}

// 4. Renderizar VISTA (HTML)
?>

<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">
    
    <h1 class="mb-4"><i class="bi bi-house-door-fill me-3"></i>Dashboard</h1>

    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        
        <div class="col-md-4">
            <a href="historial.php?filtro=stock_bajo" class="text-decoration-none">
                <div class="card card-metric shadow-sm <?php echo ($conteo_stock_bajo > 0) ? 'card-metric-danger' : 'card-metric-success'; ?>">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title fs-2 <?php echo ($conteo_stock_bajo > 0) ? 'text-danger' : 'text-success'; ?>"><?php echo $conteo_stock_bajo; ?></h5>
                            <p class="card-text mb-0">Insumos con Stock Bajo</p>
                        </div>
                        <i class="bi bi-exclamation-triangle-fill fs-2 opacity-75 <?php echo ($conteo_stock_bajo > 0) ? 'text-danger' : 'text-success'; ?>"></i>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-4">
            <div class="card card-metric shadow-sm card-metric-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title fs-2 text-primary"><?php echo $conteo_insumos_total; ?></h5>
                        <p class="card-text mb-0">Insumos en Catálogo</p>
                    </div>
                    <i class="bi bi-box-seam fs-2 opacity-75 text-primary"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <a href="reporte_vencimientos.php" class="text-decoration-none">
                <div class="card card-metric shadow-sm <?php echo ($conteo_vencimientos > 0) ? 'card-metric-warning' : 'card-metric-success'; ?>">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title fs-2 <?php echo ($conteo_vencimientos > 0) ? 'text-warning' : 'text-success'; ?>">
                                <?php echo $conteo_vencimientos; ?>
                            </h5>
                            <p class="card-text mb-0">Vencen en 30 días</p>
                        </div>
                        <i class="bi bi-clock-history fs-2 opacity-75 <?php echo ($conteo_vencimientos > 0) ? 'text-warning' : 'text-success'; ?>"></i>
                    </div>
                </div>
            </a>
        </div>
        
    </div> 
    
    <div class="row g-4">
        
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h2 class="h5 mb-0"><i class="bi bi-list-ul me-2"></i>Últimos Movimientos Registrados</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Insumo</th>
                                    <th>Depósito</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ultimos_movimientos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No hay movimientos recientes.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ultimos_movimientos as $mov): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($mov['fecha'], 0, 10)); ?></td>
                                            <td><?php echo htmlspecialchars($mov['insumo_nombre']); ?></td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    <?php echo htmlspecialchars($mov['deposito_nombre']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if($mov['tipo_movimiento'] == 'ENTRADA'): ?>
                                                    <span class="badge bg-success">ENTRADA</span>
                                                <?php elseif($mov['tipo_movimiento'] == 'SALIDA'): ?>
                                                    <span class="badge bg-danger">SALIDA</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">AJUSTE</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="<?php echo ($mov['cantidad_movida'] > 0) ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo ($mov['cantidad_movida'] > 0 ? '+' : '') . $mov['cantidad_movida']; ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($mov['username'] ?? 'Sistema'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0"><i class="bi bi-pie-chart-fill me-2"></i>Top 5 Categorías (Stock)</h2>
                </div>
                <div class="card-body">
                    <canvas id="graficoCategorias" style="max-height: 300px;"></canvas>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="h5 mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Stock Total por Depósito</h2>
                </div>
                <div class="card-body">
                    <canvas id="graficoDepositos" style="max-height: 300px;"></canvas>
                </div>
            </div>
            
        </div>
        
    </div> 
</div> 
</div> 
<?php 
// 5. Cargar el footer (que carga los JS globales)
require 'src/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    
    // --- 1. Definir Colores (Usando las variables de style.css) ---
    const getCssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    
    const backgroundColors = [
        getCssVar('--brand-primary'),
        getCssVar('--success'),
        getCssVar('--danger'),
        getCssVar('--warning'),
        '#6B7280', 
        '#F59E0B', 
        '#10B981', 
        '#4A55A2', 
        '#C5DFF8'
    ];
    
    // --- 2. Obtener los datos desde nuestra API ---
    let datosGraficos = null;
    try {
        const response = await fetch('api_dashboard.php');
        if (!response.ok) throw new Error('Error al cargar datos de gráficos');
        datosGraficos = await response.json();
    } catch (error) {
        console.error(error);
        const ctxCategorias = document.getElementById('graficoCategorias');
        const ctxDepositos = document.getElementById('graficoDepositos');
        if (ctxCategorias) ctxCategorias.parentElement.innerHTML = '<p class="text-center text-danger">Error al cargar datos de categorías.</p>';
        if (ctxDepositos) ctxDepositos.parentElement.innerHTML = '<p class="text-center text-danger">Error al cargar datos de depósitos.</p>';
        return;
    }

    // --- 3. Dibujar Gráfico de Torta (Categorías) ---
    const ctxCategorias = document.getElementById('graficoCategorias');
    if (ctxCategorias && datosGraficos.stockPorCategoria && datosGraficos.stockPorCategoria.data.length > 0) {
        new Chart(ctxCategorias, {
            type: 'pie',
            data: {
                labels: datosGraficos.stockPorCategoria.labels,
                datasets: [{
                    label: 'Stock',
                    data: datosGraficos.stockPorCategoria.data,
                    backgroundColor: backgroundColors.slice(0, datosGraficos.stockPorCategoria.data.length),
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    } else if (ctxCategorias) {
        ctxCategorias.parentElement.innerHTML = '<p class="text-center text-muted">No hay datos de stock por categoría para mostrar.</p>';
    }

    // --- 4. Dibujar Gráfico de Barras (Depósitos) ---
    const ctxDepositos = document.getElementById('graficoDepositos');
    if (ctxDepositos && datosGraficos.stockPorDeposito && datosGraficos.stockPorDeposito.data.length > 0) {
        new Chart(ctxDepositos, {
            type: 'bar',
            data: {
                labels: datosGraficos.stockPorDeposito.labels,
                datasets: [{
                    label: 'Stock Total',
                    data: datosGraficos.stockPorDeposito.data,
                    backgroundColor: backgroundColors,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false 
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    } else if (ctxDepositos) {
        ctxDepositos.parentElement.innerHTML = '<p class="text-center text-muted">No hay datos de stock por depósito para mostrar.</p>';
    }
    
});
</script>