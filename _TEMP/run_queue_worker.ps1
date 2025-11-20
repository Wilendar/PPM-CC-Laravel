$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== PROCESSING QUEUE JOB ===" -ForegroundColor Cyan
Write-Host ""

# Upload helper scripts
Write-Host "Uploading helper scripts..." -ForegroundColor Gray
& pscp -i $HostidoKey -P 64321 -q "_TEMP/count_jobs.php" "$RemoteBase/_TEMP/count_jobs.php"
& pscp -i $HostidoKey -P 64321 -q "_TEMP/show_latest_syncjob.php" "$RemoteBase/_TEMP/show_latest_syncjob.php"

Write-Host "[1/4] Checking jobs BEFORE processing..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/count_jobs.php"

Write-Host "`n[2/4] Processing ONE job from queue..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && timeout 60 php artisan queue:work --once --timeout=55"

Write-Host "`n[3/4] Checking jobs AFTER processing..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/count_jobs.php"

Write-Host "`n[4/4] Showing latest sync_job..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/show_latest_syncjob.php"

Write-Host ""
Write-Host "=== PROCESSING COMPLETE ===" -ForegroundColor Green
Write-Host ""
