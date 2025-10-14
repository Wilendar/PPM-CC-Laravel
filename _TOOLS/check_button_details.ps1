# Check exact button HTML
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING BUTTON DETAILS ===" -ForegroundColor Cyan

# Get 10 lines before and after "Dodaj do sklepu"
Write-Host "`n[1] Full button HTML (10 lines before/after):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -B 10 -A 3 'Dodaj do sklepu' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php | head -15"

Write-Host "`n=== CHECK COMPLETE ===" -ForegroundColor Green