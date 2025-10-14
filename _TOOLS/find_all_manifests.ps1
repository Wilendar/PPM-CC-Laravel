# Find ALL manifest.json files on server
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== FINDING ALL MANIFESTS ===" -ForegroundColor Cyan

# 1. Find all manifest.json files
Write-Host "`n[1] All manifest.json files:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "find domains/ppm.mpptrade.pl -name 'manifest.json' 2>/dev/null"

# 2. Check content of each manifest for category-form
Write-Host "`n[2] Checking main manifest in public/build:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cat domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json | grep -A 2 category-form"

# 3. Check if there's a manifest in public_html root
Write-Host "`n[3] Checking for manifest in public_html root:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "ls -la domains/ppm.mpptrade.pl/public_html/manifest.json 2>&1"

# 4. Check for manifest in public folder (without build)
Write-Host "`n[4] Checking for manifest in public folder:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "ls -la domains/ppm.mpptrade.pl/public_html/public/manifest.json 2>&1"

# 5. Check what Vite actually loads
Write-Host "`n[5] Checking @vite helper in admin layout (should use manifest):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n '@vite' domains/ppm.mpptrade.pl/public_html/resources/views/layouts/admin.blade.php"

Write-Host "`n=== MANIFEST SEARCH COMPLETE ===" -ForegroundColor Green