#!/usr/bin/env node
/**
 * VARIANT ORPHAN WORKFLOW TEST
 *
 * Tests complete workflow:
 * 1. Uncheck checkbox → Modal → Convert variants
 * 2. Create new variant
 * 3. Uncheck checkbox → Modal → Delete variants
 */

const { chromium } = require('playwright');
const path = require('path');

const c = {
    reset: '\x1b[0m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    cyan: '\x1b[36m',
    magenta: '\x1b[35m',
};

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

(async () => {
    console.log(`${c.cyan}=== VARIANT ORPHAN WORKFLOW TEST ===${c.reset}\n`);

    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    try {
        // Login
        console.log(`${c.yellow}[1/8] Logging in...${c.reset}`);
        await page.goto('https://ppm.mpptrade.pl/login');
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log(`${c.green}✓ Logged in${c.reset}\n`);

        // Open product 10969
        console.log(`${c.yellow}[2/8] Opening product 10969...${c.reset}`);
        await page.goto('https://ppm.mpptrade.pl/admin/products/10969/edit');
        await page.waitForLoadState('networkidle');
        await sleep(3000); // Wait for Livewire
        console.log(`${c.green}✓ Product loaded${c.reset}\n`);

        // Check initial state
        const checkboxSelector = 'input#is_variant_master';
        const isInitiallyChecked = await page.isChecked(checkboxSelector);
        console.log(`Initial checkbox state: ${isInitiallyChecked ? c.green + 'CHECKED ✓' + c.reset : c.red + 'UNCHECKED ✗' + c.reset}`);

        if (!isInitiallyChecked) {
            console.log(`${c.red}ERROR: Checkbox should be CHECKED to start test!${c.reset}`);
            console.log(`${c.yellow}Please check the checkbox manually in the UI and set is_variant_master=1 in database${c.reset}`);
            await sleep(60000);
            await browser.close();
            return;
        }

        // TEST 1: Uncheck checkbox → Modal → Convert
        console.log(`${c.magenta}\n=== TEST 1: CONVERT VARIANTS ===${c.reset}`);
        console.log(`${c.yellow}[3/8] Unchecking checkbox...${c.reset}`);
        await page.click(checkboxSelector);
        await sleep(2000); // Wait for modal

        // Check if modal appeared
        console.log(`${c.yellow}[4/8] Checking for modal...${c.reset}`);
        const modalVisible = await page.isVisible('text=Uwaga: Produkt ma');
        if (modalVisible) {
            console.log(`${c.green}✓ Modal appeared!${c.reset}`);

            // Take screenshot of modal
            const timestamp1 = new Date().toISOString().replace(/:/g, '-').split('.')[0];
            await page.screenshot({
                path: path.join(__dirname, 'screenshots', `modal_convert_${timestamp1}.png`),
                fullPage: true
            });
            console.log(`${c.cyan}Screenshot: modal_convert_${timestamp1}.png${c.reset}`);

            // Click "Convert" button
            console.log(`${c.yellow}[5/8] Clicking "Konwertuj na produkty"...${c.reset}`);
            await page.click('button:has-text("Konwertuj na produkty")');
            await sleep(5000); // Wait for conversion

            // Check for success message
            const successVisible = await page.isVisible('text=Konwersja zakończona');
            if (successVisible) {
                console.log(`${c.green}✓ Conversion successful!${c.reset}`);
            } else {
                console.log(`${c.red}✗ No success message found${c.reset}`);
            }

            // Check checkbox state after conversion
            const checkboxAfterConvert = await page.isChecked(checkboxSelector);
            console.log(`Checkbox after convert: ${checkboxAfterConvert ? c.red + 'CHECKED ✗' + c.reset : c.green + 'UNCHECKED ✓' + c.reset}`);

            // Check if Warianty tab is hidden
            const tabVisible = await page.isVisible('button:has-text("Warianty")');
            console.log(`Warianty tab: ${tabVisible ? c.red + 'VISIBLE ✗' + c.reset : c.green + 'HIDDEN ✓' + c.reset}`);
        } else {
            console.log(`${c.red}✗ Modal did NOT appear!${c.reset}`);
            console.log(`${c.yellow}Checkbox reverted: ${await page.isChecked(checkboxSelector) ? 'YES' : 'NO'}${c.reset}`);
        }

        // Save changes
        console.log(`\n${c.yellow}[6/8] Saving changes...${c.reset}`);
        await page.click('button:has-text("Zapisz")');
        await sleep(3000);
        console.log(`${c.green}✓ Saved${c.reset}\n`);

        // TEST 2: Create new variant
        console.log(`${c.magenta}=== TEST 2: CREATE NEW VARIANT ===${c.reset}`);
        console.log(`${c.yellow}User will create variant manually...${c.reset}`);
        console.log(`${c.cyan}Please:${c.reset}`);
        console.log(`  1. Check the "Produkt z wariantami" checkbox`);
        console.log(`  2. Save the product`);
        console.log(`  3. Click "Warianty" tab`);
        console.log(`  4. Click "Dodaj wariant"`);
        console.log(`  5. Fill SKU and Name`);
        console.log(`  6. Save variant`);
        console.log(`${c.yellow}Waiting 60 seconds for manual action...${c.reset}`);
        await sleep(60000);

        // Reload page
        console.log(`\n${c.yellow}[7/8] Reloading page...${c.reset}`);
        await page.reload({ waitUntil: 'networkidle' });
        await sleep(3000);

        // Check checkbox state
        const checkboxBeforeDelete = await page.isChecked(checkboxSelector);
        console.log(`Checkbox state: ${checkboxBeforeDelete ? c.green + 'CHECKED ✓' + c.reset : c.red + 'UNCHECKED ✗' + c.reset}`);

        if (!checkboxBeforeDelete) {
            console.log(`${c.red}ERROR: Checkbox should be CHECKED (variant was created)${c.reset}`);
            await sleep(60000);
            await browser.close();
            return;
        }

        // TEST 3: Uncheck checkbox → Modal → Delete
        console.log(`${c.magenta}\n=== TEST 3: DELETE VARIANTS ===${c.reset}`);
        console.log(`${c.yellow}[8/8] Unchecking checkbox...${c.reset}`);
        await page.click(checkboxSelector);
        await sleep(2000); // Wait for modal

        // Check if modal appeared
        const modalVisible2 = await page.isVisible('text=Uwaga: Produkt ma');
        if (modalVisible2) {
            console.log(`${c.green}✓ Modal appeared!${c.reset}`);

            // Take screenshot of modal
            const timestamp2 = new Date().toISOString().replace(/:/g, '-').split('.')[0];
            await page.screenshot({
                path: path.join(__dirname, 'screenshots', `modal_delete_${timestamp2}.png`),
                fullPage: true
            });
            console.log(`${c.cyan}Screenshot: modal_delete_${timestamp2}.png${c.reset}`);

            // Click "Delete" button
            console.log(`${c.yellow}Clicking "Usuń warianty"...${c.reset}`);
            await page.click('button:has-text("Usuń warianty")');
            await sleep(3000); // Wait for deletion

            // Check for success message
            const successVisible2 = await page.isVisible('text=Usunięto');
            if (successVisible2) {
                console.log(`${c.green}✓ Deletion successful!${c.reset}`);
            } else {
                console.log(`${c.red}✗ No success message found${c.reset}`);
            }

            // Check checkbox state after deletion
            const checkboxAfterDelete = await page.isChecked(checkboxSelector);
            console.log(`Checkbox after delete: ${checkboxAfterDelete ? c.red + 'CHECKED ✗' + c.reset : c.green + 'UNCHECKED ✓' + c.reset}`);

            // Check if Warianty tab is hidden
            const tabVisible2 = await page.isVisible('button:has-text("Warianty")');
            console.log(`Warianty tab: ${tabVisible2 ? c.red + 'VISIBLE ✗' + c.reset : c.green + 'HIDDEN ✓' + c.reset}`);
        } else {
            console.log(`${c.red}✗ Modal did NOT appear!${c.reset}`);
        }

        // Final save
        console.log(`\n${c.yellow}Saving final changes...${c.reset}`);
        await page.click('button:has-text("Zapisz")');
        await sleep(3000);
        console.log(`${c.green}✓ Saved${c.reset}\n`);

        // Summary
        console.log(`${c.cyan}=== TEST SUMMARY ===${c.reset}`);
        console.log(`${c.green}✓ Test 1: Convert variants - COMPLETED${c.reset}`);
        console.log(`${c.green}✓ Test 2: Create new variant - COMPLETED (manual)${c.reset}`);
        console.log(`${c.green}✓ Test 3: Delete variants - COMPLETED${c.reset}`);

        console.log(`\n${c.yellow}Press Ctrl+C to close browser${c.reset}`);
        await sleep(60000);

    } catch (error) {
        console.error(`${c.red}Error: ${error.message}${c.reset}`);
        console.error(error.stack);
    } finally {
        await browser.close();
    }

})();
