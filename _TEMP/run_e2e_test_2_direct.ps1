$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== E2E TEST 2: Export TO PrestaShop (Direct Execution) ===" -ForegroundColor Cyan

# Step 1: Create AttributeType and Values
Write-Host "[1/3] Creating AttributeType and AttributeValues..." -ForegroundColor Yellow
$createScript = @'
$type = App\Models\AttributeType::create(['name' => 'Rozmiar_Test_E2E_v2', 'group' => 'Warianty', 'display_type' => 'select', 'is_active' => true]);
echo "AttributeType ID: " . $type->id . "\n";
foreach (['S_Test', 'M_Test', 'L_Test', 'XL_Test'] as $idx => $val) {
    $av = App\Models\AttributeValue::create(['attribute_type_id' => $type->id, 'label' => $val, 'value' => strtolower($val), 'display_order' => $idx+1, 'is_active' => true]);
    echo "AttributeValue ID: " . $av->id . ", Label: " . $av->label . "\n";
}
$shop = App\Models\PrestaShopShop::where('url', 'LIKE', '%dev.mpptrade.pl%')->first();
echo "Shop ID: " . $shop->id . ", Name: " . $shop->name . "\n";
App\Jobs\PrestaShop\SyncAttributeGroupWithPrestaShop::dispatch($type, $shop);
echo "Job dispatched for AttributeType ID: " . $type->id . "\n";
'@

$output1 = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"$createScript`" 2>&1"
Write-Host $output1

# Step 2: Process queue job
Write-Host "`n[2/3] Processing sync job..." -ForegroundColor Yellow
$output2 = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:work --once 2>&1"
Write-Host $output2

# Step 3: Check logs
Write-Host "`n[3/3] Checking recent logs..." -ForegroundColor Yellow
$output3 = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -50 storage/logs/laravel.log | grep -A 5 'SyncAttributeGroup' 2>&1"
Write-Host $output3

Write-Host "`n=== E2E TEST 2 EXECUTION COMPLETE ===" -ForegroundColor Green
