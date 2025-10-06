# Deploy fixed product-form.blade.php to production

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalFile = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"
$RemotePath = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

Write-Host "Deploying fixed product-form.blade.php..." -ForegroundColor Cyan

# Upload file
& "C:\Program Files\PuTTY\pscp.exe" -i $HostidoKey -P 64321 $LocalFile $RemotePath

if ($LASTEXITCODE -eq 0) {
    Write-Host "File uploaded successfully!" -ForegroundColor Green

    Write-Host "`nClearing all caches..." -ForegroundColor Yellow

    # Clear all caches including OPcache
    & "C:\Program Files\PuTTY\plink.exe" -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php -r 'if (function_exists(""opcache_reset"")) opcache_reset();' && rm -rf storage/framework/views/* && php artisan view:clear && php artisan cache:clear && php artisan optimize:clear"

    Write-Host "`n=== DEPLOYMENT COMPLETED ===" -ForegroundColor Green
} else {
    Write-Host "Upload failed!" -ForegroundColor Red
    exit 1
}
