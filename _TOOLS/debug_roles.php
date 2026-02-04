<?php
// Debug script for checking roles table

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

echo "=== DEBUG ROLES ===\n\n";

// Check roles table
echo "1. Roles in database:\n";
$roles = DB::table('roles')->get();
foreach ($roles as $role) {
    echo "   - ID: {$role->id}, Name: {$role->name}, Guard: {$role->guard_name}\n";
}

echo "\n2. Auth config:\n";
echo "   Default guard: " . Config::get('auth.defaults.guard') . "\n";
echo "   User model: " . Config::get('auth.providers.users.model') . "\n";

echo "\n3. Permission config models:\n";
print_r(Config::get('permission.models'));

echo "\n4. getModelForGuard('web') result:\n";
$model = Spatie\Permission\Guard::getModelForGuard('web');
echo "   Result: " . ($model ?? 'NULL') . "\n";

echo "\n=== END DEBUG ===\n";
