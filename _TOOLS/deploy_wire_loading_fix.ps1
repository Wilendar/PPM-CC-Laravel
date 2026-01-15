$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING wire:loading FIX ===" -ForegroundColor Cyan

pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/management/product-form.blade.php" "${RemoteBase}/resources/views/livewire/products/management/product-form.blade.php"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

Write-Host "=== DONE ===" -ForegroundColor Green
