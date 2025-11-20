$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING IF BUG #1 FIX WAS DEPLOYED TO PRODUCTION ===" -ForegroundColor Cyan

$result = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'FIX 2025-11-19' app/Http/Livewire/Products/Management/ProductForm.php | head -10"

Write-Host "`n$result`n" -ForegroundColor White

Write-Host "=== CHECKING contextCategories IN FIELD MAPPING ===" -ForegroundColor Cyan

$result2 = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'contextCategories.*kategorie' app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "`n$result2`n" -ForegroundColor White
