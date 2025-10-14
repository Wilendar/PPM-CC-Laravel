# PPM-CC-Laravel OAuth2 Deployment Script for Hostido
# FAZA D: OAuth2 + Advanced Features - Production Deployment

param(
    [switch]$SkipBackup = $false,
    [switch]$TestOnly = $false,
    [switch]$Verbose = $false
)

# ==========================================
# CONFIGURATION
# ==========================================

$HostidoConfig = @{
    Host = "host379076.hostido.net.pl"
    Port = 64321
    Username = "host379076"
    KeyPath = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
    RemotePath = "/domains/ppm.mpptrade.pl/public_html"
    URL = "https://ppm.mpptrade.pl"
}

$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

# ==========================================
# UTILITY FUNCTIONS
# ==========================================

function Write-ColorText {
    param(
        [string]$Text,
        [ConsoleColor]$Color = [ConsoleColor]::White
    )
    
    $previousColor = $Host.UI.RawUI.ForegroundColor
    $Host.UI.RawUI.ForegroundColor = $Color
    Write-Host $Text
    $Host.UI.RawUI.ForegroundColor = $previousColor
}

function Execute-SSH {
    param(
        [string]$Command,
        [bool]$ShowOutput = $true
    )
    
    if ($Verbose) {
        Write-ColorText "SSH Command: $Command" -Color Cyan
    }
    
    $result = plink -ssh "$($HostidoConfig.Username)@$($HostidoConfig.Host)" -P $HostidoConfig.Port -i $HostidoConfig.KeyPath -batch $Command
    
    if ($ShowOutput -and $result) {
        Write-Host $result
    }
    
    return $result
}

function Test-Connection {
    Write-ColorText "🔗 Testing SSH connection..." -Color Yellow
    
    $result = Execute-SSH "echo 'Connection successful'" $false
    
    if ($result -eq "Connection successful") {
        Write-ColorText "✅ SSH connection successful" -Color Green
        return $true
    } else {
        Write-ColorText "❌ SSH connection failed" -Color Red
        return $false
    }
}

# ==========================================
# BACKUP FUNCTIONS
# ==========================================

function Backup-CurrentDeployment {
    if ($SkipBackup) {
        Write-ColorText "⚠️  Skipping backup (--SkipBackup flag used)" -Color Yellow
        return
    }
    
    Write-ColorText "💾 Creating backup of current deployment..." -Color Yellow
    
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupDir = "backup_oauth_$timestamp"
    
    Execute-SSH "cd $($HostidoConfig.RemotePath) && mkdir -p ../backups/$backupDir"
    
    # Backup critical files
    $filesToBackup = @(
        ".env",
        "composer.json",
        "config/",
        "app/Http/Controllers/Auth/",
        "app/Models/User.php",
        "app/Services/",
        "routes/",
        "database/migrations/"
    )
    
    foreach ($file in $filesToBackup) {
        Execute-SSH "cd $($HostidoConfig.RemotePath) && cp -r $file ../backups/$backupDir/ 2>/dev/null || true"
    }
    
    Write-ColorText "✅ Backup created: $backupDir" -Color Green
}

# ==========================================
# DEPLOYMENT FUNCTIONS
# ==========================================

function Upload-OAuthFiles {
    Write-ColorText "📤 Uploading OAuth2 implementation files..." -Color Yellow
    
    # Upload OAuth Controllers
    Execute-SSH "mkdir -p $($HostidoConfig.RemotePath)/app/Http/Controllers/Auth"
    
    Write-ColorText "  • Uploading Google Auth Controller..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\app\Http\Controllers\Auth\GoogleAuthController.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/app/Http/Controllers/Auth/"
    
    Write-ColorText "  • Uploading Microsoft Auth Controller..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\app\Http\Controllers\Auth\MicrosoftAuthController.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/app/Http/Controllers/Auth/"
    
    # Upload Services
    Execute-SSH "mkdir -p $($HostidoConfig.RemotePath)/app/Services"
    
    Write-ColorText "  • Uploading OAuth Session Service..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\app\Services\OAuthSessionService.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/app/Services/"
    
    Write-ColorText "  • Uploading OAuth Security Service..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\app\Services\OAuthSecurityService.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/app/Services/"
    
    # Upload Middleware
    Execute-SSH "mkdir -p $($HostidoConfig.RemotePath)/app/Http/Middleware"
    
    Write-ColorText "  • Uploading OAuth Security Middleware..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\app\Http\Middleware\OAuthSecurityMiddleware.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/app/Http/Middleware/"
    
    # Upload Models
    Write-ColorText "  • Uploading Updated User Model..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\app\Models\User.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/app/Models/"
    
    Write-ColorText "  • Uploading OAuth Audit Log Model..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\app\Models\OAuthAuditLog.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/app/Models/"
    
    # Upload Routes
    Write-ColorText "  • Uploading OAuth Routes..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\routes\oauth.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/routes/"
    
    Write-ColorText "  • Uploading Updated Web Routes..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\routes\web.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/routes/"
    
    # Upload Configuration
    Write-ColorText "  • Uploading Services Configuration..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\config\services.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/config/"
    
    # Upload Migrations
    Execute-SSH "mkdir -p $($HostidoConfig.RemotePath)/database/migrations"
    
    Write-ColorText "  • Uploading OAuth Migrations..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\database\migrations\2024_01_01_000019_add_oauth_fields_to_users_table.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/database/migrations/"
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\database\migrations\2024_01_01_000020_create_oauth_audit_logs_table.php" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/database/migrations/"
    
    # Upload Updated Composer
    Write-ColorText "  • Uploading Updated Composer.json..." -Color Cyan
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath "$LocalPath\composer.json" "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/"
    
    Write-ColorText "✅ OAuth2 files uploaded successfully" -Color Green
}

function Install-Dependencies {
    Write-ColorText "📦 Installing OAuth2 dependencies..." -Color Yellow
    
    # Install Laravel Socialite and Sanctum
    Execute-SSH "cd $($HostidoConfig.RemotePath) && composer require laravel/socialite:^5.15 laravel/sanctum:^4.0 --no-dev"
    
    Write-ColorText "✅ Dependencies installed" -Color Green
}

function Run-Migrations {
    Write-ColorText "🗃️ Running OAuth2 migrations..." -Color Yellow
    
    if ($TestOnly) {
        Write-ColorText "  • Testing migrations (dry run)..." -Color Cyan
        Execute-SSH "cd $($HostidoConfig.RemotePath) && php artisan migrate:status"
    } else {
        Write-ColorText "  • Running OAuth fields migration..." -Color Cyan
        Execute-SSH "cd $($HostidoConfig.RemotePath) && php artisan migrate --path=database/migrations/2024_01_01_000019_add_oauth_fields_to_users_table.php --force"
        
        Write-ColorText "  • Running OAuth audit logs migration..." -Color Cyan
        Execute-SSH "cd $($HostidoConfig.RemotePath) && php artisan migrate --path=database/migrations/2024_01_01_000020_create_oauth_audit_logs_table.php --force"
    }
    
    Write-ColorText "✅ Migrations completed" -Color Green
}

function Update-Environment {
    Write-ColorText "🔧 Updating environment configuration..." -Color Yellow
    
    # Add OAuth environment variables template
    $oauthEnvVars = @"

# OAuth2 Configuration (FAZA D)
OAUTH_ENABLED_PROVIDERS=google,microsoft
OAUTH_ALLOWED_DOMAINS=mpptrade.pl
OAUTH_AUTO_REGISTRATION=true
OAUTH_LINK_EXISTING=true
OAUTH_REMEMBER_SESSIONS=true
OAUTH_SESSION_LIFETIME=120
OAUTH_MAX_ATTEMPTS=5
OAUTH_LOCKOUT_MINUTES=30

# Google Workspace OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
GOOGLE_HOSTED_DOMAIN=mpptrade.pl
GOOGLE_REQUIRE_VERIFICATION=true

# Microsoft Entra ID OAuth
MICROSOFT_CLIENT_ID=your_microsoft_client_id
MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret
MICROSOFT_REDIRECT_URI=${APP_URL}/auth/microsoft/callback
MICROSOFT_TENANT_ID=common
MICROSOFT_ALLOWED_DOMAINS=mpptrade.pl
MICROSOFT_REQUIRE_VERIFICATION=true

# Cache Configuration for OAuth
CACHE_OAUTH_TOKENS_TTL=3600
CACHE_USER_PROFILE_TTL=1800
CACHE_API_RESPONSE_TTL=300

"@
    
    # Create temporary env file with OAuth vars
    $tempEnvFile = "$env:TEMP\oauth_env_vars.txt"
    $oauthEnvVars | Out-File -FilePath $tempEnvFile -Encoding UTF8
    
    # Upload and append to .env
    pscp -P $HostidoConfig.Port -i $HostidoConfig.KeyPath $tempEnvFile "$($HostidoConfig.Username)@$($HostidoConfig.Host):$($HostidoConfig.RemotePath)/oauth_env_vars.txt"
    
    Execute-SSH "cd $($HostidoConfig.RemotePath) && cat oauth_env_vars.txt >> .env && rm oauth_env_vars.txt"
    
    Remove-Item $tempEnvFile
    
    Write-ColorText "✅ Environment updated with OAuth variables" -Color Green
    Write-ColorText "⚠️  Remember to update OAuth credentials in .env file!" -Color Yellow
}

function Optimize-Application {
    Write-ColorText "⚡ Optimizing application..." -Color Yellow
    
    Execute-SSH "cd $($HostidoConfig.RemotePath) && php artisan config:cache"
    Execute-SSH "cd $($HostidoConfig.RemotePath) && php artisan route:cache"
    Execute-SSH "cd $($HostidoConfig.RemotePath) && php artisan view:cache"
    
    Write-ColorText "✅ Application optimized" -Color Green
}

# ==========================================
# TESTING FUNCTIONS
# ==========================================

function Test-OAuthDeployment {
    Write-ColorText "🧪 Testing OAuth2 deployment..." -Color Yellow
    
    # Test basic Laravel functionality
    Write-ColorText "  • Testing Laravel status..." -Color Cyan
    $status = Execute-SSH "cd $($HostidoConfig.RemotePath) && php artisan --version" $false
    
    if ($status) {
        Write-ColorText "    ✅ Laravel is working: $status" -Color Green
    } else {
        Write-ColorText "    ❌ Laravel status check failed" -Color Red
        return $false
    }
    
    # Test database connection
    Write-ColorText "  • Testing database connection..." -Color Cyan
    $dbTest = Execute-SSH "cd $($HostidoConfig.RemotePath) && php artisan tinker --execute='DB::connection()->getPdo();echo \"Database OK\";'" $false
    
    if ($dbTest -match "Database OK") {
        Write-ColorText "    ✅ Database connection successful" -Color Green
    } else {
        Write-ColorText "    ❌ Database connection failed" -Color Red
        return $false
    }
    
    # Test OAuth routes
    Write-ColorText "  • Testing OAuth routes registration..." -Color Cyan
    $routeTest = Execute-SSH "cd $($HostidoConfig.RemotePath) && php artisan route:list --name=auth.google" $false
    
    if ($routeTest -match "auth.google") {
        Write-ColorText "    ✅ OAuth routes registered" -Color Green
    } else {
        Write-ColorText "    ❌ OAuth routes not found" -Color Red
        return $false
    }
    
    # Test URL accessibility (if not TestOnly)
    if (-not $TestOnly) {
        Write-ColorText "  • Testing URL accessibility..." -Color Cyan
        
        try {
            $response = Invoke-WebRequest -Uri "$($HostidoConfig.URL)/up" -TimeoutSec 10 -UseBasicParsing
            if ($response.StatusCode -eq 200) {
                Write-ColorText "    ✅ Application is accessible" -Color Green
            } else {
                Write-ColorText "    ⚠️  Application returned status: $($response.StatusCode)" -Color Yellow
            }
        }
        catch {
            Write-ColorText "    ❌ Application not accessible: $($_.Exception.Message)" -Color Red
            return $false
        }
    }
    
    Write-ColorText "✅ OAuth2 deployment test completed successfully" -Color Green
    return $true
}

# ==========================================
# MAIN DEPLOYMENT PROCESS
# ==========================================

function Start-OAuthDeployment {
    Write-ColorText "🚀 Starting PPM OAuth2 Deployment to Hostido" -Color Green
    Write-ColorText "===============================================" -Color Green
    
    # Test connection first
    if (-not (Test-Connection)) {
        Write-ColorText "❌ Deployment aborted - SSH connection failed" -Color Red
        return $false
    }
    
    try {
        # Step 1: Backup
        Backup-CurrentDeployment
        
        # Step 2: Upload files
        Upload-OAuthFiles
        
        # Step 3: Install dependencies
        Install-Dependencies
        
        # Step 4: Run migrations
        Run-Migrations
        
        # Step 5: Update environment
        Update-Environment
        
        # Step 6: Optimize application
        if (-not $TestOnly) {
            Optimize-Application
        }
        
        # Step 7: Test deployment
        if (Test-OAuthDeployment) {
            Write-ColorText "" -Color White
            Write-ColorText "✅ OAuth2 DEPLOYMENT SUCCESSFUL! ✅" -Color Green
            Write-ColorText "===============================================" -Color Green
            Write-ColorText "🌐 Application URL: $($HostidoConfig.URL)" -Color Cyan
            Write-ColorText "🔑 OAuth Google: $($HostidoConfig.URL)/auth/google" -Color Cyan
            Write-ColorText "🔑 OAuth Microsoft: $($HostidoConfig.URL)/auth/microsoft" -Color Cyan
            Write-ColorText "" -Color White
            Write-ColorText "⚠️  IMPORTANT: Update OAuth credentials in .env:" -Color Yellow
            Write-ColorText "  • GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET" -Color Yellow
            Write-ColorText "  • MICROSOFT_CLIENT_ID and MICROSOFT_CLIENT_SECRET" -Color Yellow
            Write-ColorText "  • Configure OAuth consent screens in respective consoles" -Color Yellow
            
            return $true
        } else {
            Write-ColorText "❌ Deployment completed but tests failed" -Color Red
            return $false
        }
        
    } catch {
        Write-ColorText "❌ Deployment failed: $($_.Exception.Message)" -Color Red
        Write-ColorText "Stack trace: $($_.Exception.StackTrace)" -Color Red
        return $false
    }
}

# ==========================================
# SCRIPT EXECUTION
# ==========================================

Write-Host ""
Write-ColorText "PPM-CC-Laravel OAuth2 Deployment Script" -Color Cyan
Write-ColorText "FAZA D: OAuth2 + Advanced Features" -Color Cyan
Write-Host ""

if ($TestOnly) {
    Write-ColorText "🧪 TEST MODE - No permanent changes will be made" -Color Yellow
}

if ($SkipBackup) {
    Write-ColorText "⚠️  BACKUP DISABLED - Use with caution!" -Color Yellow
}

# Execute deployment
$deploymentSuccess = Start-OAuthDeployment

if ($deploymentSuccess) {
    Write-ColorText "" -Color White
    Write-ColorText "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!" -Color Green
    Write-ColorText "ETAP_03: System Autoryzacji - FINAL COMPLETION" -Color Green
    exit 0
} else {
    Write-ColorText "" -Color White
    Write-ColorText "💥 DEPLOYMENT FAILED!" -Color Red
    Write-ColorText "Check the error messages above for details." -Color Red
    exit 1
}