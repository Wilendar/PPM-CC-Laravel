# Quick Blade Upload
$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalBlade = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemoteBlade = "domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

Write-Host "`n=== BLADE UPLOAD ===" -ForegroundColor Cyan

& pscp -i $HostidoKey -P 64321 $LocalBlade "${RemoteHost}:${RemoteBlade}"

Write-Host "`nClearing cache..." -ForegroundColor Yellow
& plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

Write-Host "`n=== UPLOAD COMPLETE ===" -ForegroundColor Green