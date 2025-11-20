$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== DIAGNOSTYKA: Category Save/Load Flow ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "KROK 1: Sprawdzam co jest zapisane w bazie (product 11034, shop 1):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='\$psd = App\Models\ProductShopData::where(\"product_id\", 11034)->where(\"shop_id\", 1)->first(); if (\$psd) { echo \"RAW JSON:\"; echo PHP_EOL; echo \$psd->getRawOriginal(\"category_mappings\"); echo PHP_EOL; echo PHP_EOL; echo \"PARSED (via Cast):\"; echo PHP_EOL; \$cm = \$psd->category_mappings; echo \"UI Selected: \" . json_encode(\$cm[\"ui\"][\"selected\"] ?? []); echo PHP_EOL; echo \"UI Primary: \" . (\$cm[\"ui\"][\"primary\"] ?? \"NULL\"); } else { echo \"ProductShopData NOT FOUND\"; }'"

Write-Host "`n`nKROK 2: Sprawdzam logi loadShopCategories() (ostatnie 50 linii):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -100 storage/logs/laravel.log" | Select-String -Pattern "loadShopCategories|Loaded from product_shop_data|Shop categories loaded" -Context 3 | Select-Object -Last 30

Write-Host "`n`nKROK 3: Sprawdzam czy ProductCategoryManager używa nowego kodu:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'FIX 2025-11-20' app/Http/Livewire/Products/Management/Services/ProductCategoryManager.php | head -3"

Write-Host "`n`n=== DIAGNOSTYKA ZAKOŃCZONA ===" -ForegroundColor Green
Write-Host ""
Write-Host "PYTANIA DO USERA:" -ForegroundColor Yellow
Write-Host "1. Czy wykonałeś HARD REFRESH (Ctrl+F5) po ostatnim deployment?" -ForegroundColor White
Write-Host "2. Ile kategorii widzisz w UI po zapisaniu i ponownym otwarciu produktu 11034?" -ForegroundColor White
Write-Host "3. Czy problem występuje w NOWEJ KARCIE INCOGNITO (Ctrl+Shift+N)?" -ForegroundColor White
