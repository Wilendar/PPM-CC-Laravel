// Check Shop Tab Removal - Verify collapsible section
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0'
    });
    const page = await context.newPage();

    try {
        console.log('=== SHOP TAB REFACTOR VERIFICATION ===\n');

        // 1. Navigate to products list
        console.log('[1/5] Loading products list...');
        await page.goto('https://ppm.mpptrade.pl/admin/products', { waitUntil: 'networkidle' });
        await page.waitForTimeout(2000);

        // 2. Click Edit on first product with "Zobacz sklep" button
        console.log('[2/5] Finding product with shop data...');
        const editButton = await page.locator('a[title="Edytuj produkt"]').first();
        await editButton.click();
        await page.waitForTimeout(3000);

        // 3. Check if "Sklepy" tab exists (should NOT exist)
        console.log('[3/5] Checking if "Sklepy" tab was removed...');
        const shopTabButton = await page.locator('button.tab-enterprise:has-text("Sklepy")').count();

        if (shopTabButton === 0) {
            console.log('✅ "Sklepy" tab REMOVED successfully!');
        } else {
            console.log('❌ "Sklepy" tab STILL EXISTS (should be removed)');
        }

        // 4. Switch to a shop (click shop button)
        console.log('[4/5] Switching to shop...');
        const shopButton = await page.locator('button:has-text("Dane domyślne")').first();

        // Find any shop button (not default data)
        const shopButtons = await page.locator('button[wire\\:click^="switchToShop"]').all();
        if (shopButtons.length > 1) {
            // Click second button (first shop after default)
            await shopButtons[1].click();
            await page.waitForTimeout(2000);
        }

        // 5. Check if "Szczegóły synchronizacji" collapsible exists
        console.log('[5/5] Checking for collapsible section...');
        const collapsibleHeader = await page.locator('button.collapsible-header:has-text("Szczegóły synchronizacji")').count();

        if (collapsibleHeader > 0) {
            console.log('✅ Collapsible "Szczegóły synchronizacji" section EXISTS!');

            // Try to expand it
            console.log('   Expanding collapsible section...');
            await page.locator('button.collapsible-header').first().click();
            await page.waitForTimeout(1000);

            // Check if content is visible
            const contentVisible = await page.locator('.collapsible-content').isVisible();
            if (contentVisible) {
                console.log('   ✅ Collapsible content EXPANDED successfully!');

                // Take screenshot
                await page.screenshot({
                    path: '_TOOLS/screenshots/shop_tab_refactor_verification_2025-11-13.png',
                    fullPage: true
                });
                console.log('   ✅ Screenshot saved!');
            } else {
                console.log('   ❌ Collapsible content NOT visible');
            }
        } else {
            console.log('❌ Collapsible section NOT FOUND (should exist)');
        }

        console.log('\n=== VERIFICATION COMPLETE ===');

    } catch (error) {
        console.error('ERROR:', error.message);
    } finally {
        await browser.close();
    }
})();
