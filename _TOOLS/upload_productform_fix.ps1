# Upload ProductForm.php with shop label fixes
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalFile = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\Management\ProductForm.php"
$RemoteFile = "domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "=== UPLOADING PRODUCTFORM FIX ===" -ForegroundColor Cyan
Write-Host "Fix: Shop labels auto-save + UI refresh issues" -ForegroundColor Yellow

pscp -i $HostidoKey -P 64321 $LocalFile "host379076@host379076.hostido.net.pl:${RemoteFile}"

Write-Host "`nClearing cache..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

Write-Host "`n=== UPLOAD COMPLETE ===" -ForegroundColor Green
Write-Host "Test at: https://ppm.mpptrade.pl/admin/products/4/edit" -ForegroundColor Cyan