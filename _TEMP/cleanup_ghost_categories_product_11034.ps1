$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CLEANUP GHOST CATEGORIES - Product 11034 ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "STEP 1: Show CURRENT state (product_shop_data)" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$psd = App\Models\ProductShopData::where(""product_id"", 11034)->where(""shop_id"", 1)->first(); if (`$psd) { `$cm = `$psd->category_mappings; echo ""BEFORE CLEANUP:""; echo PHP_EOL; echo ""Selected: "" . json_encode(`$cm[""ui""][""selected""] ?? []); echo PHP_EOL; echo ""Primary: "" . (`$cm[""ui""][""primary""] ?? ""NULL""); echo PHP_EOL; } else { echo ""ProductShopData NOT FOUND""; }'"

Write-Host "`n`nSTEP 2: Verify which categories EXIST in categories table" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$ids = [36, 1, 2]; foreach (`$ids as `$id) { `$cat = App\Models\Category::find(`$id); if (`$cat) { echo ""Category `$id: EXISTS - "" . `$cat->name; } else { echo ""Category `$id: DOES NOT EXIST (ghost)""; } echo PHP_EOL; }'"

Write-Host "`n`nSTEP 3: CLEANUP - Remove ghost categories (ID 36)" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$psd = App\Models\ProductShopData::where(""product_id"", 11034)->where(""shop_id"", 1)->first(); if (`$psd) { `$cm = `$psd->category_mappings; `$selected = `$cm[""ui""][""selected""] ?? []; `$primary = `$cm[""ui""][""primary""] ?? null; echo ""BEFORE: Selected = "" . json_encode(`$selected) . "", Primary = `$primary""; echo PHP_EOL; // Filter out categories that do not exist `$validSelected = []; foreach (`$selected as `$catId) { if (App\Models\Category::find(`$catId)) { `$validSelected[] = `$catId; } else { echo ""REMOVING ghost category: `$catId""; echo PHP_EOL; } } // Check if primary is ghost if (`$primary && !App\Models\Category::find(`$primary)) { echo ""REMOVING ghost primary: `$primary""; echo PHP_EOL; `$primary = count(`$validSelected) > 0 ? `$validSelected[0] : null; } // Update category_mappings `$cm[""ui""][""selected""] = `$validSelected; `$cm[""ui""][""primary""] = `$primary; `$psd->category_mappings = `$cm; `$psd->save(); echo PHP_EOL; echo ""AFTER: Selected = "" . json_encode(`$validSelected) . "", Primary = `$primary""; echo PHP_EOL; echo ""✅ CLEANUP COMPLETE""; } else { echo ""ProductShopData NOT FOUND""; }'"

Write-Host "`n`nSTEP 4: VERIFY - Check updated state" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$psd = App\Models\ProductShopData::where(""product_id"", 11034)->where(""shop_id"", 1)->first(); if (`$psd) { `$cm = `$psd->category_mappings; echo ""FINAL STATE:""; echo PHP_EOL; echo ""Selected: "" . json_encode(`$cm[""ui""][""selected""] ?? []); echo PHP_EOL; echo ""Primary: "" . (`$cm[""ui""][""primary""] ?? ""NULL""); echo PHP_EOL; echo ""Updated at: "" . `$psd->updated_at; } else { echo ""ProductShopData NOT FOUND""; }'"

Write-Host "`n`n=== CLEANUP COMPLETE ===" -ForegroundColor Green
Write-Host ""
Write-Host "Teraz przetestuj w przeglądarce:" -ForegroundColor Yellow
Write-Host "1. Hard refresh (Ctrl+F5)" -ForegroundColor White
Write-Host "2. Otwórz produkt 11034" -ForegroundColor White
Write-Host "3. Kliknij Shop Tab 'B2B Test DEV'" -ForegroundColor White
Write-Host "4. Powinny być zaznaczone TYLKO kategorie 1 i 2 (bez ghost 36)" -ForegroundColor White
