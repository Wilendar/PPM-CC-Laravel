# PowerShell Script - FINAL LARAVEL ROUTE FIX for Livewire
# FILE: final_livewire_route_fix.ps1
# PURPOSE: Add Laravel route to handle /vendor/livewire/* URLs properly

param(
    [switch]$Test = $false
)

$Host.UI.RawUI.WindowTitle = "PPM-CC-Laravel - FINAL Laravel Route Fix"

# Configuration
$RemoteHost = "host379076.hostido.net.pl" 
$RemotePort = 64321
$RemoteUser = "host379076"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "🎯 FINAL LARAVEL ROUTE FIX - Livewire Assets" -ForegroundColor Green
Write-Host "Solution: Add Laravel route to handle /vendor/livewire/* URLs" -ForegroundColor Yellow
Write-Host ""

function Create-LivewireRoute {
    Write-Host "📝 Creating Livewire route fix..." -ForegroundColor Yellow
    
    # Create Laravel route that serves Livewire assets
    $LivewireRouteCode = @"
<?php

// LIVEWIRE ASSETS ROUTE FIX for Shared Hosting
// This route handles /vendor/livewire/* URLs that Laravel hijacks

use Illuminate\Support\Facades\Route;

// Route to serve Livewire assets from published public directory
Route::get('/vendor/livewire/{asset}', function ($asset) {
    $allowedAssets = ['livewire.min.js', 'livewire.js', 'livewire.esm.js'];
    
    if (!in_array($asset, $allowedAssets)) {
        abort(404);
    }
    
    $assetPath = public_path("vendor/livewire/{$asset}");
    
    if (!file_exists($assetPath)) {
        abort(404);
    }
    
    $mimeType = $asset === 'livewire.min.js' || $asset === 'livewire.js' || $asset === 'livewire.esm.js' 
        ? 'application/javascript' 
        : 'text/plain';
    
    return response()->file($assetPath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000', // 1 year cache
        'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT'
    ]);
})->where('asset', '(livewire\.min\.js|livewire\.js|livewire\.esm\.js)');

// Alternative route that ignores query parameters (like ?id=df3a17f2)
Route::get('/vendor/livewire/livewire.min.js', function () {
    $assetPath = public_path('vendor/livewire/livewire.min.js');
    
    if (!file_exists($assetPath)) {
        abort(404);
    }
    
    return response()->file($assetPath, [
        'Content-Type' => 'application/javascript; charset=utf-8',
        'Cache-Control' => 'public, max-age=31536000',
        'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT'
    ]);
});
"@

    $LivewireRouteCode | Out-File -FilePath "livewire_route_fix.php" -Encoding UTF8
    Write-Host "✅ Livewire route code created" -ForegroundColor Green
}

function Deploy-LivewireRoute {
    Write-Host "🚀 Deploying Livewire route fix to server..." -ForegroundColor Yellow
    
    $Commands = @(
        # 1. Upload the route fix file
        "scp -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" livewire_route_fix.php $RemoteUser@$RemoteHost`:$RemotePath/livewire_route_fix.php",
        
        # 2. Backup current web routes
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && cp routes/web.php routes/web.php.backup.livewire`"",
        
        # 3. Add the route to web.php
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && echo '' >> routes/web.php && cat livewire_route_fix.php >> routes/web.php`"",
        
        # 4. Clear all Laravel caches
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && php artisan route:clear && php artisan cache:clear && php artisan config:clear`"",
        
        # 5. List Laravel routes to verify
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && php artisan route:list | grep vendor || echo 'No vendor routes found'`""
    )
    
    foreach ($command in $Commands) {
        Write-Host "Executing: $($command.Split(' ')[0])..." -ForegroundColor Cyan
        
        try {
            $result = Invoke-Expression $command
            if ($result) {
                Write-Host $result -ForegroundColor Gray
            }
            Write-Host "✅ Success" -ForegroundColor Green
        } catch {
            Write-Host "⚠️ Error: $($_.Exception.Message)" -ForegroundColor Red
        }
        
        Start-Sleep -Milliseconds 1000
    }
}

function Test-LivewireRoutefix {
    Write-Host "🧪 TESTING LARAVEL ROUTE FIX..." -ForegroundColor Yellow
    
    $CriticalUrls = @(
        "https://ppm.mpptrade.pl/vendor/livewire/livewire.min.js",
        "https://ppm.mpptrade.pl/vendor/livewire/livewire.min.js?id=df3a17f2"
    )
    
    foreach ($url in $CriticalUrls) {
        Write-Host ""
        Write-Host "🚨 CRITICAL TEST: $url" -ForegroundColor Red
        
        try {
            $response = Invoke-WebRequest -Uri $url -UseBasicParsing
            
            $statusCode = $response.StatusCode
            $contentType = $response.Headers['Content-Type']
            $contentLength = $response.Content.Length
            $isJavaScript = $response.Content.StartsWith("(()=>") -or $response.Content.Contains("livewire") -and $response.Content.Contains("function")
            
            Write-Host "  Status: $statusCode" -ForegroundColor Gray
            Write-Host "  Content-Type: $contentType" -ForegroundColor Gray  
            Write-Host "  Content-Length: $contentLength bytes" -ForegroundColor Gray
            
            if ($contentType -like "*javascript*" -and $isJavaScript) {
                Write-Host "  ✅ SUCCESS: Proper JavaScript response!" -ForegroundColor Green
            } else {
                Write-Host "  ❌ FAILED: Not proper JavaScript" -ForegroundColor Red
                $preview = $response.Content.Substring(0, [Math]::Min(150, $response.Content.Length))
                Write-Host "  Preview: $preview" -ForegroundColor Yellow
            }
            
        } catch {
            Write-Host "  💥 ERROR: $($_.Exception.Message)" -ForegroundColor Red
        }
    }
}

function Test-BrowserLogin {
    Write-Host ""
    Write-Host "🌐 FINAL BROWSER TEST:" -ForegroundColor Yellow
    Write-Host "===========================================" -ForegroundColor Gray
    Write-Host ""
    Write-Host "NOW TRY THIS:" -ForegroundColor Green
    Write-Host "1. 🗑️ Clear browser cache completely (Ctrl+Shift+Delete)" -ForegroundColor White
    Write-Host "2. 🕵️ Open private/incognito window" -ForegroundColor White
    Write-Host "3. 🌐 Go to: https://ppm.mpptrade.pl/login" -ForegroundColor Cyan
    Write-Host "4. 🔧 Open Developer Tools (F12)" -ForegroundColor White
    Write-Host "5. 🔄 Reload page and check Network tab:" -ForegroundColor White
    Write-Host "   - livewire.min.js should be 200 OK" -ForegroundColor Green
    Write-Host "   - Content-Type: application/javascript" -ForegroundColor Green
    Write-Host "6. 📋 Check Console tab - NO 'Unexpected token' errors" -ForegroundColor White
    Write-Host "7. 🔐 Try login: admin@mpptrade.pl / Admin123!MPP" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "If login WORKS:" -ForegroundColor Green
    Write-Host "🎉 SUCCESS! Livewire is now working properly." -ForegroundColor Green
    Write-Host ""
    Write-Host "If login still FAILS:" -ForegroundColor Red
    Write-Host "❓ Check exact error in browser console and report back." -ForegroundColor Yellow
}

# Main execution flow
try {
    if ($Test) {
        Test-LivewireRoutefix
        Test-BrowserLogin
        exit
    }
    
    Write-Host "🚀 Starting FINAL Laravel Route Fix..." -ForegroundColor Green
    
    # Step 1: Create the route fix
    Create-LivewireRoute
    
    # Step 2: Deploy to server
    Deploy-LivewireRoute
    
    # Step 3: Wait for changes
    Write-Host "⏳ Waiting for route changes to take effect..." -ForegroundColor Yellow
    Start-Sleep -Seconds 3
    
    # Step 4: Test the fix
    Test-LivewireRoutefix
    
    # Step 5: Browser test instructions
    Test-BrowserLogin
    
    # Cleanup
    Remove-Item "livewire_route_fix.php" -Force -ErrorAction SilentlyContinue
    
    Write-Host ""
    Write-Host "🏁 FINAL LARAVEL ROUTE FIX COMPLETED!" -ForegroundColor Green
    Write-Host "This should be the ultimate solution for Livewire assets routing." -ForegroundColor Yellow
    
} catch {
    Write-Host "💥 Script failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}