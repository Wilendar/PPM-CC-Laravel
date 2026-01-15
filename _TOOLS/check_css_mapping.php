<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$d = App\Models\ProductDescription::find(8);

echo "Product ID: {$d->product_id}\n";
echo "Shop ID: {$d->shop_id}\n";
echo "CSS Mode: {$d->css_mode}\n";
echo "CSS Synced At: {$d->css_synced_at}\n\n";

echo "=== CSS CLASS MAP ===\n";
$classMap = $d->css_class_map ?? [];
if (empty($classMap)) {
    echo "EMPTY (no mapping!)\n";
} else {
    foreach ($classMap as $elementId => $className) {
        echo "{$elementId} => {$className}\n";
    }
}

echo "\n=== CSS RULES ===\n";
$rules = $d->css_rules ?? [];
echo "Total rules: " . count($rules) . "\n";
foreach (array_slice(array_keys($rules), 0, 5) as $selector) {
    echo "- {$selector}\n";
}
