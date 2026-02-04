<?php
// Quick check and fix for Admin role is_system flag

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;

$admin = Role::where('name', 'Admin')->first();

echo "Admin role:\n";
echo "- ID: " . $admin->id . "\n";
echo "- Name: " . $admin->name . "\n";
echo "- is_system: " . ($admin->is_system ? 'true' : 'false') . "\n";

if (!$admin->is_system) {
    echo "\nSetting is_system = true...\n";
    $admin->is_system = true;
    $admin->save();
    echo "Done!\n";
} else {
    echo "\nAdmin role already has is_system = true\n";
}
