$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== DIAGNOZA: CATEGORY BADGE NIE POKAZUJE SIE ===" -ForegroundColor Red
Write-Host ""

Write-Host "[CHECK 1] Weryfikacja deployment fix..." -ForegroundColor Cyan
$fix = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -A 10 'FIX 2025-11-19 BUG #1' app/Http/Livewire/Products/Management/ProductForm.php | head -15"
Write-Host $fix -ForegroundColor White
Write-Host ""

Write-Host "[CHECK 2] Sprawdzenie pending_fields dla produktu 11033, shop 5..." -ForegroundColor Cyan
$pendingData = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""
\\`$psd = DB::table('product_shop_data')
    ->where('product_id', 11033)
    ->where('shop_id', 5)
    ->first();

if (\\`$psd) {
    echo 'ProductShopData exists:' . PHP_EOL;
    echo '  sync_status: ' . \\`$psd->sync_status . PHP_EOL;
    echo '  pending_fields: ' . (\\`$psd->pending_fields ?? 'NULL') . PHP_EOL;

    if (\\`$psd->pending_fields) {
        \\`$decoded = json_decode(\\`$psd->pending_fields, true);
        echo '  Decoded pending_fields:' . PHP_EOL;
        print_r(\\`$decoded);
    }
} else {
    echo 'ProductShopData NOT FOUND for product 11033, shop 5' . PHP_EOL;
}
"""

Write-Host $pendingData -ForegroundColor White
Write-Host ""

Write-Host "[CHECK 3] Sprawdzenie czy kategorie sa w fieldsToCheck..." -ForegroundColor Cyan
$fieldsCheck = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'category_mappings.*Kategorie' app/Http/Livewire/Products/Management/ProductForm.php"
Write-Host $fieldsCheck -ForegroundColor White
Write-Host ""

Write-Host "[CHECK 4] Blade cache verification..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"
Write-Host "[OK] Caches cleared again" -ForegroundColor Green
Write-Host ""

Write-Host "[CHECK 5] Test wywolania metody getCategoryStatusIndicator..." -ForegroundColor Cyan
$methodTest = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""
\\`$product = App\\Models\\Product::find(11033);
\\`$component = new App\\Http\\Livewire\\Products\\Management\\ProductForm();
\\`$component->product = \\`$product;
\\`$component->activeShopId = 5;

echo 'Testing getCategoryStatusIndicator():' . PHP_EOL;
try {
    \\`$result = \\`$component->getCategoryStatusIndicator();
    echo 'Result: ' . json_encode(\\`$result, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\\Exception \\`$e) {
    echo 'ERROR: ' . \\`$e->getMessage() . PHP_EOL;
}
"""

Write-Host $methodTest -ForegroundColor White
Write-Host ""

Write-Host "=== KONIEC DIAGNOZY ===" -ForegroundColor Cyan
