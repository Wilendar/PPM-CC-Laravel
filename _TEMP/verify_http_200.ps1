# Verify HTTP 200 for all CSS files
# UTF-8 encoding

$cssFiles = @(
    'app-Bpyg1UVS.css',
    'layout-CBQLZIVc.css',
    'components-D8HZeXLP.css',
    'category-form-CBqfE0rW.css',
    'product-form-CU5RrTDX.css',
    'category-picker-DcGTkoqZ.css'
)

Write-Host "=== HTTP 200 VERIFICATION ===" -ForegroundColor Cyan
Write-Host ""

$allSuccess = $true

foreach ($file in $cssFiles) {
    $url = "https://ppm.mpptrade.pl/public/build/assets/$file"

    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction Stop

        if ($response.StatusCode -eq 200) {
            Write-Host "[OK] $file : HTTP $($response.StatusCode)" -ForegroundColor Green
        } else {
            Write-Host "[WARNING] $file : HTTP $($response.StatusCode)" -ForegroundColor Yellow
            $allSuccess = $false
        }
    } catch {
        Write-Host "[ERROR] $file : HTTP 404 NOT FOUND!" -ForegroundColor Red
        $allSuccess = $false
    }
}

Write-Host ""
if ($allSuccess) {
    Write-Host "=== ALL FILES RETURN HTTP 200 ===" -ForegroundColor Green
} else {
    Write-Host "=== INCOMPLETE DEPLOYMENT - SOME FILES MISSING ===" -ForegroundColor Red
}
