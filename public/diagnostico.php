<?php
header('Content-Type: text/plain; charset=UTF-8');

echo "=== DIAGNOSTICO RAPIDO ===\n\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? '(sin dato)') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? '(sin dato)') . "\n";
echo "PWD realpath: " . realpath('.') . "\n\n";

$target = 'acceso.php';
echo "Buscando '$target' desde: " . getcwd() . "\n\n";

$found = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.', FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    if (strtolower($file->getBasename()) === strtolower($target)) {
        $found[] = $file->getPathname();
    }
}
if ($found) {
    echo "ENCONTRADO(S):\n";
    foreach ($found as $f) {
        echo " - " . realpath($f) . "\n";
    }
} else {
    echo "NO se encontró '$target' debajo de esta carpeta.\n";
}

echo "\nListado de nivel actual:\n";
foreach (glob('*') as $g) { echo " - $g\n"; }

echo "\n.htaccess presente aquí? " . (file_exists('.htaccess') ? 'SI' : 'NO') . "\n";
if (file_exists('.htaccess')) {
    echo "\n--- CONTENIDO .htaccess ---\n";
    echo file_get_contents('.htaccess');
}
