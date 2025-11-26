$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"

Write-Host "=== CHECKING IF ROOT CATEGORIES AUTO-REPAIR EXISTS ON PRODUCTION ===" -ForegroundColor Cyan

plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "grep -n 'ROOT CATEGORIES MISSING\|auto-repair\|needsRepair' domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "`n=== CHECK COMPLETE ===" -ForegroundColor Green
