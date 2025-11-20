$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CURRENT DB STATE - Product 11034, Shop 1 ===" -ForegroundColor Cyan
Write-Host ""

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='`$psd = App\Models\ProductShopData::where(""product_id"", 11034)->where(""shop_id"", 1)->first(); if (`$psd) { `$cm = `$psd->category_mappings; echo ""Selected: "" . json_encode(`$cm[""ui""][""selected""] ?? []); echo PHP_EOL; echo ""Primary: "" . (`$cm[""ui""][""primary""] ?? ""NULL""); echo PHP_EOL; echo ""Updated at: "" . `$psd->updated_at; echo PHP_EOL; echo ""Mappings keys: "" . json_encode(array_keys(`$cm[""mappings""] ?? [])); } else { echo ""ProductShopData NOT FOUND""; }'"

Write-Host "`n=== DONE ===" -ForegroundColor Green
