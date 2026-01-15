<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== UVE MIGRATIONS VERIFICATION ===\n\n";

echo "product_descriptions columns:\n";
$pdCols = Illuminate\Support\Facades\Schema::getColumnListing('product_descriptions');
echo implode(", ", $pdCols) . "\n\n";

echo "description_templates columns:\n";
$dtCols = Illuminate\Support\Facades\Schema::getColumnListing('description_templates');
echo implode(", ", $dtCols) . "\n\n";

$uveColsPd = ['blocks_v2', 'format_version'];
$uveColsDt = ['source_type', 'source_shop_id', 'source_product_id', 'structure_signature', 'document_json', 'labels', 'variables', 'css_classes', 'usage_count'];

echo "=== VERIFICATION ===\n";

$missingPd = array_diff($uveColsPd, $pdCols);
if (empty($missingPd)) {
    echo "product_descriptions: OK (all UVE columns present)\n";
} else {
    echo "product_descriptions: MISSING: " . implode(", ", $missingPd) . "\n";
}

$missingDt = array_diff($uveColsDt, $dtCols);
if (empty($missingDt)) {
    echo "description_templates: OK (all UVE columns present)\n";
} else {
    echo "description_templates: MISSING: " . implode(", ", $missingDt) . "\n";
}

echo "\n=== DONE ===\n";
