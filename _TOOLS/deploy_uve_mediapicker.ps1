$HostidoKey = "D:\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\Skrypty\PPM-CC-Laravel"

Write-Host "Deploying UVE MediaPicker trait fix..." -ForegroundColor Cyan

$files = @(
    "app/Http/Livewire/Products/VisualDescription/UnifiedVisualEditor.php",
    "resources/views/livewire/products/visual-description/unified-visual-editor.blade.php"
)

foreach ($file in $files) {
    $localFile = Join-Path $LocalPath $file
    $remoteDest = "${RemotePath}/${file}"
    Write-Host "Uploading: $file" -ForegroundColor Yellow
    pscp -i $HostidoKey -P 64321 $localFile "host379076@host379076.hostido.net.pl:$remoteDest"
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  OK" -ForegroundColor Green
    } else {
        Write-Host "  FAILED" -ForegroundColor Red
    }
}

# Clear cache
Write-Host "`nClearing cache..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd ${RemotePath} && php artisan view:clear && php artisan cache:clear"

Write-Host "`nDeployment complete!" -ForegroundColor Green
