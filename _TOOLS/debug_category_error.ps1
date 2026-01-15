$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== Debug Category Error ===" -ForegroundColor Cyan

Write-Host "`n[1] Searching for MultipleRootElements error in logs..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -i 'MultipleRootElements\|category-tree' $RemoteBase/storage/logs/laravel.log | tail -20"

Write-Host "`n[2] Checking last 5 error entries..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -A 3 'ERROR' $RemoteBase/storage/logs/laravel.log | tail -20"

Write-Host "`n[3] Clearing all caches..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear"

Write-Host "`n[4] Checking category-tree blade file on server..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "head -3 $RemoteBase/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php && echo '...' && tail -3 $RemoteBase/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
