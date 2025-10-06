# Upload product-form.blade.php with wire:key fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalBlade = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"
$RemoteBlade = "domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

Write-Host "=== UPLOADING BLADE TEMPLATE ===" -ForegroundColor Cyan
Write-Host "Fix: Added wire:key for shop labels" -ForegroundColor Yellow

pscp -i $HostidoKey -P 64321 $LocalBlade "host379076@host379076.hostido.net.pl:${RemoteBlade}"

Write-Host "`nClearing cache..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

Write-Host "`n=== UPLOAD COMPLETE ===" -ForegroundColor Green