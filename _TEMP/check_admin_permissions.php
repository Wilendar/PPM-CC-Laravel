<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "\n=== ADMIN USER PERMISSIONS CHECK ===\n\n";

$admin = User::where('email', 'admin@mpptrade.pl')->first();

if (!$admin) {
    echo "❌ Admin user not found\n";
    exit;
}

echo "User: {$admin->name} ({$admin->email})\n";
echo "ID: {$admin->id}\n\n";

// Get all permissions
$permissions = $admin->getAllPermissions();

echo "Total permissions: " . $permissions->count() . "\n\n";

// Check for manage_system_settings specifically
$hasManageSystem = $admin->hasPermissionTo('manage_system_settings');

echo "Has 'manage_system_settings': " . ($hasManageSystem ? 'YES ✅' : 'NO ❌') . "\n\n";

// List all permissions
echo "All permissions:\n";
foreach ($permissions as $permission) {
    echo "  - {$permission->name}\n";
}

echo "\n";
