<?php

// Detectar la página actual para marcarla como 'active'
$pagina_actual = basename($_SERVER['PHP_SELF']);

// --- Definir qué páginas pertenecen a qué menú ---
$paginas_config_supervisor = [
    'gestion_insumos.php',
    'editar_insumo.php',
    'gestion_proveedores.php',
    'ver_proveedor_detalle.php',
    'reglas.php',
    'index.php'
];

// ⭐️ CAMBIO: Añadido 'reporte_auditoria.php' a la lista de admin ⭐️
$paginas_config_admin = [
    'gestion_usuarios.php',
    'crear_usuario.php',
    'ver_usuario_movimientos.php',
    'ver_usuario_perfil.php',
    'gestion_insumos.php',
    'editar_insumo.php',
    'gestion_proveedores.php',
    'ver_proveedor_detalle.php',
    'reglas.php',
    'index.php',
    'reporte_auditoria.php' // <-- NUEVO
];
$es_pagina_config_admin = in_array($pagina_actual, $paginas_config_admin);
$es_pagina_config_supervisor = in_array($pagina_actual, $paginas_config_supervisor);

?>

<nav class="sidebar d-flex flex-column p-3 bg-dark text-white">
    
    <a href="inicio.php" class="sidebar-brand d-flex align-items-center justify-content-center mb-3 text-decoration-none text-white">
        <span class="fs-4">Gestor de Insumos</span>
    </a>
    <hr>

    <ul class="nav nav-pills flex-column mb-auto">
        
        <li class="nav-item">
            <a class="nav-link <?php echo ($pagina_actual == 'inicio.php') ? 'active' : ''; ?>" href="inicio.php">
                <i class="bi bi-house-door-fill me-2"></i>Inicio (Dashboard)
            </a>
        </li>

        <?php if ($USUARIO_ROL !== 'observador'): // Ocultar a observadores ?>
        <li class="nav-item">
            <a class="nav-link <?php echo ($pagina_actual == 'movimientos.php') ? 'active' : ''; ?>" href="movimientos.php">
                <i class="bi bi-plus-circle-fill me-2"></i>Registrar Movimiento
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($pagina_actual == 'traslados.php') ? 'active' : ''; ?>" href="traslados.php">
                <i class="bi bi-arrow-left-right me-2"></i>Registrar Traslado
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a class="nav-link <?php echo ($pagina_actual == 'historial.php') ? 'active' : ''; ?>" href="historial.php">
                <i class="bi bi-boxes me-2"></i>Stock Actual
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($pagina_actual == 'historial_movimientos.php' || $pagina_actual == 'ver_movimiento_detalle.php' || $pagina_actual == 'anular_movimiento.php') ? 'active' : ''; ?>" href="historial_movimientos.php">
                <i class="bi bi-list-ul me-2"></i>Historial Movimientos
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo ($pagina_actual == 'reporte_agrupado.php') ? 'active' : ''; ?>" href="reporte_agrupado.php">
                <i class="bi bi-collection-fill me-2"></i>Reporte Agrupado
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?php echo ($pagina_actual == 'reporte_vencimientos.php') ? 'active' : ''; ?>" href="reporte_vencimientos.php">
                <i class="bi bi-calendar-x-fill me-2"></i>Reporte de Vencimientos
            </a>
        </li>
        
        
        <?php // ====================================================== ?>
        <?php // MENÚ DEL ADMINISTRADOR (LO VE TODO) ?>
        <?php // ====================================================== ?>
        <?php if ($USUARIO_ROL == 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link dropdown-toggle <?php echo ($es_pagina_config_admin) ? 'active' : ''; ?>" href="#" data-bs-toggle="collapse" data-bs-target="#config-collapse-admin" aria-expanded="<?php echo ($es_pagina_config_admin) ? 'true' : 'false'; ?>">
                <i class="bi bi-gear-wide-connected me-2"></i>Configuración (Admin)
            </a>
            <div class="collapse <?php echo ($es_pagina_config_admin) ? 'show' : ''; ?>" id="config-collapse-admin">
                <ul class="list-unstyled ps-3">
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'gestion_insumos.php' || $pagina_actual == 'editar_insumo.php') ? 'active' : ''; ?>" href="gestion_insumos.php">
                            <i class="bi bi-book-fill me-2"></i>Gestionar Catálogo
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'gestion_proveedores.php' || $pagina_actual == 'ver_proveedor_detalle.php') ? 'active' : ''; ?>" href="gestion_proveedores.php">
                            <i class="bi bi-building-gear me-2"></i>Gestión de Proveedores
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'reglas.php') ? 'active' : ''; ?>" href="reglas.php">
                            <i class="bi bi-diagram-3-fill me-2"></i>Asignar Reglas (Categorías)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'index.php') ? 'active' : ''; ?>" href="index.php">
                            <i class="bi bi-archive-fill me-2"></i>Maestros
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'gestion_usuarios.php' || $pagina_actual == 'crear_usuario.php' || $pagina_actual == 'ver_usuario_movimientos.php' || $pagina_actual == 'ver_usuario_perfil.php') ? 'active' : ''; ?>" href="gestion_usuarios.php">
                            <i class="bi bi-people-fill me-2"></i>Gestión de Usuarios
                        </a>
                    </li>
                    
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'reporte_auditoria.php') ? 'active' : ''; ?>" href="reporte_auditoria.php">
                            <i class="bi bi-person-check-fill me-2"></i>Reporte de Auditoría
                        </a>
                    </li>
                    </ul>
            </div>
        </li>
        
        <?php // ====================================================== ?>
        <?php // MENÚ DEL SUPERVISOR (VE UN SUBSET) ?>
        <?php // ====================================================== ?>
        <?php elseif ($USUARIO_ROL == 'supervisor'): ?>
        <li class="nav-item">
            <a class="nav-link dropdown-toggle <?php echo ($es_pagina_config_supervisor) ? 'active' : ''; ?>" href="#" data-bs-toggle="collapse" data-bs-target="#config-collapse-supervisor" aria-expanded="<?php echo ($es_pagina_config_supervisor) ? 'true' : 'false'; ?>">
                <i class="bi bi-gear-fill me-2"></i>Configuración
            </a>
            <div class="collapse <?php echo ($es_pagina_config_supervisor) ? 'show' : ''; ?>" id="config-collapse-supervisor">
                <ul class="list-unstyled ps-3">
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'gestion_insumos.php' || $pagina_actual == 'editar_insumo.php') ? 'active' : ''; ?>" href="gestion_insumos.php">
                            <i class="bi bi-book-fill me-2"></i>Gestionar Catálogo
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'gestion_proveedores.php' || $pagina_actual == 'ver_proveedor_detalle.php') ? 'active' : ''; ?>" href="gestion_proveedores.php">
                            <i class="bi bi-building-gear me-2"></i>Gestión de Proveedores
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'reglas.php') ? 'active' : ''; ?>" href="reglas.php">
                            <i class="bi bi-diagram-3-fill me-2"></i>Asignar Reglas (Categorías)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?php echo ($pagina_actual == 'index.php') ? 'active' : ''; ?>" href="index.php">
                            <i class="bi bi-archive-fill me-2"></i>Maestros
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <?php endif; ?>
        
    </ul>
    

</nav>
