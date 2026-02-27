<?php
// ======================================================================
// VER DETALLE DE INSUMO (FICHA DE PRODUCTO)
// ======================================================================
// v1.1 - Vista de solo lectura "Premium" (Completa)

require_once 'src/init.php';

// 1. Validar ID
if (!isset($_GET['id']) || empty((int)$_GET['id'])) {
    header("Location: inicio.php");
    exit;
}
$insumo_id = (int)$_GET['id'];

try {
    // A. Obtener Datos del Insumo + Categoría + Proveedor
    $stmt = $pdo->prepare("
        SELECT i.*, c.nombre as categoria_nombre, p.nombre as proveedor_nombre 
        FROM insumos i
        LEFT JOIN categorias c ON i.categoria_id = c.id
        LEFT JOIN proveedores p ON i.proveedor_id = p.id
        WHERE i.id = ?
    ");
    $stmt->execute([$insumo_id]);
    $insumo = $stmt->fetch();

    if (!$insumo) {
        die("Insumo no encontrado.");
    }

    // B. Obtener Stock por Depósito
    $stmtStock = $pdo->prepare("
        SELECT d.nombre as deposito, s.cantidad
        FROM stock s
        JOIN depositos d ON s.deposito_id = d.id
        WHERE s.insumo_id = ? AND s.cantidad > 0
        ORDER BY s.cantidad DESC
    ");
    $stmtStock->execute([$insumo_id]);
    $stocks = $stmtStock->fetchAll();
    
    // Calcular total
    $stock_total = 0;
    foreach ($stocks as $s) { $stock_total += $s['cantidad']; }

    // C. Obtener Últimos 10 Movimientos de este Insumo
    $stmtMov = $pdo->prepare("
        SELECT m.*, d.nombre as deposito_nombre, u.username
        FROM movimientos m
        JOIN depositos d ON m.deposito_id = d.id
        LEFT JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.insumo_id = ?
        ORDER BY m.fecha DESC
        LIMIT 10
    ");
    $stmtMov->execute([$insumo_id]);
    $movimientos = $stmtMov->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$titulo_pagina = $insumo['nombre'];
?>
<div class="content-wrapper">
<?php require 'src/navbar.php'; ?>
<div class="page-content-container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-truncate" style="max-width: 80%;">
            <span class="text-muted fw-light">Producto /</span> <?php echo htmlspecialchars($insumo['nombre']); ?>
        </h1>
        <button onclick="history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </button>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center p-4">
                    <div class="mb-4 position-relative d-inline-block">
                        <img src="ver_archivo.php?insumo_id=<?php echo $insumo['id']; ?>" 
                             class="img-fluid rounded shadow-sm" 
                             style="width: 200px; height: 200px; object-fit: cover;"
                             onerror="this.src='https://placehold.co/200x200/f8f9fa/adb5bd?text=Sin+Imagen';">
                        
                        <?php if ($stock_total <= $insumo['stock_minimo']): ?>
                             <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light p-2">
                                <i class="bi bi-exclamation-triangle-fill"></i> Stock Bajo
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h2 class="h4 fw-bold mb-1"><?php echo htmlspecialchars($insumo['nombre']); ?></h2>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($insumo['sku'] ?? 'Sin SKU'); ?></p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-tag-fill me-1 text-primary"></i> <?php echo htmlspecialchars($insumo['categoria_nombre']); ?>
                        </span>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-box-seam me-1"></i> <?php echo htmlspecialchars($insumo['unidad_medida']); ?>
                        </span>
                    </div>

                    <div class="bg-light rounded p-3 border text-start">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Descripción</small>
                        <p class="mb-0 small text-dark mt-1">
                            <?php echo !empty($insumo['descripcion']) ? nl2br(htmlspecialchars($insumo['descripcion'])) : 'Sin descripción disponible.'; ?>
                        </p>
                        <?php if (!empty($insumo['ubicacion'])): ?>
                            <hr class="my-2">
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Ubicación Física</small>
                            <div class="small fw-medium"><i class="bi bi-geo-alt me-1 text-danger"></i><?php echo htmlspecialchars($insumo['ubicacion']); ?></div>
                        <?php endif; ?>
                         <?php if (!empty($insumo['proveedor_nombre'])): ?>
                            <hr class="my-2">
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Proveedor</small>
                            <div class="small fw-medium"><i class="bi bi-truck me-1 text-primary"></i><?php echo htmlspecialchars($insumo['proveedor_nombre']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm border-0 bg-primary text-white" style="background: linear-gradient(45deg, var(--brand-primary), #6a75ca);">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 opacity-75">Stock Total Global</h5>
                                <h2 class="display-4 fw-bold mb-0"><?php echo $stock_total; ?></h2>
                            </div>
                            <i class="bi bi-boxes" style="font-size: 4rem; opacity: 0.2;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0"><i class="bi bi-building me-2"></i>Disponibilidad por Depósito</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($stocks)): ?>
                        <p class="text-muted text-center my-3">No hay stock disponible en ningún depósito.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($stocks as $st): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-3 text-primary">
                                            <i class="bi bi-shop"></i>
                                        </div>
                                        <span class="fw-medium"><?php echo htmlspecialchars($st['deposito']); ?></span>
                                    </div>
                                    <span class="badge bg-success rounded-pill fs-6 px-3">
                                        <?php echo $st['cantidad']; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Historial Reciente</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cant.</th>
                                <th>Depósito</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($movimientos)): ?>
                                <tr><td colspan="5" class="text-center py-3 text-muted">Sin movimientos registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($movimientos as $mov): ?>
                                    <tr>
                                        <td class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?></td>
                                        <td>
                                            <?php if ($mov['tipo_movimiento'] == 'ENTRADA'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">ENTRADA</span>
                                            <?php elseif ($mov['tipo_movimiento'] == 'SALIDA'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">SALIDA</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">AJUSTE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold <?php echo ($mov['tipo_movimiento'] == 'ENTRADA') ? 'text-success' : (($mov['tipo_movimiento'] == 'SALIDA') ? 'text-danger' : ''); ?>">
                                            <?php echo ($mov['tipo_movimiento'] == 'ENTRADA' ? '+' : ($mov['tipo_movimiento'] == 'SALIDA' ? '-' : '')); ?>
                                            <?php echo $mov['cantidad_movida']; ?>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($mov['deposito_nombre']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary fw-normal" style="font-size: 0.7rem;">
                                                <?php echo htmlspecialchars($mov['username']); ?>
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
</div>
</div>
<?php require 'src/footer.php'; ?>