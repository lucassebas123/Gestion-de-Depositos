<?php
// ======================================================================
// ANULAR MOVIMIENTO (PÁGINA DE CONFIRMACIÓN)
// ======================================================================

// 1. Cargar SOLO Auth y Funciones (SIN HTML)
require_once 'src/auth_check.php';
require_once 'src/funciones_db.php';

// ======================================================================
// ⭐️ CAMBIO: Añadida comprobación de 'supervisor' ⭐️
// ======================================================================
if ($USUARIO_ROL !== 'admin' && $USUARIO_ROL !== 'supervisor') {
    // Si no es admin ni supervisor, no tiene nada que hacer aquí.
    header("Location: inicio.php?error=" . urlencode("Acceso denegado. Se requiere rol de Supervisor o Administrador."));
    exit;
}
// ======================================================================
// FIN DE LA VALIDACIÓN
// ======================================================================

// 2. Lógica de Controlador (Validaciones y POST)
$mensaje_error = "";
$movimiento = null;

if (!isset($_GET['id']) || empty((int)$_GET['id'])) {
    header("Location: historial_movimientos.php?error=ID de movimiento no válido.");
    exit;
}
$movimiento_id = (int)$_GET['id'];

try {
    // --- MANEJO DEL POST (Confirmación de anulación) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST['movimiento_id']) && (int)$_POST['movimiento_id'] == $movimiento_id) {
            
            // Usar el $pdo global y $USUARIO_ID global (que auth_check.php nos dio)
            list($exito, $mensaje) = anular_movimiento_db($pdo, $movimiento_id, $USUARIO_ID);
            
            if ($exito) {
                header("Location: historial_movimientos.php?exito=" . urlencode($mensaje));
                exit;
            } else {
                $mensaje_error = $mensaje; // Mostrar el error en esta misma página
            }
        } else {
            $mensaje_error = "Error de validación. Intente de nuevo.";
        }
    }

    // --- OBTENER DATOS (GET) ---
    // (Usamos el $pdo global)
    $movimiento = obtener_movimiento_por_id($pdo, $movimiento_id);
    
    if (!$movimiento) {
        header("Location: historial_movimientos.php?error=El movimiento no existe.");
        exit;
    }
    
    if ($movimiento['anulado_por_id'] != null) {
         header("Location: historial_movimientos.php?error=Este movimiento ya fue anulado.");
        exit;
    }
    
    // Mantenemos la regla de que los Ajustes no se anulan
    if ($movimiento['tipo_movimiento'] == 'AJUSTE') {
         header("Location: historial_movimientos.php?error=Los ajustes no se pueden anular. Realice un nuevo ajuste para corregir.");
        exit;
    }

} catch (\PDOException $e) {
    $mensaje_error = "Error Crítico de Conexión: " . $e->getMessage();
}

// 3. Ahora que toda la lógica de 'header()' terminó, cargamos la VISTA
$titulo_pagina = "Confirmar Anulación";
require_once 'src/header.php'; // Carga <head>
require_once 'src/menu.php';   // Carga <nav>

// 4. Renderizar VISTA (HTML)
?>
<div class="content-wrapper">
<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            
            <h1 class="text-center mb-4 text-danger">Confirmar Anulación</h1>

            <?php if ($mensaje_error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-danger">
                <div class="card-body p-4">
                    <p class="fs-5 text-center">¿Está seguro de que desea anular el siguiente movimiento? Esta acción es irreversible.</p>
                    <hr>
                    
                    <?php if ($movimiento): ?>
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>ID de Movimiento:</strong>
                                <span>#<?php echo htmlspecialchars($movimiento['id']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Tipo:</strong>
                                <?php 
                                    $tipo = $movimiento['tipo_movimiento'];
                                    $clase = ($tipo == 'ENTRADA') ? 'bg-success' : 'bg-danger';
                                    echo "<span class='badge $clase'>$tipo</span>";
                                ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Cantidad:</strong>
                                <strong class="<?php echo ($movimiento['cantidad_movida'] > 0) ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($movimiento['cantidad_movida'] > 0 ? '+' : '') . $movimiento['cantidad_movida']; ?>
                                </strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Fecha Original:</strong>
                                <span><?php echo htmlspecialchars($movimiento['fecha']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Observaciones:</strong>
                                <span><?php echo htmlspecialchars($movimiento['observaciones'] ?? 'N/A'); ?></span>
                            </li>
                        </ul>
                    <?php endif; ?>

                    <form action="anular_movimiento.php?id=<?php echo $movimiento_id; ?>" method="POST">
                        <input type="hidden" name="movimiento_id" value="<?php echo $movimiento_id; ?>">
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <a href="historial_movimientos.php" class="btn btn-secondary btn-lg">
                                &lt; Cancelar
                            </a>
                            <button type="submit" class="btn btn-danger btn-lg">
                                Sí, Confirmar Anulación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> </div> <?php 
// 5. Cargar el footer
require 'src/footer.php'; 

?>
