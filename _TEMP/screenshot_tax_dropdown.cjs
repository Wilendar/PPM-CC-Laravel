#!/usr/bin/env node

const playwright = require('playwright');
const path = require('path');
const fs = require('fs');

(async () => {
    const browser = await playwright.chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        console.log('\n=== SCREENSHOT TAX DROPDOWN STYLING ===\n');

        // Navigate to product edit page
        const url = 'https://ppm.mpptrade.pl/admin/products/11033/edit';
        console.log('Loading page:', url);
        await page.goto(url, { waitUntil: 'networkidle' });
        console.log('✅ Page loaded');

        // Wait for Shop tabs to be visible
        await page.waitForSelector('.shop-tab-button', { timeout: 10000 });
        console.log('✅ Shop tabs found');

        // Click first shop tab (B2B Test DEV)
        const shopTabButton = await page.$('.shop-tab-button');
        if (!shopTabButton) {
            throw new Error('Shop tab button not found');
        }
        await shopTabButton.click();
        await page.waitForTimeout(1000); // Wait for tab switch
        console.log('✅ Shop tab clicked');

        // Wait for Basic tab content to load
        await page.waitForSelector('#tax_rate', { timeout: 10000 });
        console.log('✅ Tax rate dropdown found');

        // Scroll dropdown into view
        await page.evaluate(() => {
            document.querySelector('#tax_rate').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        await page.waitForTimeout(500);

        // Take screenshot BEFORE opening dropdown
        const screenshotDir = path.join(__dirname, '..', '_TOOLS', 'screenshots');
        const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
        const beforePath = path.join(screenshotDir, `tax_dropdown_before_${timestamp}.png`);

        await page.screenshot({ path: beforePath, fullPage: true });
        console.log('✅ Screenshot BEFORE:', beforePath);

        // Open dropdown
        await page.click('#tax_rate');
        await page.waitForTimeout(500); // Wait for dropdown animation
        console.log('✅ Dropdown opened');

        // Take screenshot WITH dropdown open
        const afterPath = path.join(screenshotDir, `tax_dropdown_open_${timestamp}.png`);
        await page.screenshot({ path: afterPath, fullPage: true });
        console.log('✅ Screenshot AFTER:', afterPath);

        // Get dropdown options HTML
        const dropdownHTML = await page.evaluate(() => {
            const select = document.querySelector('#tax_rate');
            const options = Array.from(select.querySelectorAll('option'));
            return options.map(opt => ({
                value: opt.value,
                text: opt.textContent.trim(),
                class: opt.className
            }));
        });

        console.log('\n--- Dropdown Options ---');
        dropdownHTML.forEach((opt, index) => {
            console.log(`${index + 1}. [${opt.class || 'no-class'}] ${opt.text} (value: ${opt.value})`);
        });

        console.log('\n✅ SCREENSHOT COMPLETE');
        console.log('Before:', beforePath);
        console.log('After:', afterPath);

    } catch (error) {
        console.error('❌ ERROR:', error.message);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
