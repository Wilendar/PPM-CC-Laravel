$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   UPLOAD & VERIFY PRODUCTION DATABASE" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "1️⃣ Uploading verification script...`n" -ForegroundColor White
pscp -i $HostidoKey -P 64321 "_TEMP/verify_prod_simple.php" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/verify_prod_simple.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "`n   ❌ Upload failed`n" -ForegroundColor Red
    exit 1
}

Write-Host "`n   ✅ Upload successful`n" -ForegroundColor Green

Write-Host "2️⃣ Running verification on production...`n" -ForegroundColor White
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/verify_prod_simple.php"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   VERIFICATION COMPLETED" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan
