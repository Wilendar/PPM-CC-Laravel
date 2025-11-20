/**
 * TEST: Multiple Shop Categories Save
 *
 * PURPOSE: Verify that MULTIPLE shop categories can be saved
 *
 * Test Flow:
 * 1. Open product 11034/edit
 * 2. Click shop TAB "B2B Test DEV"
 * 3. Toggle 3-4 different categories
 * 4. Click "Zapisz wszystkie zmiany"
 * 5. Verify ALL categories were saved to database
 */

const { chromium } = require('playwright');

(async () => {
    console.log('\n╔═══════════════════════════════════════════════════════════════╗');
    console.log('║   TEST: Multiple Shop Categories Save                        ║');
    console.log('╚═══════════════════════════════════════════════════════════════╝\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        console.log('📍 STEP 1: Opening product edit page...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit', {
            waitUntil: 'networkidle',
            timeout: 60000
        });
        await page.waitForTimeout(2000);
        console.log('✅ Page loaded\n');

        console.log('📍 STEP 2: Clicking shop TAB "B2B Test DEV"...');
        const shopButton = page.locator('button[wire\\:click="switchToShop(1)"]').first();
        await shopButton.click();
        await page.waitForTimeout(2000);
        console.log('✅ Shop TAB clicked\n');

        await page.screenshot({
            path: '_TOOLS/screenshots/multiple_categories_after_shop_click.png',
            fullPage: true
        });

        console.log('📍 STEP 3: Toggling MULTIPLE categories...');

        // Find UNCHECKED checkboxes in shop context (category_1_X)
        const allCheckboxes = await page.locator('input[type="checkbox"][id^="category_1_"]').all();

        console.log(`Found ${allCheckboxes.length} shop category checkboxes\n`);

        const categoriesToToggle = [];

        // Find first 4 UNCHECKED categories
        for (const checkbox of allCheckboxes) {
            const isChecked = await checkbox.isChecked();
            const checkboxId = await checkbox.getAttribute('id');

            if (!isChecked && categoriesToToggle.length < 4) {
                categoriesToToggle.push({ checkbox, checkboxId });
            }

            if (categoriesToToggle.length === 4) break;
        }

        if (categoriesToToggle.length === 0) {
            console.log('⚠️  All checkboxes are already checked!');
            console.log('   Will UNCHECK first 4 categories instead...\n');

            // Find first 4 CHECKED categories to uncheck
            for (const checkbox of allCheckboxes) {
                const isChecked = await checkbox.isChecked();
                const checkboxId = await checkbox.getAttribute('id');

                if (isChecked && categoriesToToggle.length < 4) {
                    categoriesToToggle.push({ checkbox, checkboxId });
                }

                if (categoriesToToggle.length === 4) break;
            }
        }

        console.log(`Will toggle ${categoriesToToggle.length} categories:\n`);

        // Toggle each category
        for (const { checkbox, checkboxId } of categoriesToToggle) {
            console.log(`🖱️  Toggling: ${checkboxId}`);
            await checkbox.click();
            await page.waitForTimeout(500); // Small delay between toggles
        }

        console.log('\n✅ All categories toggled\n');

        await page.screenshot({
            path: '_TOOLS/screenshots/multiple_categories_after_toggle.png',
            fullPage: true
        });

        console.log('📍 STEP 4: Clicking "Zapisz zmiany" FROM SIDEPANEL...');

        // Find button in SIDEPANEL (right column) - has specific wire:target="saveAndClose"
        const sidepanelSaveButton = page.locator('button[wire\\:target="saveAndClose"]').filter({
            hasText: /Zapisz zmiany|Utwórz produkt/i
        }).first();

        const buttonExists = await sidepanelSaveButton.count() > 0;

        if (!buttonExists) {
            console.log('❌ Sidepanel "Zapisz zmiany" button NOT FOUND');
            console.log('⚠️  Fallback to any save button...');
            const saveButton = page.locator('button').filter({ hasText: /Zapisz.*zmiany/i }).first();
            await saveButton.click();
        } else {
            console.log('✅ Found sidepanel "Zapisz zmiany" button');
            await sidepanelSaveButton.click();
        }

        await page.waitForTimeout(4000);
        console.log('✅ Save clicked\n');

        await page.screenshot({
            path: '_TOOLS/screenshots/multiple_categories_after_save.png',
            fullPage: true
        });

        // Check for success message
        const successMsg = await page.locator('.alert-success, [class*="success"]').first();
        const hasSuccess = await successMsg.count() > 0;

        if (hasSuccess) {
            const msgText = await successMsg.textContent();
            console.log(`✅ SUCCESS MESSAGE: "${msgText.trim().substring(0, 50)}..."`);
        } else {
            console.log('⚠️  NO SUCCESS MESSAGE detected');
        }

        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('   NEXT STEP: VERIFY DATABASE');
        console.log('═══════════════════════════════════════════════════════════════\n');

        console.log('Run on production:');
        console.log('php _TEMP/check_shop_categories_db.php');
        console.log('\nExpected: 4 categories with shop_id=1');

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        await page.screenshot({
            path: '_TOOLS/screenshots/multiple_categories_error.png',
            fullPage: true
        });
    } finally {
        console.log('\n⏳ Keeping browser open for 15 seconds...');
        await page.waitForTimeout(15000);

        await browser.close();
        console.log('\n✅ Test complete\n');
    }
})();
