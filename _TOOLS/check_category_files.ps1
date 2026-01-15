$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== Checking Category Tree Files ===" -ForegroundColor Cyan

Write-Host "`n[1] Checking blade file line count on server..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "wc -l $RemoteBase/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php"

Write-Host "`n[2] Checking if old category-tree.blade.php exists on server..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la $RemoteBase/resources/views/livewire/products/categories/*.blade.php | head -10"

Write-Host "`n[3] Checking CategoryTree.php render method..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -A 3 'function render' $RemoteBase/app/Http/Livewire/Products/Categories/CategoryTree.php"

Write-Host "`n[4] Checking flash-messages component structure..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "wc -l $RemoteBase/resources/views/components/flash-messages.blade.php && echo '--- Structure:' && head -3 $RemoteBase/resources/views/components/flash-messages.blade.php && echo '...' && tail -10 $RemoteBase/resources/views/components/flash-messages.blade.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
