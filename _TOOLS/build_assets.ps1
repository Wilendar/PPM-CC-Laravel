# PPM-CC-Laravel Asset Build Script
# Builds and deploys Vite assets to production

param(
    [switch]$Dev,      # Run in development mode
    [switch]$Deploy,   # Deploy to server after build
    [switch]$Watch     # Watch mode for development
)

$ErrorActionPreference = "Stop"

# Configuration
$ProjectRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n🚀 PPM-CC-Laravel Asset Build Script" -ForegroundColor Cyan
Write-Host "=====================================`n" -ForegroundColor Cyan

# Change to project directory
Set-Location $ProjectRoot

# Check if package.json exists
if (!(Test-Path "package.json")) {
    Write-Host "❌ package.json not found!" -ForegroundColor Red
    exit 1
}

# Check if vite.config.js exists
if (!(Test-Path "vite.config.js")) {
    Write-Host "❌ vite.config.js not found!" -ForegroundColor Red
    exit 1
}

# Install dependencies if node_modules doesn't exist
if (!(Test-Path "node_modules")) {
    Write-Host "📦 Installing NPM dependencies..." -ForegroundColor Yellow
    npm install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ NPM install failed!" -ForegroundColor Red
        exit 1
    }
}

# Build or run development server
if ($Watch) {
    Write-Host "👀 Starting Vite development server with watch mode..." -ForegroundColor Green
    npm run dev
}
elseif ($Dev) {
    Write-Host "🛠️ Starting Vite development server..." -ForegroundColor Green
    Start-Process -FilePath "cmd" -ArgumentList "/c", "npm run dev" -WindowStyle Normal
    Write-Host "✅ Development server started" -ForegroundColor Green
}
else {
    Write-Host "🔨 Building production assets..." -ForegroundColor Yellow
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Build failed!" -ForegroundColor Red
        exit 1
    }

    Write-Host "✅ Assets built successfully" -ForegroundColor Green

    # Check if build directory exists
    if (Test-Path "public/build") {
        Write-Host "📂 Build output:" -ForegroundColor Cyan
        Get-ChildItem "public/build" -Recurse | ForEach-Object {
            Write-Host "  $($_.FullName)" -ForegroundColor Gray
        }
    }
}

# Deploy to server if requested
if ($Deploy -and !$Dev -and !$Watch) {
    Write-Host "`n🚀 Deploying to Hostido server..." -ForegroundColor Yellow

    # Check if SSH key exists
    if (!(Test-Path $HostidoKey)) {
        Write-Host "❌ SSH key not found: $HostidoKey" -ForegroundColor Red
        exit 1
    }

    # Upload build directory
    if (Test-Path "public/build") {
        Write-Host "📤 Uploading build directory..." -ForegroundColor Cyan
        pscp -r -i $HostidoKey -P $RemotePort "public/build" "${RemoteHost}:${RemotePath}/public/"

        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Build files uploaded" -ForegroundColor Green
        } else {
            Write-Host "❌ Failed to upload build files" -ForegroundColor Red
            exit 1
        }
    }

    # Clear Laravel cache
    Write-Host "🧹 Clearing Laravel cache..." -ForegroundColor Cyan
    plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Laravel cache cleared" -ForegroundColor Green
        Write-Host "`n🎉 Deployment completed successfully!" -ForegroundColor Green
        Write-Host "🌐 Visit: https://ppm.mpptrade.pl" -ForegroundColor Cyan
    } else {
        Write-Host "⚠️ Cache clearing failed, but assets were uploaded" -ForegroundColor Yellow
    }
}

Write-Host "`n✨ Asset build process completed!" -ForegroundColor Green

# Usage examples
if (!$Dev -and !$Deploy -and !$Watch) {
    Write-Host "`n📖 Usage Examples:" -ForegroundColor Cyan
    Write-Host "  Development:  .\_TOOLS\build_assets.ps1 -Dev" -ForegroundColor Gray
    Write-Host "  Watch mode:   .\_TOOLS\build_assets.ps1 -Watch" -ForegroundColor Gray
    Write-Host "  Production:   .\_TOOLS\build_assets.ps1" -ForegroundColor Gray
    Write-Host "  Build+Deploy: .\_TOOLS\build_assets.ps1 -Deploy" -ForegroundColor Gray
}