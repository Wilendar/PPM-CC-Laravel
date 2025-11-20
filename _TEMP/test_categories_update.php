<?php
require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();
$psd = \App\Models\ProductShopData::whereHas("shop", fn($q) => $q->where("is_active", true))->whereNotNull("prestashop_product_id")->first();
$shop = $psd->shop;
$client = new \App\Services\PrestaShop\PrestaShop8Client($shop);
echo "Testing categories update...\n";
try { $data = ["product" => ["name" => [["id" => 1, "value" => "TEST " . date("H:i:s")]], "associations" => ["categories" => [["id" => 9],["id" => 15],["id" => 800],["id" => 981],["id" => 983],["id" => 985],["id" => 2350]]]]]; $client->updateProduct($psd->prestashop_product_id, $data); echo "OK: Categories without ID 2 work\!\n"; } catch (\Exception $e) { echo "FAIL: " . $e->getMessage() . "\n"; }
