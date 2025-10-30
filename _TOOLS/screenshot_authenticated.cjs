#!/usr/bin/env node
/**
 * Authenticated Screenshot - Playwright Script
 *
 * Takes screenshot of admin pages with login credentials
 * Used for visual verification of authenticated pages
 *
 * Usage:
 *   node _TOOLS/screenshot_authenticated.cjs <url>
 *
 * Credentials: admin@mpptrade.pl / Admin123!MPP
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function takeAuthenticatedScreenshot(url) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
    const screenshotsDir = path.join(__dirname, 'screenshots');

    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir, { recursive: true });
    }

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    });

    const page = await context.newPage();

    console.log(`\n=== AUTHENTICATED SCREENSHOT: ${url} ===\n`);

    try {
        // Step 1: Navigate to login page
        console.log('Step 1: Navigating to login page...');
        await page.goto('https://ppm.mpptrade.pl/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        // Step 2: Fill login form
        console.log('Step 2: Filling login credentials...');
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');

        // Step 3: Submit login form
        console.log('Step 3: Submitting login form...');
        await page.click('button[type="submit"]');

        // Wait for navigation away from login page
        await page.waitForURL(url => !url.includes('/login'), {
            timeout: 15000
        }).catch(() => {
            // If timeout, check current URL anyway
        });

        // Wait for page to settle
        await page.waitForTimeout(2000);

        // Check if login successful
        const currentUrl = page.url();
        if (currentUrl.includes('/login')) {
            throw new Error('Login failed - still on login page');
        }
        console.log('✅ Login successful');

        // Step 4: Navigate to target URL
        console.log(`Step 4: Navigating to ${url}...`);
        await page.goto(url, {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        console.log('✅ Target page loaded');

        // Wait for Livewire to initialize
        await page.waitForTimeout(2000);

        // Check for error messages
        const hasError = await page.evaluate(() => {
            const errorTexts = [
                'BadMethodCallException',
                'hasPages does not exist',
                'Exception',
                'Error at'
            ];
            const bodyText = document.body.innerText;
            return errorTexts.some(text => bodyText.includes(text));
        });

        if (hasError) {
            console.log('⚠️  WARNING: Page contains error messages');
        } else {
            console.log('✅ No error messages detected');
        }

        // Step 5: Take screenshots
        const fullPagePath = path.join(screenshotsDir, `auth_full_${timestamp}.png`);
        await page.screenshot({
            path: fullPagePath,
            fullPage: true
        });
        console.log(`✅ Full page screenshot: ${fullPagePath}`);

        const viewportPath = path.join(screenshotsDir, `auth_viewport_${timestamp}.png`);
        await page.screenshot({
            path: viewportPath,
            fullPage: false
        });
        console.log(`✅ Viewport screenshot: ${viewportPath}`);

        // Get page info
        const pageInfo = await page.evaluate(() => {
            return {
                title: document.title,
                url: window.location.href,
                bodyText: document.body.innerText.substring(0, 500),
                hasTable: !!document.querySelector('table'),
                hasLivewire: !!document.querySelector('[wire\\:id]'),
                hasPagination: !!document.querySelector('[role="navigation"]')
            };
        });

        console.log('\n--- Page Info ---');
        console.log(`Title: ${pageInfo.title}`);
        console.log(`URL: ${pageInfo.url}`);
        console.log(`Has Table: ${pageInfo.hasTable}`);
        console.log(`Has Livewire: ${pageInfo.hasLivewire}`);
        console.log(`Has Pagination: ${pageInfo.hasPagination}`);

        console.log('\n✅ AUTHENTICATED SCREENSHOT COMPLETE\n');

        return {
            success: true,
            hasError: hasError,
            screenshots: {
                fullPage: fullPagePath,
                viewport: viewportPath
            },
            pageInfo
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
const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/price-management/price-groups';
takeAuthenticatedScreenshot(url).then(result => {
    if (!result.success) {
        process.exit(1);
    }
    if (result.hasError) {
        console.log('\n⚠️  Page loaded but contains error messages - check screenshot!');
        process.exit(2);
    }
});
