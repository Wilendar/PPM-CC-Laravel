# test_ps_image_format.ps1
# Test PrestaShop /img/p/ URL format

$imageId = 23894

# Split digits
$digits = $imageId.ToString().ToCharArray()
$path = $digits -join '/'

# Build URL
$url = "https://dev.mpptrade.pl/img/p/$path/$imageId-small_default.jpg"

Write-Host "Image ID: $imageId" -ForegroundColor Cyan
Write-Host "Digits path: $path" -ForegroundColor Cyan
Write-Host "Testing URL: $url" -ForegroundColor Yellow

try {
    $response = Invoke-WebRequest -Uri $url -Method Head -UseBasicParsing -ErrorAction Stop
    Write-Host "SUCCESS: HTTP $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "FAILED: $($_.Exception.Message)" -ForegroundColor Red

    # Try alternative format without -small_default
    $altUrl = "https://dev.mpptrade.pl/img/p/$path/$imageId.jpg"
    Write-Host "`nTrying alternative: $altUrl" -ForegroundColor Yellow
    try {
        $response = Invoke-WebRequest -Uri $altUrl -Method Head -UseBasicParsing -ErrorAction Stop
        Write-Host "SUCCESS: HTTP $($response.StatusCode)" -ForegroundColor Green
    } catch {
        Write-Host "FAILED: $($_.Exception.Message)" -ForegroundColor Red
    }
}
