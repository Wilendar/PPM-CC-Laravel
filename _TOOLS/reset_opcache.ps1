$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "Uploading opcache reset script..." -ForegroundColor Cyan
& pscp -i $HostidoKey -P 64321 "$LocalBase\_TOOLS\opcache_reset.php" "host379076@host379076.hostido.net.pl:$RemoteBase/public/opcache_reset.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Script uploaded. Now access: https://ppm.mpptrade.pl/opcache_reset.php" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Upload failed" -ForegroundColor Red
}
