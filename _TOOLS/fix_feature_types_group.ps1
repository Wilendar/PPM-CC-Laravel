$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== FIXING FEATURE TYPES WITHOUT GROUP ===" -ForegroundColor Cyan

# Create PHP script to fix existing FeatureTypes
$phpScript = @'
<?php
// One-time fix: Assign feature_group_id to FeatureTypes that only have 'group' string

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FeatureType;
use App\Models\FeatureGroup;
use Illuminate\Support\Facades\Log;

echo "=== Fixing FeatureTypes without feature_group_id ===\n";

// Find or create the "imported_prestashop" group
$groupCode = 'imported_prestashop';
$group = FeatureGroup::where('code', $groupCode)->first();

if (!$group) {
    $group = FeatureGroup::create([
        'code' => $groupCode,
        'name' => 'Imported from PrestaShop',
        'name_pl' => 'Importowane z PrestaShop',
        'icon' => 'info',
        'color' => 'purple',
        'sort_order' => 999,
        'description' => 'Cechy automatycznie utworzone podczas importu z PrestaShop',
        'is_active' => true,
        'is_collapsible' => true,
    ]);
    echo "Created FeatureGroup: {$group->id} ({$group->name_pl})\n";
} else {
    echo "Found existing FeatureGroup: {$group->id} ({$group->name_pl})\n";
}

// Find all FeatureTypes without feature_group_id that have 'Importowane z PrestaShop' in group field
$featureTypesWithoutGroup = FeatureType::whereNull('feature_group_id')
    ->where('group', 'like', '%Import%')
    ->get();

echo "Found {$featureTypesWithoutGroup->count()} FeatureTypes without feature_group_id\n";

$updated = 0;
foreach ($featureTypesWithoutGroup as $ft) {
    $ft->feature_group_id = $group->id;
    $ft->save();
    $updated++;
    echo "  - Updated: {$ft->name} (ID: {$ft->id})\n";
}

// Also fix any OTHER FeatureTypes without feature_group_id
$otherWithoutGroup = FeatureType::whereNull('feature_group_id')->get();
if ($otherWithoutGroup->count() > 0) {
    echo "\nFound {$otherWithoutGroup->count()} OTHER FeatureTypes without feature_group_id:\n";
    foreach ($otherWithoutGroup as $ft) {
        $ft->feature_group_id = $group->id;
        $ft->save();
        $updated++;
        echo "  - Updated: {$ft->name} (ID: {$ft->id})\n";
    }
}

echo "\n=== DONE: Updated {$updated} FeatureTypes ===\n";

// Verify product 11140 features
$product11140Features = \App\Models\ProductFeature::where('product_id', 11140)
    ->with('featureType.featureGroup')
    ->get();

echo "\n=== Product 11140 Features Check ===\n";
echo "Total features: {$product11140Features->count()}\n";
$withGroup = $product11140Features->filter(fn($pf) => $pf->featureType && $pf->featureType->feature_group_id)->count();
echo "Features with feature_group_id: {$withGroup}\n";
'@

# Save PHP script to temp file
$tempFile = "_TEMP/fix_feature_types.php"
Set-Content -Path "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\fix_feature_types.php" -Value $phpScript -Encoding UTF8

Write-Host "[1/3] Uploading fix script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\fix_feature_types.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/fix_feature_types.php"

Write-Host "[2/3] Running fix script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/fix_feature_types.php"

Write-Host "[3/3] Cleaning up..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && rm _TEMP/fix_feature_types.php"

Write-Host "=== DONE ===" -ForegroundColor Green
