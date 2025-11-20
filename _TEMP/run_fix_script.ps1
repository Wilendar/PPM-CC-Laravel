$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`nUploading corrected fix script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "_TEMP/fix_incorrect_gross_prices.php" `
    "${RemoteHost}:${RemoteBase}/fix_incorrect_gross_prices.php"

Write-Host "`nRunning data fix..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch `
    "cd $RemoteBase && php fix_incorrect_gross_prices.php"
