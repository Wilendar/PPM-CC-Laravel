<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AttributeType;
use App\Models\AttributeValue;

echo "=== ATTRIBUTE TYPES ===\n";
$types = AttributeType::withCount('values')->orderBy('id')->get();
foreach ($types as $t) {
    echo "{$t->id} | {$t->name} | code: {$t->code} | display: {$t->display_type} | values: {$t->values_count}\n";
}

echo "\n=== LOOKING FOR DUPLICATES (same name) ===\n";
$grouped = $types->groupBy('name');
foreach ($grouped as $name => $group) {
    if ($group->count() > 1) {
        echo "DUPLICATE: {$name}\n";
        foreach ($group as $t) {
            echo "  - ID: {$t->id}, code: {$t->code}, values: {$t->values_count}\n";
        }
    }
}

echo "\n=== RECENT ATTRIBUTE TYPES (last 5) ===\n";
$recent = AttributeType::orderBy('created_at', 'desc')->take(5)->get();
foreach ($recent as $t) {
    echo "{$t->id} | {$t->name} | code: {$t->code} | created: {$t->created_at}\n";
}
