<?php
require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();
$shop = \App\Models\PrestaShopShop::where("is_active", true)->first();
$client = new \App\Services\PrestaShop\PrestaShop8Client($shop);
try { $r = $client->makeRequest("GET", "/manufacturers/286"); echo "OK: " . $r["manufacturer"]["name"] . "\n"; } catch (\Exception $e) { echo "FAIL: " . $e->getMessage() . "\n"; }
