$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== Checking File Boundaries ===" -ForegroundColor Cyan

Write-Host "`n[1] Checking for BOM and whitespace at start (hexdump first 20 bytes)..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "xxd $RemoteBase/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php | head -2"

Write-Host "`n[2] Checking last 10 lines with cat -A..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -10 $RemoteBase/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php | cat -A"

Write-Host "`n[3] Checking for content after closing div..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -c 50 $RemoteBase/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php | xxd"

Write-Host "`n[4] Checking line 1 carefully..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "head -1 $RemoteBase/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php | xxd"

Write-Host "`n=== DONE ===" -ForegroundColor Green
