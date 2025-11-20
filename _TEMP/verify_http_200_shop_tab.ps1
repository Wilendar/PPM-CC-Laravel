# Verify HTTP 200 for deployed CSS files
# UTF-8 without BOM

$cssFiles = @(
    'app-DHiDelwn.css',
    'components-C8kR8M3z.css',
    'product-form-wjHnBdF6.css',
    'category-form-CBqfE0rW.css',
    'category-picker-DcGTkoqZ.css',
    'layout-CBQLZIVc.css'
)

Write-Host "=== HTTP 200 Verification ===" -ForegroundColor Cyan
$allOk = $true

foreach ($file in $cssFiles) {
    $url = "https://ppm.mpptrade.pl/public/build/assets/$file"
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction Stop
        Write-Host "✅ $file : HTTP $($response.StatusCode)" -ForegroundColor Green
    } catch {
        Write-Host "🚨 $file : HTTP 404 NOT FOUND!" -ForegroundColor Red
        $allOk = $false
    }
}

if ($allOk) {
    Write-Host "`n✅ ALL CSS files verified - HTTP 200" -ForegroundColor Green
} else {
    Write-Host "`n🚨 DEPLOYMENT INCOMPLETE - Some files missing!" -ForegroundColor Red
}
