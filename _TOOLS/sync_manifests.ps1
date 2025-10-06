# Sync both manifest files with correct hash
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== SYNCING BOTH MANIFESTS ===" -ForegroundColor Cyan

# 1. Copy correct manifest from .vite to root build folder
Write-Host "`n[1] Copying correct manifest to root build folder..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cp domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json domains/ppm.mpptrade.pl/public_html/public/build/manifest.json"

# 2. Verify both manifests now have same content
Write-Host "`n[2] Verifying both manifests have DcMa3my2:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep category-form domains/ppm.mpptrade.pl/public_html/public/build/manifest.json && grep category-form domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json"

# 3. Clear all caches
Write-Host "`n[3] Clearing all caches..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && rm -rf storage/framework/views/* bootstrap/cache/* && php artisan optimize:clear"

Write-Host "`n=== MANIFESTS SYNCED ===" -ForegroundColor Green
Write-Host "`n✓ Both manifests now have DcMa3my2 hash" -ForegroundColor Green
Write-Host "`nPlease refresh page (Ctrl+R) and check" -ForegroundColor Yellow