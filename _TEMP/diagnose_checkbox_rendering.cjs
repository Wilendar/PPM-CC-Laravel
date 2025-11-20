/**
 * Diagnose Checkbox Rendering
 *
 * Check EXACTLY which category checkboxes are rendered and which are checked
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

        console.log('\n=== STEP 2: Click Shop Tab "B2B Test DEV" ===');
        await page.locator('text=B2B Test DEV').first().click();
        await page.waitForTimeout(3000); // Wait for categories to load
        console.log('✅ Shop tab clicked');

        console.log('\n=== STEP 3: Find ALL category checkboxes ===');
        const allCheckboxes = await page.locator('input[type="checkbox"][id^="category_"]').all();
        console.log(`Total category checkboxes found: ${allCheckboxes.length}`);

        console.log('\n=== STEP 4: Analyze each checkbox ===');
        for (let i = 0; i < allCheckboxes.length; i++) {
            const checkbox = allCheckboxes[i];
            const id = await checkbox.getAttribute('id');
            const isChecked = await checkbox.isChecked();
            const isVisible = await checkbox.isVisible();

            // Extract category ID from id="category_1_36" format
            const match = id.match(/category_(\d+)_(\d+)/);
            const shopId = match ? match[1] : 'unknown';
            const categoryId = match ? match[2] : 'unknown';

            // Get label text
            const label = await page.locator(`label[for="${id}"]`).textContent();

            console.log(`[${i}] ID: ${id} | Shop: ${shopId} | Cat: ${categoryId} | Checked: ${isChecked} | Visible: ${isVisible} | Label: ${label.trim()}`);
        }

        console.log('\n=== STEP 5: Check ONLY checkboxes for shop_id=1 ===');
        const shop1Checkboxes = await page.locator('input[type="checkbox"][id^="category_1_"]').all();
        console.log(`Shop 1 checkboxes: ${shop1Checkboxes.length}`);

        let checkedCount = 0;
        const checkedIds = [];
        for (const checkbox of shop1Checkboxes) {
            const isChecked = await checkbox.isChecked();
            if (isChecked) {
                checkedCount++;
                const id = await checkbox.getAttribute('id');
                const match = id.match(/category_1_(\d+)/);
                checkedIds.push(match ? match[1] : 'unknown');
            }
        }

        console.log(`Shop 1 CHECKED checkboxes: ${checkedCount}`);
        console.log(`Shop 1 CHECKED category IDs: [${checkedIds.join(', ')}]`);

        console.log('\n=== EXPECTED FROM DB: [36, 1, 2] ===');
        console.log('=== CRITICAL CHECK ===');

        const hasCategory36 = await page.locator('input[id="category_1_36"]').count();
        const hasCategory1 = await page.locator('input[id="category_1_1"]').count();
        const hasCategory2 = await page.locator('input[id="category_1_2"]').count();

        console.log(`Category 36 checkbox exists: ${hasCategory36 > 0 ? 'YES' : 'NO'}`);
        console.log(`Category 1 checkbox exists: ${hasCategory1 > 0 ? 'YES' : 'NO'}`);
        console.log(`Category 2 checkbox exists: ${hasCategory2 > 0 ? 'YES' : 'NO'}`);

        if (hasCategory36 > 0) {
            const cat36Checked = await page.locator('input[id="category_1_36"]').isChecked();
            const cat36Visible = await page.locator('input[id="category_1_36"]').isVisible();
            console.log(`Category 36 checked: ${cat36Checked}`);
            console.log(`Category 36 visible: ${cat36Visible}`);
        }

        // Screenshot
        await page.screenshot({
            path: '_TEMP/diagnose_checkboxes.png',
            fullPage: true
        });

    } catch (error) {
        console.error('\n=== ❌ ERROR ===');
        console.error('Error:', error.message);

        await page.screenshot({
            path: '_TEMP/diagnose_checkboxes_ERROR.png',
            fullPage: true
        });

        throw error;

    } finally {
        console.log('\nClosing browser in 5 seconds...');
        await page.waitForTimeout(5000);
        await browser.close();
    }
})();
