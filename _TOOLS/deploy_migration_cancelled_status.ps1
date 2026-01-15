# Deploy migration for cancelled status
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING MIGRATION: cancelled status ===" -ForegroundColor Cyan

Write-Host "[1/3] Uploading migration file..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "database/migrations/2025_12_10_000000_add_cancelled_status_to_job_progress.php" "${RemoteBase}/database/migrations/2025_12_10_000000_add_cancelled_status_to_job_progress.php"

Write-Host "[2/3] Running migration..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

Write-Host "[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear"

Write-Host "=== MIGRATION COMPLETE ===" -ForegroundColor Green
