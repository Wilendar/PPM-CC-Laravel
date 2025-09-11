# PPM-CC-Laravel: Deploy Welcome & Login Pages
# Deploy nowych stron welcome i login na hostido.net.pl
# Autor: Kamil Wilinski
# Data: 2025-09-10

param(
    [string]$Action = "upload",
    [switch]$Force,
    [switch]$Test
)

# === KONFIGURACJA ===
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

# Kolory dla lepszej czytelnosci
function Write-ColorText {
    param(
        [string]$Text,
        [string]$Color = "White"
    )
    Write-Host $Text -ForegroundColor $Color
}

function Write-Success { param([string]$Text) Write-Host "✅ $Text" -ForegroundColor Green }
function Write-Error { param([string]$Text) Write-Host "❌ $Text" -ForegroundColor Red }
function Write-Warning { param([string]$Text) Write-Host "⚠️  $Text" -ForegroundColor Yellow }
function Write-Info { param([string]$Text) Write-Host "ℹ️  $Text" -ForegroundColor Cyan }
function Write-Step { param([string]$Text) Write-Host "🔄 $Text" -ForegroundColor Magenta }

# === SPRAWDZENIE POLACZEN ===
function Test-SSHConnection {
    Write-Step "Testing SSH connection to Hostido..."
    
    try {
        $testResult = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "echo 'SSH Connection OK'"
        if ($testResult -match "SSH Connection OK") {
            Write-Success "SSH connection established successfully"
            return $true
        }
    }
    catch {
        Write-Error "SSH connection failed: $($_.Exception.Message)"
        return $false
    }
    
    return $false
}

# === BACKUP ISTNIEJACYCH PLIKOW ===
function Backup-RemoteFiles {
    Write-Step "Creating backup of existing files..."
    
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupPath = "$RemotePath/backups/welcome_login_$timestamp"
    
    # Tworzenie katalogu backupu
    $createBackupDir = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "mkdir -p $backupPath"
    
    # Backup welcome.blade.php jeśli istnieje
    $backupWelcome = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "if [ -f '$RemotePath/resources/views/welcome.blade.php' ]; then cp '$RemotePath/resources/views/welcome.blade.php' '$backupPath/welcome.blade.php.bak'; echo 'Welcome backed up'; fi"
    
    # Backup login.blade.php
    $backupLogin = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "if [ -f '$RemotePath/resources/views/auth/login.blade.php' ]; then cp '$RemotePath/resources/views/auth/login.blade.php' '$backupPath/login.blade.php.bak'; echo 'Login backed up'; fi"
    
    Write-Success "Backup created in: $backupPath"
}

# === UPLOAD PLIKOW ===
function Upload-ViewFiles {
    Write-Step "Uploading new view files..."
    
    # Upload welcome.blade.php
    Write-Info "Uploading welcome.blade.php..."
    pscp -scp -P $HostidoPort -i $HostidoKey "$LocalPath\resources\views\welcome.blade.php" "$HostidoHost`:$RemotePath/resources/views/"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Success "welcome.blade.php uploaded successfully"
    } else {
        Write-Error "Failed to upload welcome.blade.php"
        return $false
    }
    
    # Upload login.blade.php
    Write-Info "Uploading login.blade.php..."
    pscp -scp -P $HostidoPort -i $HostidoKey "$LocalPath\resources\views\auth\login.blade.php" "$HostidoHost`:$RemotePath/resources/views/auth/"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Success "login.blade.php uploaded successfully"
    } else {
        Write-Error "Failed to upload login.blade.php"
        return $false
    }
    
    return $true
}

# === CZYSZCZENIE CACHE ===
function Clear-LaravelCache {
    Write-Step "Clearing Laravel cache..."
    
    # Clear view cache
    $clearViews = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear"
    Write-Info "View cache: $clearViews"
    
    # Clear config cache
    $clearConfig = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan config:clear"
    Write-Info "Config cache: $clearConfig"
    
    # Clear route cache
    $clearRoutes = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $RemotePath && php artisan route:clear"
    Write-Info "Route cache: $clearRoutes"
    
    Write-Success "Laravel cache cleared"
}

# === SPRAWDZENIE WDROZENIA ===
function Test-Deployment {
    Write-Step "Testing deployment..."
    
    # Test strony glownej
    try {
        $homeResponse = curl -s -o /dev/null -w "%{http_code}" "https://ppm.mpptrade.pl/"
        if ($homeResponse -eq "200") {
            Write-Success "Home page (/) is accessible"
        } else {
            Write-Warning "Home page returned HTTP $homeResponse"
        }
    }
    catch {
        Write-Warning "Could not test home page"
    }
    
    # Test strony logowania
    try {
        $loginResponse = curl -s -o /dev/null -w "%{http_code}" "https://ppm.mpptrade.pl/login"
        if ($loginResponse -eq "200") {
            Write-Success "Login page (/login) is accessible"
        } else {
            Write-Warning "Login page returned HTTP $loginResponse"
        }
    }
    catch {
        Write-Warning "Could not test login page"
    }
}

# === GŁÓWNY WORKFLOW ===
function Deploy-WelcomeLoginPages {
    Write-ColorText "🚀 PPM-CC-Laravel: Deploying Welcome & Login Pages" "Yellow"
    Write-ColorText "=================================================" "Yellow"
    
    # 1. Test SSH connection
    if (-not (Test-SSHConnection)) {
        Write-Error "Cannot establish SSH connection. Deployment aborted."
        exit 1
    }
    
    # 2. Sprawdzenie lokalnych plikow
    Write-Step "Checking local files..."
    
    $welcomeFile = "$LocalPath\resources\views\welcome.blade.php"
    $loginFile = "$LocalPath\resources\views\auth\login.blade.php"
    
    if (-not (Test-Path $welcomeFile)) {
        Write-Error "welcome.blade.php not found at: $welcomeFile"
        exit 1
    }
    
    if (-not (Test-Path $loginFile)) {
        Write-Error "login.blade.php not found at: $loginFile"
        exit 1
    }
    
    Write-Success "Local files verified"
    
    # 3. Backup istniejących plików
    if (-not $Force) {
        Backup-RemoteFiles
    }
    
    # 4. Upload nowych plików
    if (-not (Upload-ViewFiles)) {
        Write-Error "Upload failed. Deployment aborted."
        exit 1
    }
    
    # 5. Clear Laravel cache
    Clear-LaravelCache
    
    # 6. Test deployment
    if ($Test) {
        Start-Sleep -Seconds 3
        Test-Deployment
    }
    
    Write-ColorText ""
    Write-Success "🎉 Welcome & Login Pages deployed successfully!"
    Write-Info "🌐 Test URLs:"
    Write-Info "   Home: https://ppm.mpptrade.pl/"
    Write-Info "   Login: https://ppm.mpptrade.pl/login"
    Write-Info "   Admin: https://ppm.mpptrade.pl/admin (test: admin@mpptrade.pl / Admin123!MPP)"
}

# === ROLLBACK FUNCTION ===
function Rollback-Deployment {
    Write-Step "Rolling back deployment..."
    
    $latestBackup = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "ls -t $RemotePath/backups/welcome_login_* | head -1"
    
    if ($latestBackup) {
        Write-Info "Found backup: $latestBackup"
        
        # Restore welcome.blade.php
        plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "if [ -f '$latestBackup/welcome.blade.php.bak' ]; then cp '$latestBackup/welcome.blade.php.bak' '$RemotePath/resources/views/welcome.blade.php'; fi"
        
        # Restore login.blade.php
        plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "if [ -f '$latestBackup/login.blade.php.bak' ]; then cp '$latestBackup/login.blade.php.bak' '$RemotePath/resources/views/auth/login.blade.php'; fi"
        
        Clear-LaravelCache
        
        Write-Success "Rollback completed"
    } else {
        Write-Error "No backup found for rollback"
    }
}

# === EXECUTION ===
switch ($Action.ToLower()) {
    "upload" { Deploy-WelcomeLoginPages }
    "rollback" { Rollback-Deployment }
    "test" { Test-Deployment }
    default {
        Write-ColorText "Usage: .\deploy_welcome_login_pages.ps1 [-Action upload|rollback|test] [-Force] [-Test]"
        Write-ColorText ""
        Write-ColorText "Actions:"
        Write-ColorText "  upload   - Deploy new welcome & login pages (default)"
        Write-ColorText "  rollback - Restore from latest backup"
        Write-ColorText "  test     - Test current deployment"
        Write-ColorText ""
        Write-ColorText "Options:"
        Write-ColorText "  -Force   - Skip backup creation"
        Write-ColorText "  -Test    - Run deployment tests after upload"
    }
}

# === END OF SCRIPT ===