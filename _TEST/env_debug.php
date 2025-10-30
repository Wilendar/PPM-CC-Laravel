<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PHP Environment Debug ===<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current directory: " . getcwd() . "<br>";
echo "Script filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "__DIR__: " . __DIR__ . "<br>";

echo "<br>=== Path Tests ===<br>";
$paths_to_test = [
    '__DIR__/../PPM/vendor/autoload.php',
    '/usr/home/mpptrade/domains/ppm.mpptrade.pl/PPM/vendor/autoload.php',
    '../PPM/vendor/autoload.php',
    '/domains/ppm.mpptrade.pl/PPM/vendor/autoload.php'
];

foreach ($paths_to_test as $path) {
    $resolved = (__DIR__ === dirname($path)) ? $path : str_replace('__DIR__', __DIR__, $path);
    $exists = file_exists($resolved) ? 'EXISTS' : 'MISSING';
    $readable = is_readable($resolved) ? 'READABLE' : 'NOT READABLE';
    echo "Path: $resolved<br>";
    echo "Status: $exists, $readable<br><br>";
}

echo "=== Directory listing ===<br>";
$parent_dir = dirname(__DIR__);
echo "Parent directory ($parent_dir):<br>";
if (is_dir($parent_dir)) {
    $files = scandir($parent_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- $file<br>";
        }
    }
} else {
    echo "Cannot read parent directory<br>";
}
?>