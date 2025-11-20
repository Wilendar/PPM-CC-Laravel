/**
 * Test: Category Persistence (Save → Reload → Verify)
 *
 * Workflow:
 * 1. Load product 11034
 * 2. Click Shop Tab "B2B Test DEV"
 * 3. Count selected categories BEFORE changes
 * 4. Toggle ONE checkbox
 * 5. Save (redirect to list)
 * 6. Navigate BACK to product 11034
 * 7. Click Shop Tab again
 * 8. Verify categories are PERSISTED
 */

const playwright = require('playwright');

(async () => {
    const browser = await playwright.chromium.launch({
        headless: false,
        slowMo: 500
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();
    page.on('console', msg => console.log('BROWSER:', msg.text()));

    try {
        console.log('=== STEP 1: Load product 11034 ===');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
        await page.waitForLoadState('networkidle');
        console.log('✅ Product loaded');

        console.log('\n=== STEP 2: Click Shop Tab ===');
        await page.locator('text=B2B Test DEV').first().click();
        await page.waitForTimeout(3000); // Wait for categories to load
        console.log('✅ Shop tab clicked');

        console.log('\n=== STEP 3: Count INITIAL categories ===');
        const initialChecked = await page.locator('input[type="checkbox"][id^="category_"]:checked').count();
        console.log(`Initial checked categories: ${initialChecked}`);

        // Take screenshot
        await page.screenshot({
            path: '_TEMP/persistence_01_initial.png',
            fullPage: true
        });

        console.log('\n=== STEP 4: Toggle ONE checkbox ===');
        const allCheckboxes = await page.locator('input[type="checkbox"][id^="category_"]').all();
        const firstCheckbox = allCheckboxes[0];
        const wasChecked = await firstCheckbox.isChecked();

        await firstCheckbox.click();
        await page.waitForTimeout(500);

        const nowChecked = await firstCheckbox.isChecked();
        console.log(`✅ Toggled: ${wasChecked} → ${nowChecked}`);

        const afterToggle = await page.locator('input[type="checkbox"][id^="category_"]:checked').count();
        console.log(`After toggle: ${afterToggle} categories`);

        await page.screenshot({
            path: '_TEMP/persistence_02_after_toggle.png',
            fullPage: true
        });

        console.log('\n=== STEP 5: Save (should redirect) ===');
        await page.locator('button:has-text("Zapisz zmiany")').first().click();

        // Wait for redirect
        await page.waitForURL('**/admin/products', { timeout: 10000 });
        console.log('✅ Redirected to product list');

        await page.screenshot({
            path: '_TEMP/persistence_03_product_list.png',
            fullPage: true
        });

        console.log('\n=== STEP 6: Navigate BACK to product 11034 ===');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
        await page.waitForLoadState('networkidle');
        console.log('✅ Reloaded product 11034');

        console.log('\n=== STEP 7: Click Shop Tab AGAIN ===');
        await page.locator('text=B2B Test DEV').first().click();
        await page.waitForTimeout(3000); // Wait for categories
        console.log('✅ Shop tab clicked again');

        console.log('\n=== STEP 8: VERIFY persistence ===');
        const finalChecked = await page.locator('input[type="checkbox"][id^="category_"]:checked').count();
        console.log(`Final checked categories: ${finalChecked}`);

        await page.screenshot({
            path: '_TEMP/persistence_04_after_reload.png',
            fullPage: true
        });

        // CRITICAL VERIFICATION
        console.log('\n=== ✅ VERIFICATION RESULTS ===');
        console.log(`Initial count: ${initialChecked}`);
        console.log(`After toggle: ${afterToggle}`);
        console.log(`After reload: ${finalChecked}`);

        if (finalChecked === afterToggle) {
            console.log('✅✅✅ SUCCESS: Categories PERSISTED correctly! ✅✅✅');
        } else {
            console.log(`❌❌❌ FAILED: Expected ${afterToggle} but got ${finalChecked} ❌❌❌`);
            throw new Error(`Category persistence failed: ${afterToggle} → ${finalChecked}`);
        }

    } catch (error) {
        console.error('\n=== ❌ TEST FAILED ===');
        console.error('Error:', error.message);

        await page.screenshot({
            path: '_TEMP/persistence_ERROR.png',
            fullPage: true
        });

        throw error;

    } finally {
        console.log('\nClosing browser in 5 seconds...');
        await page.waitForTimeout(5000);
        await browser.close();
    }
})();
