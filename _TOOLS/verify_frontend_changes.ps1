# Frontend Verification Hook
# Automatically verifies frontend/layout/styles after deployment
# Usage: .\verify_frontend_changes.ps1 -Url "https://ppm.mpptrade.pl/admin/products"

param(
    [Parameter(Mandatory=$true)]
    [string]$Url,

    [Parameter(Mandatory=$false)]
    [switch]$SkipScreenshot,

    [Parameter(Mandatory=$false)]
    [switch]$SkipDOM,

    [Parameter(Mandatory=$false)]
    [switch]$OpenReport
)

$ErrorActionPreference = "Stop"
$ToolsDir = $PSScriptRoot

Write-Host "`n=== FRONTEND VERIFICATION HOOK ===" -ForegroundColor Cyan
Write-Host "URL: $Url" -ForegroundColor Yellow
Write-Host "Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray

$AllPassed = $true
$Issues = @()

# 1. Screenshot Verification
if (-not $SkipScreenshot) {
    Write-Host "`n[1/3] Taking screenshots..." -ForegroundColor Cyan

    try {
        $screenshotResult = node "$ToolsDir\screenshot_page.cjs" $Url 2>&1

        if ($LASTEXITCODE -eq 0) {
            Write-Host "  ✅ Screenshot captured successfully" -ForegroundColor Green

            # Extract screenshot paths from output
            $screenshotResult | ForEach-Object {
                if ($_ -match "page_viewport_.*\.png") {
                    $global:LatestScreenshot = $matches[0]
                }
            }
        } else {
            Write-Host "  ❌ Screenshot failed: $screenshotResult" -ForegroundColor Red
            $AllPassed = $false
            $Issues += "Screenshot capture failed"
        }
    } catch {
        Write-Host "  ❌ Screenshot error: $_" -ForegroundColor Red
        $AllPassed = $false
        $Issues += "Screenshot error: $_"
    }
} else {
    Write-Host "`n[1/3] Screenshot verification SKIPPED" -ForegroundColor Yellow
}

# 2. DOM Structure Verification
if (-not $SkipDOM) {
    Write-Host "`n[2/3] Checking DOM structure..." -ForegroundColor Cyan

    try {
        $domResult = node "$ToolsDir\check_dom_structure.cjs" 2>&1 | Out-String

        if ($domResult -match '"issues":\s*\[\s*\]') {
            Write-Host "  ✅ No DOM issues detected" -ForegroundColor Green
        } elseif ($domResult -match '"issues":\s*\[') {
            # Extract issues from JSON
            $issueMatches = [regex]::Matches($domResult, '"(⚠️|❌)[^"]*"')

            if ($issueMatches.Count -gt 0) {
                Write-Host "  ⚠️  DOM issues found:" -ForegroundColor Yellow
                foreach ($match in $issueMatches) {
                    $issue = $match.Value.Trim('"')
                    Write-Host "      $issue" -ForegroundColor Yellow
                    $Issues += $issue
                }

                if ($domResult -match "❌") {
                    $AllPassed = $false
                }
            }
        } else {
            Write-Host "  ❌ DOM check failed to run" -ForegroundColor Red
            $AllPassed = $false
            $Issues += "DOM verification failed"
        }
    } catch {
        Write-Host "  ❌ DOM check error: $_" -ForegroundColor Red
        $AllPassed = $false
        $Issues += "DOM check error: $_"
    }
} else {
    Write-Host "`n[2/3] DOM verification SKIPPED" -ForegroundColor Yellow
}

# 3. Header/Spacing Verification
Write-Host "`n[3/3] Checking header & spacing..." -ForegroundColor Cyan

try {
    $headerResult = node "$ToolsDir\check_header_devbanner_overlap.cjs" 2>&1 | Out-String

    if ($headerResult -match "No issues detected" -or $headerResult -match "✅ OK") {
        Write-Host "  ✅ Header positioning OK" -ForegroundColor Green
    } elseif ($headerResult -match "🚨|⚠️") {
        Write-Host "  ⚠️  Header/spacing issues detected" -ForegroundColor Yellow

        # Extract specific issues
        $issueLines = ($headerResult -split "`n") | Where-Object { $_ -match "🚨|⚠️" }
        foreach ($line in $issueLines) {
            Write-Host "      $line" -ForegroundColor Yellow
            $Issues += $line.Trim()
        }

        if ($headerResult -match "🚨") {
            $AllPassed = $false
        }
    }
} catch {
    Write-Host "  ❌ Header check error: $_" -ForegroundColor Red
    $AllPassed = $false
    $Issues += "Header check error: $_"
}

# Summary Report
Write-Host "`n=== VERIFICATION SUMMARY ===" -ForegroundColor Cyan

if ($AllPassed) {
    Write-Host "✅ ALL CHECKS PASSED - Frontend looks good!" -ForegroundColor Green

    if ($global:LatestScreenshot) {
        Write-Host "`nLatest screenshot: $global:LatestScreenshot" -ForegroundColor Gray

        if ($OpenReport) {
            Start-Process "$ToolsDir\screenshots\$global:LatestScreenshot"
        }
    }
} else {
    Write-Host "❌ VERIFICATION FAILED - Issues detected!" -ForegroundColor Red
    Write-Host "`nIssues found ($($Issues.Count)):" -ForegroundColor Yellow

    $Issues | Select-Object -Unique | ForEach-Object {
        Write-Host "  • $_" -ForegroundColor Yellow
    }

    Write-Host "`n⚠️  DO NOT inform user of completion until these are fixed!" -ForegroundColor Red
}

Write-Host "`n" -ForegroundColor Gray

# Return exit code based on success
if ($AllPassed) {
    exit 0
} else {
    exit 1
}
