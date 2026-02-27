<?php

// 1. Funciones Auxiliares (Subidas de archivos, etc.)
require_once __DIR__ . '/db/funciones_auxiliares.php';

// 2. Funciones de Maestros (Depósitos, Categorías, Proveedores)
require_once __DIR__ . '/db/funciones_maestros.php';

// 3. Funciones de Insumos (CRUD de Insumos)
require_once __DIR__ . '/db/funciones_insumos.php';

// 4. LÓGICA DE STOCK (DIVIDIDA)
// ===================================================================
// 4a. Funciones de Stock (Helpers y Consultas de Stock)
require_once __DIR__ . '/db/funciones_stock.php';

// 4b. Funciones de Movimientos (COMANDOS - Escritura)
require_once __DIR__ . '/db/funciones_movimientos_comandos.php';

// 4c. Funciones de Movimientos (CONSULTAS - Lectura)
require_once __DIR__ . '/db/funciones_movimientos_consultas.php';

// 4d. Procesador de Movimientos Programados
require_once __DIR__ . '/db/funciones_procesador.php';
// ===================================================================

// 5. Funciones de Reportes (Dashboard, Gráficos, Vencimientos)
require_once __DIR__ . '/db/funciones_reportes.php';

// 6. Funciones de Usuarios (CRUD de Usuarios)
require_once __DIR__ . '/db/funciones_usuarios.php';

// 7. Funciones de Reglas (Links Depósito <-> Categoría/Usuario)
require_once __DIR__ . '/db/funciones_reglas.php';

// 8. Funciones de Seguridad (Rate Limiting)
require_once __DIR__ . '/db/funciones_seguridad.php';

// 9. Funciones de Email

require_once __DIR__ . '/db/funciones_email.php';
