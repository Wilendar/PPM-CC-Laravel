#!/usr/bin/env node
/**
 * Variant Test Screenshot Automation
 *
 * Captures screenshots at key test points for Variant CRUD testing
 *
 * Usage:
 *   node screenshot_variant_test.cjs [productId] [OPTIONS]
 *
 * Options:
 *   --headless     Run in headless mode (default: false)
 *   --show         Show browser window (default)
 *
 * Examples:
 *   node screenshot_variant_test.cjs 11018              # Test product 11018
 *   node screenshot_variant_test.cjs 10969 --headless   # Headless mode
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Parse arguments
const args = process.argv.slice(2);
const productId = args.find(arg => !arg.startsWith('--') && /^\d+$/.test(arg)) || '11018';
const headless = args.includes('--headless');

// Configuration
const baseUrl = 'https://ppm.mpptrade.pl';
const editUrl = `${baseUrl}/admin/products/${productId}/edit`;
const email = 'admin@mpptrade.pl';
const password = 'Admin123!MPP';

// Screenshot directory
const screenshotsDir = path.join(__dirname, 'screenshots');
if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
}

// Helper function to capture screenshot
async function captureScreenshot(page, name, description) {
    const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
    const filename = `variant_test_${name}_${timestamp}.png`;
    const filepath = path.join(screenshotsDir, filename);

    await page.screenshot({
        path: filepath,
        fullPage: true
    });

    console.log(`✅ Screenshot: ${filename}`);
    console.log(`   Description: ${description}`);
    return filepath;
}

(async () => {
    console.log('=== VARIANT TEST SCREENSHOT AUTOMATION ===\n');
    console.log(`Product ID: ${productId}`);
    console.log(`URL: ${editUrl}`);
    console.log(`Mode: ${headless ? 'Headless' : 'Visible'}\n`);

    const browser = await chromium.launch({
        headless: headless,
        args: ['--disable-blink-features=AutomationControlled']
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    // Track console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    try {
        // STEP 1: Login
        console.log('[1/8] Logging in...');
        await page.goto(`${baseUrl}/login`, {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✅ Logged in\n');

        // STEP 2: Navigate to product edit page
        console.log('[2/8] Navigating to product edit page...');
        await page.goto(editUrl, {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        await page.waitForTimeout(2000);
        console.log('✅ Page loaded\n');

        // SCREENSHOT 1: Initial page state
        console.log('[3/8] Capturing initial state...');
        await captureScreenshot(page, '01_initial', 'Product edit page - initial load');
        console.log();

        // STEP 3: Click Warianty tab
        console.log('[4/8] Clicking Warianty tab...');
        try {
            const variantTab = page.locator('button.tab-enterprise:has-text("Warianty")').first();
            await variantTab.click();
            await page.waitForTimeout(1500); // Wait for Livewire
            console.log('✅ Warianty tab clicked\n');

            // SCREENSHOT 2: Warianty tab active
            console.log('[5/8] Capturing Warianty tab state...');
            await captureScreenshot(page, '02_warianty_tab', 'Warianty tab - active state');
            console.log();
        } catch (e) {
            console.log('⚠️ Could not click Warianty tab:', e.message, '\n');
        }

        // STEP 4: Check and interact with checkbox (if present)
        console.log('[6/8] Checking variant checkbox state...');
        try {
            const checkbox = page.locator('input[type="checkbox"][wire\\:model*="hasVariants"], input[type="checkbox"][x-model*="hasVariants"]').first();
            const exists = await checkbox.count() > 0;

            if (exists) {
                const isChecked = await checkbox.isChecked();
                console.log(`✅ Checkbox found (state: ${isChecked ? 'checked' : 'unchecked'})`);

                if (!isChecked) {
                    // Try to check it
                    await checkbox.check();
                    await page.waitForTimeout(500);
                    console.log('✅ Checkbox checked');
                }

                // SCREENSHOT 3: Checkbox checked state
                console.log('[7/8] Capturing checkbox checked state...');
                await captureScreenshot(page, '03_checkbox_checked', 'Checkbox "Produkt ma warianty" - checked state');
                console.log();
            } else {
                console.log('⚠️ Checkbox not found\n');
            }
        } catch (e) {
            console.log('⚠️ Error interacting with checkbox:', e.message, '\n');
        }

        // STEP 5: Check for "Dodaj wariant" button
        console.log('[8/8] Checking "Dodaj wariant" button...');
        try {
            const addButton = page.locator('button:has-text("Dodaj wariant")').first();
            const isVisible = await addButton.isVisible({ timeout: 3000 });

            if (isVisible) {
                console.log('✅ "Dodaj wariant" button visible');

                // Scroll to button
                await addButton.scrollIntoViewIfNeeded();
                await page.waitForTimeout(500);

                // SCREENSHOT 4: Add variant button visible
                await captureScreenshot(page, '04_add_button', 'Add variant button - visible state');
                console.log();

                // Optional: Click button to show modal (uncomment if needed)
                /*
                await addButton.click();
                await page.waitForTimeout(1000);
                await captureScreenshot(page, '05_add_modal', 'Add variant modal - opened');
                console.log();
                */
            } else {
                console.log('⚠️ "Dodaj wariant" button not visible\n');
            }
        } catch (e) {
            console.log('⚠️ Error checking add button:', e.message, '\n');
        }

        // STEP 6: Check for existing variants
        console.log('Checking for existing variants...');
        try {
            const variantRows = await page.locator('.variant-row, [data-variant-id], tr[data-variant-id]').count();
            if (variantRows > 0) {
                console.log(`✅ Found ${variantRows} existing variants`);

                // SCREENSHOT 5: Variant list
                await captureScreenshot(page, '05_variant_list', `Variant list - ${variantRows} variants`);
                console.log();
            } else {
                console.log('ℹ️ No existing variants\n');
            }
        } catch (e) {
            console.log('⚠️ Error checking variants:', e.message, '\n');
        }

    } catch (error) {
        console.error('\n❌ Screenshot automation error:', error.message);
    }

    // Summary
    console.log('\n=== SUMMARY ===');
    console.log(`Screenshots saved to: ${screenshotsDir}`);
    console.log(`Console errors detected: ${consoleErrors.length}`);

    if (consoleErrors.length > 0) {
        console.log('\n⚠️ CONSOLE ERRORS:');
        consoleErrors.forEach((err, i) => {
            console.log(`${i + 1}. ${err}`);
        });
    } else {
        console.log('✅ No console errors detected');
    }

    console.log('\n✅ Screenshot automation complete!');

    await browser.close();
})();
