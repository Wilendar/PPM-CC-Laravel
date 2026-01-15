# Check latest category API logs
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING LATEST CATEGORY API LOGS ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -25 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(categories|Category|api/|apicategories)'"
