<?php
// ======================================================================
// VER DETALLE DE PROVEEDOR (Para Imprimir)
// ======================================================================
// v1.0 - Creado para la vista de detalles
declare(strict_types=1);

// 1. Cargar Auth y Funciones (SIN EL MENÚ)
// Usamos init_supervisor para asegurarnos que solo roles altos vean esto
require_once 'src/init_supervisor.php'; 

// 2. Obtener el ID
$proveedor_id = (int)($_GET['id'] ?? 0);
if ($proveedor_id <= 0) {
    die("ID de proveedor no válido.");
}

// 3. Obtener datos del proveedor
try {
    $p = obtener_proveedor_por_id($pdo, $proveedor_id);
    if (!$p) {
        die("Proveedor no encontrado.");
    }
} catch (\Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// 4. Definir título (para la pestaña del navegador)
$titulo_pagina = "Detalle Proveedor #" . $p['id'];

// 5. Cargar solo el <head> (CSS)
// (init_supervisor ya cargó header.php y menu.php, así que ocultamos el menú con CSS)

?>
<style>
    /* Estilos para la página de impresión */
    body {
        background-color: #f8f9fa !important;
    }
    
    /* Ocultar el menú lateral y la barra superior */
    .sidebar, .main-navbar {
        display: none !important;
    }
    /* El contenido principal ocupa todo */
    .content-wrapper {
        margin-left: 0 !important;
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
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
    .list-group-item strong {
        display: inline-block;
        width: 150px;
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
        /* Ocultar elementos que no se imprimen */
        .sidebar, .main-navbar, .no-print {
            display: none !important;
        }
        .content-wrapper {
            padding: 0 !important;
        }
        .page-content-container {
             padding: 0 !important;
        }
        .detalle-container {
            max-width: 100%;
            margin: 0;
            border: none;
            box-shadow: none;
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
            <h1 class="h3 mb-0">Detalle de Proveedor</h1>
            <p class="mb-0 text-muted">ID de Registro: #<?php echo $p['id']; ?></p>
        </div>
        <button class="btn btn-primary no-print" onclick="window.print();">
            <i class="bi bi-printer-fill me-2"></i>Imprimir / Guardar PDF
        </button>
    </div>
    
    <div class="detalle-body">

        <div class="row mb-3">
            <div class="col-md-8">
                <h2 class="h4"><?php echo htmlspecialchars($p['nombre']); ?></h2>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if ($p['activo']): ?>
                    <span class="badge bg-success badge-detalle">Activo</span>
                <?php else: ?>
                    <span class="badge bg-secondary badge-detalle">Inactivo</span>
                <?php endif; ?>
            </div>
        </div>

        <hr>
        
        <h5 class="mb-3 mt-4">Información de Contacto</h5>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <i class="bi bi-person-fill me-2 text-muted" style="width: 20px;"></i>
                <strong>Contacto:</strong> <?php echo htmlspecialchars($p['contacto'] ?? 'N/A'); ?>
            </li>
            <li class="list-group-item">
                <i class="bi bi-telephone-fill me-2 text-muted" style="width: 20px;"></i>
                <strong>Teléfono:</strong> <?php echo htmlspecialchars($p['telefono'] ?? 'N/A'); ?>
            </li>
            <li class="list-group-item">
                <i class="bi bi-envelope-fill me-2 text-muted" style="width: 20px;"></i>
                <strong>Email:</strong> <?php echo htmlspecialchars($p['email'] ?? 'N/A'); ?>
            </li>
            <li class="list-group-item">
                <i class="bi bi-geo-alt-fill me-2 text-muted" style="width: 20px;"></i>
                <strong>Dirección:</strong> <?php echo htmlspecialchars($p['direccion'] ?? 'N/A'); ?>
            </li>
        </ul>

    </div>
</div>

<?php 
// No cargamos footer.php
?>
</body>
</html>