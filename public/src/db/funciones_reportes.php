<?php
/**
 * MÓDULO: FUNCIONES DE REPORTES Y GRÁFICOS
 * (Separado de funciones_db.php v5.0)
 *
 * v5.2 - [Asistente] Agregada función obtener_conteo_vencimientos_proximos para Dashboard.
 */

// ======================================================================
// 6. FUNCIONES DE DASHBOARD / REPORTES
// ======================================================================
function obtener_conteo_stock_bajo($pdo) {
    $query = "
        SELECT COUNT(s.id)
        FROM stock s
        JOIN insumos i ON s.insumo_id = i.id
        WHERE i.activo = 1 AND s.cantidad <= i.stock_minimo
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("Error en obtener_conteo_stock_bajo: " . $e->getMessage());
        return 0;
    }
}

function obtener_lista_stock_bajo($pdo) {
    $query = "
        SELECT 
            i.nombre AS insumo_nombre,
            d.nombre AS deposito_nombre,
            s.cantidad,
            i.stock_minimo
        FROM stock s
        JOIN insumos i ON s.insumo_id = i.id
        JOIN depositos d ON s.deposito_id = d.id
        WHERE i.activo = 1 
        AND s.cantidad <= i.stock_minimo
        ORDER BY i.nombre, d.nombre
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_lista_stock_bajo: " . $e->getMessage());
        return [];
    }
}


function obtener_conteo_total_insumos($pdo) {
    $query = "SELECT COUNT(id) FROM insumos WHERE activo = 1";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("Error en obtener_conteo_total_insumos: " . $e->getMessage());
        return 0;
    }
}

function obtener_ultimos_movimientos($pdo, $limite = 5) {
    $query = "
        SELECT 
            m.fecha, 
            m.tipo_movimiento, 
            m.cantidad_movida,
            i.nombre AS insumo_nombre,
            d.nombre AS deposito_nombre,
            u.username
        FROM movimientos m
        JOIN insumos i ON m.insumo_id = i.id
        JOIN depositos d ON m.deposito_id = d.id
        LEFT JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.anulado_por_id IS NULL 
        AND m.estado = 'EFECTIVO'
        ORDER BY m.fecha DESC 
        LIMIT ?
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(1, $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_ultimos_movimientos: " . $e->getMessage());
        return [];
    }
}

function obtener_stock_agrupado($pdo) {
    $query = "
        SELECT 
            i.agrupador, 
            SUM(s.cantidad) as stock_total, 
            GROUP_CONCAT(
                CONCAT(i.nombre, ' (Stock: ', s.cantidad, ')') 
                SEPARATOR '||'
            ) as insumos_incluidos
        FROM stock s
        JOIN insumos i ON s.insumo_id = i.id
        WHERE 
            i.agrupador IS NOT NULL 
            AND i.agrupador != '' 
            AND s.cantidad > 0
        GROUP BY 
            i.agrupador
        ORDER BY 
            i.agrupador ASC
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_stock_agrupado: " . $e->getMessage());
        return [];
    }
}

// ======================================================================
// 9. FUNCIONES DE LOTES Y VENCIMIENTOS
// ======================================================================
function obtener_lotes_proximos_a_vencer($pdo, $dias_limite = 30, $limit = null, $offset = null) {
    $fecha_limite = date('Y-m-d', strtotime("+$dias_limite days"));
    $query = "
        SELECT 
            l.numero_lote,
            l.fecha_vencimiento,
            l.cantidad_actual,
            i.nombre AS insumo_nombre,
            d.nombre AS deposito_nombre
        FROM stock_lotes l
        JOIN insumos i ON l.insumo_id = i.id
        JOIN depositos d ON l.deposito_id = d.id
        WHERE 
            l.cantidad_actual > 0
            AND l.fecha_vencimiento IS NOT NULL
            AND l.fecha_vencimiento <= :fecha_limite
        ORDER BY 
            l.fecha_vencimiento ASC, 
            i.nombre
    ";
    
    $params = [':fecha_limite' => $fecha_limite];

    if ($limit !== null && $offset !== null) {
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;
    }

    try {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_lotes_proximos_a_vencer: " . $e->getMessage());
        return [];
    }
}

function obtener_total_lotes_proximos_a_vencer($pdo, $dias_limite = 30) {
    $fecha_limite = date('Y-m-d', strtotime("+$dias_limite days"));
    $query = "
        SELECT COUNT(l.id)
        FROM stock_lotes l
        WHERE 
            l.cantidad_actual > 0
            AND l.fecha_vencimiento IS NOT NULL
            AND l.fecha_vencimiento <= :fecha_limite
    ";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([':fecha_limite' => $fecha_limite]);
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("Error en obtener_total_lotes_proximos_a_vencer: " . $e->getMessage());
        return 0;
    }
}

/**
 * ⭐️ NUEVA FUNCIÓN PARA EL DASHBOARD ⭐️
 * Cuenta cuántos lotes vencen en los próximos X días (default 30) o ya están vencidos.
 */
function obtener_conteo_vencimientos_proximos($pdo, $dias = 30) {
    // Usamos la misma lógica que obtener_total_lotes_proximos_a_vencer
    // para mantener coherencia, pero con un nombre más claro para el dashboard.
    return obtener_total_lotes_proximos_a_vencer($pdo, $dias);
}

// ======================================================================
// 11. FUNCIONES DE GRÁFICOS (DASHBOARD)
// ======================================================================

function obtener_stock_por_categoria($pdo, $lista_depositos_permitidos = null) {
    $query = "
        SELECT 
            c.nombre AS label, 
            SUM(s.cantidad) AS data
        FROM stock s
        JOIN insumos i ON s.insumo_id = i.id
        JOIN categorias c ON i.categoria_id = c.id
        WHERE s.cantidad > 0
    ";
    
    $params = [];

    if ($lista_depositos_permitidos !== null) {
        if (empty($lista_depositos_permitidos)) {
            $query .= " AND 1 = 0";
        } else {
            $placeholders_perm = [];
            foreach ($lista_depositos_permitidos as $idx => $dep_id) {
                $key = ':dep_perm_' . $idx;
                $placeholders_perm[] = $key;
                $params[$key] = (int)$dep_id;
            }
            $query .= " AND s.deposito_id IN (" . implode(',', $placeholders_perm) . ")";
        }
    }
    
    $query .= " GROUP BY c.nombre ORDER BY data DESC LIMIT 5";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params); 
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_stock_por_categoria: " . $e->getMessage());
        return [];
    }
}

function obtener_stock_por_deposito($pdo, $lista_depositos_permitidos = null) {
    $query = "
        SELECT 
            d.nombre AS label, 
            SUM(s.cantidad) AS data
        FROM stock s
        JOIN depositos d ON s.deposito_id = d.id
        WHERE s.cantidad > 0
    ";
    
    $params = [];

    if ($lista_depositos_permitidos !== null) {
        if (empty($lista_depositos_permitidos)) {
            $query .= " AND 1 = 0";
        } else {
            $placeholders_perm = [];
            foreach ($lista_depositos_permitidos as $idx => $dep_id) {
                $key = ':dep_perm_' . $idx;
                $placeholders_perm[] = $key;
                $params[$key] = (int)$dep_id;
            }
            $query .= " AND s.deposito_id IN (" . implode(',', $placeholders_perm) . ")";
        }
    }
    
    $query .= " GROUP BY d.nombre ORDER BY data DESC";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_stock_por_deposito: " . $e->getMessage());
        return [];
    }
}

// ======================================================================
// 12. FUNCIONES DE REPORTE DE AUDITORÍA
// ======================================================================

/**
 * Obtiene el historial de consultas de stock con paginación y filtros.
 */
function obtener_consultas_stock(
    $pdo, 
    $filtro_usuario_id = null, 
    $filtro_deposito_id = null,
    $limit = null, 
    $offset = null
) {
    $query = "
        SELECT 
            a.id,
            a.fecha_consulta,
            a.stock_consultado,
            u.username,
            d.nombre AS deposito_nombre,
            i.nombre AS insumo_nombre,
            i.sku AS insumo_sku
        FROM auditoria_consultas_stock a
        JOIN usuarios u ON a.usuario_id = u.id
        JOIN depositos d ON a.deposito_id = d.id
        JOIN insumos i ON a.insumo_id = i.id
        WHERE 1=1
    ";
    
    $params = [];

    if (!empty($filtro_usuario_id)) {
        $query .= " AND a.usuario_id = :usuario_id";
        $params[':usuario_id'] = (int)$filtro_usuario_id;
    }
    
    if (!empty($filtro_deposito_id)) {
        $query .= " AND a.deposito_id = :deposito_id";
        $params[':deposito_id'] = (int)$filtro_deposito_id;
    }

    $query .= " ORDER BY a.fecha_consulta DESC";

    if ($limit !== null && $offset !== null) {
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;
    }
    
    try {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_consultas_stock: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene el conteo total de consultas de stock para paginación.
 */
function obtener_total_consultas_stock(
    $pdo, 
    $filtro_usuario_id = null, 
    $filtro_deposito_id = null
) {
    $query = "
        SELECT COUNT(a.id)
        FROM auditoria_consultas_stock a
        WHERE 1=1
    ";
    
    $params = [];

    if (!empty($filtro_usuario_id)) {
        $query .= " AND a.usuario_id = :usuario_id";
        $params[':usuario_id'] = (int)$filtro_usuario_id;
    }
    
    if (!empty($filtro_deposito_id)) {
        $query .= " AND a.deposito_id = :deposito_id";
        $params[':deposito_id'] = (int)$filtro_deposito_id;
    }
    
    try {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("Error en obtener_total_consultas_stock: " . $e->getMessage());
        return 0;
    }
}
?>