<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1: Basic PHP OK<br>";

$autoload_path = __DIR__.'/../PPM/vendor/autoload.php';
echo "Test 2: Autoload path: " . $autoload_path . "<br>";

if (file_exists($autoload_path)) {
    echo "Test 3: Autoload file exists<br>";
    try {
        require $autoload_path;
        echo "Test 4: Autoload imported successfully<br>";
    } catch (Exception $e) {
        echo "ERROR in autoload: " . $e->getMessage() . "<br>";
        exit;
    }
} else {
    echo "ERROR: Autoload file missing<br>";
    exit;
}

$bootstrap_path = __DIR__.'/../PPM/bootstrap/app.php';
echo "Test 5: Bootstrap path: " . $bootstrap_path . "<br>";

if (file_exists($bootstrap_path)) {
    echo "Test 6: Bootstrap file exists<br>";
    try {
        $app = require_once $bootstrap_path;
        echo "Test 7: Laravel app loaded: " . get_class($app) . "<br>";
        echo "Test 8: Laravel SUCCESS!<br>";
    } catch (Exception $e) {
        echo "ERROR in bootstrap: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    }
} else {
    echo "ERROR: Bootstrap file missing<br>";
}
?>