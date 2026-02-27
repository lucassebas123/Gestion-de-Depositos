<?php
/**
 * MÓDULO: FUNCIONES DE REGLAS (PERMISOS)
 * (Separado de funciones_db.php v5.0)
 */

// ======================================================================
// 7. FUNCIONES DE REGLAS (CATEGORÍA <-> DEPÓSITO)
// ======================================================================
function obtener_categorias_asignadas($pdo, $deposito_id) {
    $query = "
        SELECT c.id, c.nombre 
        FROM categorias c
        JOIN deposito_categoria_link dc ON c.id = dc.categoria_id
        WHERE dc.deposito_id = ? AND c.activo = 1
        ORDER BY c.nombre
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$deposito_id]);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_categorias_asignadas: " . $e->getMessage());
        return [];
    }
}

function obtener_categorias_no_asignadas($pdo, $deposito_id) {
    $query = "
        SELECT c.id, c.nombre 
        FROM categorias c
        WHERE c.activo = 1 AND c.id NOT IN (
            SELECT categoria_id FROM deposito_categoria_link WHERE deposito_id = ?
        )
        ORDER BY c.nombre
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$deposito_id]);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_categorias_no_asignadas: " . $e->getMessage());
        return [];
    }
}

function agregar_link_categoria($pdo, $deposito_id, $categoria_id) {
    $query = "INSERT IGNORE INTO deposito_categoria_link (deposito_id, categoria_id) VALUES (?, ?)";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$deposito_id, $categoria_id]);
        if ($stmt->rowCount() > 0) {
            return [true, "Regla asignada con éxito."];
        }
        return [true, "La regla ya existía."];
    } catch (\PDOException $e) {
        error_log("Error en agregar_link_categoria: " . $e->getMessage());
        return [false, "Error de base de datos al asignar regla."];
    }
}

function quitar_link_categoria($pdo, $deposito_id, $categoria_id) {
    $query = "DELETE FROM deposito_categoria_link WHERE deposito_id = ? AND categoria_id = ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$deposito_id, $categoria_id]);
        if ($stmt->rowCount() > 0) {
            return [true, "Regla quitada con éxito."];
        }
        return [true, "La regla no existía."];
    } catch (\PDOException $e) {
        error_log("Error en quitar_link_categoria: " . $e->getMessage());
        return [false, "Error de base de datos al quitar regla."];
    }
}


function obtener_insumos_para_deposito_asignado($pdo, $deposito_id) {
        $query = "
        SELECT 
            i.id, i.nombre, i.sku
        FROM insumos i
        JOIN categorias c ON i.categoria_id = c.id
        JOIN deposito_categoria_link dc ON c.id = dc.categoria_id
        WHERE dc.deposito_id = ? AND i.activo = 1
        ORDER BY i.nombre
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$deposito_id]);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_insumos_para_deposito_asignado: " . $e->getMessage());
        return [];
    }
}


function obtener_categorias_con_stock_por_deposito($pdo, $deposito_id = null) {
    $query = "
        SELECT DISTINCT c.id, c.nombre 
        FROM categorias c
        JOIN insumos i ON c.id = i.categoria_id
        JOIN stock s ON i.id = s.insumo_id
        WHERE c.activo = 1
    ";
    $params = [];
    if (!empty($deposito_id)) {
        $query .= " AND s.deposito_id = ?";
        $params[] = $deposito_id;
    }
    $query .= " ORDER BY c.nombre";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_categorias_con_stock_por_deposito: " . $e->getMessage());
        return [];
    }
}

// ======================================================================
// 10. FUNCIONES DE REGLAS DE USUARIO (USUARIO <-> DEPÓSITO)
// ======================================================================
function obtener_depositos_asignados_a_usuario($pdo, $usuario_id) {
    $query = "
        SELECT d.id, d.nombre 
        FROM depositos d
        JOIN usuario_deposito_link udl ON d.id = udl.deposito_id
        WHERE udl.usuario_id = ? AND d.activo = 1
        ORDER BY d.nombre
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_depositos_asignados_a_usuario: " . $e->getMessage());
        return [];
    }
}
function obtener_depositos_no_asignados_a_usuario($pdo, $usuario_id) {
    $query = "
        SELECT d.id, d.nombre 
        FROM depositos d
        WHERE d.activo = 1 AND d.id NOT IN (
            SELECT deposito_id FROM usuario_deposito_link WHERE usuario_id = ?
        )
        ORDER BY d.nombre
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_depositos_no_asignados_a_usuario: " . $e->getMessage());
        return [];
    }
}
function agregar_link_deposito_a_usuario($pdo, $usuario_id, $deposito_id) {
    $query = "INSERT IGNORE INTO usuario_deposito_link (usuario_id, deposito_id) VALUES (?, ?)";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$usuario_id, $deposito_id]);
        if ($stmt->rowCount() > 0) {
            return [true, "Regla asignada con éxito."];
        }
        return [true, "La regla ya existía."];
    } catch (\PDOException $e) {
        error_log("Error en agregar_link_deposito_a_usuario: " . $e->getMessage());
        return [false, "Error de base de datos al asignar regla."];
    }
}
function quitar_link_deposito_a_usuario($pdo, $usuario_id, $deposito_id) {
    $query = "DELETE FROM usuario_deposito_link WHERE usuario_id = ? AND deposito_id = ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$usuario_id, $deposito_id]);
        if ($stmt->rowCount() > 0) {
            return [true, "Regla quitada con éxito."];
        }
        return [true, "La regla no existía."];
    } catch (\PDOException $e) {
        error_log("Error en quitar_link_deposito_a_usuario: " . $e->getMessage());
        return [false, "Error de base de datos al quitar regla."];
    }
}