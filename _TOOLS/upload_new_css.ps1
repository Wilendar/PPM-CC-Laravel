# Upload new built CSS after npm build
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== UPLOADING NEW BUILT CSS ===" -ForegroundColor Cyan

# Upload new CSS file (DcMa3my2 hash - unchanged due to content hashing)
Write-Host "`n[1/2] Uploading category-form-DcMa3my2.css..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\category-form-DcMa3my2.css" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload CSS" -ForegroundColor Red
    exit 1
}

# Clear cache
Write-Host "`n[2/2] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n=== SUCCESS ===" -ForegroundColor Green
    Write-Host "✓ New CSS uploaded with sidepanel fixes" -ForegroundColor Green
    Write-Host "`nTest URL:" -ForegroundColor Cyan
    Write-Host "  - ProductForm: https://ppm.mpptrade.pl/admin/products/create" -ForegroundColor White
} else {
    Write-Host "`nERROR: Cache clear failed" -ForegroundColor Red
    exit 1
}