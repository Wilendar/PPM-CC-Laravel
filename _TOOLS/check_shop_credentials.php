<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;

$shop = PrestaShopShop::find(1);

echo "Shop ID: {$shop->id}\n";
echo "Shop Name: {$shop->name}\n\n";

echo "Database Credentials:\n";
echo "  db_host: " . ($shop->db_host ?? 'NULL') . "\n";
echo "  db_name: " . ($shop->db_name ?? 'NULL') . "\n";
echo "  db_user: " . ($shop->db_user ?? 'NULL') . "\n";
echo "  db_password: " . ($shop->db_password ? '[SET]' : 'NULL') . "\n\n";

if ($shop->db_host) {
    echo "Attempting to decrypt...\n";
    try {
        $host = decrypt($shop->db_host);
        echo "  ✓ db_host decrypted: {$host}\n";
    } catch (\Exception $e) {
        echo "  ❌ db_host decrypt FAILED: {$e->getMessage()}\n";
    }

    try {
        $name = decrypt($shop->db_name);
        echo "  ✓ db_name decrypted: {$name}\n";
    } catch (\Exception $e) {
        echo "  ❌ db_name decrypt FAILED: {$e->getMessage()}\n";
    }

    try {
        $user = decrypt($shop->db_user);
        echo "  ✓ db_user decrypted: {$user}\n";
    } catch (\Exception $e) {
        echo "  ❌ db_user decrypt FAILED: {$e->getMessage()}\n";
    }

    try {
        $password = decrypt($shop->db_password);
        echo "  ✓ db_password decrypted: [HIDDEN]\n";
    } catch (\Exception $e) {
        echo "  ❌ db_password decrypt FAILED: {$e->getMessage()}\n";
    }
} else {
    echo "❌ db_host is NULL - credentials not set!\n";
}
