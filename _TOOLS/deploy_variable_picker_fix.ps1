$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "Deploying variable-picker-modal fix..." -ForegroundColor Cyan

# Upload fixed modal
pscp -i $HostidoKey -P 64321 "$LocalBase\resources\views\livewire\products\visual-description\partials\variable-picker-modal.blade.php" "${RemoteBase}/resources/views/livewire/products/visual-description/partials/variable-picker-modal.blade.php"

Write-Host "Clearing view cache..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

Write-Host "Done!" -ForegroundColor Green
