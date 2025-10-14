# Check the second manifest.json file
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING SECOND MANIFEST ===" -ForegroundColor Cyan

# 1. Check manifest in public/build (NOT in .vite subfolder)
Write-Host "`n[1] Content of public/build/manifest.json:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cat domains/ppm.mpptrade.pl/public_html/public/build/manifest.json"

# 2. Compare timestamps
Write-Host "`n[2] Comparing timestamps:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "ls -lh domains/ppm.mpptrade.pl/public_html/public/build/manifest.json domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json"

Write-Host "`n=== CHECK COMPLETE ===" -ForegroundColor Green