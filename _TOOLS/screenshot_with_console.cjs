#!/usr/bin/env node
/**
 * Take screenshot AND capture ALL console messages
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/products/edit/10969';
const email = process.argv[3] || 'admin@mpptrade.pl';
const password = process.argv[4] || 'Admin123!MPP';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    // Collect ALL console messages (including logs, info, debug)
    const allMessages = [];

    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        const location = msg.location();

        allMessages.push({
            type: type,
            text: text,
            url: location.url,
            lineNumber: location.lineNumber
        });
    });

    // Collect page errors
    const pageErrors = [];
    page.on('pageerror', error => {
        pageErrors.push(error.toString());
    });

    // Collect request failures
    const requestFailures = [];
    page.on('requestfailed', request => {
        requestFailures.push({
            url: request.url(),
            error: request.failure().errorText
        });
    });

    try {
        // Login
        console.log('Logging in...');
        await page.goto('https://ppm.mpptrade.pl/login', { waitUntil: 'networkidle', timeout: 30000 });
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Navigate to product edit page
        console.log('Loading product edit page...');
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

        // Wait for page to fully load
        await page.waitForTimeout(5000);

        // Try to click Warianty tab
        try {
            const variantTab = await page.locator('text=Warianty').first();
            if (await variantTab.isVisible()) {
                console.log('Clicking Warianty tab...');
                await variantTab.click();
                await page.waitForTimeout(2000);
            }
        } catch (e) {
            console.log('Could not click Warianty tab:', e.message);
        }

        // Take screenshot
        const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
        const screenshotPath = path.join(__dirname, 'screenshots', `console_check_${timestamp}.png`);

        // Ensure screenshots directory exists
        const screenshotsDir = path.join(__dirname, 'screenshots');
        if (!fs.existsSync(screenshotsDir)) {
            fs.mkdirSync(screenshotsDir, { recursive: true });
        }

        await page.screenshot({ path: screenshotPath, fullPage: true });
        console.log(`\nScreenshot saved: ${screenshotPath}`);

    } catch (error) {
        console.error('Error:', error.message);
    }

    // Print ALL console messages
    console.log('\n=== ALL CONSOLE MESSAGES ===');
    console.log(`Total messages: ${allMessages.length}\n`);

    // Group by type
    const byType = {
        log: [],
        info: [],
        warn: [],
        warning: [],
        error: [],
        debug: []
    };

    allMessages.forEach(msg => {
        const type = msg.type.toLowerCase();
        if (byType[type]) {
            byType[type].push(msg);
        } else {
            byType['log'].push(msg);
        }
    });

    // Print each type
    Object.keys(byType).forEach(type => {
        if (byType[type].length > 0) {
            console.log(`\n--- ${type.toUpperCase()} (${byType[type].length}) ---`);
            byType[type].forEach((msg, i) => {
                console.log(`${i + 1}. ${msg.text}`);
                if (msg.url && msg.url !== 'undefined') {
                    console.log(`   Source: ${msg.url}:${msg.lineNumber}`);
                }
            });
        }
    });

    // Print page errors
    if (pageErrors.length > 0) {
        console.log(`\n--- PAGE ERRORS (${pageErrors.length}) ---`);
        pageErrors.forEach((err, i) => {
            console.log(`${i + 1}. ${err}`);
        });
    }

    // Print request failures
    if (requestFailures.length > 0) {
        console.log(`\n--- REQUEST FAILURES (${requestFailures.length}) ---`);
        requestFailures.forEach((failure, i) => {
            console.log(`${i + 1}. ${failure.url}`);
            console.log(`   Error: ${failure.error}`);
        });
    }

    await browser.close();
})();
