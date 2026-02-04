<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing UserForm component instantiation...\n\n";

// Simulate auth (needed for authorize())
$admin = App\Models\User::find(8);
if ($admin) {
    Illuminate\Support\Facades\Auth::login($admin);
    echo "Logged in as: " . $admin->email . "\n";
}

try {
    // Create a Livewire instance
    $component = new App\Http\Livewire\Admin\Users\UserForm();
    echo "Component created OK\n";

    // Try mount
    $component->mount(null);
    echo "mount() called OK\n";

    // Try render
    $view = $component->render();
    echo "render() returned: " . get_class($view) . "\n";

    echo "\n=== SUCCESS: Component works! ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}
