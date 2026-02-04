<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing route matching for /admin/users/create...\n\n";

$router = app('router');

// Test: Match the route
$request = Illuminate\Http\Request::create('/admin/users/create', 'GET');

try {
    $route = $router->getRoutes()->match($request);
    echo "ROUTE FOUND!\n";
    echo "Name: " . $route->getName() . "\n";
    echo "Action: " . print_r($route->getAction(), true) . "\n";
    echo "Controller: " . ($route->getController() ?? 'none') . "\n";
    echo "Parameters: " . print_r($route->parameters(), true) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
}

echo "\n--- All admin/users routes ---\n";
foreach ($router->getRoutes() as $route) {
    if (str_contains($route->uri(), 'admin/users')) {
        echo $route->methods()[0] . " " . $route->uri() . " => " . ($route->getName() ?? 'no name') . "\n";
    }
}
