# check_variant_images_logs.ps1
# Check debug logs for variant images

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== Checking Variant Images Debug Logs ===" -ForegroundColor Cyan

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -n 100 storage/logs/laravel.log | grep -A 5 'extractCombinationImages - Image URL'"
