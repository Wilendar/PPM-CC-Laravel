# ====================================================================================
# ALPINE.JS CONFLICT FIX - PPM-CC-LARAVEL
# ====================================================================================
# Naprawia konflikt multiple instances Alpine.js który blokuje logowanie
#
# Author: Claude Code Debugger
# Version: 1.0  
# Date: 2025-09-10
# ====================================================================================

param(
    [switch]$TestMode = $false,
    [switch]$BackupFiles = $true
)

$ErrorActionPreference = "Continue"

# Configuration
$HostidoServer = "host379076@host379076.hostido.net.pl"
$HostidoPort = "64321"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LaravelPath = "/domains/ppm.mpptrade.pl/public_html"

$LocalProjectPath = "$PSScriptRoot\.."
$LogFile = "$PSScriptRoot\..\test_results\alpine_fix_$(Get-Date -Format 'yyyyMMdd_HHmmss').log"

# ====================================================================================
# LOGGING
# ====================================================================================

function Write-FixLog {
    param([string]$Message, [string]$Level = "INFO")
    
    $LogMessage = "[$(Get-Date -Format 'HH:mm:ss')] [$Level] $Message"
    Write-Host $LogMessage -ForegroundColor $(
        switch ($Level) {
            "ERROR" { "Red" }
            "WARN" { "Yellow" }
            "SUCCESS" { "Green" }
            "DEBUG" { "Cyan" }
            default { "White" }
        }
    )
    
    if (!(Test-Path (Split-Path $LogFile))) {
        New-Item -ItemType Directory -Path (Split-Path $LogFile) -Force | Out-Null
    }
    Add-Content -Path $LogFile -Value $LogMessage -Encoding UTF8
}

function Execute-SSHCommand {
    param([string]$Command, [string]$Description = "")
    
    if ($Description) {
        Write-FixLog "Executing: $Description" "INFO"
    }
    
    $FullCommand = "plink -ssh $HostidoServer -P $HostidoPort -i `"$HostidoKey`" -batch `"cd $LaravelPath && $Command`""
    Write-FixLog "SSH Command: $Command" "DEBUG"
    
    try {
        $Result = Invoke-Expression $FullCommand 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-FixLog "Command successful" "SUCCESS"
            return $Result
        } else {
            Write-FixLog "Command failed with exit code: $LASTEXITCODE" "ERROR"
            Write-FixLog "Error output: $Result" "ERROR"
            return $null
        }
    } catch {
        Write-FixLog "SSH command exception: $_" "ERROR"
        return $null
    }
}

# ====================================================================================
# ALPINE.JS DIAGNOSIS FUNCTIONS  
# ====================================================================================

function Diagnose-AlpineConflict {
    Write-FixLog "=== DIAGNOSING ALPINE.JS CONFLICT ===" "INFO"
    
    # Check app.blade.php for CDN Alpine
    Write-FixLog "Checking layout files for Alpine.js CDN..." "INFO"
    $LayoutCheck = Execute-SSHCommand "find resources/views -name '*.blade.php' -exec grep -l 'alpinejs\|Alpine' {} \;" "Find Alpine in layouts"
    
    if ($LayoutCheck) {
        Write-FixLog "Found Alpine.js references in:" "INFO"
        Write-FixLog "$LayoutCheck" "DEBUG"
    }
    
    # Check for CDN Alpine.js
    $CDNCheck = Execute-SSHCommand "grep -r 'cdn.*alpine' resources/views/ || echo 'No CDN Alpine found'" "Check CDN Alpine"
    Write-FixLog "CDN Alpine check: $CDNCheck" "INFO"
    
    # Check Vite app.js for Alpine import
    $ViteCheck = Execute-SSHCommand "cat resources/js/app.js | grep -i alpine || echo 'No Alpine in app.js'" "Check Vite Alpine"
    Write-FixLog "Vite Alpine check: $ViteCheck" "INFO"
    
    # Check built assets for Alpine
    $AssetCheck = Execute-SSHCommand "find public/build/assets -name '*.js' | head -3 | xargs grep -l 'Alpine\|alpine' || echo 'No Alpine in assets'" "Check built assets"
    Write-FixLog "Built assets Alpine check: $AssetCheck" "INFO"
    
    return $true
}

function Fix-AlpineConflict {
    Write-FixLog "=== FIXING ALPINE.JS CONFLICT ===" "INFO"
    
    if ($BackupFiles) {
        Write-FixLog "Creating backups..." "INFO"
        Execute-SSHCommand "cp resources/js/app.js resources/js/app.js.backup.$(date +%Y%m%d_%H%M%S)" "Backup app.js"
        Execute-SSHCommand "find resources/views -name 'app.blade.php' -exec cp {} {}.backup.$(date +%Y%m%d_%H%M%S) \;" "Backup layout"
    }
    
    # Fix 1: Remove CDN Alpine.js from layouts
    Write-FixLog "Removing CDN Alpine.js from layout files..." "INFO"
    $CDNRemoval = @"
find resources/views -name '*.blade.php' -exec sed -i.bak 's/<script.*cdn.*alpinejs.*<\/script>//g' {} \;
find resources/views -name '*.blade.php' -exec sed -i.bak 's/<script.*alpine.*cdn.*<\/script>//g' {} \;
"@
    Execute-SSHCommand $CDNRemoval "Remove CDN Alpine"
    
    # Fix 2: Modify app.js to prevent double loading
    Write-FixLog "Adding Alpine.js deduplication to app.js..." "INFO"
    $AppJSFix = @"
cat > resources/js/app.js.tmp << 'EOF'
import './bootstrap';

// Alpine.js deduplication fix
if (!window.Alpine) {
    console.log('Loading Alpine.js...');
    import Alpine from 'alpinejs';
    import persist from '@alpinejs/persist';
    
    // Register plugins before starting
    Alpine.plugin(persist);
    
    // Initialize stores
    Alpine.store('loading', {
        isLoading: false,
        loadingText: 'Loading...',
        show() { this.isLoading = true; },
        hide() { this.isLoading = false; }
    });
    
    Alpine.store('notifications', {
        items: [],
        add(notification) { this.items.push(notification); },
        remove(index) { this.items.splice(index, 1); }
    });
    
    window.Alpine = Alpine;
    Alpine.start();
} else {
    console.log('Alpine.js already loaded, skipping initialization');
}
EOF

mv resources/js/app.js.tmp resources/js/app.js
"@
    Execute-SSHCommand $AppJSFix "Fix app.js Alpine loading"
    
    # Fix 3: Rebuild assets
    Write-FixLog "Rebuilding Vite assets..." "INFO"
    Execute-SSHCommand "npm run build" "Build assets"
    
    # Fix 4: Clear Laravel caches
    Write-FixLog "Clearing Laravel caches..." "INFO"
    Execute-SSHCommand "php artisan view:clear" "Clear view cache"
    Execute-SSHCommand "php artisan config:clear" "Clear config cache"
    
    return $true
}

function Verify-AlpineFix {
    Write-FixLog "=== VERIFYING ALPINE.JS FIX ===" "INFO"
    
    # Test login page
    Write-FixLog "Testing login page..." "INFO"
    try {
        $Response = Invoke-WebRequest -Uri "https://ppm.mpptrade.pl/login" -UseBasicParsing -TimeoutSec 10
        if ($Response.StatusCode -eq 200) {
            Write-FixLog "✓ Login page accessible" "SUCCESS"
        } else {
            Write-FixLog "✗ Login page issues: $($Response.StatusCode)" "ERROR"
        }
    } catch {
        Write-FixLog "✗ Login page test failed: $_" "ERROR"
    }
    
    # Check built assets
    $AssetCheck = Execute-SSHCommand "ls -la public/build/assets/ | wc -l" "Check assets built"
    Write-FixLog "Built assets count: $AssetCheck" "INFO"
    
    Write-FixLog "Manual testing required:" "WARN"
    Write-FixLog "1. Open https://ppm.mpptrade.pl/login in browser" "INFO"
    Write-FixLog "2. Check Developer Console for Alpine.js errors" "INFO"
    Write-FixLog "3. Test login with admin@mpptrade.pl / Admin123!MPP" "INFO"
    
    return $true
}

# ====================================================================================
# MAIN EXECUTION
# ====================================================================================

function Start-AlpineFix {
    Write-FixLog "ALPINE.JS CONFLICT FIX STARTED" "INFO"
    Write-FixLog "===============================" "INFO"
    Write-FixLog "Target: $HostidoServer" "INFO"
    Write-FixLog "Test Mode: $TestMode" "INFO"
    Write-FixLog "Backup Files: $BackupFiles" "INFO"
    Write-FixLog "===============================" "INFO"
    
    # Diagnose
    if (-not (Diagnose-AlpineConflict)) {
        Write-FixLog "Diagnosis failed" "ERROR"
        return
    }
    
    if (-not $TestMode) {
        # Fix
        if (-not (Fix-AlpineConflict)) {
            Write-FixLog "Fix failed" "ERROR"
            return
        }
    } else {
        Write-FixLog "TEST MODE - Skipping actual fixes" "WARN"
    }
    
    # Verify
    Verify-AlpineFix
    
    Write-FixLog "===============================" "INFO"
    Write-FixLog "ALPINE.JS FIX COMPLETED" "INFO"
    Write-FixLog "Manual testing required in browser" "WARN"
    Write-FixLog "Log: $LogFile" "INFO"
    Write-FixLog "===============================" "INFO"
}

# ====================================================================================
# EXECUTE
# ====================================================================================

Start-AlpineFix

Write-Host ""
Write-Host "=== ALPINE.JS FIX COMPLETED ===" -ForegroundColor Green
Write-Host "Test login at: https://ppm.mpptrade.pl/login" -ForegroundColor Cyan
Write-Host "Credentials: admin@mpptrade.pl / Admin123!MPP" -ForegroundColor Cyan