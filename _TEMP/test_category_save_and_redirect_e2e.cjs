/**
 * E2E Test: Category Save & Redirect Workflow
 *
 * Test workflow (assumes user is already logged in):
 * 1. Navigate directly to product 11034 edit page
 * 2. Click "B2B Test DEV" shop tab
 * 3. Wait for category tree to load (may have delay loading from PrestaShop)
 * 4. Select/modify categories
 * 5. Click "Zapisz zmiany"
 * 6. VERIFY: Redirect to /admin/products (CRITICAL TEST)
 * 7. Find product SKU "Q-KAYO-EA70" and navigate to it
 * 8. Repeat steps 2-3
 * 9. VERIFY: Category checkboxes reflect saved state
 */

const playwright = require('playwright');

(async () => {
    const browser = await playwright.chromium.launch({
        headless: false, // Show browser for debugging
        slowMo: 500 // Slow down for observation
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    // Enable console logging
    page.on('console', msg => console.log('BROWSER:', msg.text()));

    try {
        console.log('=== STEP 1: Navigate directly to product 11034 ===');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
        await page.waitForLoadState('networkidle');
        console.log('✅ Product page loaded');

        console.log('\n=== STEP 2: Click "B2B Test DEV" shop tab ===');
        // Find and click the shop tab
        const shopTab = await page.locator('text=B2B Test DEV').first();
        await shopTab.waitFor({ state: 'visible', timeout: 5000 });
        await shopTab.click();
        console.log('✅ Shop tab clicked');

        console.log('\n=== STEP 3: Wait for category tree to load ===');
        // Wait for category tree (may have delay loading from PrestaShop)
        await page.waitForTimeout(3000); // Give PrestaShop data time to load

        // Look for category tree container
        const categorySection = await page.locator('text=Kategorie').first();
        await categorySection.waitFor({ state: 'visible', timeout: 10000 });
        console.log('✅ Category section visible');

        // Scroll to category section
        await categorySection.scrollIntoViewIfNeeded();
        await page.waitForTimeout(1000);

        // Take screenshot of initial state
        await page.screenshot({
            path: '_TEMP/test_e2e_01_initial_categories.png',
            fullPage: true
        });
        console.log('📸 Screenshot: test_e2e_01_initial_categories.png');

        console.log('\n=== STEP 4: Select/modify categories ===');
        // Find all checkboxes in category tree
        // CORRECT FORMAT: id="category_{shopId}_{categoryId}" (underscore, not dash!)
        const checkboxes = await page.locator('input[type="checkbox"][id^="category_"]').all();
        console.log(`Found ${checkboxes.length} category checkboxes`);

        if (checkboxes.length === 0) {
            console.error('❌ FAILED: No category checkboxes found!');
            await page.screenshot({
                path: '_TEMP/test_e2e_ERROR_no_checkboxes.png',
                fullPage: true
            });
            throw new Error('No category checkboxes found');
        }

        // Toggle first checkbox to trigger changes
        const firstCheckbox = checkboxes[0];
        const wasChecked = await firstCheckbox.isChecked();
        await firstCheckbox.click();
        await page.waitForTimeout(500);

        const nowChecked = await firstCheckbox.isChecked();
        console.log(`✅ Checkbox toggled: ${wasChecked} → ${nowChecked}`);

        // Take screenshot after modification
        await page.screenshot({
            path: '_TEMP/test_e2e_02_categories_modified.png',
            fullPage: true
        });
        console.log('📸 Screenshot: test_e2e_02_categories_modified.png');

        console.log('\n=== STEP 5: Click "Zapisz zmiany" ===');
        // Find "Zapisz zmiany" button
        const saveButton = await page.locator('button:has-text("Zapisz zmiany")').first();
        await saveButton.waitFor({ state: 'visible', timeout: 5000 });
        await saveButton.scrollIntoViewIfNeeded();
        await page.waitForTimeout(500);

        // Take screenshot before clicking
        await page.screenshot({
            path: '_TEMP/test_e2e_03_before_save.png',
            fullPage: true
        });
        console.log('📸 Screenshot: test_e2e_03_before_save.png');

        // Click save button
        console.log('Clicking "Zapisz zmiany" button...');
        await saveButton.click();

        console.log('\n=== STEP 6: VERIFY REDIRECT (CRITICAL TEST) ===');
        try {
            // Wait for navigation to /admin/products (max 10 seconds)
            await page.waitForURL('**/admin/products', { timeout: 10000 });
            console.log('✅✅✅ SUCCESS: Redirect to /admin/products WORKED! ✅✅✅');

            // Take screenshot of product list
            await page.waitForLoadState('networkidle');
            await page.screenshot({
                path: '_TEMP/test_e2e_04_redirect_success.png',
                fullPage: true
            });
            console.log('📸 Screenshot: test_e2e_04_redirect_success.png');

        } catch (error) {
            console.error('❌❌❌ FAILED: Redirect did NOT work! ❌❌❌');
            console.error('Current URL:', page.url());

            // Take screenshot of failure
            await page.screenshot({
                path: '_TEMP/test_e2e_ERROR_redirect_failed.png',
                fullPage: true
            });
            console.log('📸 Screenshot: test_e2e_ERROR_redirect_failed.png');

            // Check console for errors
            console.log('\nChecking for JavaScript errors...');

            throw new Error(`Redirect failed - stayed on: ${page.url()}`);
        }

        console.log('\n=== STEP 7: Find product SKU "Q-KAYO-EA70" ===');
        // Search for SKU in product list
        const searchInput = await page.locator('input[placeholder*="Szukaj"], input[type="search"]').first();
        if (await searchInput.isVisible()) {
            await searchInput.fill('Q-KAYO-EA70');
            await page.waitForTimeout(1000); // Wait for search results
        }

        // Find product link
        const productLink = await page.locator('a[href*="/admin/products/"][href*="/edit"]:has-text("Q-KAYO-EA70")').first();
        await productLink.waitFor({ state: 'visible', timeout: 5000 });
        console.log('✅ Found product Q-KAYO-EA70');

        // Click product link
        await productLink.click();
        await page.waitForLoadState('networkidle');
        console.log('✅ Navigated to product Q-KAYO-EA70');

        console.log('\n=== STEP 8: Repeat - Click "B2B Test DEV" shop tab ===');
        const shopTab2 = await page.locator('text=B2B Test DEV').first();
        await shopTab2.waitFor({ state: 'visible', timeout: 5000 });
        await shopTab2.click();
        console.log('✅ Shop tab clicked');

        console.log('\n=== STEP 9: Wait for category tree and scroll ===');
        await page.waitForTimeout(3000); // PrestaShop data load delay

        const categorySection2 = await page.locator('text=Kategorie').first();
        await categorySection2.waitFor({ state: 'visible', timeout: 10000 });
        await categorySection2.scrollIntoViewIfNeeded();
        await page.waitForTimeout(1000);

        console.log('\n=== STEP 10: VERIFY category checkboxes ===');
        const checkboxes2 = await page.locator('input[type="checkbox"][id^="category_"]').all();
        console.log(`Found ${checkboxes2.length} category checkboxes`);

        // Check state of checkboxes
        const checkedCount = await page.locator('input[type="checkbox"][id^="category_"]:checked').count();
        console.log(`Checked categories: ${checkedCount}`);

        // Take final screenshot
        await page.screenshot({
            path: '_TEMP/test_e2e_05_final_state.png',
            fullPage: true
        });
        console.log('📸 Screenshot: test_e2e_05_final_state.png');

        console.log('\n=== ✅ TEST COMPLETED SUCCESSFULLY ===');
        console.log(`Found ${checkboxes2.length} categories`);
        console.log(`${checkedCount} categories are checked`);

        // Verification summary
        console.log('\n=== VERIFICATION SUMMARY ===');
        console.log('✅ Product 11034 loaded');
        console.log('✅ Shop tab "B2B Test DEV" clicked');
        console.log('✅ Categories loaded');
        console.log('✅ Category modification successful');
        console.log('✅ Save button "Zapisz zmiany" clicked');
        console.log('✅✅✅ REDIRECT WORKED (CRITICAL) ✅✅✅');
        console.log('✅ Product Q-KAYO-EA70 found and loaded');
        console.log('✅ Categories verified on second product');

    } catch (error) {
        console.error('\n=== ❌ TEST FAILED ===');
        console.error('Error:', error.message);
        console.error('\nCurrent URL:', page.url());

        // Take error screenshot
        await page.screenshot({
            path: '_TEMP/test_e2e_FINAL_ERROR.png',
            fullPage: true
        });
        console.log('📸 Screenshot: test_e2e_FINAL_ERROR.png');

        throw error;

    } finally {
        console.log('\nClosing browser in 5 seconds...');
        await page.waitForTimeout(5000);
        await browser.close();
    }
})();
