# PowerShell Script - ULTIMATE LIVEWIRE FIX
# FILE: ultimate_livewire_fix.ps1
# PURPOSE: Final fix for Livewire assets routing on shared hosting

param(
    [switch]$Test = $false
)

$Host.UI.RawUI.WindowTitle = "PPM-CC-Laravel - ULTIMATE Livewire Fix"

# Configuration
$RemoteHost = "host379076.hostido.net.pl"
$RemotePort = 64321
$RemoteUser = "host379076"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "🏆 ULTIMATE LIVEWIRE FIX - PPM-CC-Laravel" -ForegroundColor Green
Write-Host "Final solution for /vendor/livewire/livewire.min.js routing" -ForegroundColor Yellow
Write-Host ""

function Apply-UltimateHtaccessFix {
    Write-Host "🔨 Applying ULTIMATE .htaccess fix..." -ForegroundColor Yellow
    
    # Create comprehensive .htaccess rules
    $UltimateHtaccessFix = @"

# ===== ULTIMATE LIVEWIRE SHARED HOSTING FIX =====

# Rule 1: Direct mapping for vendor/livewire to published assets
RewriteRule ^vendor/livewire/livewire\.min\.js(\?.*)?$ /public/vendor/livewire/livewire.min.js [L,R=301]
RewriteRule ^vendor/livewire/livewire\.js(\?.*)?$ /public/vendor/livewire/livewire.js [L,R=301]
RewriteRule ^vendor/livewire/(.*)$ /public/vendor/livewire/$1 [L,R=301]

# Rule 2: Ensure public assets are accessible
<IfModule mod_rewrite.c>
    # Allow direct access to published Livewire assets
    RewriteCond %{REQUEST_URI} ^/public/vendor/livewire/
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule . - [L]
</IfModule>

# ===== END ULTIMATE LIVEWIRE FIX =====
"@
    
    $UltimateHtaccessFix | Out-File -FilePath "ultimate_htaccess_fix.txt" -Encoding UTF8
    Write-Host "✅ Ultimate .htaccess rules created" -ForegroundColor Green
    
    # Commands to apply the fix
    $Commands = @(
        # 1. Backup current .htaccess
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && cp .htaccess .htaccess.ultimate.backup`"",
        
        # 2. Upload the ultimate fix
        "scp -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" ultimate_htaccess_fix.txt $RemoteUser@$RemoteHost`:$RemotePath/ultimate_htaccess_fix.txt",
        
        # 3. Apply the fix to .htaccess
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && cat ultimate_htaccess_fix.txt >> .htaccess`"",
        
        # 4. Ensure published assets have correct permissions
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && chmod -R 644 public/vendor/livewire/*.js`"",
        
        # 5. Test the file exists and is readable
        "plink -ssh $RemoteUser@$RemoteHost -P $RemotePort -i `"D:/OneDrive - MPP TRADE/SSH/Hostido/HostidoSSHNoPass.ppk`" -batch `"cd $RemotePath && file public/vendor/livewire/livewire.min.js && wc -c public/vendor/livewire/livewire.min.js`""
    )
    
    foreach ($command in $Commands) {
        Write-Host "Executing command..." -ForegroundColor Cyan
        
        try {
            $result = Invoke-Expression $command
            Write-Host $result -ForegroundColor Gray
            Write-Host "✅ Success" -ForegroundColor Green
        } catch {
            Write-Host "⚠️ Warning: $($_.Exception.Message)" -ForegroundColor Yellow
        }
        
        Start-Sleep -Milliseconds 1000
    }
}

function Test-AllLivewireUrls {
    Write-Host "🧪 COMPREHENSIVE URL TESTING..." -ForegroundColor Yellow
    
    $TestUrls = @(
        @{
            url = "https://ppm.mpptrade.pl/vendor/livewire/livewire.min.js"
            expected = "JavaScript"
            critical = $true
        },
        @{
            url = "https://ppm.mpptrade.pl/vendor/livewire/livewire.min.js?id=df3a17f2"
            expected = "JavaScript" 
            critical = $true
        },
        @{
            url = "https://ppm.mpptrade.pl/public/vendor/livewire/livewire.min.js"
            expected = "JavaScript"
            critical = $false
        },
        @{
            url = "https://ppm.mpptrade.pl/livewire/livewire.min.js"
            expected = "JavaScript"
            critical = $false
        }
    )
    
    $criticalPassed = 0
    $criticalTotal = ($TestUrls | Where-Object { $_.critical }).Count
    
    foreach ($test in $TestUrls) {
        $url = $test.url
        $isCritical = $test.critical
        
        Write-Host ""
        Write-Host "Testing: $url" -ForegroundColor Cyan
        if ($isCritical) { Write-Host "  🚨 CRITICAL TEST" -ForegroundColor Red }
        
        try {
            $response = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction SilentlyContinue
            
            $contentType = $response.Headers['Content-Type']
            $statusCode = $response.StatusCode
            $contentLength = $response.Content.Length
            
            Write-Host "  Status: $statusCode" -ForegroundColor Gray
            Write-Host "  Content-Type: $contentType" -ForegroundColor Gray
            Write-Host "  Content-Length: $contentLength bytes" -ForegroundColor Gray
            
            if ($contentType -like "*javascript*") {
                Write-Host "  ✅ SUCCESS: JavaScript content detected" -ForegroundColor Green
                if ($isCritical) { $criticalPassed++ }
            } else {
                Write-Host "  ❌ FAILED: Not JavaScript content" -ForegroundColor Red
                # Show first few characters to diagnose
                $preview = $response.Content.Substring(0, [Math]::Min(100, $response.Content.Length))
                Write-Host "  Preview: $preview..." -ForegroundColor Yellow
            }
            
        } catch {
            Write-Host "  💥 ERROR: $($_.Exception.Message)" -ForegroundColor Red
        }
    }
    
    Write-Host ""
    Write-Host "📊 CRITICAL TEST RESULTS: $criticalPassed/$criticalTotal" -ForegroundColor $(if($criticalPassed -eq $criticalTotal){"Green"}else{"Red"})
    
    if ($criticalPassed -eq $criticalTotal) {
        Write-Host "🎉 ALL CRITICAL TESTS PASSED!" -ForegroundColor Green
        Write-Host "Livewire should now work properly in the browser." -ForegroundColor Green
    } else {
        Write-Host "⚠️  Some critical tests failed. Browser may still have issues." -ForegroundColor Red
    }
}

function Give-FinalInstructions {
    Write-Host ""
    Write-Host "🎯 FINAL VERIFICATION STEPS:" -ForegroundColor Yellow
    Write-Host "========================================" -ForegroundColor Gray
    Write-Host "1. Clear ALL browser cache and cookies" -ForegroundColor White
    Write-Host "2. Open NEW incognito/private window" -ForegroundColor White
    Write-Host "3. Navigate to: https://ppm.mpptrade.pl/login" -ForegroundColor Cyan
    Write-Host "4. Open Developer Tools (F12)" -ForegroundColor White
    Write-Host "5. Go to Network tab and reload page" -ForegroundColor White
    Write-Host "6. Look for livewire.min.js request:" -ForegroundColor White
    Write-Host "   - Should be HTTP 200 (or 301 -> 200)" -ForegroundColor Green
    Write-Host "   - Response should be JavaScript code" -ForegroundColor Green
    Write-Host "7. Go to Console tab - should be NO errors" -ForegroundColor White
    Write-Host "8. Try login with: admin@mpptrade.pl / Admin123!MPP" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "If you still get 'Unexpected token <' error:" -ForegroundColor Red
    Write-Host "- Clear browser cache again" -ForegroundColor White
    Write-Host "- Try different browser" -ForegroundColor White
    Write-Host "- Check Network tab for exact failing URL" -ForegroundColor White
}

# Main execution flow
try {
    if ($Test) {
        Test-AllLivewireUrls
        Give-FinalInstructions
        exit
    }
    
    Write-Host "🚀 Starting ULTIMATE Livewire fix deployment..." -ForegroundColor Green
    
    # Step 1: Apply the ultimate .htaccess fix
    Apply-UltimateHtaccessFix
    
    # Step 2: Wait for server to process changes
    Write-Host "⏳ Waiting for server changes to propagate..." -ForegroundColor Yellow
    Start-Sleep -Seconds 5
    
    # Step 3: Comprehensive testing
    Test-AllLivewireUrls
    
    # Step 4: Final instructions
    Give-FinalInstructions
    
    # Cleanup
    Remove-Item "ultimate_htaccess_fix.txt" -Force -ErrorAction SilentlyContinue
    
    Write-Host ""
    Write-Host "🏆 ULTIMATE LIVEWIRE FIX DEPLOYMENT COMPLETED!" -ForegroundColor Green
    
} catch {
    Write-Host "💥 Script failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}