# Test PrestaShop Category API Endpoints
# ETAP_07 FAZA 2B.1 - Dynamic Category Loading

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$ApiBaseUrl = "https://ppm.mpptrade.pl/api/v1/prestashop"

Write-Host "`n=== ETAP_07 FAZA 2B.1: PrestaShop Category API Test ===" -ForegroundColor Cyan

# Step 1: Check available PrestaShop shops
Write-Host "`n[1/3] Checking PrestaShop shops in database..." -ForegroundColor Yellow

$checkShopsScript = @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='print_r(App\Models\PrestaShopShop::select("id", "name", "url")->get()->toArray());'
"@

$shops = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $checkShopsScript

Write-Host "Shops in database:" -ForegroundColor Green
Write-Host $shops

# Step 2: Test category fetch endpoint
Write-Host "`n[2/3] Testing GET /api/v1/prestashop/categories/1" -ForegroundColor Yellow

$response1 = Invoke-RestMethod -Uri "$ApiBaseUrl/categories/1" -Method Get -ErrorAction SilentlyContinue

if ($response1.success) {
    Write-Host "SUCCESS: Categories fetched" -ForegroundColor Green
    Write-Host "Shop ID: $($response1.shop_id)" -ForegroundColor White
    Write-Host "Shop Name: $($response1.shop_name)" -ForegroundColor White
    Write-Host "Cached: $($response1.cached)" -ForegroundColor White
    Write-Host "Categories count: $($response1.categories.Count)" -ForegroundColor White
    Write-Host "Cache expires at: $($response1.cache_expires_at)" -ForegroundColor White

    # Show first 3 categories
    if ($response1.categories.Count -gt 0) {
        Write-Host "`nFirst 3 categories:" -ForegroundColor Cyan
        $response1.categories | Select-Object -First 3 | ForEach-Object {
            Write-Host "  - [$($_.id)] $($_.name) (Level: $($_.level), Children: $($_.children.Count))" -ForegroundColor White
        }
    }
} else {
    Write-Host "ERROR: $($response1.error)" -ForegroundColor Red
}

# Step 3: Test cache refresh endpoint
Write-Host "`n[3/3] Testing POST /api/v1/prestashop/categories/1/refresh" -ForegroundColor Yellow

$response2 = Invoke-RestMethod -Uri "$ApiBaseUrl/categories/1/refresh" -Method Post -ErrorAction SilentlyContinue

if ($response2.success) {
    Write-Host "SUCCESS: Cache refreshed" -ForegroundColor Green
    Write-Host "Cached: $($response2.cached) (should be false after refresh)" -ForegroundColor White
    Write-Host "Categories count: $($response2.categories.Count)" -ForegroundColor White
} else {
    Write-Host "ERROR: $($response2.error)" -ForegroundColor Red
}

Write-Host "`n=== Test Complete ===" -ForegroundColor Cyan
