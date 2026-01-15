<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\VariantAttribute;

echo "=== DIAGNOSTYKA ATRYBUTOW WARIANTOW ===\n\n";

// 1. Znajdz produkt
$product = Product::where('sku', 'MRF13-68-003')->first();
if (!$product) {
    echo "Produkt nie znaleziony!\n";
    exit;
}

echo "Produkt: {$product->name} (ID: {$product->id})\n";
echo "Liczba wariantow: " . $product->variants()->count() . "\n\n";

// 2. Sprawdz grupy atrybutow (AttributeType)
echo "=== GRUPY ATRYBUTOW W SYSTEMIE ===\n";
$groups = AttributeType::withCount('values')->get();
foreach ($groups as $g) {
    echo "- {$g->name} (ID: {$g->id}, code: {$g->code}): {$g->values_count} wartosci\n";
}

// 3. Sprawdz wartosci atrybutow dla wariantow produktu
echo "\n=== ATRYBUTY WARIANTOW PRODUKTU (pierwsze 5) ===\n";
$variants = $product->variants()->with(['attributes.attributeType', 'attributes.attributeValue'])->take(5)->get();

foreach ($variants as $v) {
    echo "\nWariant: {$v->sku}\n";
    if ($v->attributes->isEmpty()) {
        echo "  BRAK przypisanych wartosci atrybutow!\n";
    } else {
        foreach ($v->attributes as $attr) {
            $typeName = $attr->attributeType ? $attr->attributeType->name : 'BRAK TYPU';
            $valueName = $attr->attributeValue ? $attr->attributeValue->label : 'BRAK WARTOSCI';
            echo "  - {$typeName}: {$valueName}\n";
        }
    }
}

// 4. Sprawdz tabele pivot (variant_attributes)
echo "\n=== TABELA PIVOT (variant_attributes) ===\n";
$pivotCount = VariantAttribute::whereHas('variant', function($q) use ($product) {
    $q->where('product_id', $product->id);
})->count();
echo "Liczba przypisan dla produktu: {$pivotCount}\n";

// 5. Sprawdz czy kolory sa w bazie
echo "\n=== SPRAWDZENIE KOLOROW ===\n";
$colorType = AttributeType::where('code', 'color')->orWhere('name', 'like', '%olor%')->first();
if ($colorType) {
    echo "Grupa Kolor ID: {$colorType->id}, name: {$colorType->name}, code: {$colorType->code}\n";
    $colorValues = AttributeValue::where('attribute_type_id', $colorType->id)->get();
    echo "Wartosci kolorow w bazie: " . $colorValues->count() . "\n";
    foreach ($colorValues->take(10) as $cv) {
        echo "  - {$cv->label} (code: {$cv->code})\n";
    }
} else {
    echo "Grupa 'Kolor' nie znaleziona!\n";
}

// 6. Sprawdz unikalne kolory w wariantach produktu
echo "\n=== UNIKALNE KOLORY W WARIANTACH PRODUKTU ===\n";
$variantColorValues = VariantAttribute::whereHas('variant', function($q) use ($product) {
    $q->where('product_id', $product->id);
})->whereHas('attributeType', function($q) {
    $q->where('code', 'color')->orWhere('name', 'like', '%olor%');
})->with('attributeValue')->get();

if ($variantColorValues->isEmpty()) {
    echo "Brak przypisanych kolorow w wariantach!\n";

    // Sprawdz czy w ogole sa jakies atrybuty w wariantach
    echo "\n=== DEBUG: Wszystkie atrybuty w wariantach produktu ===\n";
    $allAttrs = VariantAttribute::whereHas('variant', function($q) use ($product) {
        $q->where('product_id', $product->id);
    })->with(['attributeType', 'attributeValue'])->take(20)->get();

    foreach ($allAttrs as $attr) {
        $typeName = $attr->attributeType ? $attr->attributeType->name : 'NULL';
        $valueName = $attr->attributeValue ? $attr->attributeValue->label : 'NULL';
        echo "  - type_id: {$attr->attribute_type_id}, value_id: {$attr->value_id} => {$typeName}: {$valueName}\n";
    }
} else {
    $uniqueColors = $variantColorValues->pluck('attributeValue.label')->unique();
    echo "Znalezione kolory: " . $uniqueColors->implode(', ') . "\n";
}

echo "\n=== KONIEC DIAGNOSTYKI ===\n";
