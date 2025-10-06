# WinSCP CSS Deployment Script for PPM-CC-Laravel
$ErrorActionPreference = "Stop"

Write-Host "🚀 WinSCP CSS Deployment to Hostido" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

# Create public/css directory structure locally
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$PublicCss = "$LocalBase\public\css"

Write-Host "`n📁 Creating public CSS structure..." -ForegroundColor Yellow

if (!(Test-Path $PublicCss)) {
    New-Item -Path $PublicCss -ItemType Directory -Force | Out-Null
    New-Item -Path "$PublicCss\admin" -ItemType Directory -Force | Out-Null
    New-Item -Path "$PublicCss\products" -ItemType Directory -Force | Out-Null
}

# Copy CSS files to public directory
Write-Host "📋 Copying CSS files to public directory..." -ForegroundColor Yellow

Copy-Item "$LocalBase\resources\css\app.css" "$PublicCss\app.css" -Force
Copy-Item "$LocalBase\resources\css\admin\layout.css" "$PublicCss\admin\layout.css" -Force
Copy-Item "$LocalBase\resources\css\admin\components.css" "$PublicCss\admin\components.css" -Force
Copy-Item "$LocalBase\resources\css\products\category-form.css" "$PublicCss\products\category-form.css" -Force

Write-Host "✅ CSS files copied" -ForegroundColor Green

# Upload via WinSCP
Write-Host "`n📤 Uploading files via WinSCP..." -ForegroundColor Yellow

$winscpScript = @"
open sftp://host379076:64321@host379076.hostido.net.pl/ -privatekey="D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk" -hostkey="ssh-ed25519 255 s5jsBvAUexZAUyZgYF3ONT2RvrcsHjhso6DCiTBICiM"
option batch on
option confirm off

# Create remote directories
mkdir domains/ppm.mpptrade.pl/public_html/public/css
mkdir domains/ppm.mpptrade.pl/public_html/public/css/admin
mkdir domains/ppm.mpptrade.pl/public_html/public/css/products

# Upload CSS files
put "$PublicCss\app.css" "domains/ppm.mpptrade.pl/public_html/public/css/app.css"
put "$PublicCss\admin\layout.css" "domains/ppm.mpptrade.pl/public_html/public/css/admin/layout.css"
put "$PublicCss\admin\components.css" "domains/ppm.mpptrade.pl/public_html/public/css/admin/components.css"
put "$PublicCss\products\category-form.css" "domains/ppm.mpptrade.pl/public_html/public/css/products/category-form.css"

# Upload admin layout
put "$LocalBase\resources\views\layouts\admin.blade.php" "domains/ppm.mpptrade.pl/public_html/resources/views/layouts/admin.blade.php"

# Upload category form (without inline styles)
put "$LocalBase\resources\views\livewire\products\categories\category-form.blade.php" "domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-form.blade.php"

close
exit
"@

$scriptPath = "$env:TEMP\winscp_css_deploy.txt"
$winscpScript | Out-File -FilePath $scriptPath -Encoding utf8

& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=$scriptPath

Remove-Item $scriptPath -Force

Write-Host "`n✅ FILES DEPLOYED!" -ForegroundColor Green
Write-Host "`n🌐 Check: https://ppm.mpptrade.pl/admin/products/categories/create" -ForegroundColor Cyan
Write-Host "⚠️  Remember to clear browser cache (Ctrl+F5)" -ForegroundColor Yellow