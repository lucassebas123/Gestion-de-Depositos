<?php
/**
 * ======================================================================
 * BARRA DE NAVEGACIÓN SUPERIOR (TOP NAVBAR) - LIGHT VERSION
 * ======================================================================
 */
?>
<nav class="main-navbar d-flex justify-content-between align-items-center">
    
    <button class="btn btn-link d-lg-none" id="btn-menu-movil" style="color: #212529;">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
        </svg>
    </button>
    
    <div class="d-none d-lg-block me-auto"></div>

    <div class="me-3 d-none d-md-block">
        <button class="btn-search-trigger" data-bs-toggle="modal" data-bs-target="#searchModal">
            <div><i class="bi bi-search me-2"></i>Buscar...</div>
            <span class="kbd-shortcut">Ctrl K</span>
        </button>
    </div>
    <button class="btn btn-link text-dark me-3 d-md-none" data-bs-toggle="modal" data-bs-target="#searchModal">
        <i class="bi bi-search fs-5"></i>
    </button>

    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" 
           id="dropdownUser" 
           data-bs-toggle="dropdown" 
           aria-expanded="false">
            
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-circle me-2" viewBox="0 0 16 16">
                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
            </svg>
            
            <div class="d-none d-sm-block">
                <span class="fw-bold"><?php echo htmlspecialchars($USUARIO_USERNAME); ?></span>
                <br>
                <small class="text-muted" style="margin-top: -5px; display: block;">
                    (Rol: <?php echo htmlspecialchars($USUARIO_ROL); ?>)
                </small>
            </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dropdownUser">
            <li>
                <a class="dropdown-item" href="guia_usuario.php">
                    <i class="bi bi-question-circle me-2"></i>Guía de Usuario
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php">Cerrar Sesión</a></li>
        </ul>
    </div>
    
</nav>