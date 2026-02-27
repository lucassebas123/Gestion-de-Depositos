<?php
// ======================================================================
// 5D. FUNCIONES DE CONSULTA DE MOVIMIENTOS (VISTA: historial_movimientos.php)
// ======================================================================

function obtener_historial_movimientos_db(
    $pdo, 
    $deposito_id = null, 
    $busqueda = null, 
    $lista_depositos_permitidos = null, 
    $filtro_tipo = null,
    $filtro_usuario_id = null,
    $limit = null,
    $offset = null
) {
    $query = "
        SELECT 
            m.id, 
            m.fecha, 
            m.fecha_efectiva,
            m.estado,
            m.tipo_movimiento, 
            m.cantidad_movida,
            m.observaciones, 
            m.recibo_path,
            m.anulado_por_id,
            m.numero_lote,
            m.fecha_vencimiento,
            i.nombre AS insumo_nombre,
            c.nombre AS categoria_nombre,
            d.nombre AS deposito_nombre,
            u.username,
            u_anula.username AS anulado_por_username
        FROM movimientos m
        JOIN insumos i ON m.insumo_id = i.id
        JOIN categorias c ON i.categoria_id = c.id
        JOIN depositos d ON m.deposito_id = d.id
        LEFT JOIN usuarios u ON m.usuario_id = u.id
        LEFT JOIN usuarios u_anula ON m.anulado_por_id = u_anula.id
        WHERE 1=1
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
            $query .= " AND m.deposito_id IN (" . implode(',', $placeholders_perm) . ")";
        }
    }

    if (!empty($deposito_id)) {
        $query .= " AND m.deposito_id = :deposito_id";
        $params[':deposito_id'] = $deposito_id;
    }
    
    if (!empty($filtro_tipo)) {
        $query .= " AND m.tipo_movimiento = :filtro_tipo";
        $params[':filtro_tipo'] = $filtro_tipo;
    }
    
    if (!empty($filtro_usuario_id)) {
        $query .= " AND m.usuario_id = :filtro_usuario_id";
        $params[':filtro_usuario_id'] = $filtro_usuario_id;
    }
    
    if (!empty($busqueda)) {
        $query .= " AND (i.nombre LIKE :like_term OR c.nombre LIKE :like_term2 OR d.nombre LIKE :like_term3)";
        $like_term = "%{$busqueda}%";
        $params[':like_term'] = $like_term;
        $params[':like_term2'] = $like_term;
        $params[':like_term3'] = $like_term;
    }
    
    $query .= " ORDER BY m.fecha_efectiva DESC, m.id DESC";

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
        error_log("Error en obtener_historial_movimientos_db: " . $e->getMessage());
        return [];
    }
}

function obtener_total_movimientos(
    $pdo, 
    $deposito_id = null, 
    $busqueda = null, 
    $lista_depositos_permitidos = null, 
    $filtro_tipo = null,
    $filtro_usuario_id = null
) {
     $query = "
        SELECT COUNT(m.id)
        FROM movimientos m
        JOIN insumos i ON m.insumo_id = i.id
        JOIN categorias c ON i.categoria_id = c.id
        JOIN depositos d ON m.deposito_id = d.id
        LEFT JOIN usuarios u ON m.usuario_id = u.id
        LEFT JOIN usuarios u_anula ON m.anulado_por_id = u_anula.id
        WHERE 1=1
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
            $query .= " AND m.deposito_id IN (" . implode(',', $placeholders_perm) . ")";
        }
    }

    if (!empty($deposito_id)) {
        $query .= " AND m.deposito_id = :deposito_id";
        $params[':deposito_id'] = $deposito_id;
    }
    
    if (!empty($filtro_tipo)) {
        $query .= " AND m.tipo_movimiento = :filtro_tipo";
        $params[':filtro_tipo'] = $filtro_tipo;
    }
    
    if (!empty($filtro_usuario_id)) {
        $query .= " AND m.usuario_id = :filtro_usuario_id";
        $params[':filtro_usuario_id'] = $filtro_usuario_id;
    }
    
    if (!empty($busqueda)) {
        $query .= " AND (i.nombre LIKE :like_term OR c.nombre LIKE :like_term2 OR d.nombre LIKE :like_term3)";
        $like_term = "%{$busqueda}%";
        $params[':like_term'] = $like_term;
        $params[':like_term2'] = $like_term;
        $params[':like_term3'] = $like_term;
    }
    
    try {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("Error en obtener_total_movimientos: " . $e->getMessage());
        return 0;
    }
}


function obtener_movimiento_detalle_db($pdo, $movimiento_id) {
    $query = "
        SELECT 
            m.id, 
            m.fecha, 
            m.fecha_efectiva,
            m.estado,
            m.tipo_movimiento, 
            m.cantidad_movida,
            m.observaciones, 
            m.recibo_path,
            m.anulado_por_id,
            m.numero_lote,
            m.fecha_vencimiento,
            i.nombre AS insumo_nombre,
            i.sku AS insumo_sku,
            i.descripcion AS insumo_descripcion,
            c.nombre AS categoria_nombre,
            d.nombre AS deposito_nombre,
            u.username AS usuario_creador,
            u_anula.username AS usuario_anulador
        FROM movimientos m
        JOIN insumos i ON m.insumo_id = i.id
        JOIN categorias c ON i.categoria_id = c.id
        JOIN depositos d ON m.deposito_id = d.id
        LEFT JOIN usuarios u ON m.usuario_id = u.id
        LEFT JOIN usuarios u_anula ON m.anulado_por_id = u_anula.id
        WHERE m.id = ?
    ";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$movimiento_id]);
        return $stmt->fetch();
    } catch (\PDOException $e) {
        error_log("Error en obtener_movimiento_detalle_db: " . $e->getMessage());
        return null;
    }

}
