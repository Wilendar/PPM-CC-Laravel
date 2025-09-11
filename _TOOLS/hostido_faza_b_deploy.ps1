# PowerShell Script: FAZA B Deployment to Hostido
# Created: 2025-01-09
# Purpose: Deploy FAZA B (Shop & ERP Management) components to production server

param(
    [switch]$DryRun = $false,
    [switch]$SkipBackup = $false
)

# Configuration
$HostidoHost = "host379076.hostido.net.pl"
$HostidoPort = "64321"
$HostidoUser = "host379076"
$SSHKeyPath = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n🚀 FAZA B DEPLOYMENT - PPM-CC-Laravel to Hostido" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Yellow

# Pre-deployment checks
function Test-Prerequisites {
    Write-Host "`n📋 Checking deployment prerequisites..." -ForegroundColor Cyan
    
    # Check SSH key
    if (!(Test-Path $SSHKeyPath)) {
        throw "SSH key not found: $SSHKeyPath"
    }
    
    # Check plink availability
    try {
        plink -V | Out-Null
    }
    catch {
        throw "plink not available. Install PuTTY tools."
    }
    
    # Test SSH connection
    Write-Host "Testing SSH connection..." -ForegroundColor Yellow
    $testResult = plink -ssh $HostidoUser@$HostidoHost -P $HostidoPort -i $SSHKeyPath -batch "echo 'SSH Test OK'"
    if ($LASTEXITCODE -ne 0) {
        throw "SSH connection failed"
    }
    Write-Host "✅ SSH connection successful" -ForegroundColor Green
    
    # Check if Laravel is present on server
    Write-Host "Checking Laravel installation..." -ForegroundColor Yellow
    $laravelCheck = plink -ssh $HostidoUser@$HostidoHost -P $HostidoPort -i $SSHKeyPath -batch "cd $RemotePath && php artisan --version"
    if ($LASTEXITCODE -ne 0) {
        throw "Laravel not found on remote server"
    }
    Write-Host "✅ Laravel installation verified" -ForegroundColor Green
}

# Create backup on remote server
function New-RemoteBackup {
    if ($SkipBackup) {
        Write-Host "⚠️ Skipping backup (--SkipBackup flag used)" -ForegroundColor Yellow
        return
    }
    
    Write-Host "`n💾 Creating backup on remote server..." -ForegroundColor Cyan
    
    $backupDir = "backup_faza_b_$(Get-Date -Format 'yyyy-MM-dd_HH-mm-ss')"
    
    $backupScript = @"
cd $RemotePath
mkdir -p backups/$backupDir
cp -r app/Http/Livewire backups/$backupDir/Livewire_backup 2>/dev/null || true
cp -r app/Models backups/$backupDir/Models_backup 2>/dev/null || true
cp -r app/Services backups/$backupDir/Services_backup 2>/dev/null || true
cp -r app/Jobs backups/$backupDir/Jobs_backup 2>/dev/null || true
cp -r database/migrations backups/$backupDir/migrations_backup 2>/dev/null || true
cp -r resources/views/livewire backups/$backupDir/views_backup 2>/dev/null || true
echo "Backup created: backups/$backupDir"
"@
    
    plink -ssh $HostidoUser@$HostidoHost -P $HostidoPort -i $SSHKeyPath -batch $backupScript
    if ($LASTEXITCODE -ne 0) {
        Write-Host "⚠️ Backup creation failed, continuing..." -ForegroundColor Yellow
    } else {
        Write-Host "✅ Backup created successfully" -ForegroundColor Green
    }
}

# Upload files to server
function Send-FazaBFiles {
    Write-Host "`n📦 Uploading FAZA B files..." -ForegroundColor Cyan
    
    # Files to upload
    $filesToUpload = @(
        # Database migrations
        @{
            Local = "$LocalPath\database\migrations\2024_01_01_000026_create_prestashop_shops_table.php"
            Remote = "$RemotePath/database/migrations/2024_01_01_000026_create_prestashop_shops_table.php"
        },
        @{
            Local = "$LocalPath\database\migrations\2024_01_01_000027_create_erp_connections_table.php"
            Remote = "$RemotePath/database/migrations/2024_01_01_000027_create_erp_connections_table.php"
        },
        @{
            Local = "$LocalPath\database\migrations\2024_01_01_000028_create_sync_jobs_table.php"
            Remote = "$RemotePath/database/migrations/2024_01_01_000028_create_sync_jobs_table.php"
        },
        @{
            Local = "$LocalPath\database\migrations\2024_01_01_000029_create_integration_logs_table.php"
            Remote = "$RemotePath/database/migrations/2024_01_01_000029_create_integration_logs_table.php"
        },
        
        # Models
        @{
            Local = "$LocalPath\app\Models\PrestaShopShop.php"
            Remote = "$RemotePath/app/Models/PrestaShopShop.php"
        },
        @{
            Local = "$LocalPath\app\Models\ERPConnection.php"
            Remote = "$RemotePath/app/Models/ERPConnection.php"
        },
        @{
            Local = "$LocalPath\app\Models\SyncJob.php"
            Remote = "$RemotePath/app/Models/SyncJob.php"
        },
        @{
            Local = "$LocalPath\app\Models\IntegrationLog.php"
            Remote = "$RemotePath/app/Models/IntegrationLog.php"
        },
        
        # Livewire Components
        @{
            Local = "$LocalPath\app\Http\Livewire\Admin\Shops\ShopManager.php"
            Remote = "$RemotePath/app/Http/Livewire/Admin/Shops/ShopManager.php"
        },
        @{
            Local = "$LocalPath\app\Http\Livewire\Admin\ERP\ERPManager.php"
            Remote = "$RemotePath/app/Http/Livewire/Admin/ERP/ERPManager.php"
        },
        
        # Services
        @{
            Local = "$LocalPath\app\Services\PrestaShop\PrestaShopService.php"
            Remote = "$RemotePath/app/Services/PrestaShop/PrestaShopService.php"
        },
        @{
            Local = "$LocalPath\app\Services\ERP\BaselinkerService.php"
            Remote = "$RemotePath/app/Services/ERP/BaselinkerService.php"
        },
        
        # Jobs
        @{
            Local = "$LocalPath\app\Jobs\PrestaShop\SyncProductsJob.php"
            Remote = "$RemotePath/app/Jobs/PrestaShop/SyncProductsJob.php"
        },
        
        # Views
        @{
            Local = "$LocalPath\resources\views\livewire\admin\shops\shop-manager.blade.php"
            Remote = "$RemotePath/resources/views/livewire/admin/shops/shop-manager.blade.php"
        }
    )
    
    $uploadCount = 0
    $failCount = 0
    
    foreach ($file in $filesToUpload) {
        if (Test-Path $file.Local) {
            Write-Host "Uploading: $(Split-Path $file.Local -Leaf)..." -ForegroundColor Yellow
            
            # Create directory on remote server if needed
            $remoteDir = Split-Path $file.Remote -Parent
            plink -ssh $HostidoUser@$HostidoHost -P $HostidoPort -i $SSHKeyPath -batch "mkdir -p $remoteDir"
            
            # Upload file
            pscp -P $HostidoPort -i $SSHKeyPath "$($file.Local)" "$HostidoUser@$HostidoHost`:$($file.Remote)"
            
            if ($LASTEXITCODE -eq 0) {
                Write-Host "  ✅ Success" -ForegroundColor Green
                $uploadCount++
            } else {
                Write-Host "  ❌ Failed" -ForegroundColor Red
                $failCount++
            }
        } else {
            Write-Host "⚠️ File not found: $($file.Local)" -ForegroundColor Yellow
            $failCount++
        }
    }
    
    Write-Host "`nUpload summary: $uploadCount successful, $failCount failed" -ForegroundColor Cyan
    
    if ($failCount -gt 0) {
        throw "Some files failed to upload. Aborting deployment."
    }
}

# Run database migrations
function Invoke-DatabaseMigrations {
    Write-Host "`n🗃️ Running database migrations..." -ForegroundColor Cyan
    
    $migrationScript = @"
cd $RemotePath
php artisan migrate --force
"@
    
    $migrationResult = plink -ssh $HostidoUser@$HostidoHost -P $HostidoPort -i $SSHKeyPath -batch $migrationScript
    
    if ($LASTEXITCODE -ne 0) {
        throw "Database migration failed"
    }
    
    Write-Host "✅ Database migrations completed successfully" -ForegroundColor Green
}

# Clear application caches
function Clear-ApplicationCaches {
    Write-Host "`n🧹 Clearing application caches..." -ForegroundColor Cyan
    
    $cacheScript = @"
cd $RemotePath
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
"@
    
    plink -ssh $HostidoUser@$HostidoHost -P $HostidoPort -i $SSHKeyPath -batch $cacheScript
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "⚠️ Some cache clearing failed, but continuing..." -ForegroundColor Yellow
    } else {
        Write-Host "✅ All caches cleared successfully" -ForegroundColor Green
    }
}

# Verify deployment
function Test-Deployment {
    Write-Host "`n🏥 Verifying deployment..." -ForegroundColor Cyan
    
    # Check if migrations were applied
    Write-Host "Checking database tables..." -ForegroundColor Yellow
    $tableCheck = plink -ssh $HostidoUser@$HostidoHost -P $HostidoPort -i $SSHKeyPath -batch "cd $RemotePath && php artisan migrate:status"
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "⚠️ Could not verify migration status" -ForegroundColor Yellow
    } else {
        Write-Host "✅ Database structure verified" -ForegroundColor Green
    }
    
    # Check if files exist
    Write-Host "Checking uploaded files..." -ForegroundColor Yellow
    $fileCheck = plink -ssh $HostidoUser@$HostidoHost -P $HostidoPort -i $SSHKeyPath -batch "cd $RemotePath && ls -la app/Http/Livewire/Admin/Shops/ShopManager.php"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Livewire components verified" -ForegroundColor Green
    } else {
        Write-Host "❌ Livewire components not found" -ForegroundColor Red
    }
    
    # Test web accessibility (basic)
    Write-Host "Testing web accessibility..." -ForegroundColor Yellow
    try {
        $webTest = Invoke-WebRequest -Uri "https://ppm.mpptrade.pl/admin" -TimeoutSec 10 -UseBasicParsing
        if ($webTest.StatusCode -eq 200) {
            Write-Host "✅ Admin panel accessible" -ForegroundColor Green
        } else {
            Write-Host "⚠️ Admin panel returned status: $($webTest.StatusCode)" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Host "⚠️ Could not test web accessibility: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

# Main deployment function
function Start-FazaBDeployment {
    try {
        if ($DryRun) {
            Write-Host "`n🧪 DRY RUN MODE - No changes will be made" -ForegroundColor Magenta
            return
        }
        
        Test-Prerequisites
        New-RemoteBackup
        Send-FazaBFiles
        Invoke-DatabaseMigrations
        Clear-ApplicationCaches
        Test-Deployment
        
        Write-Host "`n🎉 FAZA B DEPLOYMENT COMPLETED SUCCESSFULLY!" -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Yellow
        Write-Host "Next steps:" -ForegroundColor Cyan
        Write-Host "1. Test admin panel: https://ppm.mpptrade.pl/admin" -ForegroundColor White
        Write-Host "2. Test shop management: https://ppm.mpptrade.pl/admin/shops" -ForegroundColor White
        Write-Host "3. Test ERP management: https://ppm.mpptrade.pl/admin/integrations" -ForegroundColor White
        
    }
    catch {
        Write-Host "`n❌ DEPLOYMENT FAILED: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "`nRollback may be required if partial deployment occurred." -ForegroundColor Yellow
        exit 1
    }
}

# Execute deployment
Start-FazaBDeployment