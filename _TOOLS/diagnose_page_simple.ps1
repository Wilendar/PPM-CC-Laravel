# PPM-CC-Laravel Simple Page Diagnostic Tool
param(
    [string]$Url = "https://ppm.mpptrade.pl/admin/products",
    [string]$BaseOutputDir = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_DIAGNOSTICS"
)

Write-Host "`n=== PPM-CC-LARAVEL PAGE DIAGNOSTIC ===" -ForegroundColor Cyan
Write-Host "URL: $Url" -ForegroundColor Yellow

# Check Node.js
try {
    $nodeVersion = node --version 2>&1
    Write-Host "Node.js: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Node.js not installed!" -ForegroundColor Red
    exit 1
}

# Check Playwright
Write-Host "Checking Playwright..." -ForegroundColor Yellow
try {
    npm list -g playwright 2>&1 | Out-Null
    Write-Host "Playwright: OK" -ForegroundColor Green
} catch {
    Write-Host "Installing Playwright..." -ForegroundColor Yellow
    npm install -g playwright
    npx playwright install chromium
}

# Run diagnostic
$scriptPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TOOLS\playwright_diagnostic.cjs"
Write-Host "`nRunning diagnostic..." -ForegroundColor Cyan
node $scriptPath $Url $BaseOutputDir

# Find latest session folder
if (Test-Path $BaseOutputDir) {
    $latestSession = Get-ChildItem $BaseOutputDir -Directory | Sort-Object LastWriteTime -Descending | Select-Object -First 1

    if ($latestSession) {
        Write-Host "`nSession folder: $($latestSession.Name)" -ForegroundColor Green

        $reportFile = Join-Path $latestSession.FullName "diagnostic_report.md"
        $screenshotFile = Join-Path $latestSession.FullName "screenshot.png"

        if (Test-Path $reportFile) {
            Write-Host "`n=== REPORT PREVIEW ===" -ForegroundColor Cyan
            Get-Content $reportFile | Write-Host
        }

        if (Test-Path $screenshotFile) {
            Write-Host "`nOpening screenshot..." -ForegroundColor Yellow
            Start-Process $screenshotFile
        }

        Write-Host "`nAll files saved in: $($latestSession.FullName)" -ForegroundColor Cyan
    }
}

Write-Host "`n=== DIAGNOSTIC COMPLETE ===" -ForegroundColor Green