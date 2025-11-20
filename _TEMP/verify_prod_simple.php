<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRODUCTION DATABASE VERIFICATION ===\n\n";

$psd = DB::table('product_shop_data')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$psd) {
    echo "ERROR: product_shop_data NOT FOUND\n";
    exit(1);
}

echo "Updated at: {$psd->updated_at}\n\n";

$categoryMappings = json_decode($psd->category_mappings, true);

if (!$categoryMappings) {
    echo "ERROR: category_mappings is NULL\n";
    exit(1);
}

echo "CATEGORY MAPPINGS:\n";
echo json_encode($categoryMappings, JSON_PRETTY_PRINT) . "\n\n";

$uiSelected = $categoryMappings['ui']['selected'] ?? [];
$mappings = $categoryMappings['mappings'] ?? [];

echo "CHECKS:\n";

// PITGANG (PPM 41 → PS 12)
if (in_array(41, $uiSelected)) {
    echo "OK PITGANG PPM 41 FOUND\n";
    $psId = $mappings['41'] ?? null;
    echo "   Mapping PPM 41 to PS {$psId} " . ($psId === 12 ? "OK" : "FAIL") . "\n";
} else {
    echo "FAIL PITGANG PPM 41 NOT FOUND\n";
    echo "   Current ui.selected: " . json_encode($uiSelected) . "\n";
}

// Root Baza (PPM 1 → PS 1)
if (in_array(1, $uiSelected)) {
    echo "OK Root Baza PPM 1 FOUND\n";
    $psId = $mappings['1'] ?? null;
    echo "   Mapping PPM 1 to PS {$psId} " . ($psId === 1 ? "OK" : "FAIL") . "\n";
} else {
    echo "FAIL Root Baza PPM 1 NOT FOUND\n";
}

// Root Wszystko (PPM 36 → PS 2)
if (in_array(36, $uiSelected)) {
    echo "OK Root Wszystko PPM 36 FOUND\n";
    $psId = $mappings['36'] ?? null;
    echo "   Mapping PPM 36 to PS {$psId} " . ($psId === 2 ? "OK" : "FAIL") . "\n";
} else {
    echo "FAIL Root Wszystko PPM 36 NOT FOUND\n";
}

$metadata = $categoryMappings['metadata'] ?? [];
echo "\nMETADATA:\n";
echo "Last updated: " . ($metadata['last_updated'] ?? 'N/A') . "\n";
echo "Source: " . ($metadata['source'] ?? 'N/A') . "\n";
