#!/usr/bin/env node
/**
 * VERIFY CHECKBOX STATE - Quick visual check
 *
 * Opens product 10969 and checks:
 * - Is checkbox checked?
 * - Is Warianty tab visible?
 * - Takes screenshot for inspection
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

(async () => {
    console.log(`${c.cyan}=== CHECKBOX STATE VERIFICATION ===${c.reset}\n`);

    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    // Login
    console.log('Logging in...');
    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Open product
    console.log('Opening product 10969...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/10969/edit');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000); // Wait for Livewire

    // Check checkbox state
    console.log('\n=== CHECKING UI STATE ===');

    const checkboxSelector = 'input#is_variant_master';
    let isChecked = false;
    try {
        isChecked = await page.isChecked(checkboxSelector, { timeout: 5000 });
        console.log(`Checkbox state: ${isChecked ? c.green + 'CHECKED ✓' + c.reset : c.red + 'UNCHECKED ✗' + c.reset}`);
    } catch (e) {
        console.log(`${c.red}Checkbox not found!${c.reset}`);
    }

    // Check if Warianty tab exists
    const variantsTabSelectors = [
        'button:has-text("Warianty")',
        '.tab-enterprise:has-text("Warianty")',
        'button.tab-enterprise:has-text("Warianty")'
    ];

    let tabVisible = false;
    for (const selector of variantsTabSelectors) {
        try {
            const tab = page.locator(selector).first();
            tabVisible = await tab.isVisible({ timeout: 2000 });
            if (tabVisible) {
                console.log(`Warianty tab: ${c.green}VISIBLE ✓${c.reset}`);
                break;
            }
        } catch (e) {
            // Try next selector
        }
    }

    if (!tabVisible) {
        console.log(`Warianty tab: ${c.red}HIDDEN ✗${c.reset}`);
    }

    // Take screenshot
    const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
    const screenshotPath = path.join(__dirname, 'screenshots', `verify_state_${timestamp}.png`);
    await page.screenshot({ path: screenshotPath, fullPage: true });
    console.log(`\nScreenshot: ${screenshotPath}`);

    // Summary
    console.log(`\n${c.cyan}=== SUMMARY ===${c.reset}`);
    console.log(`Checkbox: ${isChecked ? 'CHECKED' : 'UNCHECKED'}`);
    console.log(`Tab visible: ${tabVisible ? 'YES' : 'NO'}`);

    console.log(`\n${c.cyan}=== EXPECTED (from database) ===${c.reset}`);
    console.log(`Checkbox: CHECKED (is_variant_master=1)`);
    console.log(`Tab visible: YES (has_variants=1)`);

    if (isChecked && tabVisible) {
        console.log(`\n${c.green}✅ UI MATCHES DATABASE${c.reset}`);
    } else {
        console.log(`\n${c.red}❌ UI DOES NOT MATCH DATABASE${c.reset}`);
        if (!isChecked) console.log(`  ${c.red}• Checkbox should be CHECKED${c.reset}`);
        if (!tabVisible) console.log(`  ${c.red}• Tab should be VISIBLE${c.reset}`);
    }

    console.log(`\n${c.yellow}Press Ctrl+C to close browser${c.reset}`);
    await page.waitForTimeout(60000); // Keep open for inspection
    await browser.close();

})().catch(error => {
    console.error(`${c.red}Error: ${error.message}${c.reset}`);
    process.exit(1);
});
