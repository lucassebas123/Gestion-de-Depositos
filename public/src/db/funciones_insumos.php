<?php
// ======================================================================
// 4. FUNCIONES DE INSUMOS
// ======================================================================

function insertar_insumo_completo($pdo, $datos) {
    if (empty(trim($datos['nombre']))) {
        return [false, "El nombre del insumo no puede estar vacío."];
    }
    if (empty((int)$datos['categoria_id'])) {
        return [false, "Debe seleccionar una categoría."];
    }

    $proveedor_id = !empty($datos['proveedor_id']) ? (int)$datos['proveedor_id'] : null;
    $agrupador = !empty($datos['agrupador']) ? trim($datos['agrupador']) : null;

    $query = "INSERT INTO insumos (
        nombre, categoria_id, sku, proveedor_id, stock_minimo, 
        unidad_medida, ubicacion, notas, imagen_path, descripcion, agrupador
    ) VALUES (
        :nombre, :categoria_id, :sku, :proveedor_id, :stock_minimo, 
        :unidad_medida, :ubicacion, :notas, :imagen_path, :descripcion, :agrupador
    )";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':categoria_id' => (int)$datos['categoria_id'],
            ':sku' => $datos['sku'],
            ':proveedor_id' => $proveedor_id,
            ':stock_minimo' => (int)$datos['stock_minimo'],
            ':unidad_medida' => $datos['unidad_medida'],
            ':ubicacion' => $datos['ubicacion'],
            ':notas' => $datos['notas'],
            ':imagen_path' => $datos['imagen_path'],
            ':descripcion' => $datos['descripcion'], 
            ':agrupador' => $agrupador
        ]);
        return [true, "Insumo '" . htmlspecialchars($datos['nombre']) . "' agregado con éxito."];
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) {
            return [false, "Ya existe un insumo con el mismo nombre o SKU."];
        }
        error_log("Error en insertar_insumo_completo: " . $e->getMessage());
        return [false, "Error de base de datos. Consulte los logs."];
    }
}

function obtener_insumo_por_id($pdo, $insumo_id) {
    $query = "SELECT * FROM insumos WHERE id = ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$insumo_id]);
        return $stmt->fetch();
    } catch (\PDOException $e) {
        error_log("Error en obtener_insumo_por_id: " . $e->getMessage());
        return null;
    }
}

function actualizar_insumo_completo($pdo, $insumo_id, $datos) {
    if (empty(trim($datos['nombre']))) {
        return [false, "El nombre del insumo no puede estar vacío."];
    }
    if (empty((int)$datos['categoria_id'])) {
        return [false, "Debe seleccionar una categoría."];
    }

    $proveedor_id = !empty($datos['proveedor_id']) ? (int)$datos['proveedor_id'] : null;
    $agrupador = !empty($datos['agrupador']) ? trim($datos['agrupador']) : null;

    $query = "UPDATE insumos SET 
        nombre = :nombre, 
        categoria_id = :categoria_id, 
        sku = :sku, 
        proveedor_id = :proveedor_id, 
        stock_minimo = :stock_minimo, 
        unidad_medida = :unidad_medida, 
        ubicacion = :ubicacion, 
        notas = :notas, 
        imagen_path = :imagen_path, 
        descripcion = :descripcion,
        agrupador = :agrupador,
        activo = :activo
    WHERE id = :insumo_id";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':categoria_id' => (int)$datos['categoria_id'],
            ':sku' => $datos['sku'],
            ':proveedor_id' => $proveedor_id,
            ':stock_minimo' => (int)$datos['stock_minimo'],
            ':unidad_medida' => $datos['unidad_medida'],
            ':ubicacion' => $datos['ubicacion'],
            ':notas' => $datos['notas'],
            ':imagen_path' => $datos['imagen_path'],
            ':descripcion' => $datos['descripcion'],
            ':agrupador' => $agrupador,
            ':activo' => (int)$datos['activo'],
            ':insumo_id' => $insumo_id
        ]);
        return [true, "Insumo '" . htmlspecialchars($datos['nombre']) . "' actualizado con éxito."];
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) {
            return [false, "Ya existe OTRO insumo con el mismo nombre o SKU."];
        }
        error_log("Error en actualizar_insumo_completo: " . $e->getMessage());
        return [false, "Error de base de datos. Consulte los logs."];
    }
}

// ⭐️ MODIFICADA: Lógica de filtrado de activos/inactivos
function obtener_insumos_completos($pdo, $termino_busqueda = null, $solo_inactivos = false, $limit = null, $offset = null, $categoria_id = null) {
    $query = "
        SELECT 
            i.*, 
            c.nombre as categoria_nombre,
            p.nombre as proveedor_nombre
        FROM insumos i
        JOIN categorias c ON i.categoria_id = c.id
        LEFT JOIN proveedores p ON i.proveedor_id = p.id
        WHERE 1=1 
    ";
    $params = [];

    // CAMBIO DE LÓGICA AQUÍ:
    if ($solo_inactivos) {
        $query .= " AND i.activo = 0"; // Solo inactivos
    } else {
        $query .= " AND i.activo = 1"; // Solo activos (por defecto)
    }

    if (!empty($categoria_id)) {
        $query .= " AND i.categoria_id = :categoria_id";
        $params[':categoria_id'] = (int)$categoria_id;
    }

    if (!empty($termino_busqueda)) {
        $query .= " AND (i.nombre LIKE :like_term OR i.sku LIKE :like_term2 OR p.nombre LIKE :like_term3)";
        $like_term = "%{$termino_busqueda}%";
        $params[':like_term'] = $like_term;
        $params[':like_term2'] = $like_term;
        $params[':like_term3'] = $like_term;
    }
    $query .= " ORDER BY i.nombre";

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
        error_log("Error en obtener_insumos_completos: " . $e->getMessage());
        return [];
    }
}

// ⭐️ MODIFICADA: Misma lógica para el conteo
function obtener_total_insumos($pdo, $termino_busqueda = null, $solo_inactivos = false, $categoria_id = null) {
    $query = "
        SELECT COUNT(i.id)
        FROM insumos i
        LEFT JOIN proveedores p ON i.proveedor_id = p.id
        WHERE 1=1
    ";
    $params = [];

    if ($solo_inactivos) {
        $query .= " AND i.activo = 0";
    } else {
        $query .= " AND i.activo = 1";
    }

    if (!empty($categoria_id)) {
        $query .= " AND i.categoria_id = :categoria_id";
        $params[':categoria_id'] = (int)$categoria_id;
    }

    if (!empty($termino_busqueda)) {
        $query .= " AND (i.nombre LIKE :like_term OR i.sku LIKE :like_term2 OR p.nombre LIKE :like_term3)";
        $like_term = "%{$termino_busqueda}%";
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
        error_log("Error en obtener_total_insumos: " . $e->getMessage());
        return 0;
    }
}

function desactivar_insumo_logico($pdo, $id) {
    try {
        $pdo->prepare("UPDATE insumos SET activo = 0 WHERE id = ?")->execute([$id]);
        return [true, "Insumo desactivado con éxito."];
    } catch (PDOException $e) {
        return [false, "Error al desactivar el insumo."];
    }
}

function activar_insumo_logico($pdo, $id) {
    try {
        $pdo->prepare("UPDATE insumos SET activo = 1 WHERE id = ?")->execute([$id]);
        return [true, "Insumo activado con éxito."];
    } catch (PDOException $e) {
        return [false, "Error al activar el insumo."];
    }
}

function obtener_movimiento_por_id($pdo, $movimiento_id) {
    $query = "
        SELECT 
            m.id, 
            m.fecha, 
            m.tipo_movimiento, 
            m.cantidad_movida, 
            m.observaciones, 
            m.anulado_por_id,
            m.numero_lote,
            m.fecha_vencimiento,
            m.insumo_id,
            m.deposito_id
        FROM movimientos m
        WHERE m.id = ?
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$movimiento_id]);
        return $stmt->fetch();
    } catch (\PDOException $e) {
        error_log("Error en obtener_movimiento_por_id: " . $e->getMessage());
        return null;
    }
}

?>
