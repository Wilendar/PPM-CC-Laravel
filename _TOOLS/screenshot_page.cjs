#!/usr/bin/env node
/**
 * Screenshot Page - Playwright Script
 *
 * Takes screenshot of specified URL for visual layout inspection
 * Part of /analizuj_strone diagnostic workflow
 *
 * Usage:
 *   node _TOOLS/screenshot_page.cjs <url>
 *
 * Example:
 *   node _TOOLS/screenshot_page.cjs https://ppm.mpptrade.pl/admin/products
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function takeScreenshot(url) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
    const screenshotsDir = path.join(__dirname, 'screenshots');

    // Create screenshots directory if not exists
    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir, { recursive: true });
    }

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    });

    const page = await context.newPage();

    console.log(`\n=== SCREENSHOT PAGE: ${url} ===\n`);
    console.log('Loading page...');

    try {
        // Navigate and wait for network idle
        await page.goto(url, {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        console.log('✅ Page loaded successfully');

        // Wait a bit for any animations/transitions
        await page.waitForTimeout(1000);

        // Full page screenshot
        const fullPagePath = path.join(screenshotsDir, `page_full_${timestamp}.png`);
        await page.screenshot({
            path: fullPagePath,
            fullPage: true
        });
        console.log(`✅ Full page screenshot: ${fullPagePath}`);

        // Viewport screenshot (what user sees)
        const viewportPath = path.join(screenshotsDir, `page_viewport_${timestamp}.png`);
        await page.screenshot({
            path: viewportPath,
            fullPage: false
        });
        console.log(`✅ Viewport screenshot: ${viewportPath}`);

        // Get page title for context
        const title = await page.title();
        console.log(`\nPage Title: ${title}`);

        // Get basic layout info
        const layoutInfo = await page.evaluate(() => {
            const body = document.body;
            const main = document.querySelector('[id*="main"], [class*="main-container"], main');

            return {
                bodySize: {
                    scrollWidth: body.scrollWidth,
                    scrollHeight: body.scrollHeight,
                    clientWidth: body.clientWidth,
                    clientHeight: body.clientHeight
                },
                mainContainer: main ? {
                    tag: main.tagName,
                    id: main.id,
                    classes: main.className,
                    width: main.offsetWidth,
                    height: main.offsetHeight
                } : null
            };
        });

        console.log('\n--- Layout Info ---');
        console.log(`Body Size: ${layoutInfo.bodySize.scrollWidth}x${layoutInfo.bodySize.scrollHeight}`);
        if (layoutInfo.mainContainer) {
            console.log(`Main Container: <${layoutInfo.mainContainer.tag}> #${layoutInfo.mainContainer.id}`);
            console.log(`  Size: ${layoutInfo.mainContainer.width}x${layoutInfo.mainContainer.height}`);
        }

        console.log('\n✅ SCREENSHOT COMPLETE\n');

        return {
            success: true,
            screenshots: {
                fullPage: fullPagePath,
                viewport: viewportPath
            },
            layoutInfo
        };

    } catch (error) {
        console.error('❌ ERROR:', error.message);
        return {
            success: false,
            error: error.message
        };
    } finally {
        await browser.close();
    }
}

// Main execution
const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/products';
takeScreenshot(url).then(result => {
    if (!result.success) {
        process.exit(1);
    }
});
