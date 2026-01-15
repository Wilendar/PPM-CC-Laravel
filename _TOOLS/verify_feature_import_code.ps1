$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== VERIFYING DEPLOYED CODE ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'syncProductFeatures' app/Services/PrestaShop/PrestaShopImportService.php | head -5"
Write-Host "=== DONE ===" -ForegroundColor Green
