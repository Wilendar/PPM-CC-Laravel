# Quick CSS Deployment Script for PPM-CC-Laravel
# Deploys CSS files directly without Vite build

$ErrorActionPreference = "Stop"

Write-Host "🚀 Quick CSS Deployment to Hostido" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan

# SSH Configuration
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

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

# Upload files to server using pscp
Write-Host "`n📤 Uploading CSS files to server..." -ForegroundColor Yellow

# Create remote directories first
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "mkdir -p $RemotePath/public/css/admin $RemotePath/public/css/products"

# Upload CSS files
$files = @(
    @{local="$PublicCss\app.css"; remote="$RemotePath/public/css/app.css"},
    @{local="$PublicCss\admin\layout.css"; remote="$RemotePath/public/css/admin/layout.css"},
    @{local="$PublicCss\admin\components.css"; remote="$RemotePath/public/css/admin/components.css"},
    @{local="$PublicCss\products\category-form.css"; remote="$RemotePath/public/css/products/category-form.css"}
)

foreach ($file in $files) {
    Write-Host "  Uploading: $($file.local.Split('\')[-1])" -ForegroundColor Cyan
    pscp -i $HostidoKey -P $HostidoPort $file.local "${HostidoHost}:$($file.remote)" 2>$null
}

# Also upload the updated layout file
Write-Host "`n📤 Uploading updated admin layout..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $HostidoPort "$LocalBase\resources\views\layouts\admin.blade.php" "${HostidoHost}:$RemotePath/resources/views/layouts/admin.blade.php"

# Clear Laravel caches
Write-Host "`n🧹 Clearing Laravel caches..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

Write-Host "`n✅ DEPLOYMENT COMPLETED!" -ForegroundColor Green
Write-Host "🌐 Check: https://ppm.mpptrade.pl/admin/products/categories/create" -ForegroundColor Cyan