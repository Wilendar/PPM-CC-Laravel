#!/usr/bin/env node
/**
 * PREPARE TEST: Check "Produkt z wariantami" checkbox
 */

const { chromium } = require('playwright');

const c = {
    reset: '\x1b[0m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    cyan: '\x1b[36m',
};

(async () => {
    console.log(`${c.cyan}=== PREPARE TEST: CHECK CHECKBOX ===${c.reset}\n`);

    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    try {
        // Login
        console.log(`${c.yellow}[1/4] Logging in...${c.reset}`);
        await page.goto('https://ppm.mpptrade.pl/login');
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log(`${c.green}✓ Logged in${c.reset}\n`);

        // Open product 10969
        console.log(`${c.yellow}[2/4] Opening product 10969...${c.reset}`);
        await page.goto('https://ppm.mpptrade.pl/admin/products/10969/edit');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(3000);
        console.log(`${c.green}✓ Product loaded${c.reset}\n`);

        // Check current state
        const checkboxSelector = 'input#is_variant_master';
        const isInitiallyChecked = await page.isChecked(checkboxSelector);
        console.log(`Current checkbox state: ${isInitiallyChecked ? c.green + 'CHECKED ✓' + c.reset : c.red + 'UNCHECKED ✗' + c.reset}`);

        if (!isInitiallyChecked) {
            // Check the checkbox
            console.log(`${c.yellow}[3/4] Checking checkbox...${c.reset}`);
            await page.click(checkboxSelector);
            await page.waitForTimeout(2000);

            const isNowChecked = await page.isChecked(checkboxSelector);
            console.log(`Checkbox after click: ${isNowChecked ? c.green + 'CHECKED ✓' + c.reset : c.red + 'UNCHECKED ✗' + c.reset}`);

            // Save
            console.log(`${c.yellow}[4/4] Saving product...${c.reset}`);
            // Try different selectors for save button
            const saveSelectors = [
                'button:has-text("Zapisz i zamknij")',
                'button:has-text("Zapisz")',
                'button.btn-enterprise-success',
                'button[type="submit"]'
            ];

            let saved = false;
            for (const selector of saveSelectors) {
                try {
                    await page.click(selector, { timeout: 5000 });
                    saved = true;
                    break;
                } catch (e) {
                    // Try next selector
                }
            }

            if (saved) {
                await page.waitForTimeout(3000);
                console.log(`${c.green}✓ Saved${c.reset}\n`);
            } else {
                console.log(`${c.red}✗ Could not find save button${c.reset}\n`);
            }

            // Reload and verify
            await page.reload({ waitUntil: 'networkidle' });
            await page.waitForTimeout(3000);

            const isFinallyChecked = await page.isChecked(checkboxSelector);
            console.log(`${c.cyan}Final verification:${c.reset}`);
            console.log(`Checkbox: ${isFinallyChecked ? c.green + 'CHECKED ✓' + c.reset : c.red + 'UNCHECKED ✗' + c.reset}`);

            if (isFinallyChecked) {
                console.log(`\n${c.green}=== PREPARATION COMPLETE ===${c.reset}`);
                console.log(`Product 10969 is ready for conversion test!`);
            } else {
                console.log(`\n${c.red}=== PREPARATION FAILED ===${c.reset}`);
                console.log(`Checkbox did not persist after save.`);
            }
        } else {
            console.log(`\n${c.green}=== ALREADY PREPARED ===${c.reset}`);
            console.log(`Checkbox is already checked. Product ready for test.`);
        }

        await page.waitForTimeout(5000);
        await browser.close();

    } catch (error) {
        console.error(`${c.red}Error: ${error.message}${c.reset}`);
        console.error(error.stack);
        await browser.close();
        process.exit(1);
    }

})();
