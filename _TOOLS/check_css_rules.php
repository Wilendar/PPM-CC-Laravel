<?php
/**
 * Check CSS rules in product_descriptions table
 * ETAP_07h verification
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$description = App\Models\ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->first(['css_rules', 'css_class_map', 'css_mode', 'css_migrated_at']);

if (!$description) {
    echo "No description found for product 11183, shop 5\n";
    exit(1);
}

echo "=== CSS-First Architecture Status ===\n\n";
echo "css_mode: " . ($description->css_mode ?? 'NULL') . "\n";
echo "css_migrated_at: " . ($description->css_migrated_at ?? 'NULL') . "\n\n";

echo "css_rules:\n";
echo json_encode($description->css_rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "css_class_map:\n";
echo json_encode($description->css_class_map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
