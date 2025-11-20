<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SHOP MAPPINGS VISIBILITY DIAGNOSIS ===\n\n";

$shops = \App\Models\PrestaShopShop::all();

if ($shops->isEmpty()) {
    echo "No shops found in database\n";
    exit(1);
}

foreach ($shops as $shop) {
    echo "Shop: {$shop->name} (ID: {$shop->id})\n";
    echo str_repeat('=', 60) . "\n";

    // RAW ATTRIBUTES (before casting)
    echo "\n1. RAW ATTRIBUTES (from DB):\n";
    echo "   price_group_mappings (raw): ";
    $rawPrice = $shop->getAttributes()['price_group_mappings'] ?? null;
    echo gettype($rawPrice) . " = ";
    if (is_null($rawPrice)) {
        echo "NULL";
    } else if (is_string($rawPrice)) {
        echo substr($rawPrice, 0, 100) . (strlen($rawPrice) > 100 ? '...' : '');
    } else {
        var_dump($rawPrice);
    }

    echo "\n   warehouse_mappings (raw): ";
    $rawWarehouse = $shop->getAttributes()['warehouse_mappings'] ?? null;
    echo gettype($rawWarehouse) . " = ";
    if (is_null($rawWarehouse)) {
        echo "NULL";
    } else if (is_string($rawWarehouse)) {
        echo substr($rawWarehouse, 0, 100) . (strlen($rawWarehouse) > 100 ? '...' : '');
    } else {
        var_dump($rawWarehouse);
    }

    // CASTED ATTRIBUTES (after casting to array)
    echo "\n\n2. CASTED ATTRIBUTES (after \$casts):\n";
    echo "   price_group_mappings: " . gettype($shop->price_group_mappings) . "\n";
    if (is_array($shop->price_group_mappings)) {
        echo "   Count: " . count($shop->price_group_mappings) . "\n";
        if (!empty($shop->price_group_mappings)) {
            echo "   Sample: " . json_encode(array_slice($shop->price_group_mappings, 0, 2)) . "\n";
        }
    } else {
        echo "   Value: ";
        var_dump($shop->price_group_mappings);
    }

    echo "\n   warehouse_mappings: " . gettype($shop->warehouse_mappings) . "\n";
    if (is_array($shop->warehouse_mappings)) {
        echo "   Count: " . count($shop->warehouse_mappings) . "\n";
        if (!empty($shop->warehouse_mappings)) {
            echo "   Sample: " . json_encode(array_slice($shop->warehouse_mappings, 0, 2)) . "\n";
        }
    } else {
        echo "   Value: ";
        var_dump($shop->warehouse_mappings);
    }

    // BLADE TEMPLATE LOGIC TEST
    echo "\n\n3. BLADE LOGIC TEST:\n";
    $priceCount = is_array($shop->price_group_mappings) ? count($shop->price_group_mappings) : 0;
    $warehouseCount = is_array($shop->warehouse_mappings) ? count($shop->warehouse_mappings) : 0;
    echo "   Price mappings count (Blade logic): {$priceCount}\n";
    echo "   Warehouse mappings count (Blade logic): {$warehouseCount}\n";

    // CHECK IF VISIBLE IN BLADE
    echo "\n\n4. VISIBILITY CHECK:\n";
    if ($priceCount > 0 || $warehouseCount > 0) {
        echo "   ✅ Should be VISIBLE on /admin/shops\n";
    } else {
        echo "   ❌ Will NOT be visible (counts are zero)\n";
    }

    echo "\n" . str_repeat('-', 60) . "\n\n";
}

// Check Livewire component query
echo "\n=== LIVEWIRE COMPONENT QUERY CHECK ===\n\n";

$livewirePath = __DIR__ . '/../app/Http/Livewire/Admin/Shops/ShopManager.php';
if (file_exists($livewirePath)) {
    $content = file_get_contents($livewirePath);

    // Find render method
    if (preg_match('/public function render\(\).*?\{(.*?)\}/s', $content, $matches)) {
        echo "Render method found:\n";
        $renderMethod = $matches[0];

        // Check if it's using PrestaShopShop::all() or similar
        if (strpos($renderMethod, 'PrestaShopShop::') !== false) {
            echo "✅ Uses PrestaShopShop model\n";

            // Extract query
            if (preg_match('/(PrestaShopShop::[^;]+);/', $renderMethod, $queryMatch)) {
                echo "Query: " . $queryMatch[1] . "\n";
            }
        }

        // Check if it passes shops to view
        if (preg_match('/\$shops\s*=\s*([^;]+);/', $renderMethod, $shopsMatch)) {
            echo "Shops variable: \$shops = " . $shopsMatch[1] . "\n";
        }
    } else {
        echo "❌ Could not find render() method\n";
    }
} else {
    echo "❌ ShopManager.php not found at: {$livewirePath}\n";
}

echo "\n=== END DIAGNOSIS ===\n";
