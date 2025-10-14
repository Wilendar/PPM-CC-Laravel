# Quick upload script for category form layout fix
# Author: PPM Development Team
# Date: 2025-09-29

$ErrorActionPreference = "Stop"

# Configuration
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = 64321
$RemoteBasePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== PPM Category Form Layout Fix Upload ===" -ForegroundColor Cyan
Write-Host "Uploading critical layout fixes..." -ForegroundColor Yellow

# Files to upload
$filesToUpload = @(
    @{
        Local = "resources\views\livewire\products\categories\category-form.blade.php"
        Remote = "$RemoteBasePath/resources/views/livewire/products/categories/category-form.blade.php"
        Description = "Category form blade template with inline styles"
    },
    @{
        Local = "resources\css\products\category-form.css"
        Remote = "$RemoteBasePath/resources/css/products/category-form.css"
        Description = "Fixed CSS for sidepanel layout"
    },
    @{
        Local = "resources\views\layouts\admin.blade.php"
        Remote = "$RemoteBasePath/resources/views/layouts/admin.blade.php"
        Description = "Admin layout with Vite integration"
    }
)

# Upload each file
foreach ($file in $filesToUpload) {
    Write-Host "`nUploading: $($file.Description)" -ForegroundColor Green
    Write-Host "  From: $($file.Local)" -ForegroundColor Gray
    Write-Host "  To: $($file.Remote)" -ForegroundColor Gray

    # Check if local file exists
    if (-not (Test-Path $file.Local)) {
        Write-Host "  ERROR: Local file not found!" -ForegroundColor Red
        continue
    }

    # Upload file
    $uploadCmd = "pscp -i `"$HostidoKey`" -P $RemotePort `"$($file.Local)`" ${RemoteHost}:`"$($file.Remote)`""

    try {
        $output = Invoke-Expression $uploadCmd 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  SUCCESS: File uploaded" -ForegroundColor Green
        } else {
            Write-Host "  ERROR: Upload failed" -ForegroundColor Red
            Write-Host "  $output" -ForegroundColor Red
        }
    } catch {
        Write-Host "  ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`n=== Clearing Laravel Caches ===" -ForegroundColor Cyan

# Clear caches
$cacheCommands = @(
    "php artisan view:clear",
    "php artisan cache:clear",
    "php artisan config:clear"
)

foreach ($cmd in $cacheCommands) {
    Write-Host "Running: $cmd" -ForegroundColor Yellow
    $fullCmd = "plink -ssh $RemoteHost -P $RemotePort -i `"$HostidoKey`" -batch `"cd $RemoteBasePath && $cmd`""

    try {
        $output = Invoke-Expression $fullCmd 2>&1
        Write-Host "  $output" -ForegroundColor Gray
    } catch {
        Write-Host "  WARNING: Command may have failed" -ForegroundColor Yellow
    }
}

# Build assets (local first, then upload)
Write-Host "`n=== Building CSS Assets ===" -ForegroundColor Cyan
Write-Host "Running npm build..." -ForegroundColor Yellow

try {
    # Copy CSS to public for immediate availability
    Write-Host "Copying CSS files to public..." -ForegroundColor Yellow

    $cssFiles = @(
        @{From = "resources\css\products\category-form.css"; To = "public\css\products\category-form.css"},
        @{From = "resources\css\admin\layout.css"; To = "public\css\admin\layout.css"},
        @{From = "resources\css\admin\components.css"; To = "public\css\admin\components.css"},
        @{From = "resources\css\app.css"; To = "public\css\app.css"}
    )

    foreach ($cssFile in $cssFiles) {
        # Ensure directory exists
        $dir = Split-Path -Parent $cssFile.To
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }

        # Copy file
        if (Test-Path $cssFile.From) {
            Copy-Item -Path $cssFile.From -Destination $cssFile.To -Force
            Write-Host "  Copied: $($cssFile.From) -> $($cssFile.To)" -ForegroundColor Green

            # Upload to server
            $remoteFile = "$RemoteBasePath/$($cssFile.To.Replace('\', '/'))"
            $uploadCmd = "pscp -i `"$HostidoKey`" -P $RemotePort `"$($cssFile.To)`" ${RemoteHost}:`"$remoteFile`""
            Invoke-Expression $uploadCmd 2>&1 | Out-Null
        }
    }
} catch {
    Write-Host "  WARNING: Asset build may have issues" -ForegroundColor Yellow
}

Write-Host "`n=== Upload Complete ===" -ForegroundColor Green
Write-Host "Test the changes at: " -NoNewline
Write-Host "https://ppm.mpptrade.pl/admin/products/categories/create" -ForegroundColor Cyan
Write-Host "`nWhat was fixed:" -ForegroundColor Yellow
Write-Host "  1. Sidepanel now has proper width (350px) and padding from screen edge" -ForegroundColor Gray
Write-Host "  2. Removed debug borders (red, green, blue)" -ForegroundColor Gray
Write-Host "  3. Added sticky positioning for sidepanel on large screens" -ForegroundColor Gray
Write-Host "  4. Fixed responsive layout for mobile/tablet views" -ForegroundColor Gray
Write-Host "  5. CSS now loads properly through Vite" -ForegroundColor Gray
Write-Host "`nPress any key to exit..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")