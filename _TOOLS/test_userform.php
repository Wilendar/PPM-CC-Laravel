<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing UserForm component...\n";

try {
    // Check if class exists
    if (!class_exists(App\Http\Livewire\Admin\Users\UserForm::class)) {
        echo "ERROR: Class does not exist!\n";
        exit(1);
    }
    echo "Class exists: OK\n";

    // Check view exists
    $viewPath = resource_path('views/livewire/admin/users/user-form.blade.php');
    if (!file_exists($viewPath)) {
        echo "ERROR: View does not exist at: $viewPath\n";
        exit(1);
    }
    echo "View exists: OK\n";

    // Check route is registered
    $routes = app('router')->getRoutes();
    $route = $routes->getByName('admin.users.create');
    if (!$route) {
        echo "ERROR: Route 'admin.users.create' not registered!\n";
        exit(1);
    }
    echo "Route registered: OK\n";
    echo "Route action: " . print_r($route->getAction(), true) . "\n";

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
