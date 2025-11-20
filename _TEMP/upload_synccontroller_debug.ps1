$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalFile = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Admin\Shops\SyncController.php"
$RemotePath = "domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Admin/Shops/SyncController.php"

Write-Host "Uploading SyncController with debug logging..." -ForegroundColor Cyan

pscp -i $HostidoKey -P 64321 $LocalFile "host379076@host379076.hostido.net.pl:$RemotePath"

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Upload successful!" -ForegroundColor Green

    # Clear cache
    Write-Host "Clearing cache..." -ForegroundColor Cyan
    plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

    Write-Host "✅ Cache cleared!" -ForegroundColor Green
    Write-Host ""
    Write-Host "NEXT STEPS:" -ForegroundColor Yellow
    Write-Host "1. User zmienia frequency: hourly → daily" -ForegroundColor White
    Write-Host "2. User klika 'Zapisz konfigurację'" -ForegroundColor White
    Write-Host "3. Check logs:" -ForegroundColor White
    Write-Host "   plink ... -batch 'tail -100 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E \"(saveSyncConfiguration|mount|loadSyncConfiguration)\"'" -ForegroundColor Gray
} else {
    Write-Host "❌ Upload failed!" -ForegroundColor Red
}
