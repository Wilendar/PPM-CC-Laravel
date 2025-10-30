<?php
// Quick check script for feature_types groups
require __DIR__ . '/../bootstrap/app.php';

$app = $app ?? require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FEATURE TYPES WITH GROUPS ===\n";
$features = App\Models\FeatureType::orderBy('group')->orderBy('position')->get(['id', 'name', 'group', 'value_type']);
foreach ($features as $feature) {
    echo sprintf("ID: %2d | Group: %-20s | Name: %-20s | Type: %s\n",
        $feature->id,
        $feature->group ?? '(null)',
        $feature->name,
        $feature->value_type
    );
}

echo "\n=== GROUPED COUNT ===\n";
$grouped = App\Models\FeatureType::active()->get()->groupBy('group');
foreach ($grouped as $group => $features) {
    echo sprintf("Group: %-20s | Count: %d\n", $group ?? '(null)', $features->count());
}

echo "\n=== FEATURE TEMPLATES ===\n";
$templates = App\Models\FeatureTemplate::all(['id', 'name', 'is_predefined', 'is_active']);
foreach ($templates as $template) {
    echo sprintf("ID: %d | Name: %-30s | Predefined: %s | Active: %s | Features: %d\n",
        $template->id,
        $template->name,
        $template->is_predefined ? 'Yes' : 'No',
        $template->is_active ? 'Yes' : 'No',
        $template->getFeaturesCount()
    );
}
