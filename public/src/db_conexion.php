<?php
/**
 * ======================================================================
 * ARCHIVO DE CONEXIÓN A LA BASE DE DATOS (PDO) - VERSIÓN .ENV
 * ======================================================================
 * v2.0 - Utiliza vlucas/phpdotenv para cargar credenciales seguras.
 */

// 1. Cargar el Autoloader de Composer
// Subimos 2 niveles desde /public/src/ para encontrar /vendor/
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

function conectar_db() {
    
    // 2. Inicializar y Cargar variables de entorno
    // Buscamos el archivo .env en la raíz del proyecto
    $root_path = realpath(__DIR__ . '/../../');
    
    if (!file_exists($root_path . '/.env')) {
        throw new \Exception("Error crítico: No se encontró el archivo .env en la raíz.");
    }

    // Carga las variables al entorno ($_ENV y $_SERVER)
    $dotenv = Dotenv::createImmutable($root_path);
    $dotenv->load();

    // 3. Validar que existan las variables requeridas
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER']);

    // 4. Obtener credenciales
    $host = $_ENV['DB_HOST'];
    $db_name = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'] ?? ''; // Puede estar vacío en local

    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // En producción, no mostrar $e->getMessage() al usuario final por seguridad
        throw new \PDOException("Error de conexión a la base de datos.");
    }
}
?>