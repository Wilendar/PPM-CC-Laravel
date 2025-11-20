$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   MANUAL IMPORT TRIGGER - BUG #14 VERIFICATION    ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

Write-Host "Step 1: Triggering import from PrestaShop..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan prestashop:pull-products 1"

Write-Host "`nStep 2: Waiting for job to process (30 seconds)..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

Write-Host "`nStep 3: Running queue worker..." -ForegroundColor Yellow
# Run queue worker with timeout (process max 3 jobs)
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && timeout 60 php artisan queue:work --stop-when-empty --max-jobs=3"

Write-Host "`nStep 4: Checking results..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

Write-Host "`nChecking product_prices for test product..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrace.pl/public_html && php artisan tinker --execute=`"echo 'Prices count: ' . \DB::table('product_prices')->where('product_id', 11029)->count();`""

Write-Host "`nDone!" -ForegroundColor Green
