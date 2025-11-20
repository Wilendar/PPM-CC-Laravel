<?php
require __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();
$psd = \App\Models\ProductShopData::whereHas("shop", fn($q) => $q->where("is_active", true))->whereNotNull("prestashop_product_id")->first();
$shop = $psd->shop;
$client = new \App\Services\PrestaShop\PrestaShop8Client($shop);
echo "1. Fetching product...\n";
$current = $client->getProduct($psd->prestashop_product_id);
echo "   Product fetched: " . $current["product"]["name"][0]["value"] . "\n";
echo "2. Updating name...\n";
$current["product"]["name"][0]["value"] = "TEST " . date("H:i:s");
try { $client->updateProduct($psd->prestashop_product_id, $current["product"]); echo "   OK: Update successful\!\n"; } catch (\Exception $e) { echo "   FAIL: " . $e->getMessage() . "\n"; }
