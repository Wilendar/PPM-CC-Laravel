# BUG #9 FIX #7 - Verify HTTP 200 for CSS files

$cssFiles = @(
    'app-C-dituoA.css',
    'components-C8kR8M3z.css',
    'layout-CBQLZIVc.css',
    'category-form-CBqfE0rW.css',
    'category-picker-DcGTkoqZ.css',
    'product-form-CU5RrTDX.css'
)

Write-Host ""
Write-Host "=== HTTP 200 VERIFICATION ===" -ForegroundColor Cyan
Write-Host ""

$allOk = $true

foreach ($file in $cssFiles) {
    $url = "https://ppm.mpptrade.pl/public/build/assets/$file"
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -Method Head -ErrorAction Stop
        if ($response.StatusCode -eq 200) {
            Write-Host "[OK] $file : HTTP $($response.StatusCode)" -ForegroundColor Green
        } else {
            Write-Host "[WARN] $file : HTTP $($response.StatusCode)" -ForegroundColor Yellow
            $allOk = $false
        }
    } catch {
        Write-Host "[ERROR] $file : HTTP 404 NOT FOUND!" -ForegroundColor Red
        $allOk = $false
    }
}

Write-Host ""
if ($allOk) {
    Write-Host "=== ALL CSS FILES OK ===" -ForegroundColor Green
} else {
    Write-Host "=== SOME CSS FILES MISSING ===" -ForegroundColor Red
}
Write-Host ""
