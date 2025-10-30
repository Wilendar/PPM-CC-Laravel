<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1: Basic PHP OK<br>";

$autoload_path = '/usr/home/mpptrade/domains/ppm.mpptrade.pl/PPM/vendor/autoload.php';
echo "Test 2: Absolute autoload path: " . $autoload_path . "<br>";

if (file_exists($autoload_path)) {
    echo "Test 3: Autoload file exists (absolute)<br>";
    try {
        require $autoload_path;
        echo "Test 4: Autoload imported successfully<br>";
    } catch (Exception $e) {
        echo "ERROR in autoload: " . $e->getMessage() . "<br>";
        exit;
    }
} else {
    echo "ERROR: Autoload file missing (absolute path)<br>";
    exit;
}

$bootstrap_path = '/usr/home/mpptrade/domains/ppm.mpptrade.pl/PPM/bootstrap/app.php';
echo "Test 5: Absolute bootstrap path: " . $bootstrap_path . "<br>";

if (file_exists($bootstrap_path)) {
    echo "Test 6: Bootstrap file exists (absolute)<br>";
    try {
        $app = require_once $bootstrap_path;
        echo "Test 7: Laravel app loaded: " . get_class($app) . "<br>";
        echo "Test 8: Laravel SUCCESS WITH ABSOLUTE PATHS!<br>";
    } catch (Exception $e) {
        echo "ERROR in bootstrap: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    }
} else {
    echo "ERROR: Bootstrap file missing (absolute path)<br>";
}
?>