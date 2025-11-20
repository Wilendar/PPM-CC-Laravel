$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   VERIFYING FIX #4 ON PRODUCTION" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "📋 Checking saveAndClose() method:`n" -ForegroundColor White

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -A 15 'public function saveAndClose' app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
