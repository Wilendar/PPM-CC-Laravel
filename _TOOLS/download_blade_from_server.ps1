# Download blade file from server to compare
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteFile = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"
$LocalFile = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\category-form-server.blade.php"

Write-Host "=== DOWNLOADING FROM SERVER ===" -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 $RemoteFile $LocalFile

if(Test-Path $LocalFile) {
    Write-Host "`n=== DOWNLOAD COMPLETE ===" -ForegroundColor Green
    Write-Host "File size: $((Get-Item $LocalFile).Length) bytes"
} else {
    Write-Host "`n=== DOWNLOAD FAILED ===" -ForegroundColor Red
}