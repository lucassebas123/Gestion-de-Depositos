<?php
// ======================================================================
// ACCESO (LOGIN) - CON PROTECCIÓN BRUTE FORCE
// ======================================================================
// v2.2 - CORREGIDO: Sincronización de Hora Local para bloqueo efectivo

session_start();

// 1. CONFIGURACIÓN DE HORA (PHP)
// Esto es vital para que coincida con el bloqueo de 15 minutos
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (isset($_SESSION["usuario_id"])) {
    header("Location: inicio.php");
    exit;
}

// 2. Cargar Conexión y Funciones
require_once __DIR__ . '/src/db_conexion.php';
require_once __DIR__ . '/src/funciones_db.php'; 

$mensaje_error = $_GET['mensaje_error'] ?? '';
$username_form = "";

// Obtener IP real del cliente
$ip_cliente = $_SERVER['REMOTE_ADDR'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username_form = trim($_POST["username"] ?? "");
    $password_form = $_POST["password"] ?? "";

    if (empty($username_form) || empty($password_form)) {
        $mensaje_error = "Usuario y contraseña son obligatorios.";
    } else {
        try {
            $pdo = conectar_db();

            // 3. SINCRONIZAR HORA (MySQL)
            // Obligamos a la base de datos a usar la misma hora que PHP
            try {
                $pdo->exec("SET time_zone = '" . date('P') . "'");
            } catch (Exception $e) {
                // Ignorar si falla en hosting compartido, pero vital en XAMPP
            }

            // --- 🛡️ VERIFICACIÓN DE SEGURIDAD (RATE LIMITING) 🛡️ ---
            // Ahora sí, ambos tienen la misma hora
            list($esta_bloqueado, $minutos_espera) = verificar_bloqueo_ip($pdo, $ip_cliente);
            
            if ($esta_bloqueado) {
                $mensaje_error = "Demasiados intentos fallidos. Por seguridad, espere $minutos_espera minutos.";
            } else {
                // --- INTENTO DE LOGIN ---
                $stmt = $pdo->prepare("SELECT id, username, password_hash, rol, activo FROM usuarios WHERE username = ?");
                $stmt->execute([$username_form]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($usuario && password_verify($password_form, $usuario['password_hash'])) {
                    if ((int)$usuario['activo'] === 1) {
                        
                        // ✅ ÉXITO: Limpiamos los intentos fallidos previos
                        limpiar_intentos_ip($pdo, $ip_cliente);

                        session_regenerate_id(true);
                        $_SESSION["usuario_id"] = (int)$usuario['id'];
                        $_SESSION["username"]   = $usuario['username'];
                        $_SESSION["rol"]        = $usuario['rol'];

                        // Actualizar última conexión
                        $pdo->prepare("UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?")->execute([$usuario['id']]);

                        header("Location: inicio.php");
                        exit;
                    } else {
                        $mensaje_error = "El usuario no está activo.";
                    }
                } else {
                    // ❌ FALLO: Registramos el intento
                    registrar_intento_fallido($pdo, $ip_cliente, $username_form);
                    
                    // Mensaje genérico por seguridad
                    $mensaje_error = "Usuario o contraseña incorrectos.";
                }
            }

        } catch (Throwable $e) {
            error_log("Error crítico en acceso.php: " . $e->getMessage());
            $mensaje_error = "Error del sistema. Por favor, intente más tarde.";
        }
    }
}

// 4. RENDERIZACIÓN DE LA VISTA
$titulo_pagina = "Iniciar Sesión";
$body_class = "login-page-body"; 

require __DIR__ . '/src/header.php';
?>

<div class="container d-flex flex-column align-items-center justify-content-center" style="position: relative; z-index: 2; min-height: 100vh;">
    
    <div class="glass-card">
        <div class="text-center mb-4">
            <div class="bg-white rounded-circle p-3 d-inline-block mb-3 shadow-lg">
                <img src="logo1.png" alt="Logo" style="width: 80px; height: 80px; object-fit: contain;">
            </div>
            <h3 class="mb-1">Bienvenido</h3>
            <p class="small">Gestor de Insumos e Inventario</p>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger bg-danger text-white border-0 bg-opacity-75 shadow-sm" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <?= htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off" novalidate>
            
            <div class="form-floating mb-3">
                <input type="text" id="username" name="username" class="form-control" required 
                       value="<?= htmlspecialchars($username_form, ENT_QUOTES, 'UTF-8') ?>" 
                       placeholder="Usuario">
                <label for="username"><i class="bi bi-person me-2"></i>Usuario</label>
            </div>
            
            <div class="form-floating mb-4">
                <input type="password" id="password" name="password" class="form-control" required 
                       placeholder="Contraseña">
                <label for="password"><i class="bi bi-lock me-2"></i>Contraseña</label>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg btn-login-premium">
                    Iniciar Sesión <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </form>
        <div class="text-center mt-3">
            <a href="recuperar.php" class="text-white-50 text-decoration-none small">
                ¿Olvidaste tu contraseña?
            </a>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-white-50">
                &copy; <?= date('Y') ?> Sistema de Gestión Interno
            </small>
        </div>
    </div>
</div>

<?php 
require __DIR__ . '/src/footer.php'; 
?>