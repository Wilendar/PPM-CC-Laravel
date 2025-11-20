$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING FOR NEW loadShopCategories LOGS ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Looking for debug logs from NEW code (after deployment)..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -100 storage/logs/laravel.log" | Select-String -Pattern "Loaded from product_shop_data" -Context 2

Write-Host "`nIf no logs found above, it means no one opened the product yet after deployment." -ForegroundColor Gray
Write-Host "User needs to:" -ForegroundColor Yellow
Write-Host "  1. Hard refresh page (Ctrl+F5)" -ForegroundColor White
Write-Host "  2. Or open product in new tab/session" -ForegroundColor White
Write-Host "  3. Then NEW code will activate" -ForegroundColor White
