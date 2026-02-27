<?php
/**
 * API DE BÚSQUEDA GLOBAL (SPOTLIGHT)
 FIX: Restricción estricta para Operadores (Solo ven Insumos)
 */

ob_start();
require_once 'src/api_auth_check.php';
require_once 'src/funciones_db.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');

$resultados = [];

try {
    if (!isset($USUARIO_ID) || !isset($USUARIO_ROL)) {
        echo json_encode([]); 
        exit;
    }

    $query = trim($_GET['q'] ?? '');

    if (strlen($query) < 2) {
        echo json_encode([]); 
        exit;
    }
    
    $term = "%$query%";
    
    $es_admin = ($USUARIO_ROL === 'admin');
    $es_supervisor = ($USUARIO_ROL === 'supervisor');
    
    // Roles de Gestión: Ven TODO (Editar, Categorías, Depósitos, Usuarios)
    $es_gestion = ($es_admin || $es_supervisor);
    
    // Roles Operativos: Ven SOLO Insumos (Ficha) y Acciones
    $es_operativo = !$es_gestion;

    // ==================================================================
    // 1. BUSCAR INSUMOS (Visible para TODOS)
    // ==================================================================
    $sqlInsumos = "SELECT id, nombre, sku FROM insumos 
                   WHERE nombre LIKE :q1 OR sku LIKE :q2 LIMIT 8";
    
    $stmt = $pdo->prepare($sqlInsumos);
    $stmt->execute([':q1' => $term, ':q2' => $term]);
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $skuStr = !empty($row['sku']) ? ' (' . $row['sku'] . ')' : '';
        
        if ($es_gestion) {
            // Admin/Supervisor -> Link a EDITAR
            $resultados[] = [
                'title'    => $row['nombre'] . $skuStr,
                'category' => 'Editar Catálogo',
                'icon'     => 'bi-pencil-square',
                'url'      => 'editar_insumo.php?id=' . $row['id']
            ];
        } else {
            // Operador/Observador -> Link a VER FICHA
            $resultados[] = [
                'title'    => $row['nombre'] . $skuStr,
                'category' => 'Producto',
                'icon'     => 'bi-box-seam',
                'url'      => 'ver_insumo.php?id=' . $row['id']
            ];
        }
    }

    // ==================================================================
    // 2. BLOQUE DE GESTIÓN (SOLO ADMIN Y SUPERVISOR)
    // ==================================================================
    // Aquí es donde hacemos el cambio: Encerramos esto en un IF
    if ($es_gestion) {

        // --- BUSCAR CATEGORÍAS ---
        $sqlCat = "SELECT id, nombre FROM categorias WHERE nombre LIKE :qCat LIMIT 5";
        $stmt = $pdo->prepare($sqlCat);
        $stmt->execute([':qCat' => $term]);
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultados[] = [
                'title'    => $row['nombre'],
                'category' => 'Categorías',
                'icon'     => 'bi-tag',
                'url'      => 'gestion_insumos.php?categoria_id=' . $row['id']
            ];
        }

        // --- BUSCAR DEPÓSITOS ---
        $sqlDep = "SELECT id, nombre FROM depositos WHERE nombre LIKE :qDep LIMIT 5";
        $stmt = $pdo->prepare($sqlDep);
        $stmt->execute([':qDep' => $term]);
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultados[] = [
                'title'    => $row['nombre'],
                'category' => 'Depósitos',
                'icon'     => 'bi-building',
                'url'      => 'historial.php?filtro_deposito_id=' . $row['id']
            ];
        }
        
        // --- BUSCAR USUARIOS (SOLO ADMIN) ---
        if ($es_admin) {
            $sqlUser = "SELECT id, username FROM usuarios WHERE username LIKE :qUser LIMIT 3";
            $stmt = $pdo->prepare($sqlUser);
            $stmt->execute([':qUser' => $term]);
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $resultados[] = [
                    'title'    => $row['username'],
                    'category' => 'Usuarios',
                    'icon'     => 'bi-person',
                    'url'      => 'gestion_usuarios.php' 
                ];
            }
        }
        
        // --- COMANDOS RÁPIDOS DE CREACIÓN (SOLO GESTIÓN) ---
        if (stripos('nuevo crear alta', $query) !== false) {
            $resultados[] = ['title' => 'Nuevo Insumo', 'category' => 'Crear', 'icon' => 'bi-plus-lg', 'url' => 'gestion_insumos.php'];
            $resultados[] = ['title' => 'Nuevo Depósito', 'category' => 'Crear', 'icon' => 'bi-building-add', 'url' => 'index.php'];
            $resultados[] = ['title' => 'Nueva Categoría', 'category' => 'Crear', 'icon' => 'bi-tags', 'url' => 'index.php'];
            
            if ($es_admin) {
                $resultados[] = ['title' => 'Nuevo Usuario', 'category' => 'Crear', 'icon' => 'bi-person-plus', 'url' => 'crear_usuario.php'];
            }
        }
        
        // --- REPORTES (SOLO GESTIÓN) ---
        if (stripos('reporte exportar', $query) !== false) {
            $resultados[] = ['title' => 'Reporte Agrupado', 'category' => 'Reportes', 'icon' => 'bi-file-earmark-spreadsheet', 'url' => 'reporte_agrupado.php'];
            $resultados[] = ['title' => 'Reporte Vencimientos', 'category' => 'Reportes', 'icon' => 'bi-calendar-x', 'url' => 'reporte_vencimientos.php'];
            if($es_admin) {
                 $resultados[] = ['title' => 'Reporte Auditoría', 'category' => 'Reportes', 'icon' => 'bi-shield-check', 'url' => 'reporte_auditoria.php'];
            }
        }
    }

    // ==================================================================
    // 3. BLOQUE OPERATIVO (SOLO OPERADORES)
    // ==================================================================
    if ($es_operativo) {
        // Atajos para Operadores (excluyendo Observadores que solo miran)
        if ($USUARIO_ROL !== 'observador') {
            if (stripos('nuevo registrar movimiento entrada salida', $query) !== false) {
                $resultados[] = ['title' => 'Registrar Movimiento', 'category' => 'Acciones', 'icon' => 'bi-plus-circle', 'url' => 'movimientos.php'];
            }
            if (stripos('traslado mover enviar', $query) !== false) {
                $resultados[] = ['title' => 'Registrar Traslado', 'category' => 'Acciones', 'icon' => 'bi-arrow-left-right', 'url' => 'traslados.php'];
            }
        }
    }

    echo json_encode($resultados);

} catch (Throwable $e) {
    echo json_encode([['title' => 'Error', 'category' => 'Sistema', 'icon' => 'bi-bug', 'url' => '#']]);
}

?>
