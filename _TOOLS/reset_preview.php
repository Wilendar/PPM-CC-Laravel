<?php

// Reset CategoryPreview shown_count to allow re-testing
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$previewId = $argv[1] ?? 105;

echo "=== RESET CATEGORY PREVIEW ===\n\n";

$updated = DB::table('category_preview')
    ->where('id', $previewId)
    ->update(['shown_count' => 0]);

if ($updated) {
    echo "✅ Preview #{$previewId} reset (shown_count = 0)\n";
    echo "   Modal will appear again on next poll\n";
} else {
    echo "❌ Preview #{$previewId} not found\n";
}

echo "\n=== END ===\n";
