<?php
// -*- coding: utf-8 -*-
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base = '/home/host379076/domains/ppm.mpptrade.pl/public_html';
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

$email = $argv[1] ?? 'admin@mpptrade.pl';
$u = User::where('email', $email)->first();
if (!$u) {
    echo "NO_USER\n";
    exit(1);
}

$roles = [];
try {
    if (method_exists($u, 'getRoleNames')) {
        $roles = $u->getRoleNames()->toArray();
    }
} catch (Throwable $e) {
    $roles = ['ERR:' . $e->getMessage()];
}

echo "USER_ID=" . $u->id . "\n";
echo "USER_EMAIL=" . $u->email . "\n";
echo "ROLES=" . implode(',', $roles) . "\n";

try {
    $rs = DB::select("SELECT id,name,guard_name FROM roles ORDER BY id");
    echo "ROLES_TABLE=" . json_encode($rs) . "\n";
    $mrs = DB::select("SELECT * FROM model_has_roles WHERE model_id=?", [$u->id]);
    echo "MODEL_HAS_ROLES=" . json_encode($mrs) . "\n";
} catch (Throwable $e) {
    echo "DBERR=" . $e->getMessage() . "\n";
}
