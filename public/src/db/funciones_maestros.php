<?php
/**
 * MÓDULO: FUNCIONES DE MAESTROS (Depósitos, Categorías, Proveedores)
 * VERSIÓN FINAL COMPLETA - MODIFICADA
 * - Incluye: Auditoría de Bajas y Altas (quién y cuándo).
 * - Incluye: Filtros exclusivos para inactivos.
 * - Incluye: CRUD completo de Proveedores.
 * - CORRECCIÓN: Operadores y Observadores ven TODOS los depósitos activos.
 */

// ======================================================================
// 2. FUNCIONES DE DEPÓSITOS
// ======================================================================

/**
 * Obtiene los depósitos.
 * CORREGIDO:
 * - Admin: Ve todo (opcional inactivos).
 * - Operador/Supervisor/Observador: Ven TODOS los depósitos activos (sin restricción de asignación).
 * - Mantiene los datos de auditoría (baja_por, alta_por).
 */
function obtener_depositos_por_usuario($pdo, $usuario_id, $usuario_rol, $incluir_inactivos = false) {
    
    // JOINs para traer los nombres de los usuarios de auditoría (MANTENIDO)
    $query = "
        SELECT 
            d.*, 
            u_baja.username as baja_por_username,
            u_alta.username as alta_por_username
        FROM depositos d
        LEFT JOIN usuarios u_baja ON d.baja_por_id = u_baja.id
        LEFT JOIN usuarios u_alta ON d.alta_por_id = u_alta.id
    ";
    
    $params = [];

    // LÓGICA CORREGIDA:
    // Ya no usamos el JOIN a 'usuario_deposito_link'.
    
    if ($usuario_rol === 'admin') {
        // Si es ADMIN, puede elegir ver inactivos o no.
        if (!$incluir_inactivos) {
            $query .= " WHERE d.activo = 1";
        } else {
            $query .= " WHERE 1=1"; // Para permitir ver todo
        }
    } else {
        // Si es Operador, Supervisor u Observador:
        // Ven TODOS los depósitos, pero SIEMPRE filtrando que estén activos.
        $query .= " WHERE d.activo = 1";
    }
    
    $query .= " ORDER BY d.nombre";
    
    try {
        $stmt = $pdo->prepare($query);
        // Ya no hay parámetros dinámicos en esta versión simplificada, 
        // pero mantenemos el loop por si agregamos filtros a futuro.
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error al obtener depósitos: " . $e->getMessage());
        return [];
    }
}

function agregar_deposito($pdo, $nombre) {
    if (empty(trim($nombre))) {
        return [false, "El nombre del depósito no puede estar vacío."];
    }
    try {
        // Al crear, no hay auditoría de baja/alta todavía
        $stmt = $pdo->prepare("INSERT INTO depositos (nombre) VALUES (?)");
        $stmt->execute([$nombre]);
        return [true, "Depósito '" . htmlspecialchars($nombre) . "' agregado con éxito."];
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) {
            return [false, "Ya existe un depósito con ese nombre."];
        }
        return [false, "Error de base de datos."];
    }
}

// ======================================================================
// 3. FUNCIONES DE CATEGORÍAS
// ======================================================================

function obtener_todas_categorias($pdo, $incluir_inactivos = false) {
    // JOINs para auditoría
    $query = "
        SELECT 
            c.*, 
            u_baja.username as baja_por_username,
            u_alta.username as alta_por_username
        FROM categorias c
        LEFT JOIN usuarios u_baja ON c.baja_por_id = u_baja.id
        LEFT JOIN usuarios u_alta ON c.alta_por_id = u_alta.id
    ";
    
    if (!$incluir_inactivos) {
        $query .= " WHERE c.activo = 1";
    }
    
    $query .= " ORDER BY c.nombre";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error al obtener categorías: " . $e->getMessage());
        return [];
    }
}

function agregar_categoria($pdo, $nombre) {
    if (empty(trim($nombre))) {
        return [false, "El nombre de la categoría no puede estar vacío."];
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (?)");
        $stmt->execute([$nombre]);
        return [true, "Categoría '" . htmlspecialchars($nombre) . "' agregada con éxito."];
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) {
            return [false, "Ya existe una categoría con ese nombre."];
        }
        return [false, "Error de base de datos."];
    }
}

// ======================================================================
// 3.5. FUNCIONES DE PROVEEDORES
// ======================================================================

function obtener_todos_proveedores($pdo, $solo_inactivos = false, $limit = null, $offset = null) {
    $sql = "SELECT * FROM proveedores WHERE 1=1";
    
    // Lógica exclusiva (Switch): O activos O inactivos, no ambos mezclados
    if ($solo_inactivos) {
        $sql .= " AND activo = 0";
    } else {
        $sql .= " AND activo = 1";
    }
    
    $sql .= " ORDER BY nombre";
    
    if ($limit !== null && $offset !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        if ($limit !== null && $offset !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_todos_proveedores: " . $e->getMessage());
        return [];
    }
}

function obtener_total_proveedores($pdo, $solo_inactivos = false) {
    $sql = "SELECT COUNT(id) FROM proveedores WHERE 1=1";
    
    if ($solo_inactivos) {
        $sql .= " AND activo = 0";
    } else {
        $sql .= " AND activo = 1";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("Error en obtener_total_proveedores: " . $e->getMessage());
        return 0;
    }
}

function obtener_proveedor_por_id($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (\PDOException $e) {
        return null;
    }
}

function guardar_proveedor($pdo, $datos) {
    $nombre = trim($datos['nombre']);
    if (empty($nombre)) {
        return [false, "El nombre es obligatorio."];
    }

    try {
        if (!empty($datos['id'])) {
            // Editar existente
            $sql = "UPDATE proveedores SET nombre=?, contacto=?, telefono=?, email=?, direccion=?, activo=? WHERE id=?";
            $pdo->prepare($sql)->execute([
                $nombre, 
                $datos['contacto'], 
                $datos['telefono'], 
                $datos['email'], 
                $datos['direccion'], 
                (int)$datos['activo'], 
                $datos['id']
            ]);
            return [true, "Proveedor actualizado con éxito."];
        } else {
            // Crear nuevo
            $sql = "INSERT INTO proveedores (nombre, contacto, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $nombre, 
                $datos['contacto'], 
                $datos['telefono'], 
                $datos['email'], 
                $datos['direccion']
            ]);
            return [true, "Proveedor creado con éxito."];
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return [false, "Ya existe un proveedor con ese nombre."];
        }
        error_log("Error en guardar_proveedor: " . $e->getMessage());
        return [false, "Error de base de datos."];
    }
}

function desactivar_proveedor_logico($pdo, $id) {
    try {
        $pdo->prepare("UPDATE proveedores SET activo = 0 WHERE id = ?")->execute([$id]);
        return [true, "Proveedor desactivado con éxito."];
    } catch (PDOException $e) {
        return [false, "Error al desactivar proveedor."];
    }
}

function activar_proveedor_logico($pdo, $id) {
    try {
        $pdo->prepare("UPDATE proveedores SET activo = 1 WHERE id = ?")->execute([$id]);
        return [true, "Proveedor activado con éxito."];
    } catch (PDOException $e) {
        return [false, "Error al activar proveedor."];
    }
}
?>