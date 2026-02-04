<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UserSession;

// Check sessions for admin (user_id=8)
$count = UserSession::where('user_id', 8)->count();
echo "Sessions for admin (user_id=8): " . $count . "\n";

if ($count == 0) {
    UserSession::create([
        'user_id' => 8,
        'session_id' => 'manual-session-' . time(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Manual session for admin panel test',
        'device_type' => 'desktop',
        'browser' => 'Chrome',
        'os' => 'Windows',
        'location' => 'Poland',
        'is_current' => true,
        'last_activity' => now(),
    ]);
    echo "Session created!\n";
}

$newCount = UserSession::where('user_id', 8)->count();
echo "Sessions after: " . $newCount . "\n";
