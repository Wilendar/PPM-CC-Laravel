# Verify flash-messages fix on production
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Checking max-w-md on production..." -ForegroundColor Cyan
$result = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -c 'max-w-md' domains/ppm.mpptrade.pl/public_html/resources/views/components/flash-messages.blade.php"
Write-Host "Found $result occurrences of max-w-md" -ForegroundColor Green
