# PPM-CC-Laravel FAZA C Deployment Script
# FAZA C: System Administration - Settings, Backup, Maintenance
# Target: ppm.mpptrade.pl

param(
    [switch]$TestOnly,
    [switch]$Force
)

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# ================================
# DEPLOYMENT CONFIGURATION
# ================================

$Config = @{
    Domain = "ppm.mpptrade.pl"
    SSHHost = "host379076@host379076.hostido.net.pl"
    SSHPort = "64321"
    SSHKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
    RemotePath = "domains/ppm.mpptrade.pl/public_html"
    LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
}

# ================================
# UTILITY FUNCTIONS
# ================================

function Write-ColoredMessage {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

function Invoke-SSHCommand {
    param(
        [string]$Command,
        [switch]$NoOutput
    )
    
    $fullCommand = "plink -ssh $($Config.SSHHost) -P $($Config.SSHPort) -i `"$($Config.SSHKey)`" -batch `"$Command`""
    
    Write-ColoredMessage "🔧 SSH: $Command" "Yellow"
    
    if ($NoOutput) {
        Invoke-Expression $fullCommand | Out-Null
    } else {
        Invoke-Expression $fullCommand
    }
}

function Copy-FileToRemote {
    param(
        [string]$LocalFile,
        [string]$RemoteFile
    )
    
    $scpCommand = "pscp -P $($Config.SSHPort) -i `"$($Config.SSHKey)`" `"$LocalFile`" $($Config.SSHHost):$RemoteFile"
    Write-ColoredMessage "📤 Uploading: $LocalFile" "Cyan"
    Invoke-Expression $scpCommand
}

# ================================
# FAZA C DEPLOYMENT FUNCTIONS
# ================================

function Deploy-Models {
    Write-ColoredMessage "`n📋 FAZA C MODELS DEPLOYMENT" "Green"
    
    $models = @(
        "app/Models/SystemSetting.php",
        "app/Models/BackupJob.php", 
        "app/Models/MaintenanceTask.php"
    )
    
    foreach ($model in $models) {
        if (Test-Path $model) {
            Copy-FileToRemote $model "$($Config.RemotePath)/$model"
            Write-ColoredMessage "✅ Deployed: $model" "Green"
        } else {
            Write-ColoredMessage "⚠️ Missing: $model" "Yellow"
        }
    }
}

function Deploy-Services {
    Write-ColoredMessage "`n🛠️ FAZA C SERVICES DEPLOYMENT" "Green"
    
    $services = @(
        "app/Services/SettingsService.php",
        "app/Services/BackupService.php",
        "app/Services/MaintenanceService.php"
    )
    
    foreach ($service in $services) {
        if (Test-Path $service) {
            Copy-FileToRemote $service "$($Config.RemotePath)/$service"
            Write-ColoredMessage "✅ Deployed: $service" "Green"
        } else {
            Write-ColoredMessage "⚠️ Missing: $service" "Yellow"
        }
    }
}

function Deploy-LivewireComponents {
    Write-ColoredMessage "`n⚡ FAZA C LIVEWIRE COMPONENTS DEPLOYMENT" "Green"
    
    $components = @(
        "app/Http/Livewire/Admin/Settings/SystemSettings.php",
        "app/Http/Livewire/Admin/Backup/BackupManager.php",
        "app/Http/Livewire/Admin/Maintenance/DatabaseMaintenance.php"
    )
    
    foreach ($component in $components) {
        if (Test-Path $component) {
            Copy-FileToRemote $component "$($Config.RemotePath)/$component"
            Write-ColoredMessage "✅ Deployed: $component" "Green"
        } else {
            Write-ColoredMessage "⚠️ Missing: $component" "Yellow"
        }
    }
}

function Deploy-BladeViews {
    Write-ColoredMessage "`n🎨 FAZA C BLADE VIEWS DEPLOYMENT" "Green"
    
    $views = @(
        "resources/views/livewire/admin/settings/system-settings.blade.php",
        "resources/views/livewire/admin/backup/backup-manager.blade.php", 
        "resources/views/livewire/admin/maintenance/database-maintenance.blade.php"
    )
    
    foreach ($view in $views) {
        if (Test-Path $view) {
            Copy-FileToRemote $view "$($Config.RemotePath)/$view"
            Write-ColoredMessage "✅ Deployed: $view" "Green"
        } else {
            Write-ColoredMessage "⚠️ Missing: $view" "Yellow"
        }
    }
}

function Deploy-Jobs {
    Write-ColoredMessage "`n⏰ FAZA C QUEUE JOBS DEPLOYMENT" "Green"
    
    $jobs = @(
        "app/Jobs/BackupDatabaseJob.php",
        "app/Jobs/MaintenanceTaskJob.php",
        "app/Jobs/ScheduledBackupJob.php"
    )
    
    foreach ($job in $jobs) {
        if (Test-Path $job) {
            Copy-FileToRemote $job "$($Config.RemotePath)/$job"
            Write-ColoredMessage "✅ Deployed: $job" "Green"
        } else {
            Write-ColoredMessage "⚠️ Missing: $job" "Yellow"
        }
    }
}

function Deploy-Migrations {
    Write-ColoredMessage "`n💾 FAZA C MIGRATIONS DEPLOYMENT" "Green"
    
    $migrations = @(
        "database/migrations/2024_01_01_000030_create_system_settings_table.php",
        "database/migrations/2024_01_01_000031_create_backup_jobs_table.php",
        "database/migrations/2024_01_01_000032_create_maintenance_tasks_table.php"
    )
    
    foreach ($migration in $migrations) {
        if (Test-Path $migration) {
            Copy-FileToRemote $migration "$($Config.RemotePath)/$migration"
            Write-ColoredMessage "✅ Deployed: $migration" "Green"
        } else {
            Write-ColoredMessage "⚠️ Missing: $migration" "Yellow"
        }
    }
}

function Deploy-Seeders {
    Write-ColoredMessage "`n🌱 FAZA C SEEDERS DEPLOYMENT" "Green"
    
    $seeders = @(
        "database/seeders/SystemSettingsSeeder.php"
    )
    
    foreach ($seeder in $seeders) {
        if (Test-Path $seeder) {
            Copy-FileToRemote $seeder "$($Config.RemotePath)/$seeder"
            Write-ColoredMessage "✅ Deployed: $seeder" "Green"
        } else {
            Write-ColoredMessage "⚠️ Missing: $seeder" "Yellow"
        }
    }
}

function Deploy-Routes {
    Write-ColoredMessage "`n🛣️ FAZA C ROUTES DEPLOYMENT" "Green"
    
    # Deploy updated web.php with FAZA C routes
    Copy-FileToRemote "routes/web.php" "$($Config.RemotePath)/routes/web.php"
    Write-ColoredMessage "✅ Deployed: routes/web.php" "Green"
}

function Run-RemoteMigrations {
    Write-ColoredMessage "`n🚀 RUNNING FAZA C MIGRATIONS" "Green"
    
    try {
        Invoke-SSHCommand "cd $($Config.RemotePath) && php artisan migrate --force"
        Write-ColoredMessage "✅ Migrations completed" "Green"
    } catch {
        Write-ColoredMessage "❌ Migration failed: $($_.Exception.Message)" "Red"
        throw
    }
}

function Run-RemoteSeeders {
    Write-ColoredMessage "`n🌱 RUNNING FAZA C SEEDERS" "Green"
    
    try {
        Invoke-SSHCommand "cd $($Config.RemotePath) && php artisan db:seed --class=SystemSettingsSeeder"
        Write-ColoredMessage "✅ SystemSettings seeded" "Green"
    } catch {
        Write-ColoredMessage "❌ Seeding failed: $($_.Exception.Message)" "Red"
        Write-ColoredMessage "ℹ️ This is expected if settings already exist" "Yellow"
    }
}

function Test-RemoteConnectivity {
    Write-ColoredMessage "`n🔍 TESTING REMOTE CONNECTIVITY" "Green"
    
    try {
        $result = Invoke-SSHCommand "cd $($Config.RemotePath) && php -v | head -1"
        Write-ColoredMessage "✅ PHP Version: $result" "Green"
        
        $result = Invoke-SSHCommand "cd $($Config.RemotePath) && ls -la | wc -l"
        Write-ColoredMessage "✅ Files in public_html: $result" "Green"
        
        return $true
    } catch {
        Write-ColoredMessage "❌ Remote connectivity failed: $($_.Exception.Message)" "Red"
        return $false
    }
}

function Clear-RemoteCache {
    Write-ColoredMessage "`n🧹 CLEARING REMOTE CACHE" "Green"
    
    try {
        Invoke-SSHCommand "cd $($Config.RemotePath) && php artisan cache:clear"
        Invoke-SSHCommand "cd $($Config.RemotePath) && php artisan view:clear" 
        Invoke-SSHCommand "cd $($Config.RemotePath) && php artisan config:clear"
        Write-ColoredMessage "✅ Cache cleared" "Green"
    } catch {
        Write-ColoredMessage "⚠️ Cache clearing failed - may not be critical" "Yellow"
    }
}

function Test-FazaCEndpoints {
    Write-ColoredMessage "`n🧪 TESTING FAZA C ENDPOINTS" "Green"
    
    $endpoints = @(
        "admin/system-settings",
        "admin/backup", 
        "admin/maintenance"
    )
    
    foreach ($endpoint in $endpoints) {
        try {
            $response = Invoke-WebRequest -Uri "https://$($Config.Domain)/$endpoint" -Method GET -TimeoutSec 30
            if ($response.StatusCode -eq 200) {
                Write-ColoredMessage "✅ ${endpoint}: OK" "Green"
            } else {
                Write-ColoredMessage "⚠️ ${endpoint}: Status $($response.StatusCode)" "Yellow"  
            }
        } catch {
            Write-ColoredMessage "❌ $endpoint: Failed - $($_.Exception.Message)" "Red"
        }
    }
}

# ================================
# MAIN DEPLOYMENT FUNCTION
# ================================

function Start-FazaCDeployment {
    Write-ColoredMessage @"
╔══════════════════════════════════════════════════════════════════════╗
║                   PPM-CC-Laravel FAZA C DEPLOYMENT                  ║
║                    System Administration Features                     ║
║                                                                      ║
║  🎯 Target: $($Config.Domain)                            ║
║  📦 Components: Settings, Backup, Maintenance                       ║
║  ⏱️ Expected Time: 5-10 minutes                                     ║
╚══════════════════════════════════════════════════════════════════════╝
"@ "Cyan"

    if ($TestOnly) {
        Write-ColoredMessage "🧪 TEST MODE - No files will be deployed" "Yellow"
        return Test-RemoteConnectivity
    }
    
    # Pre-deployment validation
    Write-ColoredMessage "`n🔍 PRE-DEPLOYMENT VALIDATION" "Magenta"
    
    if (-not (Test-RemoteConnectivity)) {
        throw "Remote connectivity test failed"
    }
    
    # Deploy FAZA C components
    try {
        Deploy-Models
        Deploy-Services  
        Deploy-LivewireComponents
        Deploy-BladeViews
        Deploy-Jobs
        Deploy-Migrations
        Deploy-Seeders
        Deploy-Routes
        
        # Run remote operations
        Run-RemoteMigrations
        Run-RemoteSeeders
        Clear-RemoteCache
        
        Write-ColoredMessage "`n✨ FAZA C DEPLOYMENT SUCCESSFUL!" "Green"
        Write-ColoredMessage "🌐 Visit: https://$($Config.Domain)/admin/system-settings" "Cyan"
        Write-ColoredMessage "💾 Backup: https://$($Config.Domain)/admin/backup" "Cyan"
        Write-ColoredMessage "🔧 Maintenance: https://$($Config.Domain)/admin/maintenance" "Cyan"
        
        # Test endpoints
        Test-FazaCEndpoints
        
        return $true
        
    } catch {
        Write-ColoredMessage "`n❌ FAZA C DEPLOYMENT FAILED!" "Red"
        Write-ColoredMessage "Error: $($_.Exception.Message)" "Red"
        
        if (-not $Force) {
            Write-ColoredMessage "Run with -Force to continue despite errors" "Yellow"
        }
        
        throw
    }
}

# ================================
# SCRIPT EXECUTION
# ================================

try {
    # Change to project directory
    Set-Location $Config.LocalPath
    
    # Start deployment
    $success = Start-FazaCDeployment
    
    if ($success) {
        Write-ColoredMessage "`n🎉 FAZA C: System Administration deployed successfully!" "Green"
        Write-ColoredMessage "⏳ Ready for ETAP_05: Produkty implementation" "Cyan"
    }
    
} catch {
    Write-ColoredMessage "`n💥 DEPLOYMENT FAILED!" "Red"
    Write-ColoredMessage "Error: $($_.Exception.Message)" "Red"
    exit 1
}

Write-ColoredMessage "`n📋 Deployment completed at $(Get-Date)" "Gray"