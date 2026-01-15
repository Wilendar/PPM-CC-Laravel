# Deploy awaiting_user status fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING awaiting_user FIX ===" -ForegroundColor Cyan

Write-Host "[1/3] Uploading JobProgress.php (scopeActive fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Models/JobProgress.php" "$RemoteBase/app/Models/JobProgress.php"

Write-Host "[2/3] Uploading JobProgressBar.php (message fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Components/JobProgressBar.php" "$RemoteBase/app/Http/Livewire/Components/JobProgressBar.php"

Write-Host "[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
