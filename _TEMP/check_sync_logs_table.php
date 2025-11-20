<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n=== CHECKING sync_logs TABLE ===\n\n";

// Check if table exists
if (!Schema::hasTable('sync_logs')) {
    echo "❌ Table 'sync_logs' does NOT exist!\n\n";

    // List all tables with 'sync' in name
    echo "Tables with 'sync' in name:\n";
    $tables = DB::select("SHOW TABLES LIKE '%sync%'");
    foreach ($tables as $table) {
        $tableName = reset($table);
        echo "  - $tableName\n";
    }
    exit;
}

echo "✅ Table 'sync_logs' EXISTS\n\n";

// Get table structure
echo "=== TABLE STRUCTURE ===\n";
$columns = DB::select("DESCRIBE sync_logs");
foreach ($columns as $col) {
    printf("  %-25s %-20s %s\n", $col->Field, $col->Type, $col->Null === 'YES' ? 'NULL' : 'NOT NULL');
}

// Count records
echo "\n=== RECORD COUNT ===\n";
$total = DB::table('sync_logs')->count();
printf("Total records: %d\n\n", number_format($total));

// Distribution by level/type if columns exist
if (Schema::hasColumn('sync_logs', 'level')) {
    echo "=== BY LEVEL ===\n";
    $byLevel = DB::table('sync_logs')
        ->select('level', DB::raw('COUNT(*) as count'))
        ->groupBy('level')
        ->orderByDesc('count')
        ->get();

    foreach ($byLevel as $row) {
        printf("  %-20s: %s\n", $row->level, number_format($row->count));
    }
    echo "\n";
}

// Age distribution
echo "=== AGE DISTRIBUTION ===\n";
$ageStats = DB::table('sync_logs')
    ->selectRaw("
        MIN(created_at) as oldest,
        MAX(created_at) as newest,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as last_24h,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7d,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30d
    ")
    ->first();

printf("  Oldest record: %s\n", $ageStats->oldest);
printf("  Newest record: %s\n", $ageStats->newest);
printf("  Last 24 hours: %s\n", number_format($ageStats->last_24h));
printf("  Last 7 days:   %s\n", number_format($ageStats->last_7d));
printf("  Last 30 days:  %s\n", number_format($ageStats->last_30d));

// Sample records
echo "\n=== SAMPLE RECORDS (last 5) ===\n";
$samples = DB::table('sync_logs')
    ->orderByDesc('created_at')
    ->limit(5)
    ->get();

foreach ($samples as $sample) {
    echo "---\n";
    foreach ((array)$sample as $key => $value) {
        if (strlen($value) > 100) {
            $value = substr($value, 0, 100) . '...';
        }
        printf("  %s: %s\n", $key, $value);
    }
}

echo "\n";
