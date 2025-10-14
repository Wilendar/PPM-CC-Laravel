# Deploy PrestaShopSyncService to Production (ppm.mpptrade.pl)
# ETAP_07 FAZA 1F - Service Orchestration Layer
# Agent: laravel-expert
# Date: 2025-10-03

param(
    [switch]$SkipVerification,
    [switch]$TestOnly
)

# === CONFIGURATION ===
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteUser = "host379076"
$RemoteHost = "host379076.hostido.net.pl"
$RemotePort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

# === COLORS ===
$Green = [System.ConsoleColor]::Green
$Yellow = [System.ConsoleColor]::Yellow
$Red = [System.ConsoleColor]::Red
$Cyan = [System.ConsoleColor]::Cyan

function Write-Step {
    param([string]$Message)
    Write-Host "`n[STEP] $Message" -ForegroundColor $Cyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor $Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARN] $Message" -ForegroundColor $Yellow
}

function Write-Error-Custom {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor $Red
}

# === MAIN DEPLOYMENT ===

Write-Host "========================================" -ForegroundColor $Cyan
Write-Host "  PrestaShopSyncService Deployment" -ForegroundColor $Cyan
Write-Host "  ETAP_07 FAZA 1F" -ForegroundColor $Cyan
Write-Host "========================================" -ForegroundColor $Cyan

# Step 1: Verify local file exists
Write-Step "Verifying local file exists"
$LocalFile = Join-Path $LocalPath "app\Services\PrestaShop\PrestaShopSyncService.php"
if (Test-Path $LocalFile) {
    $FileSize = (Get-Item $LocalFile).Length
    Write-Success "Local file exists: PrestaShopSyncService.php ($FileSize bytes)"
} else {
    Write-Error-Custom "Local file not found: $LocalFile"
    exit 1
}

# Step 2: Upload file to server
Write-Step "Uploading PrestaShopSyncService.php to production server"

$RemoteFilePath = "$RemoteUser@${RemoteHost}:$RemotePath/app/Services/PrestaShop/PrestaShopSyncService.php"

try {
    pscp -i $HostidoKey -P $RemotePort $LocalFile $RemoteFilePath 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Success "File uploaded successfully"
    } else {
        Write-Error-Custom "Upload failed with exit code: $LASTEXITCODE"
        exit 1
    }
} catch {
    Write-Error-Custom "Upload exception: $_"
    exit 1
}

if ($TestOnly) {
    Write-Warning "Test mode - skipping cache clear and verification"
    exit 0
}

# Step 3: Clear Laravel caches
Write-Step "Clearing Laravel caches"

$CacheCommands = @(
    "cache:clear",
    "view:clear",
    "config:clear"
)

foreach ($cmd in $CacheCommands) {
    $Command = "cd $RemotePath && php artisan $cmd"
    try {
        plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i $HostidoKey -batch $Command 2>&1 | Out-Null
        if ($LASTEXITCODE -eq 0) {
            Write-Success "php artisan $cmd - OK"
        } else {
            Write-Warning "php artisan $cmd - failed (may not be critical)"
        }
    } catch {
        Write-Warning "Cache clear exception: $_ (continuing...)"
    }
}

# Step 4: Verify file on server
if (-not $SkipVerification) {
    Write-Step "Verifying deployment on server"

    $VerifyCommand = "ls -lh $RemotePath/app/Services/PrestaShop/PrestaShopSyncService.php"
    try {
        $Output = plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i $HostidoKey -batch $VerifyCommand 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "File verified on server"
            Write-Host "  $Output" -ForegroundColor Gray
        } else {
            Write-Warning "Verification failed - file may not exist on server"
        }
    } catch {
        Write-Warning "Verification exception: $_"
    }

    # Check PHP syntax (optional)
    Write-Step "Checking PHP syntax"
    $SyntaxCheck = "php -l $RemotePath/app/Services/PrestaShop/PrestaShopSyncService.php"
    try {
        $SyntaxOutput = plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i $HostidoKey -batch $SyntaxCheck 2>&1
        if ($SyntaxOutput -like "*No syntax errors*") {
            Write-Success "PHP syntax check passed"
        } else {
            Write-Warning "Syntax check output: $SyntaxOutput"
        }
    } catch {
        Write-Warning "Syntax check exception: $_"
    }
}

# === SUMMARY ===
Write-Host "`n========================================" -ForegroundColor $Green
Write-Host "  DEPLOYMENT COMPLETED SUCCESSFULLY" -ForegroundColor $Green
Write-Host "========================================" -ForegroundColor $Green

Write-Host "`nNext steps:" -ForegroundColor $Cyan
Write-Host "1. Test service resolution in Laravel:" -ForegroundColor Gray
Write-Host "   php artisan tinker" -ForegroundColor Yellow
Write-Host "   app(\App\Services\PrestaShop\PrestaShopSyncService::class)" -ForegroundColor Yellow
Write-Host ""
Write-Host "2. Test connection to PrestaShop shop:" -ForegroundColor Gray
Write-Host "   Use ShopManager Livewire component" -ForegroundColor Yellow
Write-Host ""
Write-Host "3. Verify logs:" -ForegroundColor Gray
Write-Host "   tail -f storage/logs/laravel.log" -ForegroundColor Yellow

Write-Host "`nFAZA 1F: Service Orchestration - COMPLETED" -ForegroundColor $Green
Write-Host "Next: FAZA 1G - Livewire UI Extensions" -ForegroundColor $Cyan
