<?php
// ======================================================================
// REPORTE DE AUDITORÍA DE CONSULTAS
// ======================================================================
// MODIFICADO v2.0 - [Asistente] Diseño Premium (Métricas + Avatares)

require_once 'src/init_admin.php'; // Solo admins

$titulo_pagina = "Reporte de Auditoría";

// 1. Filtros
$filtro_usuario = $_GET['usuario_id'] ?? '';
$filtro_deposito = $_GET['deposito_id'] ?? '';

// 2. Obtener Datos (Usando función existente)
try {
    $usuarios = obtener_todos_los_usuarios($pdo);
    $depositos = obtener_depositos_por_usuario($pdo, $USUARIO_ID, 'admin', true);
    
    // Paginación simple (o cargar últimos 100 para rendimiento)
    $limit = 100; 
    $consultas = obtener_consultas_stock($pdo, $filtro_usuario, $filtro_deposito, $limit, 0);
    
    // --- CÁLCULO DE MÉTRICAS RÁPIDAS (Ad-Hoc) ---
    // 1. Total Consultas Hoy
    $stmtHoy = $pdo->query("SELECT COUNT(*) FROM auditoria_consultas_stock WHERE DATE(fecha_consulta) = CURDATE()");
    $total_hoy = $stmtHoy->fetchColumn();

    // 2. Usuario más activo (Histórico)
    $stmtTopUser = $pdo->query("
        SELECT u.username, COUNT(a.id) as c 
        FROM auditoria_consultas_stock a 
        JOIN usuarios u ON a.usuario_id = u.id 
        GROUP BY u.id ORDER BY c DESC LIMIT 1
    ");
    $top_user = $stmtTopUser->fetch(PDO::FETCH_ASSOC);

    // 3. Insumo más consultado (Histórico)
    $stmtTopItem = $pdo->query("
        SELECT i.nombre, COUNT(a.id) as c 
        FROM auditoria_consultas_stock a 
        JOIN insumos i ON a.insumo_id = i.id 
        GROUP BY i.id ORDER BY c DESC LIMIT 1
    ");
    $top_item = $stmtTopItem->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $mensaje_error = "Error: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* Estilos de Avatar (Consistente con Usuarios) */
    .user-avatar-sm {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: 600;
        font-size: 0.75rem;
        color: #fff;
        margin-right: 0.75rem;
        text-transform: uppercase;
    }
    .user-cell { display: flex; align-items: center; }
  </style>
</head>
<body class="bg-light">

<div class="content-wrapper">
    <?php require 'src/navbar.php'; ?>
    
    <div class="page-content-container">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Auditoría de Consultas</h2>
                <p class="text-muted mb-0 small">Monitoreo de quién consulta el stock y desde dónde.</p>
            </div>
            <button onclick="exportTableToCSV('auditoria_stock.csv')" class="btn btn-success shadow-sm">
                <i class="bi bi-file-earmark-excel-fill me-2"></i>Exportar
            </button>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-metric border-0 shadow-sm h-100" style="border-left: 4px solid var(--brand-primary);">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-primary-subtle p-3 rounded-circle me-3 text-primary">
                            <i class="bi bi-activity fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase mb-1 small fw-bold">Consultas Hoy</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($total_hoy); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-metric border-0 shadow-sm h-100" style="border-left: 4px solid var(--success);">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-success-subtle p-3 rounded-circle me-3 text-success">
                            <i class="bi bi-person-fill-up fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase mb-1 small fw-bold">Usuario Más Activo</h6>
                            <h5 class="mb-0 fw-bold text-truncate" style="max-width: 150px;">
                                <?php echo htmlspecialchars($top_user['username'] ?? 'N/A'); ?>
                            </h5>
                            <small class="text-muted"><?php echo ($top_user['c'] ?? 0); ?> consultas</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-metric border-0 shadow-sm h-100" style="border-left: 4px solid var(--warning);">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-warning-subtle p-3 rounded-circle me-3 text-warning-emphasis">
                            <i class="bi bi-star-fill fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase mb-1 small fw-bold">Insumo Más Buscado</h6>
                            <h5 class="mb-0 fw-bold text-truncate" style="max-width: 150px;">
                                <?php echo htmlspecialchars($top_item['nombre'] ?? 'N/A'); ?>
                            </h5>
                            <small class="text-muted"><?php echo ($top_item['c'] ?? 0); ?> veces</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3 bg-white rounded">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Filtrar por Usuario</label>
                        <select name="usuario_id" class="form-select form-select-sm">
                            <option value="">-- Todos --</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo ($filtro_usuario == $u['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Filtrar por Depósito</label>
                        <select name="deposito_id" class="form-select form-select-sm">
                            <option value="">-- Todos --</option>
                            <?php foreach ($depositos as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo ($filtro_deposito == $d['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-funnel-fill me-1"></i> Filtrar
                        </button>
                        <?php if($filtro_usuario || $filtro_deposito): ?>
                            <a href="reporte_auditoria.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-muted small text-uppercase fw-bold">Últimos Registros de Actividad</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaAuditoria">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Insumo Consultado</th>
                            <th>Depósito Objetivo</th>
                            <th class="text-center">Stock Devuelto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultas)): ?>
                            <tr>
                                <td colspan="5" class="text-center p-5">
                                    <i class="bi bi-search empty-state-icon opacity-25 mb-3" style="font-size: 3rem;"></i>
                                    <p class="text-muted">No se encontraron registros de auditoría.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($consultas as $row): ?>
                                <tr>
                                    <td class="text-muted small">
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i:s', strtotime($row['fecha_consulta'])); ?>
                                    </td>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar-sm shadow-sm" data-username="<?php echo htmlspecialchars($row['username']); ?>">
                                                <?php echo strtoupper(substr($row['username'], 0, 2)); ?>
                                            </div>
                                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($row['username']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['insumo_nombre']); ?></div>
                                        <div class="small text-muted font-monospace"><?php echo htmlspecialchars($row['insumo_sku']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-normal">
                                            <i class="bi bi-geo-alt me-1 text-muted"></i>
                                            <?php echo htmlspecialchars($row['deposito_nombre']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                            $stock = (int)$row['stock_consultado'];
                                            if ($stock === 0) {
                                                echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 rounded-pill">0</span>';
                                            } elseif ($stock < 10) {
                                                echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 rounded-pill">' . $stock . '</span>';
                                            } else {
                                                echo '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 rounded-pill">' . $stock . '</span>';
                                            }
                                        ?>
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

<?php require 'src/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Colorear Avatares (Igual que en Gestión Usuarios)
    function getColorAvatar(str) {
        const colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6610f2', '#fd7e14', '#20c997'];
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        return colors[Math.abs(hash) % colors.length];
    }

    document.querySelectorAll('.user-avatar-sm').forEach(avatar => {
        const username = avatar.dataset.username || '??';
        avatar.style.backgroundColor = getColorAvatar(username);
    });

    // 2. Exportar a Excel (CSV)
    window.exportTableToCSV = function(filename) {
        let csv = [];
        let rows = document.querySelectorAll("#tablaAuditoria tr");
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ").trim();
                row.push('"' + data + '"');
            }
            csv.push(row.join(","));
        }
        
        let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
        let downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
    };
});
</script>