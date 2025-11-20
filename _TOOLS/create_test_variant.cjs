#!/usr/bin/env node
/**
 * CREATE TEST VARIANT - Automate variant creation for product 10969
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
    console.log(`${c.cyan}=== CREATE TEST VARIANT ===${c.reset}\n`);

    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    try {
        // Login
        console.log(`${c.yellow}[1/7] Logging in...${c.reset}`);
        await page.goto('https://ppm.mpptrade.pl/login');
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log(`${c.green}✓ Logged in${c.reset}\n`);

        // Open product
        console.log(`${c.yellow}[2/7] Opening product 10969...${c.reset}`);
        await page.goto('https://ppm.mpptrade.pl/admin/products/10969/edit');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(3000);
        console.log(`${c.green}✓ Product loaded${c.reset}\n`);

        // Click Warianty tab
        console.log(`${c.yellow}[3/7] Clicking Warianty tab...${c.reset}`);
        await page.click('button:has-text("Warianty")');
        await page.waitForTimeout(2000);
        console.log(`${c.green}✓ Tab opened${c.reset}\n`);

        // Click "Dodaj wariant" button
        console.log(`${c.yellow}[4/7] Clicking "Dodaj wariant"...${c.reset}`);
        const addButtonSelectors = [
            'button:has-text("Dodaj wariant")',
            'button:has-text("Dodaj")',
            '.btn-enterprise-primary:has-text("Dodaj")'
        ];

        let buttonClicked = false;
        for (const selector of addButtonSelectors) {
            try {
                await page.click(selector, { timeout: 5000 });
                buttonClicked = true;
                break;
            } catch (e) {
                // Try next
            }
        }

        if (!buttonClicked) {
            console.log(`${c.red}✗ Could not find "Dodaj wariant" button${c.reset}`);
            await page.waitForTimeout(60000); // Keep open for manual inspection
            await browser.close();
            return;
        }
        console.log(`${c.green}✓ Button clicked${c.reset}\n`);

        // Wait for modal
        await page.waitForTimeout(2000);

        // Fill variant form
        console.log(`${c.yellow}[5/7] Filling variant form...${c.reset}`);

        const timestamp = Date.now();
        const testSKU = `TEST-VAR-${timestamp}`;
        const testName = `Test Variant ${timestamp}`;

        // Fill SKU
        const skuSelectors = ['input[name="sku"]', 'input#sku', 'input[placeholder*="SKU"]'];
        for (const selector of skuSelectors) {
            try {
                await page.fill(selector, testSKU, { timeout: 3000 });
                console.log(`  SKU: ${testSKU}`);
                break;
            } catch (e) {
                // Try next
            }
        }

        // Fill Name
        const nameSelectors = ['input[name="name"]', 'input#name', 'input[placeholder*="nazwa"]', 'input[placeholder*="Nazwa"]'];
        for (const selector of nameSelectors) {
            try {
                await page.fill(selector, testName, { timeout: 3000 });
                console.log(`  Name: ${testName}`);
                break;
            } catch (e) {
                // Try next
            }
        }

        console.log(`${c.green}✓ Form filled${c.reset}\n`);

        // Save variant
        console.log(`${c.yellow}[6/7] Saving variant...${c.reset}`);
        const saveSelectors = [
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
                // Try next
            }
        }

        if (saved) {
            await page.waitForTimeout(3000);
            console.log(`${c.green}✓ Variant saved${c.reset}\n`);
        } else {
            console.log(`${c.yellow}Could not find save button - check manually${c.reset}\n`);
        }

        // Verify variant was created
        console.log(`${c.yellow}[7/7] Verifying variant...${c.reset}`);
        await page.waitForTimeout(2000);

        const variantVisible = await page.isVisible(`text=${testSKU}`);
        if (variantVisible) {
            console.log(`${c.green}✓ Variant visible in list!${c.reset}\n`);
            console.log(`${c.green}=== VARIANT CREATED SUCCESSFULLY ===${c.reset}`);
            console.log(`SKU: ${testSKU}`);
            console.log(`Name: ${testName}`);
        } else {
            console.log(`${c.yellow}Variant not visible yet - might need page reload${c.reset}\n`);
        }

        console.log(`\n${c.cyan}You can now run the conversion test!${c.reset}`);
        await page.waitForTimeout(10000);
        await browser.close();

    } catch (error) {
        console.error(`${c.red}Error: ${error.message}${c.reset}`);
        console.error(error.stack);
        await browser.close();
        process.exit(1);
    }

})();
