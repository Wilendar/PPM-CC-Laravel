<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\AttributeType;

echo "=== EXISTING ATTRIBUTE TYPES ===" . PHP_EOL . PHP_EOL;

$attributeTypes = AttributeType::with('values')->get();

if ($attributeTypes->isEmpty()) {
    echo "No AttributeTypes found in database." . PHP_EOL;
} else {
    foreach ($attributeTypes as $attr) {
        echo "AttributeType #" . $attr->id . PHP_EOL;
        echo "  Name: " . $attr->name . PHP_EOL;
        echo "  Display Type: " . $attr->display_type . PHP_EOL;
        echo "  Values: " . $attr->values->count() . PHP_EOL;
        echo PHP_EOL;
    }
}

echo PHP_EOL . "=== PRESTASHOP ATTRIBUTE GROUP MAPPINGS ===" . PHP_EOL . PHP_EOL;

$mappings = DB::table('prestashop_attribute_group_mapping')->get();

if ($mappings->isEmpty()) {
    echo "No mappings found." . PHP_EOL;
} else {
    foreach ($mappings as $mapping) {
        echo "Mapping #" . $mapping->id . PHP_EOL;
        echo "  AttributeType ID: " . $mapping->attribute_type_id . PHP_EOL;
        echo "  Shop ID: " . $mapping->prestashop_shop_id . PHP_EOL;
        echo "  PS Group ID: " . $mapping->prestashop_attribute_group_id . PHP_EOL;
        echo "  Sync Status: " . $mapping->sync_status . PHP_EOL;
        echo "  Last Synced: " . ($mapping->last_synced_at ?? 'never') . PHP_EOL;
        echo PHP_EOL;
    }
}
