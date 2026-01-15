# Deploy icon color fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "[1/3] Uploading job-progress-bar.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/job-progress-bar.blade.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/components/job-progress-bar.blade.php"

Write-Host "[2/3] Uploading job-progress-icon.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/partials/job-progress-icon.blade.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/components/partials/job-progress-icon.blade.php"

Write-Host "[3/3] Clearing view cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

Write-Host "Done!" -ForegroundColor Green
