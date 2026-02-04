<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing HTTP request to /admin/users/create...\n\n";

// Create request
$request = Illuminate\Http\Request::create('/admin/users/create', 'GET');

try {
    // Process request through full Laravel stack
    $response = $kernel->handle($request);

    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";

    $content = $response->getContent();

    // Check if it's 404 page
    if (str_contains($content, '404') || str_contains($content, 'nie została znaleziona')) {
        echo "\n=== 404 DETECTED ===\n";

        // Check route matching
        $router = app('router');
        $routeRequest = Illuminate\Http\Request::create('/admin/users/create', 'GET');

        try {
            $route = $router->getRoutes()->match($routeRequest);
            echo "Route matched: " . $route->getName() . "\n";
            echo "Action controller: " . ($route->getActionName() ?? 'closure') . "\n";
            echo "Middleware: " . implode(', ', $route->gatherMiddleware()) . "\n";
        } catch (Exception $e) {
            echo "Route NOT matched: " . $e->getMessage() . "\n";
        }
    } else {
        echo "\n=== SUCCESS - Response received ===\n";
        echo "First 500 chars of content:\n";
        echo substr($content, 0, 500) . "\n";
    }

} catch (Exception $e) {
    echo "EXCEPTION during request handling:\n";
    echo "Class: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
