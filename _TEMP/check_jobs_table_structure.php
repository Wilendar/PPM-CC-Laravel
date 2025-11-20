<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== STRUKTURA TABELI jobs ===\n\n";

$columns = DB::select("SHOW COLUMNS FROM jobs");

foreach ($columns as $col) {
    printf("%-20s %-30s %s\n",
        $col->Field,
        $col->Type,
        $col->Null == 'YES' ? 'NULL' : 'NOT NULL'
    );
}

echo "\n=== TEST: MANUAL JOB INSERT ===\n\n";

// Try to manually insert a test job
try {
    $testPayload = json_encode([
        'displayName' => 'Test Job',
        'job' => 'test',
        'maxTries' => 1,
        'timeout' => null,
        'data' => ['test' => true],
    ]);

    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => $testPayload,
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => time(),
        'created_at' => time(),
    ]);

    echo "✅ Manual insert SUCCEEDED\n";

    // Count jobs
    $count = DB::table('jobs')->count();
    echo "Jobs in table: $count\n";

    // Clean up test job
    DB::table('jobs')->where('payload', $testPayload)->delete();
    echo "Test job cleaned up\n\n";

    echo "⚠️ PROBLEM: Manual insert works, but dispatch() doesn't create jobs!\n";
    echo "⚠️ MOŻLIWE PRZYCZYNY:\n";
    echo "   1. Jobs są wyko nywane synchronicznie mimo config=database\n";
    echo "   2. Queue driver nie jest poprawnie zainicjalizowany\n";
    echo "   3. Cached config jest przestarzały\n";

} catch (\Exception $e) {
    echo "❌ Manual insert FAILED: " . $e->getMessage() . "\n";
}

echo "\n";
