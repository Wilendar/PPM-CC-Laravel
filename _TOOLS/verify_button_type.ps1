# Verify type=button is actually there
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== VERIFYING BUTTON TYPE ATTRIBUTE ===" -ForegroundColor Cyan

# Show 10 lines before and after "Dodaj do sklepu"
Write-Host "`n[1] Full context around 'Dodaj do sklepu' button (10 lines before/after):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -A 10 -B 10 'Dodaj do sklepu' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

# Count all type="button" occurrences
Write-Host "`n[2] Total count of type=button in ProductForm:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -c 'type=\""button\""' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

# Check local file too
Write-Host "`n[3] Local file - context around 'Dodaj do sklepu':" -ForegroundColor Yellow
Get-Content "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php" | Select-String -Pattern "Dodaj do sklepu" -Context 10

Write-Host "`n=== VERIFICATION COMPLETE ===" -ForegroundColor Green