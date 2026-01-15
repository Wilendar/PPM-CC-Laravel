<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$categories = \App\Models\Category::where('name', 'Wszystko')->get();

echo "=== KATEGORIE 'WSZYSTKO' ===\n";
foreach ($categories as $c) {
    $productsCount = $c->products()->count();
    $childrenCount = $c->children()->count();

    echo "ID: {$c->id}\n";
    echo "  Level: {$c->level}\n";
    echo "  Parent ID: " . ($c->parent_id ?? 'NULL') . "\n";
    echo "  Products assigned: {$productsCount}\n";
    echo "  Children categories: {$childrenCount}\n";
    echo "  Created: {$c->created_at}\n";
    echo "---\n";
}

// Check which one is the correct one (level 1, child of Baza)
$correctWszystko = \App\Models\Category::where('name', 'Wszystko')
    ->where('level', 1)
    ->first();

$incorrectWszystko = \App\Models\Category::where('name', 'Wszystko')
    ->where('level', 0)
    ->first();

echo "\nPRAWIDLOWA kategoria Wszystko (level=1): " . ($correctWszystko ? "ID {$correctWszystko->id}" : "NIE ZNALEZIONO") . "\n";
echo "BLEDNA kategoria Wszystko (level=0): " . ($incorrectWszystko ? "ID {$incorrectWszystko->id}" : "NIE ZNALEZIONO") . "\n";

if ($incorrectWszystko) {
    $canDelete = $incorrectWszystko->products()->count() === 0 && $incorrectWszystko->children()->count() === 0;
    echo "Mozna usunac bledna kategorie: " . ($canDelete ? "TAK" : "NIE - ma produkty lub dzieci") . "\n";
}
