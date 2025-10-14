# PowerShell Script - Browser vs HTTP Debugger
# FILE: browser_vs_http_debugger.ps1  
# PURPOSE: Detailed analysis of HTTP vs Browser request differences for Livewire.js

param(
    [string]$BaseUrl = "https://ppm.mpptrade.pl",
    [switch]$Verbose = $false
)

$Host.UI.RawUI.WindowTitle = "PPM-CC-Laravel - Browser vs HTTP Debugger"

# Configuration
$TestUrls = @(
    "/vendor/livewire/livewire.min.js",
    "/vendor/livewire/livewire.min.js?id=df3a17f2", 
    "/livewire/livewire.min.js",
    "/public/vendor/livewire/livewire.min.js"
)

Write-Host "🔍 BROWSER vs HTTP DEBUGGER - PPM-CC-Laravel" -ForegroundColor Green
Write-Host "Analyzing exact differences between browser and HTTP requests" -ForegroundColor Yellow
Write-Host "Base URL: $BaseUrl" -ForegroundColor Cyan
Write-Host ""

function Test-WithUserAgent {
    param(
        [string]$Url,
        [string]$UserAgent,
        [hashtable]$Headers = @{}
    )
    
    $FullUrl = "$BaseUrl$Url"
    
    try {
        # Add User-Agent to headers
        $TestHeaders = $Headers.Clone()
        $TestHeaders['User-Agent'] = $UserAgent
        
        $response = Invoke-WebRequest -Uri $FullUrl -Headers $TestHeaders -UseBasicParsing -ErrorAction SilentlyContinue
        
        # Analyze response
        $contentType = $response.Headers['Content-Type']
        $statusCode = $response.StatusCode
        $contentLength = $response.Content.Length
        $isJavaScript = $contentType -like "*javascript*"
        
        # Get content preview
        $contentPreview = ""
        if ($response.Content.Length -gt 0) {
            $previewLength = [Math]::Min(150, $response.Content.Length)
            $contentPreview = $response.Content.Substring(0, $previewLength)
        }
        
        return @{
            Success = $true
            StatusCode = $statusCode
            ContentType = $contentType
            ContentLength = $contentLength
            IsJavaScript = $isJavaScript
            ContentPreview = $contentPreview
            UserAgent = $UserAgent
        }
    }
    catch {
        return @{
            Success = $false
            Error = $_.Exception.Message
            UserAgent = $UserAgent
        }
    }
}

function Compare-UserAgents {
    param([string]$Url)
    
    Write-Host ""
    Write-Host "🧪 TESTING URL: $Url" -ForegroundColor Yellow
    Write-Host "=" * 80 -ForegroundColor Gray
    
    # Different User-Agent configurations to test
    $UserAgentTests = @{
        "PowerShell-Default" = "Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.5007"
        "Chrome-Windows" = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36"
        "Firefox-Windows" = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0"
        "Edge-Windows" = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0"
    }
    
    $BrowserHeaders = @{
        "Chrome-Full" = @{
            'Accept' = 'application/javascript, text/javascript, */*; q=0.01'
            'Accept-Language' = 'en-US,en;q=0.9'
            'Accept-Encoding' = 'gzip, deflate, br'
            'Connection' = 'keep-alive'
            'Sec-Fetch-Dest' = 'script'
            'Sec-Fetch-Mode' = 'no-cors'
            'Sec-Fetch-Site' = 'same-origin'
            'Cache-Control' = 'no-cache'
            'Pragma' = 'no-cache'
        }
        "Firefox-Full" = @{
            'Accept' = '*/*'
            'Accept-Language' = 'en-US,en;q=0.5'
            'Accept-Encoding' = 'gzip, deflate, br'
            'Connection' = 'keep-alive'
        }
    }
    
    $results = @()
    
    # Test with different User-Agents only
    foreach ($uaName in $UserAgentTests.Keys) {
        $userAgent = $UserAgentTests[$uaName]
        $result = Test-WithUserAgent -Url $Url -UserAgent $userAgent
        $result.TestType = "UserAgent-Only"
        $result.TestName = $uaName
        $results += $result
        
        Write-Host "  $uaName : " -NoNewline
        if ($result.Success) {
            if ($result.IsJavaScript) {
                Write-Host "✅ JavaScript ($($result.ContentLength) bytes)" -ForegroundColor Green
            } else {
                Write-Host "❌ $($result.ContentType) ($($result.ContentLength) bytes)" -ForegroundColor Red
            }
        } else {
            Write-Host "💥 Error: $($result.Error)" -ForegroundColor Red
        }
    }
    
    Write-Host ""
    Write-Host "🌐 TESTING WITH FULL BROWSER HEADERS:" -ForegroundColor Cyan
    
    # Test with full browser headers
    foreach ($browserName in $BrowserHeaders.Keys) {
        $headers = $BrowserHeaders[$browserName]
        $userAgent = if ($browserName -eq "Chrome-Full") { $UserAgentTests["Chrome-Windows"] } else { $UserAgentTests["Firefox-Windows"] }
        
        $result = Test-WithUserAgent -Url $Url -UserAgent $userAgent -Headers $headers
        $result.TestType = "Full-Headers"
        $result.TestName = $browserName
        $results += $result
        
        Write-Host "  $browserName : " -NoNewline
        if ($result.Success) {
            if ($result.IsJavaScript) {
                Write-Host "✅ JavaScript ($($result.ContentLength) bytes)" -ForegroundColor Green
            } else {
                Write-Host "❌ $($result.ContentType) ($($result.ContentLength) bytes)" -ForegroundColor Red
            }
        } else {
            Write-Host "💥 Error: $($result.Error)" -ForegroundColor Red
        }
    }
    
    # Show detailed analysis for failures
    $failures = $results | Where-Object { $_.Success -and -not $_.IsJavaScript }
    if ($failures.Count -gt 0 -and $Verbose) {
        Write-Host ""
        Write-Host "❌ FAILURE ANALYSIS:" -ForegroundColor Red
        foreach ($failure in $failures) {
            Write-Host "  $($failure.TestName) ($($failure.TestType)):" -ForegroundColor Yellow
            Write-Host "    Content-Type: $($failure.ContentType)" -ForegroundColor Gray
            Write-Host "    Preview: $($failure.ContentPreview)..." -ForegroundColor Gray
            Write-Host ""
        }
    }
    
    return $results
}

function Analyze-ContentDifferences {
    param([array]$Results)
    
    Write-Host ""
    Write-Host "📊 CONTENT ANALYSIS SUMMARY:" -ForegroundColor Yellow
    Write-Host "=" * 50 -ForegroundColor Gray
    
    $jsResults = $Results | Where-Object { $_.Success -and $_.IsJavaScript }
    $htmlResults = $Results | Where-Object { $_.Success -and -not $_.IsJavaScript }
    
    Write-Host "✅ JavaScript responses: $($jsResults.Count)" -ForegroundColor Green
    Write-Host "❌ HTML/Other responses: $($htmlResults.Count)" -ForegroundColor Red
    
    if ($jsResults.Count -gt 0) {
        Write-Host ""
        Write-Host "📈 JavaScript Content Analysis:" -ForegroundColor Green
        $jsSizes = $jsResults | Select-Object -ExpandProperty ContentLength -Unique
        Write-Host "  Unique sizes: $($jsSizes -join ', ') bytes" -ForegroundColor Gray
        
        # Check if all JS files are identical
        $firstJsContent = ($jsResults | Select-Object -First 1).ContentPreview
        $allSame = $true
        foreach ($js in $jsResults) {
            if ($js.ContentPreview -ne $firstJsContent) {
                $allSame = $false
                break
            }
        }
        Write-Host "  Content consistency: $(if($allSame){'✅ All identical'}else{'⚠️ Differences detected'})" -ForegroundColor $(if($allSame){'Green'}else{'Yellow'})
    }
    
    if ($htmlResults.Count -gt 0) {
        Write-Host ""
        Write-Host "📉 HTML/Error Content Analysis:" -ForegroundColor Red
        $htmlSizes = $htmlResults | Select-Object -ExpandProperty ContentLength -Unique
        Write-Host "  Unique sizes: $($htmlSizes -join ', ') bytes" -ForegroundColor Gray
        
        # Show common error patterns
        $errorPatterns = @(
            "<!DOCTYPE html",
            "<html",
            "404",
            "Not Found",
            "Laravel",
            "error"
        )
        
        foreach ($html in $htmlResults | Select-Object -First 3) {
            $matchedPatterns = $errorPatterns | Where-Object { $html.ContentPreview -like "*$_*" }
            if ($matchedPatterns.Count -gt 0) {
                Write-Host "  $($html.TestName): Contains $($matchedPatterns -join ', ')" -ForegroundColor Gray
            }
        }
    }
}

function Generate-FixRecommendations {
    param([array]$AllResults)
    
    Write-Host ""
    Write-Host "🔧 FIX RECOMMENDATIONS:" -ForegroundColor Yellow
    Write-Host "=" * 40 -ForegroundColor Gray
    
    # Analyze patterns
    $failingUrls = @()
    $workingUrls = @()
    
    foreach ($url in $TestUrls) {
        $urlResults = $AllResults | Where-Object { $_.Url -eq $url }
        $jsCount = ($urlResults | Where-Object { $_.Success -and $_.IsJavaScript }).Count
        $totalCount = ($urlResults | Where-Object { $_.Success }).Count
        
        if ($jsCount -eq 0 -and $totalCount -gt 0) {
            $failingUrls += $url
        } elseif ($jsCount -eq $totalCount) {
            $workingUrls += $url
        }
    }
    
    Write-Host "❌ Always failing URLs:" -ForegroundColor Red
    foreach ($url in $failingUrls) {
        Write-Host "  $url" -ForegroundColor Red
    }
    
    Write-Host "✅ Always working URLs:" -ForegroundColor Green
    foreach ($url in $workingUrls) {
        Write-Host "  $url" -ForegroundColor Green
    }
    
    # Specific recommendations
    if ($failingUrls -contains "/vendor/livewire/livewire.min.js") {
        Write-Host ""
        Write-Host "🎯 PRIMARY ISSUE IDENTIFIED:" -ForegroundColor Red
        Write-Host "  Browser requests /vendor/livewire/livewire.min.js" -ForegroundColor Red
        Write-Host "  This URL returns HTML error page instead of JavaScript" -ForegroundColor Red
        Write-Host ""
        Write-Host "💡 SOLUTION STRATEGIES:" -ForegroundColor Yellow
        Write-Host "  1. Fix Laravel routing to handle /vendor/livewire/* properly" -ForegroundColor White
        Write-Host "  2. Add .htaccess rewrite from /vendor/livewire/* to /livewire/*" -ForegroundColor White
        Write-Host "  3. Update Livewire config to use working URL path" -ForegroundColor White
        Write-Host "  4. Ensure vendor assets are published correctly" -ForegroundColor White
    }
}

# Main execution
Write-Host "🚀 Starting comprehensive browser vs HTTP analysis..." -ForegroundColor Green

$allResults = @()

foreach ($url in $TestUrls) {
    $results = Compare-UserAgents -Url $url
    foreach ($result in $results) {
        $result.Url = $url
    }
    $allResults += $results
    
    Start-Sleep -Milliseconds 500  # Rate limiting
}

Analyze-ContentDifferences -Results $allResults
Generate-FixRecommendations -AllResults $allResults

Write-Host ""
Write-Host "📋 NEXT STEPS:" -ForegroundColor Cyan
Write-Host "1. Run livewire_url_inspector.ps1 for detailed URL routing analysis" -ForegroundColor White
Write-Host "2. Check Laravel Livewire configuration" -ForegroundColor White  
Write-Host "3. Verify asset publication and .htaccess rules" -ForegroundColor White
Write-Host "4. Test fix in browser with Developer Tools" -ForegroundColor White

Write-Host ""
Write-Host "✅ Browser vs HTTP debugging completed!" -ForegroundColor Green