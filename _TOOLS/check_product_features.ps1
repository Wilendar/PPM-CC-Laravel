$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== CHECKING PRODUCT 11140 FEATURES ===" -ForegroundColor Cyan

Write-Host "[1/3] Checking product_features table..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""echo 'Product 11140 features in DB: ' . \App\Models\ProductFeature::where('product_id', 11140)->count();"""

Write-Host "[2/3] Checking feature import logs..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -300 storage/logs/laravel.log | grep -E 'FEATURE IMPORT.*11140|Completed.*imported' | tail -10"

Write-Host "[3/3] Checking feature_types count..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""echo 'Total FeatureTypes: ' . \App\Models\FeatureType::count() . ', Active: ' . \App\Models\FeatureType::where('is_active', true)->count();"""

Write-Host "=== DONE ===" -ForegroundColor Green
