<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "\n=== ADMIN ROLES & PERMISSIONS CHECK ===\n\n";

$admin = User::where('email', 'admin@mpptrade.pl')->first();

if (!$admin) {
    echo "❌ Admin user not found\n";
    exit;
}

echo "User: {$admin->name} ({$admin->email})\n\n";

// Get roles
$roles = $admin->getRoleNames();
echo "Roles: " . $roles->implode(', ') . "\n\n";

// Get permissions via roles
foreach ($admin->roles as $role) {
    echo "Role: {$role->name}\n";
    echo "  Permissions via this role:\n";
    foreach ($role->permissions as $perm) {
        echo "    - {$perm->name}\n";
    }
    echo "\n";
}

// Direct permissions
$directPermissions = $admin->getDirectPermissions();
if ($directPermissions->count() > 0) {
    echo "Direct permissions (not via role):\n";
    foreach ($directPermissions as $perm) {
        echo "  - {$perm->name}\n";
    }
} else {
    echo "No direct permissions (all via roles)\n";
}

echo "\n";
