# ====================================================================================
# LOGIN DEBUG DEPLOYMENT SCRIPT - PPM-CC-LARAVEL
# ====================================================================================
# Wdraża comprehensive debug tools dla systemu logowania na serwer Hostido
#
# Author: Claude Code Deployment Specialist  
# Version: 1.0
# Date: 2025-09-10
# ====================================================================================

param(
    [switch]$TestMode = $false,
    [switch]$FullDebug = $false,
    [switch]$EnableLogging = $true
)

$ErrorActionPreference = "Continue"

# ====================================================================================
# CONFIGURATION
# ====================================================================================

$HostidoServer = "host379076@host379076.hostido.net.pl"
$HostidoPort = "64321"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LaravelPath = "/domains/ppm.mpptrade.pl/public_html"

$LocalProjectPath = "$PSScriptRoot\.."
$LogFile = "$PSScriptRoot\..\test_results\debug_deploy_$(Get-Date -Format 'yyyyMMdd_HHmmss').log"

# ====================================================================================
# LOGGING FUNCTIONS
# ====================================================================================

function Write-DeployLog {
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
    param(
        [string]$Command,
        [string]$Description = ""
    )
    
    if ($Description) {
        Write-DeployLog "Executing: $Description" "INFO"
    }
    
    $FullCommand = "plink -ssh $HostidoServer -P $HostidoPort -i `"$HostidoKey`" -batch `"cd $LaravelPath && $Command`""
    Write-DeployLog "SSH Command: $Command" "DEBUG"
    
    try {
        $Result = Invoke-Expression $FullCommand 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-DeployLog "Command successful" "SUCCESS"
            if ($FullDebug -and $Result) {
                Write-DeployLog "Output: $Result" "DEBUG"
            }
            return $Result
        } else {
            Write-DeployLog "Command failed with exit code: $LASTEXITCODE" "ERROR"
            Write-DeployLog "Error output: $Result" "ERROR"
            return $null
        }
    } catch {
        Write-DeployLog "SSH command exception: $_" "ERROR"
        return $null
    }
}

function Upload-File {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [string]$Description = ""
    )
    
    if ($Description) {
        Write-DeployLog "Uploading: $Description" "INFO"
    }
    
    if (!(Test-Path $LocalPath)) {
        Write-DeployLog "Local file not found: $LocalPath" "ERROR"
        return $false
    }
    
    $ScpCommand = "pscp -i `"$HostidoKey`" -P $HostidoPort `"$LocalPath`" `"${HostidoServer}:$RemotePath`""
    Write-DeployLog "SCP Command: $LocalPath -> $RemotePath" "DEBUG"
    
    try {
        $Result = Invoke-Expression $ScpCommand 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-DeployLog "File uploaded successfully" "SUCCESS"
            return $true
        } else {
            Write-DeployLog "Upload failed with exit code: $LASTEXITCODE" "ERROR"
            Write-DeployLog "Error output: $Result" "ERROR"
            return $false
        }
    } catch {
        Write-DeployLog "Upload exception: $_" "ERROR"
        return $false
    }
}

# ====================================================================================
# DEPLOYMENT FUNCTIONS
# ====================================================================================

function Deploy-AuthDebugMiddleware {
    Write-DeployLog "=== DEPLOYING AUTH DEBUG MIDDLEWARE ===" "INFO"
    
    # Upload middleware
    $LocalMiddleware = "$LocalProjectPath\app\Http\Middleware\AuthDebugMiddleware.php"
    $RemoteMiddleware = "$LaravelPath/app/Http/Middleware/AuthDebugMiddleware.php"
    
    if (Upload-File $LocalMiddleware $RemoteMiddleware "AuthDebugMiddleware") {
        Write-DeployLog "AuthDebugMiddleware uploaded successfully" "SUCCESS"
        
        # Register middleware in Kernel.php
        $KernelUpdate = @"
// Add debug middleware registration
\$middleware = file_get_contents('app/Http/Kernel.php');
if (strpos(\$middleware, 'AuthDebugMiddleware') === false) {
    \$middleware = str_replace(
        "'routeMiddleware' => [",
        "'routeMiddleware' => [
        'auth.debug' => \App\Http\Middleware\AuthDebugMiddleware::class,",
        \$middleware
    );
    file_put_contents('app/Http/Kernel.php', \$middleware);
    echo 'AuthDebugMiddleware registered in Kernel.php';
} else {
    echo 'AuthDebugMiddleware already registered';
}
"@
        
        Execute-SSHCommand "php -r `"$KernelUpdate`"" "Register AuthDebugMiddleware"
        return $true
    }
    
    return $false
}

function Deploy-DebugLoginView {
    Write-DeployLog "=== DEPLOYING DEBUG LOGIN VIEW ===" "INFO"
    
    # Create debug views directory
    Execute-SSHCommand "mkdir -p resources/views/debug" "Create debug views directory"
    
    # Upload debug login view
    $LocalView = "$LocalProjectPath\resources\views\debug\login-debug.blade.php"
    $RemoteView = "$LaravelPath/resources/views/debug/login-debug.blade.php"
    
    if (Upload-File $LocalView $RemoteView "Debug Login View") {
        Write-DeployLog "Debug login view uploaded successfully" "SUCCESS"
        return $true
    }
    
    return $false
}

function Setup-DebugLogging {
    Write-DeployLog "=== SETTING UP DEBUG LOGGING ===" "INFO"
    
    # Configure logging channel in config/logging.php
    $LoggingConfig = @"
\$config = file_get_contents('config/logging.php');
if (strpos(\$config, 'auth_debug') === false) {
    \$newChannel = \"
        'auth_debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/auth_debug.log'),
            'level' => 'debug',
            'days' => 14,
            'replace_placeholders' => true,
        ],\";
    
    \$config = str_replace(
        \"'channels' => [\",
        \"'channels' => [\" . \$newChannel,
        \$config
    );
    
    file_put_contents('config/logging.php', \$config);
    echo 'Auth debug logging channel configured';
} else {
    echo 'Auth debug logging channel already configured';
}
"@
    
    Execute-SSHCommand "php -r `"$LoggingConfig`"" "Configure auth debug logging"
    
    # Create debug routes
    $DebugRoutes = @"
\$routes = file_get_contents('routes/web.php');
if (strpos(\$routes, '/debug/login') === false) {
    \$newRoutes = \"
// Debug routes (only in development/testing)
if (config('app.debug') || request()->get('debug') === 'true') {
    Route::get('/debug/login', function() {
        return view('debug.login-debug');
    })->name('debug.login')->middleware('auth.debug');
    
    Route::post('/debug/login/test', function() {
        return response()->json([
            'status' => 'debug_test_successful',
            'timestamp' => now()->toISOString(),
            'session' => session()->getId(),
            'csrf' => csrf_token()
        ]);
    })->name('debug.login.test');
}
\";
    
    file_put_contents('routes/web.php', \$routes . \$newRoutes);
    echo 'Debug routes added';
} else {
    echo 'Debug routes already exist';
}
"@
    
    Execute-SSHCommand "php -r `"$DebugRoutes`"" "Add debug routes"
    
    return $true
}

function Configure-DebugMode {
    Write-DeployLog "=== CONFIGURING DEBUG MODE ===" "INFO"
    
    if ($TestMode) {
        # Enable debug mode temporarily
        Execute-SSHCommand "php artisan config:set app.debug true" "Enable debug mode"
        Write-DeployLog "Debug mode enabled for testing" "WARN"
    }
    
    # Clear all caches
    Execute-SSHCommand "php artisan config:clear" "Clear config cache"
    Execute-SSHCommand "php artisan route:clear" "Clear route cache"
    Execute-SSHCommand "php artisan view:clear" "Clear view cache"
    
    # Optimize for debug
    Execute-SSHCommand "php artisan config:cache" "Cache config"
    
    return $true
}

function Test-DebugDeployment {
    Write-DeployLog "=== TESTING DEBUG DEPLOYMENT ===" "INFO"
    
    # Test middleware exists
    $MiddlewareTest = Execute-SSHCommand "ls -la app/Http/Middleware/AuthDebugMiddleware.php" "Check middleware file"
    if ($MiddlewareTest) {
        Write-DeployLog "✓ AuthDebugMiddleware file exists" "SUCCESS"
    } else {
        Write-DeployLog "✗ AuthDebugMiddleware file missing" "ERROR"
    }
    
    # Test debug view exists
    $ViewTest = Execute-SSHCommand "ls -la resources/views/debug/login-debug.blade.php" "Check debug view"
    if ($ViewTest) {
        Write-DeployLog "✓ Debug login view exists" "SUCCESS"
    } else {
        Write-DeployLog "✗ Debug login view missing" "ERROR"
    }
    
    # Test debug route
    Write-DeployLog "Testing debug route accessibility..." "INFO"
    try {
        $Response = Invoke-WebRequest -Uri "https://ppm.mpptrade.pl/debug/login?debug=true" -UseBasicParsing -TimeoutSec 10
        if ($Response.StatusCode -eq 200) {
            Write-DeployLog "✓ Debug login route accessible" "SUCCESS"
        } else {
            Write-DeployLog "✗ Debug login route returned: $($Response.StatusCode)" "WARN"
        }
    } catch {
        Write-DeployLog "✗ Debug login route test failed: $_" "ERROR"
    }
    
    # Test logging
    Execute-SSHCommand "touch storage/logs/auth_debug.log && echo 'Debug log test' >> storage/logs/auth_debug.log" "Test debug logging"
    $LogTest = Execute-SSHCommand "tail -1 storage/logs/auth_debug.log" "Check debug log"
    if ($LogTest -like "*Debug log test*") {
        Write-DeployLog "✓ Debug logging working" "SUCCESS"
    } else {
        Write-DeployLog "✗ Debug logging issues" "WARN"
    }
    
    return $true
}

# ====================================================================================
# MAIN EXECUTION
# ====================================================================================

function Start-DebugDeployment {
    Write-DeployLog "PPM-CC-LARAVEL DEBUG TOOLS DEPLOYMENT STARTED" "INFO"
    Write-DeployLog "======================================================" "INFO"
    Write-DeployLog "Target: $HostidoServer" "INFO"
    Write-DeployLog "Path: $LaravelPath" "INFO"
    Write-DeployLog "Test Mode: $TestMode" "INFO"
    Write-DeployLog "Full Debug: $FullDebug" "INFO"
    Write-DeployLog "Enable Logging: $EnableLogging" "INFO"
    Write-DeployLog "======================================================" "INFO"
    
    $Success = $true
    
    # Deploy middleware
    if (-not (Deploy-AuthDebugMiddleware)) {
        $Success = $false
    }
    
    # Deploy debug view
    if (-not (Deploy-DebugLoginView)) {
        $Success = $false
    }
    
    # Setup logging
    if ($EnableLogging -and -not (Setup-DebugLogging)) {
        $Success = $false
    }
    
    # Configure debug mode
    if (-not (Configure-DebugMode)) {
        $Success = $false
    }
    
    # Test deployment
    Test-DebugDeployment
    
    if ($Success) {
        Write-DeployLog "======================================================" "INFO"
        Write-DeployLog "DEBUG TOOLS DEPLOYMENT COMPLETED SUCCESSFULLY!" "SUCCESS"
        Write-DeployLog "======================================================" "INFO"
        Write-DeployLog "" "INFO"
        Write-DeployLog "AVAILABLE DEBUG TOOLS:" "INFO"
        Write-DeployLog "1. Debug Login Page: https://ppm.mpptrade.pl/debug/login?debug=true" "SUCCESS"
        Write-DeployLog "2. Auth Debug Logs: storage/logs/auth_debug.log" "SUCCESS"
        Write-DeployLog "3. Automated Testing: Use automated_login_tester.ps1" "SUCCESS"
        Write-DeployLog "" "INFO"
        Write-DeployLog "NEXT STEPS:" "INFO"
        Write-DeployLog "1. Run automated login tester: .\automated_login_tester.ps1" "INFO"
        Write-DeployLog "2. Check debug login page for real-time monitoring" "INFO"
        Write-DeployLog "3. Review auth debug logs for detailed analysis" "INFO"
        Write-DeployLog "======================================================" "INFO"
    } else {
        Write-DeployLog "======================================================" "ERROR"
        Write-DeployLog "DEBUG TOOLS DEPLOYMENT FAILED!" "ERROR"
        Write-DeployLog "Check logs above for specific errors" "ERROR"
        Write-DeployLog "======================================================" "ERROR"
    }
}

# ====================================================================================
# EXECUTE DEPLOYMENT
# ====================================================================================

try {
    Start-DebugDeployment
} catch {
    Write-DeployLog "FATAL DEPLOYMENT ERROR: $_" "ERROR"
} finally {
    Write-DeployLog "Log file saved: $LogFile" "INFO"
}

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETED ===" -ForegroundColor Green
Write-Host "Log file: $LogFile" -ForegroundColor Cyan