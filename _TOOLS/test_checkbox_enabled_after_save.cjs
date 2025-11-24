const { chromium } = require('playwright');

(async () => {
    console.log('=== TEST: Checkboxes ENABLED After Save ===\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });

    const page = await browser.newPage();

    try {
        // STEP 1: Open product
        console.log('STEP 1: Opening product 11034...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
        await page.waitForSelector('[wire\\:id]', { timeout: 15000 });
        await page.waitForTimeout(2000);
        console.log('✅ Product loaded\n');

        // STEP 2: Click B2B Test DEV tab
        console.log('STEP 2: Clicking B2B Test DEV tab...');
        const shopTab = page.locator('button:has-text("B2B Test DEV")').first();
        await shopTab.click();
        await page.waitForTimeout(3000);
        console.log('✅ Tab clicked\n');

        // STEP 3: Check if checkboxes are enabled BEFORE save
        console.log('STEP 3: Checking checkboxes BEFORE save...');
        const checkboxesBefore = await page.evaluate(() => {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][x-model="isSelected"]');
            const results = [];
            checkboxes.forEach(cb => {
                results.push({
                    disabled: cb.disabled,
                    checked: cb.checked
                });
            });
            return results;
        });

        console.log(`   Found ${checkboxesBefore.length} category checkboxes`);
        const disabledBeforeCount = checkboxesBefore.filter(cb => cb.disabled).length;
        console.log(`   Disabled: ${disabledBeforeCount}/${checkboxesBefore.length}`);

        if (disabledBeforeCount > 0) {
            console.log('❌ FAIL: Checkboxes already disabled BEFORE save!');
            await page.screenshot({ path: 'screenshots/test_disabled_before_save.png', fullPage: true });
            return;
        }
        console.log('✅ All checkboxes enabled before save\n');

        // STEP 4: Toggle first checkbox
        console.log('STEP 4: Toggling first checkbox...');
        await page.evaluate(() => {
            const checkbox = document.querySelector('input[type="checkbox"][x-model="isSelected"]');
            if (checkbox) checkbox.click();
        });
        await page.waitForTimeout(1000);
        console.log('✅ Checkbox toggled\n');

        // STEP 5: Click "Zapisz zmiany"
        console.log('STEP 5: Clicking "Zapisz zmiany"...');
        const saveButton = page.locator('button:has-text("Zapisz zmiany")').first();
        await saveButton.click();
        console.log('✅ Save clicked\n');

        // STEP 6: Wait for redirect
        console.log('STEP 6: Waiting for redirect...');
        await page.waitForURL('**/admin/products', { timeout: 15000 });
        console.log('✅ Redirected to product list\n');

        // STEP 7: Go back to product (test if checkboxes stay enabled)
        console.log('STEP 7: Returning to product edit...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
        await page.waitForSelector('[wire\\:id]', { timeout: 15000 });
        await page.waitForTimeout(2000);
        console.log('✅ Product reloaded\n');

        // STEP 8: Click B2B Test DEV tab again
        console.log('STEP 8: Clicking B2B Test DEV tab again...');
        const shopTab2 = page.locator('button:has-text("B2B Test DEV")').first();
        await shopTab2.click();
        await page.waitForTimeout(3000);
        console.log('✅ Tab clicked\n');

        // STEP 9: Check if checkboxes are enabled AFTER save + reload
        console.log('STEP 9: Checking checkboxes AFTER save + reload...');
        const checkboxesAfter = await page.evaluate(() => {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][x-model="isSelected"]');
            const results = [];
            checkboxes.forEach(cb => {
                results.push({
                    disabled: cb.disabled,
                    checked: cb.checked
                });
            });
            return results;
        });

        console.log(`   Found ${checkboxesAfter.length} category checkboxes`);
        const disabledAfterCount = checkboxesAfter.filter(cb => cb.disabled).length;
        console.log(`   Disabled: ${disabledAfterCount}/${checkboxesAfter.length}`);

        await page.screenshot({ path: 'screenshots/test_checkbox_after_save.png', fullPage: true });

        console.log('\n=== RESULT ===\n');
        if (disabledAfterCount === 0) {
            console.log('✅✅✅ SUCCESS! ✅✅✅');
            console.log('✅ All checkboxes ENABLED after save + reload!');
            console.log('✅ No race condition - users can edit immediately!');
        } else {
            console.log('❌ FAIL!');
            console.log(`❌ ${disabledAfterCount} checkboxes are DISABLED after save!`);
            console.log('❌ Race condition still exists!');
        }

        console.log('\nBrowser will close in 10 seconds...');
        await page.waitForTimeout(10000);

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        await page.screenshot({ path: 'screenshots/test_error.png', fullPage: true });
    } finally {
        await browser.close();
    }
})();
