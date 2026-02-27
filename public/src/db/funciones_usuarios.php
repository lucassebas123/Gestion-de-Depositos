<?php
/**
 * MÓDULO: FUNCIONES DE USUARIOS
 * (Separado de funciones_db.php v5.0)
 */

// ======================================================================
// 8. FUNCIONES DE USUARIOS
// ======================================================================

function crear_usuario($pdo, $username, $password, $rol, $email = null, $telefono = null, $domicilio = null, $admin_id_creador = null) {
    $username = trim($username);
    $password = trim($password);
    $rol = strtolower(trim($rol));
    if (empty($username) || empty($password) || empty($rol)) {
        return [false, "Usuario, contraseña y rol son obligatorios."];
    }
    
    if (!in_array($rol, ['admin', 'operador', 'supervisor', 'observador'])) {
        return [false, "Rol no válido. Debe ser 'admin', 'operador', 'supervisor' o 'observador'."];
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO usuarios (username, password_hash, rol, email, telefono, domicilio, creado_por_admin_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$username, $password_hash, $rol, $email, $telefono, $domicilio, $admin_id_creador]);
        return [true, "Usuario '" . htmlspecialchars($username) . "' creado con éxito."];
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) { 
            return [false, "El nombre de usuario '" . htmlspecialchars($username) . "' ya existe."];
        }
        error_log("Error en crear_usuario: " . $e->getMessage());
        return [false, "Error de base de datos."];
    }
}

function obtener_todos_los_usuarios($pdo) {
    $query = "SELECT id, username, rol, activo, ultima_conexion, fecha_creacion FROM usuarios ORDER BY username";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_todos_los_usuarios: " . $e->getMessage());
        return [];
    }
}

function obtener_todos_los_operadores($pdo) {
    //  Incluye 'observador'
    $query = "SELECT id, username, rol FROM usuarios 
              WHERE rol = 'operador' OR rol = 'supervisor' OR rol = 'observador' 
              ORDER BY username";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_todos_los_operadores: " . $e->getMessage());
        return [];
    }
}

function obtener_usuarios_con_movimientos($pdo) {
    $query = "
        SELECT DISTINCT u.id, u.username 
        FROM usuarios u
        JOIN movimientos m ON u.id = m.usuario_id
        WHERE u.activo = 1
        ORDER BY u.username
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_usuarios_con_movimientos: " . $e->getMessage());
        return [];
    }
}

function obtener_usuario_por_id($pdo, $usuario_id) {
    $query = "SELECT id, username, rol FROM usuarios WHERE id = ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$usuario_id]);
        return $stmt->fetch();
    } catch (\PDOException $e) {
        error_log("Error en obtener_usuario_por_id: " . $e->getMessage());
        return null;
    }
}

function obtener_perfil_usuario_completo($pdo, $usuario_id) {
    $query = "
        SELECT 
            u.id,
            u.username,
            u.rol,
            u.activo,
            u.email,
            u.telefono,
            u.domicilio,
            u.ultima_conexion,
            u.fecha_creacion,
            a.username AS creador_username 
        FROM usuarios u
        LEFT JOIN usuarios a ON u.creado_por_admin_id = a.id
        WHERE u.id = ?
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$usuario_id]);
        return $stmt->fetch();
    } catch (\PDOException $e) {
        error_log("Error en obtener_perfil_usuario_completo: " . $e->getMessage());
        return null;
    }
}

function obtener_movimientos_por_usuario_id($pdo, $usuario_id, $dias = 7) {
    $query = "
        SELECT 
            m.fecha, 
            m.fecha_efectiva,
            m.estado,
            m.tipo_movimiento, 
            m.cantidad_movida,
            m.observaciones,
            i.nombre AS insumo_nombre,
            d.nombre AS deposito_nombre
        FROM movimientos m
        JOIN insumos i ON m.insumo_id = i.id
        JOIN depositos d ON m.deposito_id = d.id
        WHERE m.usuario_id = :usuario_id
        AND m.fecha >= DATE_SUB(NOW(), INTERVAL :dias DAY)
        ORDER BY m.fecha DESC
    ";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':dias' => (int)$dias
        ]);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Error en obtener_movimientos_por_usuario_id: " . $e->getMessage());
        return [];
    }
}


function desactivar_usuario_db($pdo, $usuario_id) {
    if ((int)$usuario_id === 1) { 
        return [false, "No se puede desactivar al administrador principal."];
    }
    $query = "UPDATE usuarios SET activo = 0 WHERE id = ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$usuario_id]);
        return [true, "Usuario desactivado."];
    } catch (\PDOException $e) {
        error_log("Error en desactivar_usuario_db: " . $e->getMessage());
        return [false, "Error de base de datos."];
    }
}

function reactivar_usuario_db($pdo, $usuario_id) {
    $query = "UPDATE usuarios SET activo = 1 WHERE id = ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$usuario_id]);
        return [true, "Usuario reactivado."];
    } catch (\PDOException $e) {
        error_log("Error en reactivar_usuario_db: " . $e->getMessage());
        return [false, "Error de base de datos."];
    }
}

function cambiar_password_admin($pdo, $usuario_id, $nueva_password) {
    if (empty($nueva_password)) {
        return [false, "La nueva contraseña no puede estar vacía."];
    }
    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    $query = "UPDATE usuarios SET password_hash = ? WHERE id = ?";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$password_hash, $usuario_id]);
        return [true, "Contraseña cambiada con éxito."];
    } catch (\PDOException $e) {
        error_log("Error en cambiar_password_admin: " . $e->getMessage());
        return [false, "Error de base de datos."];
    }
}