<?php
require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();
$psd = \App\Models\ProductShopData::whereHas("shop", fn($q) => $q->where("is_active", true))->whereNotNull("prestashop_product_id")->first();
$shop = $psd->shop;
$client = new \App\Services\PrestaShop\PrestaShop8Client($shop);
echo "Testing minimal update with ID...\n";
try { $data = ["id" => $psd->prestashop_product_id, "name" => [["id" => 1, "value" => "TEST " . date("H:i:s")]]]; $client->updateProduct($psd->prestashop_product_id, $data); echo "OK: Update works\!\n"; } catch (\Exception $e) { echo "FAIL: " . $e->getMessage() . "\n"; }
