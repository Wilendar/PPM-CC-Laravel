Write-Host ''
Write-Host '=== HTTP 200 VERIFICATION ===' -ForegroundColor Cyan
Write-Host ''

$manifest = Get-Content 'D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\manifest.json' | ConvertFrom-Json
$cssFile = $manifest.'resources/css/products/product-form.css'.file

Write-Host 'Compiled CSS file: ' -NoNewline -ForegroundColor Yellow
Write-Host $cssFile -ForegroundColor White
Write-Host ''

$url = "https://ppm.mpptrade.pl/public/build/assets/$cssFile"
Write-Host 'Checking: ' -NoNewline -ForegroundColor Cyan
Write-Host $url -ForegroundColor White

try {
    $response = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host ''
        Write-Host 'SUCCESS: HTTP 200 OK' -ForegroundColor Green
        Write-Host 'File size: ' -NoNewline -ForegroundColor Yellow
        Write-Host $response.RawContentLength 'bytes' -ForegroundColor White
        Write-Host ''
        Write-Host 'CSS file is accessible on production!' -ForegroundColor Green
    }
} catch {
    Write-Host ''
    Write-Host 'ERROR: HTTP 404 NOT FOUND!' -ForegroundColor Red
    Write-Host 'Deployment incomplete - missing CSS file!' -ForegroundColor Red
    exit 1
}
