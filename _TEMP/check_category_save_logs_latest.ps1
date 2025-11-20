$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING CATEGORY SAVE LOGS (Last Test) ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Checking saveAndClose + savePendingChangesToShop logs..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -300 storage/logs/laravel.log" | Select-String -Pattern "saveAndClose|savePendingChangesToShop|All pending changes saved|Checking category sync condition" -Context 3 | Select-Object -Last 40

Write-Host "`n`n=== CHECKING DB: product_shop_data for product 11034, shop 1 ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$psd = App\Models\ProductShopData::where(""product_id"", 11034)->where(""shop_id"", 1)->first(); if (`$psd) { `$cm = `$psd->category_mappings; echo ""Selected: "" . json_encode(`$cm[""ui""][""selected""] ?? []); echo PHP_EOL; echo ""Primary: "" . (`$cm[""ui""][""primary""] ?? ""NULL""); echo PHP_EOL; echo ""Updated_at: "" . `$psd->updated_at; } else { echo ""ProductShopData NOT FOUND""; }'"

Write-Host "`n`n=== DONE ===" -ForegroundColor Green
