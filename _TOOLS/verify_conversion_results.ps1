$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== VERIFYING VARIANT CONVERSION RESULTS ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/3] Checking if new products were created from variants..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo json_encode(App\Models\Product::where(\"sku\", \"like\", \"TEST-KONWERSJA-1761918035-VAR-%\")->get([\"id\", \"sku\", \"name\", \"is_variant_master\"])->toArray(), JSON_PRETTY_PRINT);'"

Write-Host ""
Write-Host "[2/3] Checking if variants were deleted from product_variants table..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo json_encode(App\Models\ProductVariant::where(\"product_id\", 10984)->get([\"id\", \"sku\", \"name\"])->toArray(), JSON_PRETTY_PRINT);'"

Write-Host ""
Write-Host "[3/3] Checking master product status..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='echo json_encode(App\Models\Product::find(10984, [\"id\", \"sku\", \"is_variant_master\", \"default_variant_id\"]), JSON_PRETTY_PRINT);'"

Write-Host ""
Write-Host "=== VERIFICATION COMPLETE ===" -ForegroundColor Green
