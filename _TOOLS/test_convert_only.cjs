#!/usr/bin/env node
/**
 * QUICK TEST: Convert Variants Only
 */

const { chromium } = require('playwright');
const path = require('path');

const c = {
    reset: '\x1b[0m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    cyan: '\x1b[36m',
};

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

(async () => {
    console.log(`${c.cyan}=== QUICK TEST: CONVERT VARIANTS ===${c.reset}\n`);

    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    try {
        // Login
        console.log(`${c.yellow}Logging in...${c.reset}`);
        await page.goto('https://ppm.mpptrade.pl/login');
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log(`${c.green}✓ Logged in${c.reset}\n`);

        // Open product 10969
        console.log(`${c.yellow}Opening product 10969...${c.reset}`);
        await page.goto('https://ppm.mpptrade.pl/admin/products/10969/edit');
        await page.waitForLoadState('networkidle');
        await sleep(3000);
        console.log(`${c.green}✓ Product loaded${c.reset}\n`);

        // Check initial state
        const checkboxSelector = 'input#is_variant_master';
        const isInitiallyChecked = await page.isChecked(checkboxSelector);
        console.log(`Checkbox state: ${isInitiallyChecked ? c.green + 'CHECKED ✓' + c.reset : c.red + 'UNCHECKED ✗' + c.reset}`);

        if (!isInitiallyChecked) {
            console.log(`${c.red}ERROR: Checkbox should be CHECKED!${c.reset}`);
            await sleep(60000);
            await browser.close();
            return;
        }

        // Uncheck checkbox
        console.log(`\n${c.yellow}Unchecking checkbox...${c.reset}`);
        await page.click(checkboxSelector);
        await sleep(2000);

        // Check if modal appeared
        console.log(`${c.yellow}Checking for modal...${c.reset}`);
        const modalVisible = await page.isVisible('text=Uwaga: Produkt ma');
        if (modalVisible) {
            console.log(`${c.green}✓ Modal appeared!${c.reset}\n`);

            // Take screenshot
            const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
            await page.screenshot({
                path: path.join(__dirname, 'screenshots', `modal_convert_test_${timestamp}.png`),
                fullPage: true
            });

            // Click "Convert"
            console.log(`${c.yellow}Clicking "Konwertuj na produkty"...${c.reset}`);
            await page.click('button:has-text("Konwertuj na produkty")');
            await sleep(5000); // Wait for conversion

            // Check for success message
            const successVisible = await page.isVisible('text=Konwersja zakończona');
            if (successVisible) {
                console.log(`${c.green}✓ SUCCESS MESSAGE FOUND!${c.reset}\n`);

                // Check final state
                const checkboxAfter = await page.isChecked(checkboxSelector);
                console.log(`Checkbox: ${checkboxAfter ? c.red + 'CHECKED ✗' + c.reset : c.green + 'UNCHECKED ✓' + c.reset}`);

                const tabVisible = await page.isVisible('button:has-text("Warianty")');
                console.log(`Tab: ${tabVisible ? c.red + 'VISIBLE ✗' + c.reset : c.green + 'HIDDEN ✓' + c.reset}`);

                // Save
                console.log(`\n${c.yellow}Saving...${c.reset}`);
                await page.click('button.btn-enterprise-success:has-text("Zapisz i zamknij")');
                await sleep(3000);
                console.log(`${c.green}✓ Saved!${c.reset}`);

                // Check if redirected to products list
                await sleep(2000);
                const currentUrl = page.url();
                if (currentUrl.includes('/admin/products') && !currentUrl.includes('/edit')) {
                    console.log(`${c.green}✓ Redirected to products list${c.reset}`);
                }

                console.log(`\n${c.green}=== TEST PASSED ===${c.reset}`);
            } else {
                console.log(`${c.red}✗ No success message found${c.reset}`);

                // Check for error message
                const errorVisible = await page.isVisible('text=Błąd');
                if (errorVisible) {
                    const errorText = await page.textContent('text=Błąd');
                    console.log(`${c.red}Error message: ${errorText}${c.reset}`);
                }

                console.log(`\n${c.red}=== TEST FAILED ===${c.reset}`);
            }
        } else {
            console.log(`${c.red}✗ Modal did NOT appear!${c.reset}`);
            console.log(`\n${c.red}=== TEST FAILED ===${c.reset}`);
        }

        console.log(`\n${c.yellow}Press Ctrl+C to close browser${c.reset}`);
        await sleep(30000);

    } catch (error) {
        console.error(`${c.red}Error: ${error.message}${c.reset}`);
        console.error(error.stack);
    } finally {
        await browser.close();
    }

})();
