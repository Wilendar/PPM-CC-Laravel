# Quick CSS Fix Deployment
$ErrorActionPreference = "Stop"

Write-Host "🔧 Fixing Category Form Layout CSS" -ForegroundColor Cyan

# Copy to public
Copy-Item "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\css\products\category-form.css" `
          "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\css\products\category-form.css" -Force

Write-Host "✓ CSS copied to public" -ForegroundColor Green

# Upload to server
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "📤 Uploading to server..." -ForegroundColor Yellow

pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\css\products\category-form.css" `
    "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/css/products/category-form.css"

# Clear cache
Write-Host "🧹 Clearing cache..." -ForegroundColor Yellow

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

Write-Host "`n✅ DEPLOYED!" -ForegroundColor Green
Write-Host "🌐 Check: https://ppm.mpptrade.pl/admin/products/categories/create" -ForegroundColor Cyan
Write-Host "⚠️  Press Ctrl+F5 to clear browser cache!" -ForegroundColor Yellow