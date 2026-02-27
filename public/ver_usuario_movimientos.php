<?php
// ======================================================
// VER MOVIMIENTOS POR USUARIO
// ======================================================
// v1.1 - [Asistente] Corregido path y variable no definida

// 1. Cargar el NÚCLEO DE ADMIN (Solo admin puede ver esto)
// ⭐️ CORRECCIÓN DE RUTA: La ruta correcta es a '/src/' que está al mismo nivel
require_once __DIR__ . '/src/init_admin.php';

// 2. Obtener el ID del usuario
$usuario_id = (int)($_GET['id'] ?? 0);
if ($usuario_id <= 0) {
    header("Location: gestion_usuarios.php?mensaje_error=ID de usuario no válido.");
    exit;
}

// 3. Obtener datos
$usuario = null;
$movimientos = [];
$dias_filtro = 7; // Por defecto, últimos 7 días

// ⭐️ CORRECCIÓN DE BUG: Inicializar la variable
$mensaje_error = "";

if (isset($_GET['dias']) && (int)$_GET['dias'] > 0) {
    $dias_filtro = (int)$_GET['dias'];
}

try {
    $usuario = obtener_usuario_por_id($pdo, $usuario_id);
    if (!$usuario) {
        header("Location: gestion_usuarios.php?mensaje_error=Usuario no encontrado.");
        exit;
    }
    
    // Obtener los movimientos de este usuario
    $movimientos = obtener_movimientos_por_usuario_id($pdo, $usuario_id, $dias_filtro);

} catch (\PDOException $e) {
    $mensaje_error = "Error de base de datos: " . $e->getMessage();
}

// 4. Definir título
$titulo_pagina = "Actividad de " . htmlspecialchars($usuario['username']);

// 5. Renderizar VISTA
?>
<div class="content-wrapper">

    <?php require 'src/navbar.php'; ?>

    <div class="page-content-container">

        <h2 class="mb-4">
            <a href="gestion_usuarios.php" class="btn btn-light btn-lg me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            Actividad de Usuario: <?php echo htmlspecialchars($usuario['username']); ?>
        </h2>
        
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Mostrando movimientos de los últimos <?php echo $dias_filtro; ?> días</h5>
                
                <form action="ver_usuario_movimientos.php" method="GET" class="d-flex">
                    <input type="hidden" name="id" value="<?php echo $usuario_id; ?>">
                    <label for="dias" class="form-label me-2 col-auto col-form-label">Ver:</label>
                    <select name="dias" id="dias" class="form-select" onchange="this.form.submit()">
                        <option value="3" <?php echo ($dias_filtro == 3) ? 'selected' : ''; ?>>Últimos 3 días</option>
                        <option value="7" <?php echo ($dias_filtro == 7) ? 'selected' : ''; ?>>Últimos 7 días</option>
                        <option value="30" <?php echo ($dias_filtro == 30) ? 'selected' : ''; ?>>Últimos 30 días</option>
                    </select>
                </form>
            </div>
            <div class="card-body">
                
                <?php if ($mensaje_error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha Carga</th>
                                <th>Fecha Efectiva</th>
                                <th>Estado</th>
                                <th>Insumo</th>
                                <th>Depósito</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($movimientos)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        Este usuario no ha registrado movimientos en este período.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($movimientos as $mov): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($mov['fecha'], 0, 16)); ?></td>
                                        <td>
                                            <?php if ($mov['estado'] == 'PROGRAMADO'): ?>
                                                <small>Programado:</small><br>
                                                <strong><?php echo htmlspecialchars(substr($mov['fecha_efectiva'], 0, 10)); ?></strong>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars(substr($mov['fecha_efectiva'], 0, 16)); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($mov['estado'] == 'PROGRAMADO'): ?>
                                                <span class="badge bg-info text-dark">Programado</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Efectivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($mov['insumo_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($mov['deposito_nombre']); ?></td>
                                        <td>
                                            <?php 
                                                $tipo = $mov['tipo_movimiento'];
                                                $clase = 'secondary';
                                                if ($tipo == 'ENTRADA') $clase = 'success';
                                                if ($tipo == 'SALIDA') $clase = 'danger';
                                                if ($tipo == 'AJUSTE') $clase = 'warning text-dark';
                                            ?>
                                            <span class="badge bg-<?php echo $clase; ?>"><?php echo $tipo; ?></span>
                                        </td>
                                        <td>
                                            <strong class="text-<?php echo ($mov['cantidad_movida'] > 0) ? 'success' : 'danger'; ?>">
                                                <?php echo ($mov['cantidad_movida'] > 0 ? '+' : '') . $mov['cantidad_movida']; ?>
                                            </strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($mov['observaciones']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div> </div> 
<?php 
// Cargar el Footer
require_once __DIR__ . '/src/footer.php'; 
?>
</body>
</html>