$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOY AND TEST MEDIA SYSTEM ===" -ForegroundColor Cyan

Write-Host "`n[1/6] Uploading fixed MediaSyncService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaSyncService.php" "${RemoteBase}/app/Services/Media/MediaSyncService.php"

Write-Host "`n[2/6] Clearing cache and flushing queue..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan queue:flush"

Write-Host "`n[3/6] Checking storage folders..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la domains/ppm.mpptrade.pl/public_html/storage/app/"

Write-Host "`n[4/6] Creating media folder if needed..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mkdir -p domains/ppm.mpptrade.pl/public_html/storage/app/public/products"

Write-Host "`n[5/6] Checking public storage symlink..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la domains/ppm.mpptrade.pl/public_html/public/storage 2>/dev/null || cd domains/ppm.mpptrade.pl/public_html && php artisan storage:link"

Write-Host "`n[6/6] Creating test file to verify storage works..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "echo 'test' > domains/ppm.mpptrade.pl/public_html/storage/app/public/test.txt && ls -la domains/ppm.mpptrade.pl/public_html/storage/app/public/"

Write-Host "`n=== STORAGE CHECK DONE ===" -ForegroundColor Green
Write-Host "Uruchom import ponownie, a ja natychmiast uruchomie queue worker" -ForegroundColor Cyan
