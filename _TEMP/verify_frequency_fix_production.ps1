# Verify frequency fix on production
# Session-based guard dla zapobieżenia nadpisywaniu frequency

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$Port = 64321
$Path = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n" -NoNewline
Write-Host "==============================================`n" -ForegroundColor Cyan
Write-Host "WERYFIKACJA: Session-Based Guard dla Frequency`n" -ForegroundColor Cyan
Write-Host "==============================================`n" -ForegroundColor Cyan

# Upload test script
Write-Host "[1/5] Uploading test script...`n" -ForegroundColor Yellow
pscp -i $HostidoKey -P $Port `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\test_frequency_fix_session.php" `
    "${HostidoHost}:${Path}/_TEMP/test_frequency_fix_session.php"

Write-Host "`n[2/5] Running test on production...`n" -ForegroundColor Yellow
plink -ssh $HostidoHost -P $Port -i $HostidoKey -batch `
    "cd ${Path} && php _TEMP/test_frequency_fix_session.php"

Write-Host "`n[3/5] Manual verification: Check logs...`n" -ForegroundColor Yellow
plink -ssh $HostidoHost -P $Port -i $HostidoKey -batch `
    "cd ${Path} && tail -n 50 storage/logs/laravel.log | grep -A2 'sync_config_just_saved'"

Write-Host "`n[4/5] Current frequency value in DB:...`n" -ForegroundColor Yellow
$checkFrequency = @"
<?php
require 'vendor/autoload.php';
`$app = require_once 'bootstrap/app.php';
`$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
`$setting = \App\Models\SystemSetting::where('key', 'sync.schedule.frequency')->first();
echo `$setting ? `$setting->value : 'NOT FOUND';
"@

$checkFrequency | Out-File -FilePath "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\check_freq_value.php" -Encoding UTF8

pscp -i $HostidoKey -P $Port `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\check_freq_value.php" `
    "${HostidoHost}:${Path}/_TEMP/check_freq_value.php"

$currentValue = plink -ssh $HostidoHost -P $Port -i $HostidoKey -batch `
    "cd ${Path} && php _TEMP/check_freq_value.php"

Write-Host "Current frequency: " -NoNewline -ForegroundColor White
Write-Host "$currentValue`n" -ForegroundColor Green

Write-Host "[5/5] TESTING INSTRUCTIONS:`n" -ForegroundColor Yellow
Write-Host "1. Otwórz: https://ppm.mpptrade.pl/admin/shops/sync`n" -ForegroundColor White
Write-Host "2. Rozwiń sekcje 'Konfiguracja synchronizacji'`n" -ForegroundColor White
Write-Host "3. Zmień frequency z 'hourly' na 'daily'`n" -ForegroundColor White
Write-Host "4. Kliknij 'Zapisz konfigurację'`n" -ForegroundColor White
Write-Host "5. Sprawdź czy po odświeżeniu strony frequency = 'daily'`n" -ForegroundColor White
Write-Host "6. Sprawdź logi (Log::debug 'Skipping config reload')`n`n" -ForegroundColor White

Write-Host "========================================`n" -ForegroundColor Green
Write-Host "Deployment zakończony - gotowe do testu!`n" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green
