#!/usr/bin/env node
/**
 * CHECK VARIANT COUNT - Does product 10969 have actual variants in database?
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
    console.log(`${c.cyan}=== CHECK VARIANT COUNT ===${c.reset}\n`);

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    try {
        // Login
        await page.goto('https://ppm.mpptrade.pl/login');
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Open product
        await page.goto('https://ppm.mpptrade.pl/admin/products/10969/edit');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(3000);

        // Check if Warianty tab exists
        const tabVisible = await page.isVisible('button:has-text("Warianty")');

        if (tabVisible) {
            console.log(`${c.yellow}Warianty tab is VISIBLE - clicking to check variant count...${c.reset}`);

            // Click Warianty tab
            await page.click('button:has-text("Warianty")');
            await page.waitForTimeout(2000);

            // Check for "Brak wariantów" message
            const noVariantsMessage = await page.isVisible('text=Brak wariantów');

            // Check for variant table
            const variantTable = await page.isVisible('table');

            // Count variant rows (if table exists)
            let variantCount = 0;
            if (variantTable && !noVariantsMessage) {
                try {
                    const rows = await page.locator('tbody tr').count();
                    variantCount = rows;
                } catch (e) {
                    // No rows
                }
            }

            console.log(`\n${c.cyan}=== RESULT ===${c.reset}`);
            if (noVariantsMessage) {
                console.log(`${c.red}NO VARIANTS found in UI ("Brak wariantów" message)${c.reset}`);
            } else if (variantCount > 0) {
                console.log(`${c.green}✓ Found ${variantCount} variant(s)${c.reset}`);
            } else {
                console.log(`${c.yellow}Variant table exists but no rows detected${c.reset}`);
            }
        } else {
            console.log(`${c.red}Warianty tab is NOT VISIBLE${c.reset}`);
            console.log(`This means has_variants=0 in database`);
        }

        await browser.close();

    } catch (error) {
        console.error(`${c.red}Error: ${error.message}${c.reset}`);
        await browser.close();
        process.exit(1);
    }

})();
