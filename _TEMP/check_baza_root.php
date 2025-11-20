<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   DIAGNOSTYKA ROOT 'BAZA' (PS ID 1)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Check PPM categories table
echo "1️⃣ SPRAWDZENIE PPM CATEGORIES TABLE:\n\n";

$ppmBaza = DB::table('categories')->where('id', 1)->first();
if ($ppmBaza) {
    echo "   ✅ PPM category ID 1 EXISTS:\n";
    echo "      Name: {$ppmBaza->name}\n";
    echo "      Slug: {$ppmBaza->slug}\n";
    echo "      Parent ID: " . ($ppmBaza->parent_id ?? 'NULL') . "\n";
    echo "      Active: " . ($ppmBaza->is_active ? 'YES' : 'NO') . "\n\n";
} else {
    echo "   ❌ PPM category ID 1 DOES NOT EXIST\n\n";
}

// 2. Check PrestaShop database for category ID 1
echo "2️⃣ SPRAWDZENIE PRESTASHOP DATABASE (shop_id=1, B2B Test DEV):\n\n";

// Get shop credentials from prestashop_shops table
$shop = DB::table('prestashop_shops')->where('id', 1)->first();

if (!$shop) {
    echo "   ❌ Shop ID 1 NOT FOUND in prestashop_shops\n\n";
    exit(1);
}

echo "   Shop: {$shop->name}\n";
echo "   URL: {$shop->url}\n";
echo "   DB Host: {$shop->db_host}\n";
echo "   DB Name: {$shop->db_name}\n\n";

try {
    // Connect to PrestaShop database
    $dbPassword = $shop->db_password;

    // Try to decrypt if encrypted
    try {
        $dbPassword = Crypt::decryptString($dbPassword);
    } catch (\Exception $e) {
        // If decryption fails, assume password is stored in plain text (legacy)
        // Continue with original value
    }

    $prestaPdo = new PDO(
        "mysql:host={$shop->db_host};dbname={$shop->db_name};charset=utf8mb4",
        $shop->db_user,
        $dbPassword
    );
    $prestaPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "   ✅ Connected to PrestaShop database\n\n";

    // PrestaShop standard table prefix
    $prefix = 'ps_';

    // Query PrestaShop category ID 1 & 2
    $stmt = $prestaPdo->prepare("
        SELECT c.id_category, cl.name, c.id_parent, c.active, c.level_depth
        FROM {$prefix}category c
        LEFT JOIN {$prefix}category_lang cl ON c.id_category = cl.id_category AND cl.id_lang = 1
        WHERE c.id_category IN (1, 2)
        ORDER BY c.id_category
    ");
    $stmt->execute();
    $psCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($psCategories)) {
        echo "   ❌ PrestaShop categories 1 & 2 NOT FOUND\n\n";
    } else {
        echo "   PrestaShop root categories:\n\n";
        foreach ($psCategories as $cat) {
            $status = $cat['active'] ? '✅' : '❌';
            echo "   $status ID {$cat['id_category']}: {$cat['name']}\n";
            echo "      Parent ID: {$cat['id_parent']}\n";
            echo "      Level Depth: {$cat['level_depth']}\n";
            echo "      Active: " . ($cat['active'] ? 'YES' : 'NO') . "\n\n";
        }
    }

} catch (PDOException $e) {
    echo "   ❌ DATABASE ERROR: " . $e->getMessage() . "\n\n";
}

// 3. Check shop_mappings for PS ID 1 & 2
echo "3️⃣ SPRAWDZENIE SHOP_MAPPINGS (shop_id=1):\n\n";

$mappings = DB::table('shop_mappings')
    ->where('shop_id', 1)
    ->where('mapping_type', 'category')
    ->whereIn('prestashop_id', [1, 2])
    ->orderBy('prestashop_id')
    ->get(['prestashop_id', 'ppm_value', 'prestashop_value', 'is_active']);

if ($mappings->isEmpty()) {
    echo "   ❌ NO MAPPINGS for PS ID 1 or 2\n\n";
} else {
    echo "   Existing mappings:\n\n";
    foreach ($mappings as $mapping) {
        $status = $mapping->is_active ? '✅' : '❌';
        echo "   $status PS ID {$mapping->prestashop_id} → PPM ID {$mapping->ppm_value} ({$mapping->prestashop_value})\n";
    }
    echo "\n";
}

// 4. Recommendations
echo "4️⃣ REKOMENDACJA:\n\n";

$psIdOneExists = !empty(array_filter($psCategories, fn($c) => $c['id_category'] == 1));
$psIdTwoExists = !empty(array_filter($psCategories, fn($c) => $c['id_category'] == 2));
$mappingOneExists = $mappings->contains('prestashop_id', 1);
$mappingTwoExists = $mappings->contains('prestashop_id', 2);

if (!$psIdOneExists && $psIdTwoExists) {
    echo "   🎯 PrestaShop ma tylko root ID 2 (brak ID 1)\n";
    echo "   ✅ REMOVE PS ID 1 z auto-inject array\n";
    echo "   📝 Change line 236: \$requiredRoots = [1, 2]; → \$requiredRoots = [2];\n\n";
} elseif ($psIdOneExists && !$mappingOneExists && $ppmBaza) {
    echo "   🎯 PS ID 1 EXISTS + PPM ID 1 EXISTS + NO MAPPING\n";
    echo "   ✅ CREATE MAPPING: PS ID 1 → PPM ID 1\n";
    echo "   📝 INSERT INTO shop_mappings (shop_id=1, mapping_type='category', prestashop_id=1, ppm_value='1', prestashop_value='{$ppmBaza->name}', is_active=1)\n\n";
} elseif ($psIdOneExists && !$ppmBaza) {
    echo "   🎯 PS ID 1 EXISTS ale PPM ID 1 MISSING\n";
    echo "   ✅ CREATE PPM CATEGORY + MAPPING\n\n";
} else {
    echo "   ⚠️  Niejednoznaczna sytuacja - wymaga dalszej analizy\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n\n";
