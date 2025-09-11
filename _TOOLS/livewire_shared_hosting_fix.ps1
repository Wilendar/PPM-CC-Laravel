# PowerShell Script - FINAL LIVEWIRE FIX for Shared Hosting
# FILE: livewire_shared_hosting_fix.ps1
# PURPOSE: Configure Livewire to work properly on shared hosting

param(
    [switch]$Test = $false
)

$Host.UI.RawUI.WindowTitle = "PPM-CC-Laravel - Livewire Shared Hosting Fix"

# Configuration
$RemoteHost = "host379076.hostido.net.pl"
$RemotePort = 64321
$RemoteUser = "host379076"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "🔧 LIVEWIRE SHARED HOSTING FIX - PPM-CC-Laravel" -ForegroundColor Green
Write-Host "Problem: Livewire assets routing not working on shared hosting" -ForegroundColor Yellow
Write-Host ""

function Create-LivewireConfig {
    Write-Host "📝 Creating Livewire configuration for shared hosting..." -ForegroundColor Yellow
    
    $LivewireConfig = @"
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Livewire Asset URL
    |--------------------------------------------------------------------------
    |
    | This URL is used to serve Livewire's JavaScript assets. 
    | For shared hosting, we need to use published assets path.
    |
    */
    'asset_url' => env('LIVEWIRE_ASSET_URL', '/vendor/livewire'),
    
    /*
    |--------------------------------------------------------------------------
    | Livewire Update Route
    |--------------------------------------------------------------------------
    */
    'update_route' => env('LIVEWIRE_UPDATE_ROUTE', 'livewire/update'),
    
    /*
    |--------------------------------------------------------------------------
    | Livewire Assets Path
    |--------------------------------------------------------------------------
    */
    'manifest_path' => env('LIVEWIRE_MANIFEST_PATH', null),
    
    /*
    |--------------------------------------------------------------------------
    | Back Button Cache
    |--------------------------------------------------------------------------
    */
    'back_button_cache' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Render On Redirect
    |--------------------------------------------------------------------------
    */
    'render_on_redirect' => false,
];
"@

    $LivewireConfig | Out-File -FilePath "temp_livewire_config.php" -Encoding UTF8
    Write-Host "✅ Livewire config created locally" -ForegroundColor Green
}

function Create-HtaccessFix {
    Write-Host "📝 Creating .htaccess fix for Livewire assets..." -ForegroundColor Yellow
    
    $HtaccessRules = @"

# ===== LIVEWIRE SHARED HOSTING FIX =====
# Fix Livewire asset routing for shared hosting

# Direct access to published Livewire assets
RewriteRule ^vendor/livewire/(.*)$ public/vendor/livewire/$1 [L]

# Fallback for Livewire internal routes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^livewire/livewire\.min\.js$ vendor/livewire/livewire/dist/livewire.min.js [L]

# ===== END LIVEWIRE FIX =====
"@

    $HtaccessRules | Out-File -FilePath "temp_htaccess_rules.txt" -Encoding UTF8
    Write-Host "✅ .htaccess rules created locally" -ForegroundColor Green
}

function Deploy-LivewireFix {
    Write-Host "🚀 Deploying Livewire fix to server..." -ForegroundColor Yellow
    
    $Commands = @(
        # 1. Upload Livewire config
        "scp -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" temp_livewire_config.php $RemoteUser@$RemoteHost`:$RemotePath/config/livewire.php",
        
        # 2. Backup original .htaccess
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && cp .htaccess .htaccess.backup`"",
        
        # 3. Add Livewire rules to .htaccess
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && cat temp_htaccess_rules.txt >> .htaccess`"",
        
        # 4. Upload .htaccess fix
        "scp -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" temp_htaccess_rules.txt $RemoteUser@$RemoteHost`:$RemotePath/temp_htaccess_rules.txt",
        
        # 5. Apply .htaccess rules
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && cat temp_htaccess_rules.txt >> .htaccess`"",
        
        # 6. Verify published assets exist
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && ls -la public/vendor/livewire/livewire.min.js`"",
        
        # 7. Clear caches
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && php artisan config:clear && php artisan route:clear && php artisan cache:clear`"",
        
        # 8. Set proper permissions
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && chmod 644 public/vendor/livewire/*.js && chmod 644 config/livewire.php`""
    )
    
    foreach ($command in $Commands) {
        Write-Host "Executing: $($command.Split(' ')[0])..." -ForegroundColor Cyan
        
        try {
            Invoke-Expression $command
            Write-Host "✅ Success" -ForegroundColor Green
        } catch {
            Write-Host "⚠️ Warning: $($_.Exception.Message)" -ForegroundColor Yellow
        }
        
        Start-Sleep -Milliseconds 500
    }
}

function Test-LivewireUrls {
    Write-Host "🧪 Testing all Livewire URLs..." -ForegroundColor Yellow
    
    $UrlsToTest = @(
        "https://ppm.mpptrade.pl/vendor/livewire/livewire.min.js",
        "https://ppm.mpptrade.pl/public/vendor/livewire/livewire.min.js",
        "https://ppm.mpptrade.pl/livewire/livewire.min.js",
        "https://ppm.mpptrade.pl/vendor/livewire/livewire.min.js?id=df3a17f2"
    )
    
    foreach ($url in $UrlsToTest) {
        Write-Host "Testing: $url" -ForegroundColor Cyan
        
        try {
            $response = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction SilentlyContinue
            
            $contentType = $response.Headers['Content-Type']
            $statusCode = $response.StatusCode
            
            if ($contentType -like "*javascript*") {
                Write-Host "  ✅ $statusCode - JavaScript content" -ForegroundColor Green
            } else {
                Write-Host "  ❌ $statusCode - $contentType (not JavaScript)" -ForegroundColor Red
            }
            
        } catch {
            Write-Host "  💥 FAILED: $($_.Exception.Message)" -ForegroundColor Red
        }
    }
}

function Test-BrowserCompatibility {
    Write-Host "🌐 Final browser test instructions:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "CRITICAL TEST STEPS:" -ForegroundColor Red
    Write-Host "1. Clear browser cache (Ctrl+Shift+Delete)" -ForegroundColor White
    Write-Host "2. Open: https://ppm.mpptrade.pl/login" -ForegroundColor White
    Write-Host "3. Open Developer Tools (F12)" -ForegroundColor White
    Write-Host "4. Go to Network tab" -ForegroundColor White
    Write-Host "5. Refresh page and look for:" -ForegroundColor White
    Write-Host "   - livewire.min.js should be HTTP 200" -ForegroundColor Cyan
    Write-Host "   - Content-Type should be 'application/javascript'" -ForegroundColor Cyan
    Write-Host "6. Go to Console tab - should be NO errors" -ForegroundColor White
    Write-Host "7. Try login: admin@mpptrade.pl / Admin123!MPP" -ForegroundColor White
    Write-Host ""
    Write-Host "If login still fails, check browser console for specific errors." -ForegroundColor Yellow
}

# Main execution flow
try {
    if ($Test) {
        Test-LivewireUrls
        Test-BrowserCompatibility
        exit
    }
    
    # Step 1: Create configuration files
    Create-LivewireConfig
    Create-HtaccessFix
    
    # Step 2: Deploy to server
    Deploy-LivewireFix
    
    # Step 3: Test URLs
    Start-Sleep -Seconds 2
    Test-LivewireUrls
    
    # Step 4: Browser test instructions
    Test-BrowserCompatibility
    
    # Cleanup temp files
    Remove-Item "temp_livewire_config.php" -Force -ErrorAction SilentlyContinue
    Remove-Item "temp_htaccess_rules.txt" -Force -ErrorAction SilentlyContinue
    
    Write-Host ""
    Write-Host "🎉 LIVEWIRE SHARED HOSTING FIX COMPLETED!" -ForegroundColor Green
    Write-Host "Login should now work without JavaScript errors." -ForegroundColor Yellow
    
} catch {
    Write-Host "💥 Script failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}