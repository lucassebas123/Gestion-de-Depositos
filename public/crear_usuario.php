<?php
// ======================================================
// CREAR NUEVO USUARIO (Formulario y Lógica)
// ======================================================
// MODIFICADO v1.3 - [Asistente] Corregidos paths y value de rol
// MODIFICADO v1.4 - [Asistente] Añadido 'creado_por_admin_id'

// 1. Definir el título ANTES de cargar el init
$titulo_pagina = "Crear Nuevo Usuario";

// 2. Cargar el INICIALIZADOR DE ADMIN
// ⭐️ CORRECCIÓN DE RUTA: La ruta correcta es a '/src/' que está al mismo nivel
require_once __DIR__ . '/src/init_admin.php';

$mensaje_exito = "";
$mensaje_error = "";
$username_form = "";
$rol_form = "operador";
$email_form = "";
$telefono_form = "";
$domicilio_form = "";

// 3. Lógica de la Página (Procesar el formulario)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_form = trim($_POST['username'] ?? '');
    $password_form = trim($_POST['password'] ?? '');
    $rol_form = trim($_POST['rol'] ?? 'operador');
    // ⭐️ NUEVOS CAMPOS
    $email_form = trim($_POST['email'] ?? '');
    $telefono_form = trim($_POST['telefono'] ?? '');
    $domicilio_form = trim($_POST['domicilio'] ?? '');

    // ==================================================================
    // ⭐️ MODIFICADO: Se pasa el $USUARIO_ID como el admin creador ⭐️
    // ==================================================================
    list($exito, $mensaje) = crear_usuario(
        $pdo, 
        $username_form, 
        $password_form, 
        $rol_form,
        $email_form,
        $telefono_form,
        $domicilio_form,
        $USUARIO_ID // <-- ¡NUEVO PARÁMETRO!
    );
    
    if ($exito) {
        $mensaje_exito = $mensaje;
        // Limpiamos los campos después de un éxito
        $username_form = "";
        $rol_form = "operador";
        $email_form = "";
        $telefono_form = "";
        $domicilio_form = "";
    } else {
        $mensaje_error = $mensaje;
    }
}

// 4. Renderizar VISTA (HTML)
?>

<div class="content-wrapper">

    <?php require 'src/navbar.php'; ?>

    <div class="page-content-container">

        <h2 class="mb-4"><i class="bi bi-person-plus-fill me-3"></i>Gestión de Usuarios</h2>
        
        <div class="card shadow-sm">
            <div class="card-body">
                
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link" href="gestion_usuarios.php">Listado de Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link active">Crear Nuevo Usuario</span>
                    </li>
                </ul>

                <?php if ($mensaje_exito): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($mensaje_exito); ?>
                    </div>
                <?php endif; ?>
                <?php if ($mensaje_error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($mensaje_error); ?>
                    </div>
                <?php endif; ?>

                <form action="crear_usuario.php" method="POST" class="col-md-8 col-lg-7">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username_form); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="operador" <?php echo ($rol_form == 'operador') ? 'selected' : ''; ?>>
                                    Operador
                                </option>
                                <option value="supervisor" <?php echo ($rol_form == 'supervisor') ? 'selected' : ''; ?>>
                                    Supervisor
                                </option>
                                
                                <option value="observador" <?php echo ($rol_form == 'observador') ? 'selected' : ''; ?>>
                                    Observador (Solo Lectura)
                                </option>
                                
                                <option value="admin" <?php echo ($rol_form == 'admin') ? 'selected' : ''; ?>>
                                    Administrador
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($email_form); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono"
                                   value="<?php echo htmlspecialchars($telefono_form); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="domicilio" class="form-label">Domicilio</label>
                            <input type="text" class="form-control" id="domicilio" name="domicilio"
                                   value="<?php echo htmlspecialchars($domicilio_form); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-4">Crear Usuario</button>
                    
                </form>

            </div>
        </div>

    </div> </div> <?php 
// Cargar el Footer
require_once __DIR__ . '/src/footer.php'; 
?>