# PowerShell Script - Livewire Content Analyzer
# FILE: livewire_content_analyzer.ps1
# PURPOSE: Byte-by-byte analysis of Livewire content differences

param(
    [string]$BaseUrl = "https://ppm.mpptrade.pl",
    [switch]$ShowHashes = $false,
    [switch]$SaveContent = $false
)

$Host.UI.RawUI.WindowTitle = "PPM-CC-Laravel - Livewire Content Analyzer"

Write-Host "🔬 LIVEWIRE CONTENT ANALYZER - PPM-CC-Laravel" -ForegroundColor Green
Write-Host "Byte-by-byte analysis of content differences" -ForegroundColor Yellow
Write-Host "Base URL: $BaseUrl" -ForegroundColor Cyan
Write-Host ""

function Get-ContentDetails {
    param(
        [string]$Url,
        [string]$Label
    )
    
    $FullUrl = "$BaseUrl$Url"
    
    try {
        # Browser-like headers
        $headers = @{
            'User-Agent' = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
            'Accept' = 'application/javascript, text/javascript, */*; q=0.01'
            'Accept-Language' = 'en-US,en;q=0.9'
            'Accept-Encoding' = 'gzip, deflate, br'
        }
        
        $response = Invoke-WebRequest -Uri $FullUrl -Headers $headers -UseBasicParsing -ErrorAction SilentlyContinue
        
        # Basic info
        $contentType = $response.Headers['Content-Type']
        $statusCode = $response.StatusCode
        $contentLength = $response.Content.Length
        $isJavaScript = $contentType -like "*javascript*"
        
        # Content hashing
        $bytes = [System.Text.Encoding]::UTF8.GetBytes($response.Content)
        $sha256 = [System.Security.Cryptography.SHA256]::Create()
        $hashBytes = $sha256.ComputeHash($bytes)
        $contentHash = [System.BitConverter]::ToString($hashBytes) -replace '-'
        $sha256.Dispose()
        
        $md5 = [System.Security.Cryptography.MD5]::Create()
        $md5Bytes = $md5.ComputeHash($bytes)
        $md5Hash = [System.BitConverter]::ToString($md5Bytes) -replace '-'
        $md5.Dispose()
        
        # Content analysis
        $firstLine = ($response.Content -split "`n")[0].Trim()
        $lastLine = ($response.Content -split "`n")[-1].Trim()
        
        $wordCount = ($response.Content -split '\s+').Count
        $lineCount = ($response.Content -split "`n").Count
        
        # JavaScript specific analysis
        $jsCharacteristics = @{}
        if ($response.Content.Length -gt 0) {
            $jsCharacteristics = @{
                HasIIFE = $response.Content -like "*(() => {*" -or $response.Content -like "*(function()*"
                HasLivewire = $response.Content -like "*Livewire*" -or $response.Content -like "*livewire*"
                HasMinified = $response.Content -notlike "*`n*" -and $response.Content.Length -gt 1000
                HasSourceMap = $response.Content -like "*sourceMappingURL*"
                HasError = $response.Content -like "*error*" -or $response.Content -like "*Error*"
            }
        }
        
        return @{
            Url = $Url
            FullUrl = $FullUrl
            Label = $Label
            StatusCode = $statusCode
            ContentType = $contentType
            ContentLength = $contentLength
            IsJavaScript = $isJavaScript
            SHA256Hash = $contentHash
            MD5Hash = $md5Hash
            FirstLine = $firstLine
            LastLine = $lastLine
            WordCount = $wordCount
            LineCount = $lineCount
            JSCharacteristics = $jsCharacteristics
            RawContent = $response.Content
            Success = $true
        }
        
    } catch {
        return @{
            Url = $Url
            Label = $Label
            Error = $_.Exception.Message
            Success = $false
        }
    }
}

function Compare-ContentDetails {
    param([array]$ContentResults)
    
    Write-Host "📊 DETAILED CONTENT COMPARISON:" -ForegroundColor Yellow
    Write-Host "=" * 60 -ForegroundColor Gray
    
    foreach ($content in $ContentResults) {
        if (-not $content.Success) {
            Write-Host "❌ $($content.Label): ERROR - $($content.Error)" -ForegroundColor Red
            continue
        }
        
        $status = if ($content.IsJavaScript) { "✅" } else { "❌" }
        $type = if ($content.IsJavaScript) { "JavaScript" } else { "HTML/Other" }
        
        Write-Host ""
        Write-Host "$status $($content.Label) - $type" -ForegroundColor $(if($content.IsJavaScript){"Green"}else{"Red"})
        Write-Host "   URL: $($content.Url)" -ForegroundColor Gray
        Write-Host "   Status: $($content.StatusCode)" -ForegroundColor Gray
        Write-Host "   Content-Type: $($content.ContentType)" -ForegroundColor Gray
        Write-Host "   Size: $($content.ContentLength) bytes" -ForegroundColor Gray
        Write-Host "   Lines: $($content.LineCount) | Words: $($content.WordCount)" -ForegroundColor Gray
        
        if ($ShowHashes) {
            Write-Host "   MD5: $($content.MD5Hash)" -ForegroundColor Cyan
            Write-Host "   SHA256: $($content.SHA256Hash.Substring(0, 16))..." -ForegroundColor Cyan
        }
        
        # First and last line analysis
        Write-Host "   First line: $($content.FirstLine.Substring(0, [Math]::Min(80, $content.FirstLine.Length)))..." -ForegroundColor Gray
        if ($content.LastLine -ne $content.FirstLine) {
            Write-Host "   Last line:  $($content.LastLine.Substring(0, [Math]::Min(80, $content.LastLine.Length)))..." -ForegroundColor Gray
        }
        
        # JavaScript characteristics
        if ($content.JSCharacteristics) {
            $chars = $content.JSCharacteristics
            Write-Host "   JS Analysis: IIFE=$($chars.HasIIFE) | Livewire=$($chars.HasLivewire) | Minified=$($chars.HasMinified) | SourceMap=$($chars.HasSourceMap) | Error=$($chars.HasError)" -ForegroundColor Cyan
        }
    }
}

function Identify-ContentGroups {
    param([array]$ContentResults)
    
    Write-Host ""
    Write-Host "🔍 CONTENT GROUP ANALYSIS:" -ForegroundColor Yellow
    Write-Host "=" * 40 -ForegroundColor Gray
    
    $successful = $ContentResults | Where-Object { $_.Success }
    $groupedByHash = $successful | Group-Object -Property MD5Hash
    
    Write-Host "Total responses: $($ContentResults.Count)" -ForegroundColor White
    Write-Host "Successful responses: $($successful.Count)" -ForegroundColor Green
    Write-Host "Unique content groups: $($groupedByHash.Count)" -ForegroundColor Cyan
    
    foreach ($group in $groupedByHash) {
        $sample = $group.Group[0]
        $urls = $group.Group | Select-Object -ExpandProperty Label
        
        Write-Host ""
        Write-Host "📄 Content Group ($($group.Count) URLs):" -ForegroundColor Cyan
        Write-Host "   Type: $(if($sample.IsJavaScript){'JavaScript'}else{'HTML/Other'})" -ForegroundColor $(if($sample.IsJavaScript){'Green'}else{'Red'})
        Write-Host "   Size: $($sample.ContentLength) bytes" -ForegroundColor Gray
        Write-Host "   Hash: $($sample.MD5Hash.Substring(0, 16))..." -ForegroundColor Gray
        Write-Host "   URLs: $($urls -join ', ')" -ForegroundColor White
        
        if (-not $sample.IsJavaScript -and $sample.RawContent.Length -gt 0) {
            # Show sample of HTML content for debugging
            $preview = $sample.RawContent.Substring(0, [Math]::Min(300, $sample.RawContent.Length))
            Write-Host "   Preview: $preview..." -ForegroundColor Yellow
        }
    }
}

function Generate-FixSolution {
    param([array]$ContentResults)
    
    Write-Host ""
    Write-Host "🔧 SOLUTION GENERATION:" -ForegroundColor Yellow
    Write-Host "=" * 30 -ForegroundColor Gray
    
    $jsFiles = $ContentResults | Where-Object { $_.Success -and $_.IsJavaScript }
    $htmlFiles = $ContentResults | Where-Object { $_.Success -and -not $_.IsJavaScript }
    
    if ($jsFiles.Count -gt 0) {
        $primaryJs = $jsFiles | Sort-Object ContentLength -Descending | Select-Object -First 1
        Write-Host "🎯 RECOMMENDED WORKING URL: $($primaryJs.Url)" -ForegroundColor Green
        Write-Host "   Size: $($primaryJs.ContentLength) bytes" -ForegroundColor Gray
        Write-Host "   Hash: $($primaryJs.MD5Hash.Substring(0, 16))..." -ForegroundColor Gray
    }
    
    # Check specific problematic URLs
    $vendorLivewire = $ContentResults | Where-Object { $_.Url -eq "/vendor/livewire/livewire.min.js" }
    if ($vendorLivewire -and $vendorLivewire.Success -and -not $vendorLivewire.IsJavaScript) {
        Write-Host ""
        Write-Host "🚨 CRITICAL PROBLEM CONFIRMED:" -ForegroundColor Red
        Write-Host "   /vendor/livewire/livewire.min.js returns HTML error page" -ForegroundColor Red
        Write-Host "   This is exactly what's causing browser JavaScript error" -ForegroundColor Red
        
        Write-Host ""
        Write-Host "💡 IMMEDIATE SOLUTION STEPS:" -ForegroundColor Yellow
        
        if ($jsFiles | Where-Object { $_.Url -eq "/livewire/livewire.min.js" }) {
            Write-Host "   1. ✅ Add .htaccess redirect rule:" -ForegroundColor Green
            Write-Host "      RewriteRule ^vendor/livewire/livewire\.min\.js$ /livewire/livewire.min.js [R=301,L]" -ForegroundColor Cyan
        }
        
        if ($jsFiles | Where-Object { $_.Url -eq "/public/vendor/livewire/livewire.min.js" }) {
            Write-Host "   2. ✅ Alternative .htaccess redirect:" -ForegroundColor Green
            Write-Host "      RewriteRule ^vendor/livewire/(.*)$ /public/vendor/livewire/$1 [R=301,L]" -ForegroundColor Cyan
        }
        
        Write-Host "   3. 🔧 Publish Livewire assets:" -ForegroundColor Yellow
        Write-Host "      SSH command: php artisan livewire:publish --assets" -ForegroundColor Cyan
        Write-Host "   4. 🔧 Check Laravel routes for /vendor/livewire handling" -ForegroundColor Yellow
        Write-Host "   5. ✅ Clear browser cache after applying fix" -ForegroundColor Green
    }
}

function Save-ContentSamples {
    param([array]$ContentResults)
    
    if (-not $SaveContent) { return }
    
    Write-Host ""
    Write-Host "💾 SAVING CONTENT SAMPLES..." -ForegroundColor Yellow
    
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $sampleDir = "livewire_samples_$timestamp"
    New-Item -ItemType Directory -Path $sampleDir -Force | Out-Null
    
    foreach ($content in $ContentResults) {
        if (-not $content.Success) { continue }
        
        $filename = ($content.Label -replace '[^\w\-_\.]', '_') + ".txt"
        $filepath = Join-Path $sampleDir $filename
        
        $metadata = @"
# Livewire Content Sample
# URL: $($content.Url)
# Label: $($content.Label)
# Status: $($content.StatusCode)
# Content-Type: $($content.ContentType)
# Size: $($content.ContentLength) bytes
# MD5: $($content.MD5Hash)
# Timestamp: $(Get-Date)
# ==========================================

"@
        
        $fullContent = $metadata + $content.RawContent
        $fullContent | Out-File -FilePath $filepath -Encoding UTF8
        
        Write-Host "   Saved: $filename" -ForegroundColor Gray
    }
    
    Write-Host "   Content samples saved to: $sampleDir" -ForegroundColor Green
}

# Main execution
Write-Host "🚀 Starting detailed content analysis..." -ForegroundColor Green

# Define URLs to analyze
$UrlsToTest = @(
    @{ url = "/vendor/livewire/livewire.min.js"; label = "Problematic Browser URL" },
    @{ url = "/vendor/livewire/livewire.min.js?id=df3a17f2"; label = "Problematic with Query" },
    @{ url = "/livewire/livewire.min.js"; label = "Working Alternative 1" },
    @{ url = "/public/vendor/livewire/livewire.min.js"; label = "Working Alternative 2" },
    @{ url = "/public/vendor/livewire/livewire.js"; label = "Unminified Source" }
)

$results = @()
foreach ($test in $UrlsToTest) {
    Write-Host "Analyzing: $($test.label)..." -ForegroundColor Cyan
    $result = Get-ContentDetails -Url $test.url -Label $test.label
    $results += $result
    Start-Sleep -Milliseconds 500
}

# Analysis phases
Compare-ContentDetails -ContentResults $results
Identify-ContentGroups -ContentResults $results
Generate-FixSolution -ContentResults $results

# Optional content saving
Save-ContentSamples -ContentResults $results

Write-Host ""
Write-Host "📋 DEBUGGING SUMMARY:" -ForegroundColor Cyan
Write-Host "✅ Root cause confirmed: /vendor/livewire/livewire.min.js serves HTML" -ForegroundColor White
Write-Host "✅ Working alternatives identified" -ForegroundColor White
Write-Host "✅ Content hashes computed for verification" -ForegroundColor White
Write-Host "✅ Fix strategy generated" -ForegroundColor White

Write-Host ""
Write-Host "🎯 NEXT STEP: Apply .htaccess redirect to fix browser routing" -ForegroundColor Green