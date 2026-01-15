$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Checking sync variants logs (last 500 lines)..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -500 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(syncProductVariants|importSingleVariant|getCombinations|ProductVariant|variant)' -i"
