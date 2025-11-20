# HTTP 200 Verification - FAZA 5.2 UI Fix
# Verify product-form CSS is accessible on production
# Date: 2025-11-14

$cssFile = "product-form-jLn5JWcM.css"
$url = "https://ppm.mpptrade.pl/public/build/assets/$cssFile"

Write-Host "`n=== HTTP 200 VERIFICATION ===" -ForegroundColor Cyan
Write-Host "File: $cssFile" -ForegroundColor White
Write-Host "URL: $url`n" -ForegroundColor White

try {
    $response = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction Stop

    if ($response.StatusCode -eq 200) {
        Write-Host "SUCCESS: HTTP $($response.StatusCode)" -ForegroundColor Green
        Write-Host "File Size: $($response.Content.Length) bytes" -ForegroundColor Green

        # Check if CSS contains our new rules
        $content = $response.Content
        if ($content -match 'border-green-600' -and $content -match 'border-yellow-600') {
            Write-Host "VERIFIED: New CSS rules present (border-green-600, border-yellow-600)" -ForegroundColor Green
        } else {
            Write-Host "WARNING: New CSS rules NOT FOUND in file" -ForegroundColor Yellow
        }
    } else {
        Write-Host "ERROR: HTTP $($response.StatusCode) (expected 200)" -ForegroundColor Red
    }
} catch {
    Write-Host "ERROR: HTTP 404 NOT FOUND!" -ForegroundColor Red
    Write-Host "Details: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "`nACTION REQUIRED:" -ForegroundColor Yellow
    Write-Host "1. Re-run deployment script" -ForegroundColor White
    Write-Host "2. Check if file exists on server" -ForegroundColor White
    Write-Host "3. Verify manifest.json points to correct hash" -ForegroundColor White
}
