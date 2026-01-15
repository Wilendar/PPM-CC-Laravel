# Deploy VBB fixes to Hostido
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

$files = @(
    @{
        Local = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\VisualDescription\BlockBuilder\BlockBuilderCanvas.php"
        Remote = "app/Http/Livewire/Products/VisualDescription/BlockBuilder/BlockBuilderCanvas.php"
    },
    @{
        Local = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Services\VisualEditor\BlockDocumentToHtmlExporter.php"
        Remote = "app/Services/VisualEditor/BlockDocumentToHtmlExporter.php"
    }
)

foreach ($file in $files) {
    Write-Host "Uploading $($file.Remote)..." -ForegroundColor Cyan
    & pscp -i $HostidoKey -P 64321 $file.Local "host379076@host379076.hostido.net.pl:$RemoteBase/$($file.Remote)"

    if ($LASTEXITCODE -eq 0) {
        Write-Host "OK" -ForegroundColor Green
    } else {
        Write-Host "FAILED" -ForegroundColor Red
        exit 1
    }
}

# Clear cache
Write-Host "Clearing cache..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan cache:clear && php artisan view:clear && php artisan config:clear"

Write-Host "Done!" -ForegroundColor Green
