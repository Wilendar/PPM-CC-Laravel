# Upload ALL CSS assets
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== UPLOADING ALL CSS ASSETS ===" -ForegroundColor Cyan

$cssFiles = @(
    "app-DWt9ygTM.css",
    "components-CJpepm2H.css",
    "layout-CBQLZIVc.css",
    "category-picker-DcGTkoqZ.css",
    "category-form-CBqfE0rW.css"
)

foreach ($file in $cssFiles) {
    Write-Host "Uploading $file..." -ForegroundColor Yellow
    pscp -i $HostidoKey -P $RemotePort `
      "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\$file" `
      "${RemoteHost}:${RemotePath}/public/build/assets/$file"
}

Write-Host "`n=== ALL CSS UPLOADED ===" -ForegroundColor Green
