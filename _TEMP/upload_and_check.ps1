# Upload and Check Prices/Stock Data
# 2025-11-07

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== UPLOADING AND CHECKING ===" -ForegroundColor Cyan

Write-Host "[1] Uploading script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP\check_prices_stock_data.php" "${RemoteHost}:${RemoteBase}/_TEMP/check_prices_stock_data.php"

Write-Host "`n[2] Running check..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php _TEMP/check_prices_stock_data.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
