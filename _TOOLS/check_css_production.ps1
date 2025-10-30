# Check if CSS changes deployed correctly
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Checking CSS on production..." -ForegroundColor Cyan

Write-Host "`n[1] Checking built CSS file..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -A 5 'modal-category-preview-root' domains/ppm.mpptrade.pl/public_html/public/build/assets/components-BF7GTy66.css"

Write-Host "`n[2] Checking manifest.json..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cat domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json | grep -A 2 'admin/components.css'"

Write-Host "`n[3] Listing all CSS files in build/assets..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -lh domains/ppm.mpptrade.pl/public_html/public/build/assets/components-*.css"
