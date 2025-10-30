<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FEATURE TEMPLATES CHECK ===" . PHP_EOL;
echo "Templates count: " . \App\Models\FeatureTemplate::count() . PHP_EOL . PHP_EOL;

if (\App\Models\FeatureTemplate::count() > 0) {
    echo "Templates in database:" . PHP_EOL;
    foreach (\App\Models\FeatureTemplate::all() as $template) {
        echo "  - ID {$template->id}: {$template->name} (predefined: " . ($template->is_predefined ? 'YES' : 'NO') . ", features: " . $template->getFeaturesCount() . ")" . PHP_EOL;
    }
} else {
    echo "❌ NO TEMPLATES FOUND - seeder not run!" . PHP_EOL;
}

echo PHP_EOL . "=== FEATURE TYPES CHECK ===" . PHP_EOL;
echo "Feature types count: " . \App\Models\FeatureType::count() . PHP_EOL;

// Check for 'group' column
$columns = \Illuminate\Support\Facades\Schema::getColumnListing('feature_types');
echo "'group' column exists: " . (in_array('group', $columns) ? 'YES' : 'NO') . PHP_EOL;
