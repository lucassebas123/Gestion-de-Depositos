<?php
// ======================================================================
// VER DETALLE DE MOVIMIENTO (Vista de Comprobante)
// ======================================================================
// v1.2 - Añadido botón para descargar PDF Oficial con Dompdf
declare(strict_types=1);

// 1. Cargar Auth y Funciones (SIN EL MENÚ)
require_once 'src/auth_check.php';
require_once 'src/funciones_db.php';

// 2. Obtener el ID
$movimiento_id = (int)($_GET['id'] ?? 0);
if ($movimiento_id <= 0) {
    die("ID de movimiento no válido.");
}

// 3. Obtener datos del movimiento
try {
    $mov = obtener_movimiento_detalle_db($pdo, $movimiento_id);
    if (!$mov) {
        die("Movimiento no encontrado.");
    }
} catch (\Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// 4. Definir título (para la pestaña del navegador)
$titulo_pagina = "Detalle Movimiento #" . $mov['id'];

// 5. Cargar solo el <head> (CSS)
require 'src/header.php';
// ¡No cargamos menu.php!

?>
<style>
    /* Estilos para la página de impresión */
    body {
        background-color: #f8f9fa !important;
    }
    .detalle-container {
        max-width: 800px;
        margin: 2rem auto;
        background-color: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .detalle-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 1.5rem;
    }
    .detalle-body {
        padding: 1.5rem;
    }
    .list-group-item {
        border-bottom: 1px solid #eee !important;
    }
    .list-group-item strong {
        display: inline-block;
        width: 180px;
        color: #555;
    }
    .badge-detalle {
        font-size: 1rem;
        font-weight: 500;
        padding: 0.5em 0.8em;
    }
    
    /* Estilos solo para impresión */
    @media print {
        body {
            background-color: #ffffff !important;
            margin: 0;
            padding: 0;
        }
        .detalle-container {
            max-width: 100%;
            margin: 0;
            border: none;
            box-shadow: none;
        }
        .no-print {
            display: none !important;
        }
        .detalle-body {
            padding: 0.5rem;
        }
        a {
            text-decoration: none !important;
            color: #000 !important;
        }
        .badge {
            border: 1px solid #000;
            color: #000 !important;
            background-color: #fff !important;
        }
    }
</style>

<div class="detalle-container">
    <div class="detalle-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0">Detalle de Movimiento</h1>
            <p class="mb-0 text-muted">ID de Registro: #<?php echo $mov['id']; ?></p>
        </div>
        
        <div class="btn-group no-print">
            <a href="generar_pdf.php?id=<?php echo $mov['id']; ?>" class="btn btn-danger" target="_blank">
                <i class="bi bi-file-pdf-fill me-2"></i>Descargar PDF Oficial
            </a>
            <button class="btn btn-outline-secondary" onclick="window.print();">
                <i class="bi bi-printer me-2"></i>Imprimir Pantalla
            </button>
        </div>
    </div>
    
    <div class="detalle-body">

        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="mb-3">Información del Insumo</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Insumo:</strong> <?php echo htmlspecialchars($mov['insumo_nombre']); ?></li>
                    <li class="list-group-item"><strong>Categoría:</strong> <?php echo htmlspecialchars($mov['categoria_nombre']); ?></li>
                    <li class="list-group-item"><strong>SKU:</strong> <?php echo htmlspecialchars($mov['insumo_sku'] ?? 'N/A'); ?></li>
                    <li class="list-group-item"><strong>Descripción:</strong> <?php echo htmlspecialchars($mov['insumo_descripcion'] ?? 'N/A'); ?></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h5 class="mb-3">Detalles del Movimiento</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Tipo:</strong>
                        <?php 
                            $tipo = $mov['tipo_movimiento'];
                            $clase = 'secondary';
                            if ($tipo == 'ENTRADA') $clase = 'success';
                            if ($tipo == 'SALIDA') $clase = 'danger';
                            if ($tipo == 'AJUSTE') $clase = 'warning text-dark';
                        ?>
                        <span class="badge bg-<?php echo $clase; ?> badge-detalle"><?php echo $tipo; ?></span>
                    </li>
                    <li class="list-group-item">
                        <strong>Cantidad:</strong>
                        <strong class="fs-4 text-<?php echo ($mov['cantidad_movida'] > 0) ? 'success' : 'danger'; ?>">
                            <?php echo ($mov['cantidad_movida'] > 0 ? '+' : '') . $mov['cantidad_movida']; ?>
                        </strong>
                    </li>
                    <li class="list-group-item"><strong>Depósito:</strong> <?php echo htmlspecialchars($mov['deposito_nombre']); ?></li>
                    <li class="list-group-item"><strong>Observaciones:</strong> <?php echo htmlspecialchars($mov['observaciones'] ?? 'Sin observaciones'); ?></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h5 class="mb-3">Auditoría y Lote</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Estado:</strong>
                        <?php
                            $estado = $mov['estado'];
                            $clase_estado = 'success';
                            if ($mov['anulado_por_id']) { $clase_estado = 'secondary'; $estado = 'Anulado'; }
                            if ($estado == 'PROGRAMADO') { $clase_estado = 'info text-dark'; }
                        ?>
                        <span class="badge bg-<?php echo $clase_estado; ?> badge-detalle"><?php echo $estado; ?></span>
                    </li>
                    <li class="list-group-item"><strong>Fecha de Carga:</strong> <?php echo htmlspecialchars($mov['fecha']); ?></li>
                    <li class="list-group-item"><strong>Fecha Efectiva:</strong> <?php echo htmlspecialchars($mov['fecha_efectiva']); ?></li>
                    <li class="list-group-item"><strong>Usuario Creador:</strong> <?php echo htmlspecialchars($mov['usuario_creador'] ?? 'N/A'); ?></li>
                    <li class="list-group-item"><strong>N° de Lote:</strong> <?php echo htmlspecialchars($mov['numero_lote'] ?? 'N/A'); ?></li>
                    <li class="list-group-item"><strong>Vencimiento:</strong> <?php echo htmlspecialchars($mov['fecha_vencimiento'] ?? 'No aplica'); ?></li>
                    <?php if ($mov['anulado_por_id']): ?>
                    <li class="list-group-item list-group-item-danger"><strong>Anulado por:</strong> <?php echo htmlspecialchars($mov['usuario_anulador'] ?? 'N/A'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

    </div>
</div>

<?php 
// No cargamos footer.php, solo el script de Bootstrap
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>