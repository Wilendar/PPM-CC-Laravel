$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== TEST 4: MULTI-SHOP SUPPORT ===" -ForegroundColor Cyan

# Step 1: Check existing shops
Write-Host "`n[Step 1] Checking existing PrestaShop shops..." -ForegroundColor Yellow
$shops = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$shops = DB::table('prestashop_shops')->select('id', 'name', 'base_url', 'is_active')->get();
echo json_encode(\`$shops, JSON_PRETTY_PRINT);
"
"@

Write-Host $shops

# Step 2: Check if test.kayomoto.pl exists
Write-Host "`n[Step 2] Looking for test.kayomoto.pl..." -ForegroundColor Yellow
if ($shops -like "*test.kayomoto.pl*") {
    Write-Host "✅ Shop exists" -ForegroundColor Green
} else {
    Write-Host "⚠️  Shop NOT found - need to add it" -ForegroundColor Yellow

    # Add test.kayomoto.pl shop
    Write-Host "`nAdding test.kayomoto.pl to database..." -ForegroundColor Yellow
    $addShop = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
DB::table('prestashop_shops')->insert([
    'name' => 'Kayomoto Test',
    'base_url' => 'https://test.kayomoto.pl',
    'api_key' => '1ZEUFUI8JTYY5Z9XXQV2RRANZTKK4R77',
    'prestashop_version' => '8',
    'is_active' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);
echo 'Shop added';
"
"@
    Write-Host $addShop
}

# Step 3: Create test AttributeType
Write-Host "`n[Step 3] Creating test AttributeType..." -ForegroundColor Yellow
$createAttr = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$timestamp = date('YmdHis');
\`$attr = DB::table('attribute_types')->insertGetId([
    'name' => 'MultiShop_Test_' . \`$timestamp,
    'code' => 'multishop_test_' . \`$timestamp,
    'display_type' => 'dropdown',
    'position' => 0,
    'is_active' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);
echo json_encode(['id' => \`$attr, 'name' => 'MultiShop_Test_' . \`$timestamp]);
"
"@

Write-Host $createAttr
$attrData = $createAttr | ConvertFrom-Json
$attrId = $attrData.id

Write-Host "✅ Created AttributeType ID=$attrId" -ForegroundColor Green

# Step 4: Dispatch sync jobs to BOTH shops
Write-Host "`n[Step 4] Dispatching sync to BOTH shops..." -ForegroundColor Yellow

# Shop 1: B2B Test DEV (ID=1)
Write-Host "  → Shop 1: B2B Test DEV" -ForegroundColor White
$sync1 = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$attr = App\Models\AttributeType::find($attrId);
\`$shop = App\Models\PrestaShopShop::find(1);
App\Jobs\PrestaShop\SyncAttributeGroupWithPrestaShop::dispatch(\`$attr, \`$shop);
echo 'Job dispatched to Shop 1';
"
"@
Write-Host "    $sync1" -ForegroundColor Gray

# Shop 2: Kayomoto Test
Write-Host "  → Shop 2: Kayomoto Test" -ForegroundColor White
$shopQuery = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$shop = DB::table('prestashop_shops')->where('base_url', 'like', '%kayomoto%')->first();
echo json_encode(\`$shop);
"
"@
$shopData = $shopQuery | ConvertFrom-Json
$shop2Id = $shopData.id

$sync2 = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$attr = App\Models\AttributeType::find($attrId);
\`$shop = App\Models\PrestaShopShop::find($shop2Id);
App\Jobs\PrestaShop\SyncAttributeGroupWithPrestaShop::dispatch(\`$attr, \`$shop);
echo 'Job dispatched to Shop 2';
"
"@
Write-Host "    $sync2" -ForegroundColor Gray

# Step 5: Process queue jobs
Write-Host "`n[Step 5] Processing queue (2 jobs)..." -ForegroundColor Yellow
1..2 | ForEach-Object {
    $queue = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
      "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:work --once"
    Write-Host "  Job $_ processed" -ForegroundColor Gray
}

# Step 6: Verify independent mapping records
Write-Host "`n[Step 6] Verifying independent mapping records..." -ForegroundColor Yellow
$mappings = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$mappings = DB::table('prestashop_attribute_group_mapping')
    ->where('attribute_type_id', $attrId)
    ->join('prestashop_shops', 'prestashop_attribute_group_mapping.prestashop_shop_id', '=', 'prestashop_shops.id')
    ->select(
        'prestashop_shops.name as shop_name',
        'prestashop_attribute_group_mapping.sync_status',
        'prestashop_attribute_group_mapping.prestashop_attribute_group_id',
        'prestashop_attribute_group_mapping.last_synced_at'
    )
    ->get();
echo json_encode(\`$mappings, JSON_PRETTY_PRINT);
"
"@

Write-Host $mappings

Write-Host "`n=== TEST 4 RESULTS ===" -ForegroundColor Cyan
if ($mappings -like "*missing*" -and $mappings -like "*B2B Test DEV*" -and $mappings -like "*Kayomoto*") {
    Write-Host "✅ PASSED: Independent mapping records created for both shops" -ForegroundColor Green
    Write-Host "✅ PASSED: Each shop has its own sync_status" -ForegroundColor Green
    Write-Host "✅ PASSED: Multi-shop support verified" -ForegroundColor Green
} else {
    Write-Host "❌ FAILED: Check mapping records above" -ForegroundColor Red
}

Write-Host "`n=== DONE ===" -ForegroundColor Green
