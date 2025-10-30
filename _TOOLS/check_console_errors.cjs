#!/usr/bin/env node
/**
 * Console Errors Checker - PPM-CC-Laravel
 * Captures all console messages (errors, warnings, logs) from page
 */

const { chromium } = require('playwright');

// Admin credentials
const ADMIN_CREDENTIALS = {
    email: 'admin@mpptrade.pl',
    password: 'Admin123!MPP'
};

async function checkConsoleErrors(url) {
    console.log('\n=== CONSOLE ERRORS CHECKER ===');
    console.log(`URL: ${url}\n`);

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    // Storage for all console messages
    const consoleMessages = {
        errors: [],
        warnings: [],
        logs: [],
        info: []
    };

    // Capture ALL console messages
    page.on('console', msg => {
        const text = msg.text();
        const type = msg.type();
        const location = msg.location();

        const entry = {
            type,
            text,
            url: location.url,
            lineNumber: location.lineNumber,
            columnNumber: location.columnNumber
        };

        if (type === 'error') {
            consoleMessages.errors.push(entry);
            console.log(`🔴 ERROR: ${text}`);
            if (location.url) {
                console.log(`   at ${location.url}:${location.lineNumber}:${location.columnNumber}`);
            }
        } else if (type === 'warning') {
            consoleMessages.warnings.push(entry);
            console.log(`🟡 WARNING: ${text}`);
        } else if (type === 'log') {
            consoleMessages.logs.push(entry);
        } else if (type === 'info') {
            consoleMessages.info.push(entry);
        }
    });

    // Capture page errors (unhandled exceptions)
    page.on('pageerror', error => {
        consoleMessages.errors.push({
            type: 'pageerror',
            text: error.message,
            stack: error.stack
        });
        console.log(`💥 PAGE ERROR: ${error.message}`);
        console.log(`   Stack: ${error.stack}`);
    });

    // Capture failed requests
    page.on('requestfailed', request => {
        const failure = request.failure();
        console.log(`🌐 REQUEST FAILED: ${request.url()}`);
        console.log(`   Reason: ${failure ? failure.errorText : 'Unknown'}`);
    });

    try {
        // Login
        console.log('\n🔐 Logging in...');
        await page.goto('https://ppm.mpptrade.pl/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        await page.fill('input[name="email"]', ADMIN_CREDENTIALS.email);
        await page.fill('input[name="password"]', ADMIN_CREDENTIALS.password);
        await page.click('button[type="submit"]');

        await page.waitForTimeout(3000);
        console.log('✅ Logged in');

        // Navigate to target page
        console.log(`\n📄 Loading ${url}...`);
        await page.goto(url, {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        // Wait for page to fully render
        await page.waitForTimeout(2000);
        console.log('✅ Page loaded\n');

        // Try to open modal with "Wartości" button
        console.log('🖱️  Attempting to click "Wartości" button...');
        try {
            const wartosciButton = await page.locator('button:has-text("Wartości")').first();
            if (await wartosciButton.isVisible()) {
                await wartosciButton.click();
                console.log('✅ Clicked "Wartości"');
                await page.waitForTimeout(2000);

                // Try to click "Dodaj Wartość"
                console.log('🖱️  Attempting to click "Dodaj Wartość"...');
                const dodajButton = await page.locator('button:has-text("Dodaj Wartość"), button:has-text("Dodaj Wartosc")').first();
                if (await dodajButton.isVisible()) {
                    await dodajButton.click();
                    console.log('✅ Clicked "Dodaj Wartość"');
                    await page.waitForTimeout(2000);
                }
            }
        } catch (e) {
            console.log('⚠️  Could not interact with modal:', e.message);
        }

        // Wait additional time to catch any delayed errors
        await page.waitForTimeout(3000);

        // Print summary
        console.log('\n' + '='.repeat(60));
        console.log('CONSOLE MESSAGES SUMMARY');
        console.log('='.repeat(60));
        console.log(`🔴 ERRORS: ${consoleMessages.errors.length}`);
        console.log(`🟡 WARNINGS: ${consoleMessages.warnings.length}`);
        console.log(`📝 LOGS: ${consoleMessages.logs.length}`);
        console.log(`ℹ️  INFO: ${consoleMessages.info.length}`);
        console.log('='.repeat(60));

        // Detailed error report
        if (consoleMessages.errors.length > 0) {
            console.log('\n📋 DETAILED ERROR REPORT:');
            console.log('='.repeat(60));
            consoleMessages.errors.forEach((err, idx) => {
                console.log(`\n[ERROR ${idx + 1}]`);
                console.log(`Type: ${err.type}`);
                console.log(`Message: ${err.text}`);
                if (err.url) {
                    console.log(`Location: ${err.url}:${err.lineNumber}:${err.columnNumber}`);
                }
                if (err.stack) {
                    console.log(`Stack: ${err.stack}`);
                }
            });
        }

        // Detailed warning report
        if (consoleMessages.warnings.length > 0) {
            console.log('\n\n📋 DETAILED WARNING REPORT:');
            console.log('='.repeat(60));
            consoleMessages.warnings.forEach((warn, idx) => {
                console.log(`\n[WARNING ${idx + 1}]`);
                console.log(`Message: ${warn.text}`);
                if (warn.url) {
                    console.log(`Location: ${warn.url}`);
                }
            });
        }

        console.log('\n');

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        console.error(error.stack);
    } finally {
        await browser.close();
    }
}

// Run
const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/variants';
checkConsoleErrors(url).catch(console.error);
