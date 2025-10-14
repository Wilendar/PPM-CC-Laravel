# PowerShell Script - Livewire URL Inspector
# FILE: livewire_url_inspector.ps1
# PURPOSE: Deep inspection of Livewire URL routing and asset discovery

param(
    [string]$BaseUrl = "https://ppm.mpptrade.pl",
    [switch]$DeepScan = $false,
    [switch]$CheckRoutes = $false
)

$Host.UI.RawUI.WindowTitle = "PPM-CC-Laravel - Livewire URL Inspector"

Write-Host "🕵️ LIVEWIRE URL INSPECTOR - PPM-CC-Laravel" -ForegroundColor Green
Write-Host "Deep analysis of Livewire asset URLs and routing" -ForegroundColor Yellow
Write-Host "Base URL: $BaseUrl" -ForegroundColor Cyan
Write-Host ""

function Test-LivewireUrl {
    param(
        [string]$Url,
        [string]$Description = "",
        [switch]$ShowContent = $false
    )
    
    $FullUrl = "$BaseUrl$Url"
    
    try {
        # Use browser-like headers
        $headers = @{
            'User-Agent' = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
            'Accept' = 'application/javascript, text/javascript, */*; q=0.01'
            'Accept-Language' = 'en-US,en;q=0.9'
            'Accept-Encoding' = 'gzip, deflate, br'
            'Sec-Fetch-Dest' = 'script'
            'Sec-Fetch-Mode' = 'no-cors'
            'Sec-Fetch-Site' = 'same-origin'
        }
        
        $response = Invoke-WebRequest -Uri $FullUrl -Headers $headers -UseBasicParsing -ErrorAction SilentlyContinue
        
        $contentType = $response.Headers['Content-Type']
        $statusCode = $response.StatusCode
        $contentLength = $response.Content.Length
        $isJavaScript = $contentType -like "*javascript*"
        
        # Content analysis
        $contentPreview = ""
        $isLikelyHtml = $false
        if ($response.Content.Length -gt 0) {
            $previewLength = [Math]::Min(200, $response.Content.Length)
            $contentPreview = $response.Content.Substring(0, $previewLength)
            $isLikelyHtml = $contentPreview -like "*<!DOCTYPE*" -or $contentPreview -like "*<html*"
        }
        
        # Calculate content hash for comparison
        $contentHash = $null
        if ($response.Content.Length -gt 0) {
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($response.Content)
            $hash = [System.Security.Cryptography.SHA256]::Create()
            $hashBytes = $hash.ComputeHash($bytes)
            $contentHash = [System.BitConverter]::ToString($hashBytes) -replace '-'
            $hash.Dispose()
        }
        
        # Display result
        $status = if ($isJavaScript) { "✅" } else { "❌" }
        $sizeInfo = "$contentLength bytes"
        $typeInfo = if ($isLikelyHtml) { "HTML" } elseif ($isJavaScript) { "JS" } else { "OTHER" }
        
        Write-Host "  $status $Url" -NoNewline
        if ($Description) { Write-Host " ($Description)" -NoNewline -ForegroundColor Gray }
        Write-Host ""
        Write-Host "    Status: $statusCode | Type: $typeInfo | Size: $sizeInfo" -ForegroundColor Gray
        
        if ($ShowContent -and -not $isJavaScript) {
            Write-Host "    Content preview:" -ForegroundColor Yellow
            Write-Host "    $contentPreview..." -ForegroundColor Gray
        }
        
        return @{
            Url = $Url
            FullUrl = $FullUrl
            StatusCode = $statusCode
            ContentType = $contentType
            ContentLength = $contentLength
            IsJavaScript = $isJavaScript
            IsHtml = $isLikelyHtml
            ContentHash = $contentHash
            ContentPreview = $contentPreview
            Success = $true
        }
        
    } catch {
        Write-Host "  💥 $Url - ERROR: $($_.Exception.Message)" -ForegroundColor Red
        return @{
            Url = $Url
            FullUrl = $FullUrl
            Error = $_.Exception.Message
            Success = $false
        }
    }
}

function Scan-StandardLivewirePaths {
    Write-Host "📍 SCANNING STANDARD LIVEWIRE PATHS:" -ForegroundColor Yellow
    Write-Host ""
    
    $StandardPaths = @(
        @{ path = "/vendor/livewire/livewire.min.js"; desc = "Laravel expected path" },
        @{ path = "/vendor/livewire/livewire.js"; desc = "Laravel unminified" },
        @{ path = "/livewire/livewire.min.js"; desc = "Alternative routing" },  
        @{ path = "/livewire/livewire.js"; desc = "Alternative unminified" },
        @{ path = "/public/vendor/livewire/livewire.min.js"; desc = "Direct public access" },
        @{ path = "/public/vendor/livewire/livewire.js"; desc = "Direct public unminified" },
        @{ path = "/js/livewire.min.js"; desc = "Custom compiled location" },
        @{ path = "/assets/livewire.min.js"; desc = "Vite assets location" }
    )
    
    $results = @()
    foreach ($pathInfo in $StandardPaths) {
        $result = Test-LivewireUrl -Url $pathInfo.path -Description $pathInfo.desc -ShowContent:(-not $pathInfo.path.Contains("public"))
        $results += $result
        Start-Sleep -Milliseconds 300
    }
    
    return $results
}

function Scan-QueryParameterVariants {
    Write-Host ""
    Write-Host "🔍 SCANNING QUERY PARAMETER VARIANTS:" -ForegroundColor Yellow
    Write-Host ""
    
    $BaseUrls = @("/vendor/livewire/livewire.min.js", "/livewire/livewire.min.js")
    $QueryParams = @(
        "?id=df3a17f2",
        "?v=1.0.0", 
        "?timestamp=1234567890",
        "?nocache=true",
        "?_token=test"
    )
    
    $results = @()
    foreach ($baseUrl in $BaseUrls) {
        Write-Host "  Testing variants of: $baseUrl" -ForegroundColor Cyan
        
        foreach ($param in $QueryParams) {
            $fullPath = "$baseUrl$param"
            $result = Test-LivewireUrl -Url $fullPath -Description "with parameters"
            $results += $result
            Start-Sleep -Milliseconds 200
        }
    }
    
    return $results
}

function Discover-LivewireAssets {
    Write-Host ""
    Write-Host "🔎 ASSET DISCOVERY SCAN:" -ForegroundColor Yellow
    Write-Host ""
    
    # Try to find Livewire assets through common paths
    $DiscoveryPaths = @(
        "/", 
        "/livewire",
        "/vendor", 
        "/vendor/livewire",
        "/public",
        "/public/vendor",
        "/public/vendor/livewire",
        "/assets",
        "/js",
        "/dist"
    )
    
    $results = @()
    foreach ($path in $DiscoveryPaths) {
        try {
            $fullUrl = "$BaseUrl$path"
            $response = Invoke-WebRequest -Uri $fullUrl -UseBasicParsing -ErrorAction SilentlyContinue
            
            if ($response.StatusCode -eq 200 -and $response.Content -like "*livewire*") {
                Write-Host "  🎯 Found Livewire references in: $path" -ForegroundColor Green
                
                # Extract potential Livewire URLs from content
                $livewireMatches = [regex]::Matches($response.Content, 'livewire[^"\s]*\.js')
                foreach ($match in $livewireMatches) {
                    $foundUrl = $match.Value
                    Write-Host "    Found asset: $foundUrl" -ForegroundColor Gray
                    
                    # Test this found URL
                    if ($foundUrl.StartsWith("http")) {
                        $testUrl = $foundUrl
                    } elseif ($foundUrl.StartsWith("/")) {
                        $testUrl = "$BaseUrl$foundUrl"
                    } else {
                        $testUrl = "$BaseUrl/$foundUrl"
                    }
                    
                    $result = Test-LivewireUrl -Url $foundUrl -Description "discovered asset"
                    $results += $result
                }
            }
        } catch {
            # Ignore discovery errors
        }
        
        Start-Sleep -Milliseconds 100
    }
    
    return $results
}

function Check-LaravelRoutes {
    if (-not $CheckRoutes) {
        return @()
    }
    
    Write-Host ""
    Write-Host "🛤️ CHECKING LARAVEL ROUTES:" -ForegroundColor Yellow
    Write-Host ""
    
    # Try to access Laravel route information
    $RouteEndpoints = @(
        "/route:list",
        "/debug/routes", 
        "/admin/routes",
        "/_debugbar/routes"
    )
    
    $results = @()
    foreach ($endpoint in $RouteEndpoints) {
        try {
            $result = Test-LivewireUrl -Url $endpoint -Description "route info" -ShowContent:$true
            $results += $result
        } catch {
            # Ignore route check errors
        }
    }
    
    return $results
}

function Analyze-Results {
    param([array]$AllResults)
    
    Write-Host ""
    Write-Host "📊 ANALYSIS RESULTS:" -ForegroundColor Yellow
    Write-Host "=" * 50 -ForegroundColor Gray
    
    $successful = $AllResults | Where-Object { $_.Success }
    $javascript = $successful | Where-Object { $_.IsJavaScript }
    $html = $successful | Where-Object { $_.IsHtml }
    $other = $successful | Where-Object { -not $_.IsJavaScript -and -not $_.IsHtml }
    
    Write-Host "Total URLs tested: $($AllResults.Count)" -ForegroundColor White
    Write-Host "Successful responses: $($successful.Count)" -ForegroundColor Green
    Write-Host "JavaScript responses: $($javascript.Count)" -ForegroundColor Green  
    Write-Host "HTML responses: $($html.Count)" -ForegroundColor Red
    Write-Host "Other responses: $($other.Count)" -ForegroundColor Yellow
    
    if ($javascript.Count -gt 0) {
        Write-Host ""
        Write-Host "✅ WORKING JAVASCRIPT URLS:" -ForegroundColor Green
        foreach ($js in $javascript) {
            Write-Host "  $($js.Url) ($($js.ContentLength) bytes)" -ForegroundColor Green
        }
        
        # Check for identical content
        $uniqueHashes = $javascript | Select-Object -ExpandProperty ContentHash -Unique
        if ($uniqueHashes.Count -eq 1) {
            Write-Host "  📍 All JavaScript files are identical (same hash)" -ForegroundColor Cyan
        } else {
            Write-Host "  ⚠️  JavaScript files have different content!" -ForegroundColor Yellow
        }
    }
    
    if ($html.Count -gt 0) {
        Write-Host ""
        Write-Host "❌ PROBLEMATIC HTML RESPONSES:" -ForegroundColor Red
        foreach ($htmlResp in $html) {
            Write-Host "  $($htmlResp.Url)" -ForegroundColor Red
            if ($htmlResp.ContentPreview -like "*404*" -or $htmlResp.ContentPreview -like "*Not Found*") {
                Write-Host "    🔍 Appears to be 404 error page" -ForegroundColor Yellow
            } elseif ($htmlResp.ContentPreview -like "*Laravel*") {
                Write-Host "    🔍 Laravel error/welcome page" -ForegroundColor Yellow
            }
        }
    }
}

function Generate-UrlFixStrategy {
    param([array]$AllResults)
    
    Write-Host ""
    Write-Host "🔧 URL FIX STRATEGY:" -ForegroundColor Yellow
    Write-Host "=" * 30 -ForegroundColor Gray
    
    $workingJs = $AllResults | Where-Object { $_.Success -and $_.IsJavaScript }
    $failingUrls = $AllResults | Where-Object { $_.Success -and -not $_.IsJavaScript } | Select-Object -ExpandProperty Url
    
    if ($workingJs.Count -gt 0) {
        $primaryWorking = $workingJs | Sort-Object ContentLength -Descending | Select-Object -First 1
        Write-Host "🎯 PRIMARY WORKING URL: $($primaryWorking.Url)" -ForegroundColor Green
        Write-Host "   Size: $($primaryWorking.ContentLength) bytes" -ForegroundColor Gray
        Write-Host "   Content-Type: $($primaryWorking.ContentType)" -ForegroundColor Gray
    }
    
    if ($failingUrls -contains "/vendor/livewire/livewire.min.js") {
        Write-Host ""
        Write-Host "🚨 CRITICAL ISSUE CONFIRMED:" -ForegroundColor Red
        Write-Host "   Browser expects: /vendor/livewire/livewire.min.js" -ForegroundColor Red
        Write-Host "   But this URL serves HTML instead of JavaScript" -ForegroundColor Red
        Write-Host ""
        Write-Host "💡 IMMEDIATE FIX OPTIONS:" -ForegroundColor Yellow
        
        if ($workingJs | Where-Object { $_.Url -eq "/livewire/livewire.min.js" }) {
            Write-Host "   1. ✅ Redirect /vendor/livewire/* to /livewire/*" -ForegroundColor Green
            Write-Host "      Add to .htaccess: RewriteRule ^vendor/livewire/(.*)$ /livewire/$1 [R=301,L]" -ForegroundColor Cyan
        }
        
        if ($workingJs | Where-Object { $_.Url -eq "/public/vendor/livewire/livewire.min.js" }) {
            Write-Host "   2. ✅ Redirect /vendor/livewire/* to /public/vendor/livewire/*" -ForegroundColor Green  
            Write-Host "      Add to .htaccess: RewriteRule ^vendor/livewire/(.*)$ /public/vendor/livewire/$1 [R=301,L]" -ForegroundColor Cyan
        }
        
        Write-Host "   3. 🔧 Fix Laravel Livewire asset publishing" -ForegroundColor Yellow
        Write-Host "      Run: php artisan livewire:publish --assets" -ForegroundColor Cyan
        Write-Host "   4. 🔧 Update Livewire configuration" -ForegroundColor Yellow
        Write-Host "      Check config/livewire.php asset_url setting" -ForegroundColor Cyan
    }
}

# Main execution flow
Write-Host "🚀 Starting comprehensive Livewire URL inspection..." -ForegroundColor Green

$allResults = @()

# Phase 1: Standard paths
$standardResults = Scan-StandardLivewirePaths
$allResults += $standardResults

# Phase 2: Query parameter variants
$paramResults = Scan-QueryParameterVariants  
$allResults += $paramResults

# Phase 3: Asset discovery (if deep scan)
if ($DeepScan) {
    $discoveryResults = Discover-LivewireAssets
    $allResults += $discoveryResults
}

# Phase 4: Route information (if requested)
if ($CheckRoutes) {
    $routeResults = Check-LaravelRoutes
    $allResults += $routeResults
}

# Analysis and recommendations
Analyze-Results -AllResults $allResults
Generate-UrlFixStrategy -AllResults $allResults

Write-Host ""
Write-Host "📋 NEXT ACTIONS:" -ForegroundColor Cyan
Write-Host "1. Apply .htaccess redirect rule for /vendor/livewire/*" -ForegroundColor White
Write-Host "2. Test in browser with Developer Tools Network tab" -ForegroundColor White
Write-Host "3. Verify Livewire asset publishing: php artisan livewire:publish --assets" -ForegroundColor White
Write-Host "4. Clear browser cache completely after fix" -ForegroundColor White

Write-Host ""
Write-Host "✅ Livewire URL inspection completed!" -ForegroundColor Green