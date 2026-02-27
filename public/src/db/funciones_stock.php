<?php
/**
 * MÓDULO: FUNCIONES DE STOCK
 * (Separado de funciones_stock_mov.php v5.1)
 *
 * Contiene:
 * 1. Helpers de bajo nivel (actualizar/obtener stock)
 * 2. Consultas a la tabla 'stock' (consultar_stock_db)
 * 3. Función para obtener lotes disponibles (para salidas/traslados)
 */

// ======================================================================
// 5. FUNCIONES DE STOCK (HELPERS)
// ======================================================================

function actualizar_stock_total_db($pdo, $insumo_id, $deposito_id, $cantidad_movida, $es_ajuste = false) {
    if ($es_ajuste) {
        $query = "
            INSERT INTO stock (insumo_id, deposito_id, cantidad) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                cantidad = ? 
        ";
         $params = [$insumo_id, $deposito_id, $cantidad_movida, $cantidad_movida];
    } else {
        $query = "
            INSERT INTO stock (insumo_id, deposito_id, cantidad) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                cantidad = cantidad + ?
        ";
         $params = [$insumo_id, $deposito_id, $cantidad_movida, $cantidad_movida];
    }
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return [true, "Stock total actualizado."];
    } catch (\PDOException $e) {
        error_log("Error en actualizar_stock_total_db: " . $e->getMessage());
        return [false, "Error al actualizar el stock total."];
    }
}

function obtener_stock_actual_insumo($pdo, $insumo_id, $deposito_id) {
    $query = "SELECT cantidad FROM stock WHERE insumo_id = ? AND deposito_id = ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$insumo_id, $deposito_id]);
        $resultado = $stmt->fetchColumn();
        return $resultado !== false ? (int)$resultado : 0;
    } catch (\PDOException $e) {
        error_log("Error en obtener_stock_actual_insumo: " . $e->getMessage());
        return 0; 
    }
}


// ======================================================================
// 5B. FUNCIONES DE CONSULTA DE STOCK (VISTA: historial.php)
// ======================================================================

function consultar_stock_db(
    $pdo, 
    $lista_depositos_permitidos = null, 
    $deposito_id = null, 
    $categoria_id = null, 
    $busqueda = null, 
    $filtro_especial = null,
    $limit = null,
    $offset = null
) {
    $query = "
        SELECT 
            i.nombre AS insumo_nombre,
            c.nombre AS categoria_nombre,
            d.nombre AS deposito_nombre,
            p.nombre as proveedor,
            i.stock_minimo,
            s.cantidad,
            s.fecha_actualizacion,
            i.imagen_path,
            i.id AS insumo_id
        FROM stock s
        JOIN insumos i ON s.insumo_id = i.id
        JOIN categorias c ON i.categoria_id = c.id
        JOIN depositos d ON s.deposito_id = d.id
        LEFT JOIN proveedores p ON i.proveedor_id = p.id
        WHERE i.activo = 1 
    ";
    
    $params = [];

    if ($lista_depositos_permitidos !== null) {
        if (empty($lista_depositos_permitidos)) {
            $query .= " AND 1 = 0"; // No tiene permisos sobre nada
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

    if (!empty($deposito_id)) {
        $query .= " AND s.deposito_id = :deposito_id";
        $params[':deposito_id'] = $deposito_id;
    }
    if (!empty($categoria_id)) {
        $query .= " AND c.id = :categoria_id";
        $params[':categoria_id'] = $categoria_id;
    }
    if (!empty($busqueda)) {
        $query .= " AND (i.nombre LIKE :like_term OR i.sku LIKE :like_term2 OR p.nombre LIKE :like_term3)";
        $like_term = "%{$busqueda}%";
        $params[':like_term'] = $like_term;
        $params[':like_term2'] = $like_term;
        $params[':like_term3'] = $like_term;
    }

    if ($filtro_especial === 'stock_bajo') {
        $query .= " AND s.cantidad <= i.stock_minimo";
    } elseif ($filtro_especial === 'sin_stock') {
        $query .= " AND s.cantidad = 0";
    }
    
    $query .= " ORDER BY i.nombre, d.nombre";

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
        error_log("Error en consultar_stock_db: " . $e->getMessage());
        return [];
    }
}

function obtener_total_stock(
    $pdo, 
    $lista_depositos_permitidos = null, 
    $deposito_id = null, 
    $categoria_id = null, 
    $busqueda = null, 
    $filtro_especial = null
) {
    $query = "
        SELECT COUNT(s.id)
        FROM stock s
        JOIN insumos i ON s.insumo_id = i.id
        JOIN categorias c ON i.categoria_id = c.id
        JOIN depositos d ON s.deposito_id = d.id
        LEFT JOIN proveedores p ON i.proveedor_id = p.id
        WHERE i.activo = 1 
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

    if (!empty($deposito_id)) {
        $query .= " AND s.deposito_id = :deposito_id";
        $params[':deposito_id'] = $deposito_id;
    }
    if (!empty($categoria_id)) {
        $query .= " AND c.id = :categoria_id";
        $params[':categoria_id'] = $categoria_id;
    }
    if (!empty($busqueda)) {
        $query .= " AND (i.nombre LIKE :like_term OR i.sku LIKE :like_term2 OR p.nombre LIKE :like_term3)";
        $like_term = "%{$busqueda}%";
        $params[':like_term'] = $like_term;
        $params[':like_term2'] = $like_term;
        $params[':like_term3'] = $like_term;
    }

    if ($filtro_especial === 'stock_bajo') {
        $query .= " AND s.cantidad <= i.stock_minimo";
    } elseif ($filtro_especial === 'sin_stock') {
        $query .= " AND s.cantidad = 0";
    }
    
    try {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("Error en obtener_total_stock: " . $e->getMessage());
        return 0;
    }
}

// ======================================================================
// 5C. FUNCIONES DE LOTES (PARA SALIDAS)
// ======================================================================

/**
 * Obtiene los lotes disponibles (con stock > 0) para un insumo/depósito.
 * Ordenados por FIFO (primero los que vencen antes, luego los que
 * ingresaron antes).
 */
function obtener_lotes_disponibles_por_insumo($pdo, $insumo_id, $deposito_id) {
    $query = "
        SELECT 
            sl.id as lote_id, 
            sl.numero_lote, 
            sl.fecha_vencimiento, 
            sl.cantidad_actual,
            sl.fecha_ingreso
        FROM stock_lotes sl
        WHERE sl.insumo_id = ? 
          AND sl.deposito_id = ? 
          AND sl.cantidad_actual > 0
        ORDER BY 
            sl.fecha_vencimiento ASC, -- Prioritiza los que vencen antes
            sl.fecha_ingreso ASC      -- Si no tienen vencimiento, usa el más antiguo
    ";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$insumo_id, $deposito_id]);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_lotes_disponibles_por_insumo: " . $e->getMessage());
        return [];
    }
}