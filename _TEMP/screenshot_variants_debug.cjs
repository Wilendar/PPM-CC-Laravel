#!/usr/bin/env node
/**
 * Debug Screenshot - VariantManagement page
 * Extended timeout + network logging
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function takeScreenshot() {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
    const screenshotsDir = path.join(__dirname, '..', '_TOOLS', 'screenshots');

    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir, { recursive: true });
    }

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    console.log('\n=== DEBUG SCREENSHOT: /admin/variants ===\n');

    // Enable request logging
    page.on('response', response => {
        if (response.status() >= 400) {
            console.log(`⚠️  ${response.status()} ${response.url()}`);
        }
    });

    try {
        console.log('Loading page (30s timeout)...');
        await page.goto('https://ppm.mpptrade.pl/admin/variants', {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        console.log('Waiting for content (5s)...');
        await page.waitForTimeout(5000);

        const finalUrl = page.url();
        console.log(`Final URL: ${finalUrl}`);

        // Check for redirects
        if (finalUrl.includes('/login')) {
            console.log('❌ REDIRECTED TO LOGIN - Auth required!');
        } else {
            console.log('✅ No redirect - page loaded');
        }

        // Take screenshot
        const viewportPath = path.join(screenshotsDir, `debug_viewport_${timestamp}.png`);
        await page.screenshot({
            path: viewportPath,
            fullPage: false
        });
        console.log(`✅ Screenshot: ${viewportPath}`);

        // Get page content info
        const pageInfo = await page.evaluate(() => {
            return {
                title: document.title,
                hasTable: !!document.querySelector('.enterprise-table'),
                hasEmptyState: document.body.innerText.includes('Brak wariantów'),
                bodyText: document.body.innerText.substring(0, 500)
            };
        });

        console.log('\n--- Page Info ---');
        console.log(`Title: ${pageInfo.title}`);
        console.log(`Has Table: ${pageInfo.hasTable}`);
        console.log(`Has Empty State: ${pageInfo.hasEmptyState}`);
        console.log(`Body Text Preview: ${pageInfo.bodyText.substring(0, 100)}...`);

        console.log('\n✅ DEBUG COMPLETE\n');

    } catch (error) {
        console.error('❌ ERROR:', error.message);
    } finally {
        await browser.close();
    }
}

takeScreenshot();
