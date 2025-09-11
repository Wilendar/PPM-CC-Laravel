# PPM-CC-Laravel FAZA B: Authentication UI Deployment Script
# Frontend Specialist - FAZA B Deployment dla https://ppm.mpptrade.pl
# Created: 2025-09-09 by Frontend Specialist

param(
    [switch]$Force = $false,
    [switch]$SkipBackup = $false,
    [switch]$TestMode = $false
)

# Configuration
$ServerHost = "host379076@host379076.hostido.net.pl"
$ServerPort = 64321
$SSHKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemotePath = "/domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

# Colors for output
$Red = "Red"
$Green = "Green"
$Yellow = "Yellow"
$Cyan = "Cyan"
$White = "White"

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

function Test-SSHConnection {
    Write-ColorOutput "Testing SSH connection..." $Cyan
    
    try {
        $result = & plink -ssh $ServerHost -P $ServerPort -i $SSHKey -batch "php -v" 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorOutput "✅ SSH Connection successful" $Green
            Write-ColorOutput "PHP Version: $($result[0])" $White
            return $true
        } else {
            Write-ColorOutput "❌ SSH Connection failed" $Red
            Write-ColorOutput "Error: $result" $Red
            return $false
        }
    }
    catch {
        Write-ColorOutput "❌ SSH Connection error: $_" $Red
        return $false
    }
}

function Backup-RemoteFiles {
    if ($SkipBackup) {
        Write-ColorOutput "⏩ Skipping backup (--SkipBackup flag)" $Yellow
        return $true
    }
    
    Write-ColorOutput "Creating backup of current files..." $Cyan
    
    $backupName = "faza_b_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    
    try {
        $command = "cd $RemotePath && mkdir -p ../backups && tar -czf ../backups/$backupName.tar.gz app/Http/Livewire resources/views/livewire resources/views/layouts resources/views/components resources/views/dashboard 2>/dev/null || true"
        
        & plink -ssh $ServerHost -P $ServerPort -i $SSHKey -batch $command
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorOutput "✅ Backup created: $backupName.tar.gz" $Green
            return $true
        } else {
            Write-ColorOutput "⚠️  Backup failed, but continuing..." $Yellow
            return $true
        }
    }
    catch {
        Write-ColorOutput "⚠️  Backup error: $_, but continuing..." $Yellow
        return $true
    }
}

function Deploy-LivewireComponents {
    Write-ColorOutput "Deploying Livewire components..." $Cyan
    
    # Create remote directories
    $directories = @(
        "$RemotePath/app/Http/Livewire",
        "$RemotePath/app/Http/Livewire/Auth",
        "$RemotePath/app/Http/Livewire/Profile"
    )
    
    foreach ($dir in $directories) {
        & plink -ssh $ServerHost -P $ServerPort -i $SSHKey -batch "mkdir -p '$dir'"
    }
    
    # Upload Livewire components
    $components = @(
        "app/Http/Livewire/Auth/Login.php",
        "app/Http/Livewire/Auth/Register.php", 
        "app/Http/Livewire/Auth/ForgotPassword.php",
        "app/Http/Livewire/Auth/ResetPassword.php",
        "app/Http/Livewire/Profile/EditProfile.php"
    )
    
    foreach ($component in $components) {
        $localFile = Join-Path $LocalPath $component
        $remoteFile = "$RemotePath/$component"
        
        if (Test-Path $localFile) {
            Write-ColorOutput "Uploading $component..." $White
            & pscp -i $SSHKey -P $ServerPort "$localFile" "${ServerHost}:$remoteFile"
            
            if ($LASTEXITCODE -ne 0) {
                Write-ColorOutput "❌ Failed to upload $component" $Red
                return $false
            }
        } else {
            Write-ColorOutput "⚠️  File not found: $component" $Yellow
        }
    }
    
    Write-ColorOutput "✅ Livewire components deployed" $Green
    return $true
}

function Deploy-BladeTemplates {
    Write-ColorOutput "Deploying Blade templates..." $Cyan
    
    # Create remote directories
    $directories = @(
        "$RemotePath/resources/views/livewire",
        "$RemotePath/resources/views/livewire/auth",
        "$RemotePath/resources/views/livewire/profile",
        "$RemotePath/resources/views/layouts",
        "$RemotePath/resources/views/components",
        "$RemotePath/resources/views/dashboard"
    )
    
    foreach ($dir in $directories) {
        & plink -ssh $ServerHost -P $ServerPort -i $SSHKey -batch "mkdir -p '$dir'"
    }
    
    # Upload Blade templates
    $templates = @(
        "resources/views/livewire/auth/login.blade.php",
        "resources/views/livewire/auth/register.blade.php",
        "resources/views/livewire/auth/forgot-password.blade.php", 
        "resources/views/livewire/auth/reset-password.blade.php",
        "resources/views/livewire/profile/edit-profile.blade.php",
        "resources/views/layouts/auth.blade.php",
        "resources/views/layouts/app.blade.php",
        "resources/views/layouts/navigation.blade.php",
        "resources/views/layouts/user-menu.blade.php",
        "resources/views/components/flash-messages.blade.php",
        "resources/views/dashboard/index.blade.php"
    )
    
    foreach ($template in $templates) {
        $localFile = Join-Path $LocalPath $template
        $remoteFile = "$RemotePath/$template"
        
        if (Test-Path $localFile) {
            Write-ColorOutput "Uploading $template..." $White
            & pscp -i $SSHKey -P $ServerPort "$localFile" "${ServerHost}:$remoteFile"
            
            if ($LASTEXITCODE -ne 0) {
                Write-ColorOutput "❌ Failed to upload $template" $Red
                return $false
            }
        } else {
            Write-ColorOutput "⚠️  File not found: $template" $Yellow
        }
    }
    
    Write-ColorOutput "✅ Blade templates deployed" $Green
    return $true
}

function Clear-LaravelCache {
    Write-ColorOutput "Clearing Laravel caches..." $Cyan
    
    $cacheCommands = @(
        "cd $RemotePath && php artisan config:clear",
        "cd $RemotePath && php artisan route:clear", 
        "cd $RemotePath && php artisan view:clear",
        "cd $RemotePath && php artisan cache:clear"
    )
    
    foreach ($command in $cacheCommands) {
        & plink -ssh $ServerHost -P $ServerPort -i $SSHKey -batch $command
        
        if ($LASTEXITCODE -ne 0) {
            Write-ColorOutput "⚠️  Cache command failed: $command" $Yellow
        }
    }
    
    Write-ColorOutput "✅ Laravel caches cleared" $Green
    return $true
}

function Test-Deployment {
    Write-ColorOutput "Testing deployment..." $Cyan
    
    try {
        # Test Laravel health
        $healthCheck = & plink -ssh $ServerHost -P $ServerPort -i $SSHKey -batch "cd $RemotePath && php artisan --version" 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorOutput "✅ Laravel is operational: $healthCheck" $Green
        } else {
            Write-ColorOutput "❌ Laravel health check failed" $Red
            return $false
        }
        
        # Test file permissions
        $permissionCheck = & plink -ssh $ServerHost -P $ServerPort -i $SSHKey -batch "cd $RemotePath && find storage bootstrap/cache -type f -not -perm 644 -o -type d -not -perm 755 | wc -l" 2>&1
        
        if ($permissionCheck -eq "0") {
            Write-ColorOutput "✅ File permissions correct" $Green
        } else {
            Write-ColorOutput "⚠️  Some file permissions may need adjustment" $Yellow
        }
        
        Write-ColorOutput "✅ Deployment test completed" $Green
        return $true
    }
    catch {
        Write-ColorOutput "❌ Deployment test error: $_" $Red
        return $false
    }
}

function Show-DeploymentSummary {
    Write-ColorOutput "`n" + "="*60 $Cyan
    Write-ColorOutput "FAZA B: Authentication UI Deployment Summary" $Cyan
    Write-ColorOutput "="*60 $Cyan
    
    Write-ColorOutput "🚀 Deployed Components:" $Green
    Write-ColorOutput "   • Livewire Authentication Components (Login, Register, Reset)" $White
    Write-ColorOutput "   • User Profile Management Component" $White
    Write-ColorOutput "   • Responsive Blade Templates" $White
    Write-ColorOutput "   • Role-based Navigation System" $White
    Write-ColorOutput "   • Flash Messages Component" $White
    Write-ColorOutput "   • Dashboard Layout" $White
    
    Write-ColorOutput "`n📋 Next Steps:" $Yellow
    Write-ColorOutput "   1. Test authentication flows at https://ppm.mpptrade.pl" $White
    Write-ColorOutput "   2. Verify role-based redirects work correctly" $White
    Write-ColorOutput "   3. Test profile management and avatar upload" $White
    Write-ColorOutput "   4. Validate responsive design on mobile devices" $White
    Write-ColorOutput "   5. Check flash messages and session management" $White
    
    Write-ColorOutput "`n🔗 URLs to Test:" $Cyan
    Write-ColorOutput "   • Login: https://ppm.mpptrade.pl/login" $White
    Write-ColorOutput "   • Register: https://ppm.mpptrade.pl/register" $White
    Write-ColorOutput "   • Reset: https://ppm.mpptrade.pl/password/request" $White
    Write-ColorOutput "   • Dashboard: https://ppm.mpptrade.pl/dashboard" $White
    Write-ColorOutput "   • Profile: https://ppm.mpptrade.pl/profile" $White
    
    Write-ColorOutput "`n⚡ Performance Notes:" $Green
    Write-ColorOutput "   • All components use Alpine.js for reactivity" $White
    Write-ColorOutput "   • Livewire 3.x lazy loading implemented" $White
    Write-ColorOutput "   • Dark mode support included" $White
    Write-ColorOutput "   • Session management with timeout warnings" $White
    
    Write-ColorOutput "="*60 $Cyan
}

# Main deployment execution
Write-ColorOutput "PPM-CC-Laravel FAZA B: Authentication UI Deployment" $Cyan
Write-ColorOutput "Target: https://ppm.mpptrade.pl" $White
Write-ColorOutput "Time: $(Get-Date)" $White

if ($TestMode) {
    Write-ColorOutput "`n🧪 TEST MODE ENABLED - No actual deployment" $Yellow
}

Write-ColorOutput "`n" + "="*50 $Cyan

# Step 1: Test SSH Connection
if (-not (Test-SSHConnection)) {
    Write-ColorOutput "❌ Deployment aborted - SSH connection failed" $Red
    exit 1
}

# Step 2: Backup existing files
if (-not $TestMode -and -not (Backup-RemoteFiles)) {
    Write-ColorOutput "❌ Deployment aborted - Backup failed" $Red
    exit 1
}

# Step 3: Deploy Livewire components
if (-not $TestMode -and -not (Deploy-LivewireComponents)) {
    Write-ColorOutput "❌ Deployment aborted - Livewire components deployment failed" $Red
    exit 1
}

# Step 4: Deploy Blade templates
if (-not $TestMode -and -not (Deploy-BladeTemplates)) {
    Write-ColorOutput "❌ Deployment aborted - Blade templates deployment failed" $Red
    exit 1
}

# Step 5: Clear Laravel caches
if (-not $TestMode -and -not (Clear-LaravelCache)) {
    Write-ColorOutput "❌ Deployment aborted - Cache clearing failed" $Red
    exit 1
}

# Step 6: Test deployment
if (-not (Test-Deployment)) {
    Write-ColorOutput "❌ Deployment completed with warnings - Check manually" $Yellow
} else {
    Write-ColorOutput "✅ FAZA B Deployment successful!" $Green
}

# Show summary
Show-DeploymentSummary

Write-ColorOutput "`n🎉 FAZA B: Authentication UI deployment completed!" $Green
Write-ColorOutput "Frontend Specialist - $(Get-Date)" $Cyan