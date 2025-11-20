# Test Price Mappings Load in Edit Mode
# Expected: User opens edit mode → sees existing mappings

Write-Host "`n=== TEST: Price Mappings Load in Edit Mode ===" -ForegroundColor Cyan

$shopId = 1 # B2B Test DEV shop

Write-Host "`nShop ID: $shopId (B2B Test DEV)"
Write-Host "Test URL: https://ppm.mpptrade.pl/admin/shops/add-shop?edit=$shopId"

Write-Host "`nExpected Behavior:" -ForegroundColor Yellow
Write-Host "  ✅ Step 4 (Price Group Mapping) shows 9 pre-selected mappings"
Write-Host "  ✅ Dropdowns show selected PPM groups for each PS group"
Write-Host "  ✅ User can modify existing mappings"
Write-Host "  ✅ Changes persist after save"

Write-Host "`nDatabase Verification:" -ForegroundColor Green
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
\$mappings = DB::table('prestashop_shop_price_mappings')->where('prestashop_shop_id', $shopId)->get();
echo 'Mappings in DB: ' . \$mappings->count() . PHP_EOL;
foreach (\$mappings as \$m) {
    echo '  PS ' . \$m->prestashop_price_group_id . ' (' . \$m->prestashop_price_group_name . ') → ' . \$m->ppm_price_group_name . PHP_EOL;
}
"
"@

Write-Host "`n=== TEST INSTRUCTIONS ===" -ForegroundColor Cyan
Write-Host "1. Open: https://ppm.mpptrade.pl/admin/shops/add-shop?edit=$shopId"
Write-Host "2. Navigate to Step 4 (Price Group Mapping)"
Write-Host "3. Verify that dropdowns show existing selections"
Write-Host "4. (Optional) Change a mapping and save"
Write-Host "5. Re-open edit mode and verify changes persisted"

Write-Host "`n=== TEST COMPLETE ===" -ForegroundColor Green
