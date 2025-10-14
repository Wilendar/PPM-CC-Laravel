# Plink CSS Deployment Script for PPM-CC-Laravel
$ErrorActionPreference = "Stop"

Write-Host "🚀 CSS Deployment to Hostido via plink" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# SSH Configuration from dane_hostingu.md
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

# Local paths
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$PublicCss = "$LocalBase\public\css"

Write-Host "`n📁 Creating public CSS structure locally..." -ForegroundColor Yellow

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

Write-Host "✅ CSS files copied to public" -ForegroundColor Green

# Test SSH connection
Write-Host "`n🔗 Testing SSH connection..." -ForegroundColor Yellow
$testResult = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "echo 'Connection OK'"
if ($testResult -like "*Connection OK*") {
    Write-Host "✅ SSH connection successful" -ForegroundColor Green
} else {
    Write-Host "❌ SSH connection failed" -ForegroundColor Red
    exit 1
}

# Create remote directories
Write-Host "`n📂 Creating remote directories..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "mkdir -p $RemotePath/public/css/admin $RemotePath/public/css/products"

# Upload CSS files using pscp
Write-Host "`n📤 Uploading CSS files..." -ForegroundColor Yellow

$files = @(
    @{local="$PublicCss\app.css"; remote="public/css/app.css"},
    @{local="$PublicCss\admin\layout.css"; remote="public/css/admin/layout.css"},
    @{local="$PublicCss\admin\components.css"; remote="public/css/admin/components.css"},
    @{local="$PublicCss\products\category-form.css"; remote="public/css/products/category-form.css"}
)

foreach ($file in $files) {
    $fileName = Split-Path $file.local -Leaf
    Write-Host "  Uploading: $fileName" -ForegroundColor Cyan
    pscp -i $HostidoKey -P $HostidoPort $file.local "${HostidoHost}:$RemotePath/$($file.remote)"
}

# Upload updated Blade templates
Write-Host "`n📤 Uploading updated Blade templates..." -ForegroundColor Yellow

# Admin layout (with external CSS links)
Write-Host "  Uploading: admin.blade.php" -ForegroundColor Cyan
pscp -i $HostidoKey -P $HostidoPort "$LocalBase\resources\views\layouts\admin.blade.php" "${HostidoHost}:$RemotePath/resources/views/layouts/admin.blade.php"

# Category form (without inline styles)
Write-Host "  Uploading: category-form.blade.php" -ForegroundColor Cyan
pscp -i $HostidoKey -P $HostidoPort "$LocalBase\resources\views\livewire\products\categories\category-form.blade.php" "${HostidoHost}:$RemotePath/resources/views/livewire/products/categories/category-form.blade.php"

# Clear Laravel caches
Write-Host "`n🧹 Clearing Laravel caches..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "`n✅ DEPLOYMENT COMPLETED!" -ForegroundColor Green
Write-Host "🌐 Check: https://ppm.mpptrade.pl/admin/products/categories/create" -ForegroundColor Cyan
Write-Host "⚠️  Clear browser cache with Ctrl+F5" -ForegroundColor Yellow
Write-Host "`n📊 CSS Structure:" -ForegroundColor Cyan
Write-Host "   /public/css/app.css - Main styles" -ForegroundColor White
Write-Host "   /public/css/admin/layout.css - Admin layout" -ForegroundColor White
Write-Host "   /public/css/admin/components.css - Components" -ForegroundColor White
Write-Host "   /public/css/products/category-form.css - Category form" -ForegroundColor White