#!/usr/bin/env node
/**
 * Screenshot Variants Tab - Authenticated with Tab Click
 *
 * Logs in, navigates to product edit, clicks Warianty tab, takes screenshot
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/products/10969/edit';

(async () => {
    console.log('=== SCREENSHOT VARIANTS TAB ===\n');

    const browser = await chromium.launch({
        headless: false,  // Show browser to verify
        args: ['--disable-blink-features=AutomationControlled']
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    try {
        // Login
        console.log('[1/5] Logging in...');
        await page.goto('https://ppm.mpptrade.pl/login', { waitUntil: 'networkidle', timeout: 30000 });
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✅ Logged in');

        // Navigate to product edit
        console.log('[2/5] Loading product edit page...');
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
        console.log('✅ Page loaded');

        // Wait for Livewire
        console.log('[3/5] Waiting for Livewire...');
        await page.waitForFunction(() => {
            return window.Livewire !== undefined && window.Alpine !== undefined;
        }, { timeout: 10000 });
        console.log('✅ Livewire ready');

        // Click Warianty tab (button with wire:click)
        console.log('[4/5] Clicking Warianty tab...');
        const variantTab = page.locator('button.tab-enterprise:has-text("Warianty")');
        await variantTab.waitFor({ state: 'visible', timeout: 5000 });
        await variantTab.click();

        // Wait for Livewire to process wire:click and show tab content
        await page.waitForTimeout(3000);
        console.log('✅ Warianty tab active');

        // Scroll to Warianty section
        await page.evaluate(() => {
            const variantsSection = document.querySelector('div[x-data*="variantPrices"]');
            if (variantsSection) {
                variantsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        await page.waitForTimeout(1000);

        // Take screenshots
        console.log('[5/5] Taking screenshots...');
        const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
        const screenshotsDir = path.join(__dirname, 'screenshots');

        if (!fs.existsSync(screenshotsDir)) {
            fs.mkdirSync(screenshotsDir, { recursive: true });
        }

        const fullPath = path.join(screenshotsDir, `variants_tab_full_${timestamp}.png`);
        const viewportPath = path.join(screenshotsDir, `variants_tab_viewport_${timestamp}.png`);

        await page.screenshot({ path: fullPath, fullPage: true });
        await page.screenshot({ path: viewportPath, fullPage: false });

        console.log(`\n✅ SCREENSHOTS SAVED:`);
        console.log(`   Full page: ${fullPath}`);
        console.log(`   Viewport: ${viewportPath}`);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
    }

    await browser.close();
})();
