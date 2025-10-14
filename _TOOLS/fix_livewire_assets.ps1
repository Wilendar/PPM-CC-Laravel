# PowerShell Script to Fix Livewire Assets Issue
# FILE: fix_livewire_assets.ps1
# PURPOSE: Fix Livewire JavaScript routing issue on Hostido shared hosting

param(
    [switch]$Test = $false
)

$Host.UI.RawUI.WindowTitle = "PPM-CC-Laravel - Livewire Assets Fix"

# Configuration
$RemoteHost = "host379076.hostido.net.pl"
$RemotePort = 64321
$RemoteUser = "host379076"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "🔧 LIVEWIRE ASSETS FIX - PPM-CC-Laravel" -ForegroundColor Green
Write-Host "Target: https://ppm.mpptrade.pl" -ForegroundColor Yellow
Write-Host "Issue: livewire.min.js returns HTML instead of JavaScript" -ForegroundColor Red
Write-Host ""

function Test-SSHConnection {
    Write-Host "📡 Testing SSH connection..." -ForegroundColor Yellow
    
    # Use PuTTY plink with private key (convert from .ppk if needed)
    $TestCommand = "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"echo 'SSH OK'`""
    
    try {
        $result = Invoke-Expression $TestCommand
        if ($result -match "SSH OK") {
            Write-Host "✅ SSH connection successful" -ForegroundColor Green
            return $true
        } else {
            throw "SSH test failed: $result"
        }
    } catch {
        Write-Host "❌ SSH connection failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "💡 Make sure PuTTY plink is in PATH and SSH key is accessible" -ForegroundColor Yellow
        return $false
    }
}

function Fix-LivewireAssets {
    Write-Host "🔨 Fixing Livewire assets on server..." -ForegroundColor Yellow
    
    $Commands = @(
        # 1. Check current directory and artisan
        "cd $RemotePath && pwd && ls -la artisan",
        
        # 2. Try to publish Livewire assets
        "cd $RemotePath && php artisan livewire:publish --assets",
        
        # 3. Check if assets were published
        "cd $RemotePath && ls -la public/vendor/livewire/",
        
        # 4. If not published, create manual structure
        "cd $RemotePath && mkdir -p public/vendor/livewire",
        
        # 5. Check if vendor/livewire exists
        "cd $RemotePath && ls -la vendor/livewire/livewire/dist/ || echo 'No Livewire dist found'",
        
        # 6. Create .htaccess rule for Livewire routing
        "cd $RemotePath && echo '# Livewire assets routing
RewriteRule ^vendor/livewire/livewire\.min\.js$ /livewire/livewire.min.js [L,R=301]' >> public/.htaccess",

        # 7. Clear all caches
        "cd $RemotePath && php artisan route:clear && php artisan config:clear && php artisan cache:clear"
    )
    
    foreach ($command in $Commands) {
        Write-Host "Executing: $command" -ForegroundColor Cyan
        
        $sshCmd = "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"$command`""
        
        try {
            $result = Invoke-Expression $sshCmd
            Write-Host $result -ForegroundColor Gray
        } catch {
            Write-Host "⚠️ Command failed: $($_.Exception.Message)" -ForegroundColor Red
        }
        
        Start-Sleep -Milliseconds 500
    }
}

function Test-LivewireFix {
    Write-Host "🧪 Testing Livewire fix..." -ForegroundColor Yellow
    
    # Test the problematic URL
    $LivewireURL = "https://ppm.mpptrade.pl/vendor/livewire/livewire.min.js?id=df3a17f2"
    
    try {
        $response = Invoke-WebRequest -Uri $LivewireURL -UseBasicParsing
        
        Write-Host "Status Code: $($response.StatusCode)" -ForegroundColor Cyan
        Write-Host "Content-Type: $($response.Headers['Content-Type'])" -ForegroundColor Cyan
        
        if ($response.Headers['Content-Type'] -like "*javascript*") {
            Write-Host "✅ SUCCESS: Livewire.js now returns JavaScript!" -ForegroundColor Green
        } else {
            Write-Host "❌ STILL BROKEN: Content-Type is not JavaScript" -ForegroundColor Red
            Write-Host "First 200 chars of response:" -ForegroundColor Yellow
            Write-Host $response.Content.Substring(0, [Math]::Min(200, $response.Content.Length)) -ForegroundColor Gray
        }
        
    } catch {
        Write-Host "❌ Failed to test URL: $($_.Exception.Message)" -ForegroundColor Red
    }
}

function Test-BrowserLogin {
    Write-Host "🌐 Testing browser login functionality..." -ForegroundColor Yellow
    
    Write-Host "Manual verification steps:" -ForegroundColor Cyan
    Write-Host "1. Open: https://ppm.mpptrade.pl/login" -ForegroundColor White
    Write-Host "2. Open browser Developer Tools (F12)" -ForegroundColor White
    Write-Host "3. Check Network tab - livewire.min.js should load with 200 status" -ForegroundColor White
    Write-Host "4. Check Console tab - should be NO 'Unexpected token' errors" -ForegroundColor White
    Write-Host "5. Try login: admin@mpptrade.pl / Admin123!MPP" -ForegroundColor White
    Write-Host ""
    
    # Also test login page loads
    try {
        $loginResponse = Invoke-WebRequest -Uri "https://ppm.mpptrade.pl/login" -UseBasicParsing
        if ($loginResponse.StatusCode -eq 200) {
            Write-Host "✅ Login page loads successfully (HTTP 200)" -ForegroundColor Green
        }
    } catch {
        Write-Host "❌ Login page failed to load: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Main execution flow
if ($Test) {
    Write-Host "🧪 RUNNING IN TEST MODE" -ForegroundColor Cyan
    Test-LivewireFix
    Test-BrowserLogin
    exit
}

try {
    # Step 1: Test SSH connection
    if (-not (Test-SSHConnection)) {
        exit 1
    }
    
    # Step 2: Fix Livewire assets
    Fix-LivewireAssets
    
    # Step 3: Test the fix
    Start-Sleep -Seconds 2
    Test-LivewireFix
    
    # Step 4: Manual browser test instructions
    Test-BrowserLogin
    
    Write-Host ""
    Write-Host "🎉 Livewire fix deployment completed!" -ForegroundColor Green
    Write-Host "Please verify manually in browser that login works without JavaScript errors." -ForegroundColor Yellow
    
} catch {
    Write-Host "💥 Script failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}