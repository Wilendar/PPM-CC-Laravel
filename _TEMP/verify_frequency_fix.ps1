# Verify frequency save fix on production

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== WERYFIKACJA FIX FREQUENCY SAVE ===" -ForegroundColor Cyan

# 1. Sprawdź czy blade używa wire:model.live
Write-Host "`n1. Sprawdzanie czy Blade używa wire:model.live..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'wire:model.live.*autoSyncFrequency' resources/views/livewire/admin/shops/sync-controller.blade.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Blade używa wire:model.live (POPRAWNIE)" -ForegroundColor Green
} else {
    Write-Host "✗ Blade NIE używa wire:model.live (BŁĄD!)" -ForegroundColor Red
}

# 2. Sprawdź czy nie ma już wire:model.defer
Write-Host "`n2. Sprawdzanie czy usunięto wire:model.defer..." -ForegroundColor Yellow
$deferCount = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -c 'wire:model.defer' resources/views/livewire/admin/shops/sync-controller.blade.php || echo 0"

if ($deferCount -eq 0) {
    Write-Host "✓ Brak wire:model.defer (POPRAWNIE)" -ForegroundColor Green
} else {
    Write-Host "✗ Znaleziono $deferCount wystąpień wire:model.defer (BŁĄD!)" -ForegroundColor Red
}

# 3. Sprawdź aktualną wartość w bazie
Write-Host "`n3. Sprawdzanie wartości w bazie danych..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"echo \App\Models\SystemSetting::where('key', 'sync.schedule.frequency')->value('value') ?? 'BRAK';\""

Write-Host "`n=== INSTRUKCJA TESTU MANUALNEGO ===" -ForegroundColor Cyan
Write-Host "1. Otwórz: https://ppm.mpptrade.pl/admin/shops/sync" -ForegroundColor White
Write-Host "2. Kliknij: 'Pokaż konfigurację'" -ForegroundColor White
Write-Host "3. Zmień częstotliwość z 'Co godzinę' na 'Codziennie'" -ForegroundColor White
Write-Host "4. Kliknij: 'Zapisz konfigurację'" -ForegroundColor White
Write-Host "5. Odśwież stronę (F5)" -ForegroundColor White
Write-Host "6. EXPECTED: 'Codziennie' wciąż zaznaczone" -ForegroundColor Green
Write-Host "7. POPRZEDNI BUG: Wracało do 'Co godzinę'" -ForegroundColor Red
Write-Host ""
