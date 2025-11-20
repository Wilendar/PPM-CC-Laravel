$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Uploading ProductForm.php hotfix..." -ForegroundColor Cyan

pscp -i $HostidoKey -P 64321 `
    "app/Http/Livewire/Products/Management/ProductForm.php" `
    "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "Clearing Laravel cache..." -ForegroundColor Cyan

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear"

Write-Host "HOTFIX DEPLOYED!" -ForegroundColor Green
