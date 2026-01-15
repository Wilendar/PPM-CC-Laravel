# Upload components.css with resizable columns styles
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== UPLOADING components.css ===" -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 "resources/css/admin/components.css" "$RemoteBase/resources/css/admin/components.css"
Write-Host "=== COMPLETE ===" -ForegroundColor Green
