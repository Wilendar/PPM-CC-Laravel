$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== VERIFYING DEPLOYED CODE v2 ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'getProductFeature\|generateFeatureTypeCode\|Auto-created FeatureType' app/Services/PrestaShop/PrestaShopImportService.php | head -10"
Write-Host "=== DONE ===" -ForegroundColor Green
