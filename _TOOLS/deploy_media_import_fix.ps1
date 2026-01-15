$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING MEDIA IMPORT INTEGRATION ===" -ForegroundColor Cyan

# 1. Upload BulkImportProducts.php (with media sync)
Write-Host "[1/2] Uploading BulkImportProducts.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/PrestaShop/BulkImportProducts.php" "${RemoteBase}/app/Jobs/PrestaShop/BulkImportProducts.php"

# 2. Clear cache
Write-Host "[2/2] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan queue:restart"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host ""
Write-Host "ZMIANY:" -ForegroundColor Cyan
Write-Host "- BulkImportProducts teraz automatycznie pobiera zdjecia z PrestaShop" -ForegroundColor White
Write-Host "- Kazdy zaimportowany produkt dostaje SyncMediaFromPrestaShop job" -ForegroundColor White
Write-Host "- Domyslnie sync_media=true (mozna wylaczyc przez options['sync_media']=false)" -ForegroundColor White
