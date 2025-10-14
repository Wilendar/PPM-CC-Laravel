# PPM-CC-Laravel Page Diagnostic Tool
# Requires: Playwright for PowerShell (install: npm install -g playwright; npx playwright install)

param(
    [Parameter(Mandatory=$false)]
    [string]$Url = "https://ppm.mpptrade.pl/admin/products",

    [Parameter(Mandatory=$false)]
    [string]$OutputDir = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_DIAGNOSTICS",

    [Parameter(Mandatory=$false)]
    [switch]$FullPage = $true,

    [Parameter(Mandatory=$false)]
    [switch]$CaptureConsole = $true,

    [Parameter(Mandatory=$false)]
    [switch]$CaptureNetwork = $true
)

Write-Host "`n=== PPM-CC-LARAVEL PAGE DIAGNOSTIC TOOL ===" -ForegroundColor Cyan
Write-Host "Target URL: $Url" -ForegroundColor Yellow
Write-Host "Output Directory: $OutputDir`n" -ForegroundColor Yellow

# Create output directory
if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$reportFile = Join-Path $OutputDir "diagnostic_report_$timestamp.md"
$screenshotFile = Join-Path $OutputDir "screenshot_$timestamp.png"
$consoleLogFile = Join-Path $OutputDir "console_$timestamp.log"
$networkLogFile = Join-Path $OutputDir "network_$timestamp.log"

# Create Node.js script for Playwright
$playwrightScript = @"
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'
    });

    const page = await context.newPage();

    // Capture console messages
    const consoleLogs = [];
    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        consoleLogs.push({ type, text, timestamp: new Date().toISOString() });
        console.log(\`[CONSOLE \${type.toUpperCase()}] \${text}\`);
    });

    // Capture network requests
    const networkLogs = [];
    page.on('request', request => {
        networkLogs.push({
            type: 'request',
            url: request.url(),
            method: request.method(),
            resourceType: request.resourceType(),
            timestamp: new Date().toISOString()
        });
    });

    page.on('response', response => {
        networkLogs.push({
            type: 'response',
            url: response.url(),
            status: response.status(),
            statusText: response.statusText(),
            contentType: response.headers()['content-type'],
            timestamp: new Date().toISOString()
        });
    });

    // Capture page errors
    const pageErrors = [];
    page.on('pageerror', error => {
        pageErrors.push({
            message: error.message,
            stack: error.stack,
            timestamp: new Date().toISOString()
        });
        console.error(\`[PAGE ERROR] \${error.message}\`);
    });

    try {
        console.log('Navigating to: $Url');
        await page.goto('$Url', { waitUntil: 'networkidle', timeout: 30000 });

        // Wait for page to be fully loaded
        await page.waitForTimeout(2000);

        // Get page metrics
        const metrics = await page.evaluate(() => {
            return {
                title: document.title,
                url: window.location.href,
                loadTime: performance.timing.loadEventEnd - performance.timing.navigationStart,
                domContentLoaded: performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart,
                stylesheets: Array.from(document.styleSheets).map(sheet => ({
                    href: sheet.href,
                    rules: sheet.cssRules ? sheet.cssRules.length : 0
                })),
                scripts: Array.from(document.scripts).map(script => script.src).filter(src => src),
                bodyClasses: document.body.className,
                htmlClasses: document.documentElement.className
            };
        });

        // Take full page screenshot
        console.log('Taking screenshot...');
        await page.screenshot({
            path: '$($screenshotFile -replace '\\', '\\\\')',
            fullPage: $($FullPage.ToString().ToLower())
        });

        // Save console logs
        const fs = require('fs');
        if ($($CaptureConsole.ToString().ToLower())) {
            fs.writeFileSync('$($consoleLogFile -replace '\\', '\\\\')', JSON.stringify(consoleLogs, null, 2));
        }

        // Save network logs (filter CSS/JS)
        if ($($CaptureNetwork.ToString().ToLower())) {
            const cssJsRequests = networkLogs.filter(log =>
                log.url.includes('.css') ||
                log.url.includes('.js') ||
                log.url.includes('build/assets')
            );
            fs.writeFileSync('$($networkLogFile -replace '\\', '\\\\')', JSON.stringify(cssJsRequests, null, 2));
        }

        // Create diagnostic report
        const report = {
            timestamp: new Date().toISOString(),
            url: '$Url',
            metrics: metrics,
            consoleLogs: consoleLogs,
            pageErrors: pageErrors,
            networkSummary: {
                totalRequests: networkLogs.filter(l => l.type === 'request').length,
                totalResponses: networkLogs.filter(l => l.type === 'response').length,
                failedRequests: networkLogs.filter(l => l.type === 'response' && l.status >= 400).length,
                cssFiles: networkLogs.filter(l => l.url.includes('.css')).map(l => ({ url: l.url, status: l.status }))
            }
        };

        fs.writeFileSync('$($reportFile -replace '\\', '\\\\')', \`# Page Diagnostic Report
Generated: \${report.timestamp}
URL: \${report.url}

## Page Metrics
- Title: \${metrics.title}
- Load Time: \${metrics.loadTime}ms
- DOM Content Loaded: \${metrics.domContentLoaded}ms

## Stylesheets Loaded
\${metrics.stylesheets.map(s => \`- \${s.href} (\${s.rules} rules)\`).join('\\n')}

## Console Errors
\${consoleLogs.filter(l => l.type === 'error').map(l => \`- [\${l.timestamp}] \${l.text}\`).join('\\n') || '(none)'}

## Page Errors
\${pageErrors.map(e => \`- \${e.message}\\n  \${e.stack}\`).join('\\n\\n') || '(none)'}

## Network Summary
- Total Requests: \${report.networkSummary.totalRequests}
- Failed Requests: \${report.networkSummary.failedRequests}

## CSS Files Status
\${report.networkSummary.cssFiles.map(f => \`- [\${f.status || 'pending'}] \${f.url}\`).join('\\n')}

## Screenshots
- Full Page: $screenshotFile
- Console Log: $consoleLogFile
- Network Log: $networkLogFile
\`);

        console.log(\`\\nDiagnostic complete!\\nReport: $reportFile\\nScreenshot: $screenshotFile\`);

    } catch (error) {
        console.error('Error during diagnostic:', error);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
"@

$scriptPath = Join-Path $OutputDir "playwright_script_$timestamp.js"
$playwrightScript | Out-File -FilePath $scriptPath -Encoding UTF8

# Check if Node.js and Playwright are installed
Write-Host "Checking dependencies..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version 2>$null
    if (-not $nodeVersion) {
        throw "Node.js not installed"
    }
    Write-Host "✓ Node.js: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ Node.js not found. Install from: https://nodejs.org/" -ForegroundColor Red
    exit 1
}

try {
    npm list -g playwright 2>$null | Out-Null
    Write-Host "✓ Playwright installed" -ForegroundColor Green
} catch {
    Write-Host "Installing Playwright..." -ForegroundColor Yellow
    npm install -g playwright
    npx playwright install chromium
}

# Run diagnostic
Write-Host "`nRunning page diagnostic..." -ForegroundColor Cyan
node $scriptPath

# Display results
if (Test-Path $reportFile) {
    Write-Host "`n=== DIAGNOSTIC REPORT ===" -ForegroundColor Cyan
    Get-Content $reportFile | Write-Host

    Write-Host "`n=== FILES GENERATED ===" -ForegroundColor Cyan
    Write-Host "Report:     $reportFile" -ForegroundColor Green
    Write-Host "Screenshot: $screenshotFile" -ForegroundColor Green
    if (Test-Path $consoleLogFile) {
        Write-Host "Console:    $consoleLogFile" -ForegroundColor Green
    }
    if (Test-Path $networkLogFile) {
        Write-Host "Network:    $networkLogFile" -ForegroundColor Green
    }

    # Open screenshot
    if (Test-Path $screenshotFile) {
        Write-Host "`nOpening screenshot..." -ForegroundColor Yellow
        Start-Process $screenshotFile
    }
} else {
    Write-Host "`n✗ Diagnostic failed - check logs" -ForegroundColor Red
}

Write-Host "`n=== DIAGNOSTIC COMPLETE ===" -ForegroundColor Cyan