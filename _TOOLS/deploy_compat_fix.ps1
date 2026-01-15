$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "[1/2] Uploading fixed CompatibilityManagement.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php" "$RemoteBase/app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php"

Write-Host "[2/2] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear"

Write-Host "Done!" -ForegroundColor Green
