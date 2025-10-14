// PPM-CC-Laravel Playwright Diagnostic Script
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const config = {
    url: process.argv[2] || 'https://ppm.mpptrade.pl/admin/products',
    baseOutputDir: process.argv[3] || 'D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel\\_DIAGNOSTICS',
    timestamp: new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5)
};

// Generate clean filename from URL
function urlToFilename(url) {
    // Extract path from URL (remove protocol and domain)
    const urlObj = new URL(url);
    let filename = urlObj.pathname.replace(/^\//, '').replace(/\//g, '_');

    // If no path, use hostname
    if (!filename || filename === '') {
        filename = urlObj.hostname.replace(/\./g, '_');
    }

    // Clean special characters
    filename = filename.replace(/[^a-zA-Z0-9_-]/g, '_');

    // Remove trailing underscores
    filename = filename.replace(/_+$/, '');

    return filename || 'page';
}

(async () => {
    console.log('\n=== PPM-CC-LARAVEL PAGE DIAGNOSTIC ===');
    console.log(`Target URL: ${config.url}`);

    // Generate folder name from URL and timestamp
    const pageFilename = urlToFilename(config.url);
    const sessionFolder = `${pageFilename}_${config.timestamp}`;
    config.outputDir = path.join(config.baseOutputDir, sessionFolder);

    console.log(`Output Dir: ${config.outputDir}\n`);

    // Create output directory
    if (!fs.existsSync(config.outputDir)) {
        fs.mkdirSync(config.outputDir, { recursive: true });
    }

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    // Data collectors
    const consoleLogs = [];
    const networkLogs = [];
    const pageErrors = [];

    // Capture console
    page.on('console', msg => {
        const log = {
            type: msg.type(),
            text: msg.text(),
            timestamp: new Date().toISOString()
        };
        consoleLogs.push(log);
        console.log(`[CONSOLE ${log.type.toUpperCase()}] ${log.text}`);
    });

    // Capture network
    page.on('response', response => {
        networkLogs.push({
            url: response.url(),
            status: response.status(),
            statusText: response.statusText(),
            contentType: response.headers()['content-type'],
            timestamp: new Date().toISOString()
        });
    });

    // Capture errors
    page.on('pageerror', error => {
        const err = {
            message: error.message,
            stack: error.stack,
            timestamp: new Date().toISOString()
        };
        pageErrors.push(err);
        console.error(`[PAGE ERROR] ${err.message}`);
    });

    try {
        console.log('Navigating to page...');
        await page.goto(config.url, { waitUntil: 'networkidle', timeout: 30000 });
        await page.waitForTimeout(2000);

        // Get page metrics
        console.log('Collecting metrics...');
        const metrics = await page.evaluate(() => {
            return {
                title: document.title,
                url: window.location.href,
                loadTime: performance.timing.loadEventEnd - performance.timing.navigationStart,
                domContentLoaded: performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart,
                stylesheets: Array.from(document.styleSheets).map(sheet => {
                    try {
                        return {
                            href: sheet.href,
                            rules: sheet.cssRules ? sheet.cssRules.length : 0
                        };
                    } catch (e) {
                        return { href: sheet.href, rules: 'CORS blocked' };
                    }
                }),
                scripts: Array.from(document.scripts).map(script => script.src).filter(src => src),
                bodyClasses: document.body ? document.body.className : '',
                htmlClasses: document.documentElement ? document.documentElement.className : ''
            };
        });

        // Screenshots with optimized format
        console.log('Taking screenshots...');
        const screenshotFile = path.join(config.outputDir, `screenshot.png`);
        await page.screenshot({
            path: screenshotFile,
            fullPage: true,
            type: 'png'
        });

        // Save logs
        const consoleFile = path.join(config.outputDir, `console.json`);
        fs.writeFileSync(consoleFile, JSON.stringify(consoleLogs, null, 2));

        const networkFile = path.join(config.outputDir, `network.json`);
        const cssJsRequests = networkLogs.filter(log =>
            log.url.includes('.css') || log.url.includes('.js') || log.url.includes('build/assets')
        );
        fs.writeFileSync(networkFile, JSON.stringify(cssJsRequests, null, 2));

        // Create markdown report
        const reportFile = path.join(config.outputDir, `diagnostic_report.md`);
        const consoleErrors = consoleLogs.filter(l => l.type === 'error');
        const failedRequests = networkLogs.filter(l => l.status >= 400);
        const cssFiles = networkLogs.filter(l => l.url.includes('.css'));

        const report = `# Page Diagnostic Report

**Generated:** ${new Date().toISOString()}
**URL:** ${config.url}

## Page Metrics

- **Title:** ${metrics.title}
- **Load Time:** ${metrics.loadTime}ms
- **DOM Content Loaded:** ${metrics.domContentLoaded}ms

## Stylesheets Loaded (${metrics.stylesheets.length})

${metrics.stylesheets.map(s => `- ${s.href} (${s.rules} rules)`).join('\n')}

## Console Errors (${consoleErrors.length})

${consoleErrors.length > 0 ? consoleErrors.map(l => `- [${l.timestamp}] ${l.text}`).join('\n') : '✓ No console errors'}

## Page Errors (${pageErrors.length})

${pageErrors.length > 0 ? pageErrors.map(e => `- ${e.message}\n  ${e.stack}`).join('\n\n') : '✓ No page errors'}

## Network Summary

- **Total Requests:** ${networkLogs.length}
- **Failed Requests:** ${failedRequests.length}
- **CSS Files Loaded:** ${cssFiles.length}

## CSS Files Status

${cssFiles.map(f => `- [${f.status}] ${f.url}`).join('\n')}

${failedRequests.length > 0 ? `\n## Failed Requests\n\n${failedRequests.map(f => `- [${f.status}] ${f.url}`).join('\n')}` : ''}

## Generated Files

- Screenshot: ${screenshotFile}
- Console Log: ${consoleFile}
- Network Log: ${networkFile}
`;

        fs.writeFileSync(reportFile, report);

        console.log('\n=== DIAGNOSTIC COMPLETE ===');
        console.log(`Report: ${reportFile}`);
        console.log(`Screenshot: ${screenshotFile}`);
        console.log(`Console: ${consoleFile}`);
        console.log(`Network: ${networkFile}`);

        // Return results
        process.exit(0);

    } catch (error) {
        console.error('Error during diagnostic:', error);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();