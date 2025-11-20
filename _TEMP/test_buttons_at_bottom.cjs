/**
 * TEST: Action Buttons Positioned at Bottom (Under Categories)
 *
 * PURPOSE: Verify buttons are BELOW categories section, NOT at top!
 */

const { chromium } = require('playwright');

(async () => {
    console.log('\n╔═══════════════════════════════════════════════════════════════╗');
    console.log('║   TEST: Buttons at Bottom (Under Categories)                 ║');
    console.log('╚═══════════════════════════════════════════════════════════════╝\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        console.log('📍 STEP 1: Opening product edit page (11034)...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit', {
            waitUntil: 'networkidle',
            timeout: 60000
        });
        await page.waitForTimeout(2000);
        console.log('✅ Page loaded\n');

        // ========================================
        // TEST 1: DEFAULT TAB - Buttons at BOTTOM
        // ========================================
        console.log('═══════════════════════════════════════════════════════════════');
        console.log('   TEST 1: DEFAULT TAB - Buttons Position');
        console.log('═══════════════════════════════════════════════════════════════\n');

        // Scroll to categories section
        await page.evaluate(() => {
            const categoriesHeading = Array.from(document.querySelectorAll('h3')).find(h => h.textContent.includes('Kategorie'));
            if (categoriesHeading) {
                categoriesHeading.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        await page.waitForTimeout(1500);

        // Take screenshot of categories + buttons
        await page.screenshot({
            path: '_TOOLS/screenshots/buttons_bottom_default_full.png',
            fullPage: true
        });

        // Get position of "Kategorie produktu" heading
        const categoriesHeading = page.locator('h3').filter({ hasText: /Kategorie produktu/i }).first();
        const categoriesBox = await categoriesHeading.boundingBox();

        if (categoriesBox) {
            console.log(`✅ Found "Kategorie produktu" at Y=${Math.round(categoriesBox.y)}`);
        } else {
            console.log('⚠️  Could not find "Kategorie produktu" heading');
        }

        // Get position of "Zapisz zmiany" button
        const saveButton = page.locator('button').filter({ hasText: /Zapisz zmiany/i }).first();
        const saveButtonBox = await saveButton.boundingBox();

        if (saveButtonBox) {
            console.log(`✅ Found "Zapisz zmiany" button at Y=${Math.round(saveButtonBox.y)}`);

            // Check if button is BELOW categories
            if (categoriesBox && saveButtonBox.y > categoriesBox.y + 100) {
                console.log(`✅ Button is BELOW categories (${Math.round(saveButtonBox.y - categoriesBox.y)}px distance)`);
            } else {
                console.log(`❌ Button is NOT below categories or too close!`);
            }
        } else {
            console.log('❌ Could not find "Zapisz zmiany" button');
        }

        // Scroll to buttons
        await page.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Zapisz zmiany'));
            if (btn) btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        await page.waitForTimeout(1000);

        await page.screenshot({
            path: '_TOOLS/screenshots/buttons_bottom_default_viewport.png',
            fullPage: false
        });
        console.log('\n📸 Screenshot saved: buttons_bottom_default\n');

        // Count buttons in DEFAULT context
        const defaultSaveBtn = await page.locator('button').filter({ hasText: /Zapisz zmiany|Utwórz produkt/i }).count();
        const defaultCancelLink = await page.locator('a').filter({ hasText: /Anuluj i wróć/i }).count();

        console.log(`${ defaultSaveBtn > 0 ? '✅' : '❌' } "Zapisz zmiany" button: ${defaultSaveBtn} instance(s)`);
        console.log(`${ defaultCancelLink > 0 ? '✅' : '❌' } "Anuluj i wróć" link: ${defaultCancelLink} instance(s)`);

        // ========================================
        // TEST 2: SHOP TAB - Buttons at BOTTOM + Shop Actions
        // ========================================
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('   TEST 2: SHOP TAB - Buttons Position + Shop Actions');
        console.log('═══════════════════════════════════════════════════════════════\n');

        console.log('📍 STEP 2: Switching to shop TAB "B2B Test DEV"...');
        const shopButton = page.locator('button[wire\\:click="switchToShop(1)"]').first();
        await shopButton.click();
        await page.waitForTimeout(2000);
        console.log('✅ Shop TAB clicked\n');

        // Scroll to categories section
        await page.evaluate(() => {
            const categoriesHeading = Array.from(document.querySelectorAll('h3')).find(h => h.textContent.includes('Kategorie'));
            if (categoriesHeading) {
                categoriesHeading.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        await page.waitForTimeout(1500);

        // Get position of buttons in SHOP context
        const shopSaveButton = page.locator('button').filter({ hasText: /Zapisz zmiany/i }).first();
        const shopSaveButtonBox = await shopSaveButton.boundingBox();

        const shopCategoriesHeading = page.locator('h3').filter({ hasText: /Kategorie produktu/i }).first();
        const shopCategoriesBox = await shopCategoriesHeading.boundingBox();

        if (shopCategoriesBox && shopSaveButtonBox) {
            console.log(`✅ Found "Kategorie produktu" at Y=${Math.round(shopCategoriesBox.y)}`);
            console.log(`✅ Found "Zapisz zmiany" button at Y=${Math.round(shopSaveButtonBox.y)}`);

            if (shopSaveButtonBox.y > shopCategoriesBox.y + 100) {
                console.log(`✅ Button is BELOW categories in SHOP context (${Math.round(shopSaveButtonBox.y - shopCategoriesBox.y)}px distance)`);
            } else {
                console.log(`❌ Button is NOT below categories in SHOP context!`);
            }
        }

        // Scroll to buttons
        await page.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Zapisz zmiany'));
            if (btn) btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        await page.waitForTimeout(1000);

        await page.screenshot({
            path: '_TOOLS/screenshots/buttons_bottom_shop_viewport.png',
            fullPage: false
        });
        console.log('\n📸 Screenshot saved: buttons_bottom_shop\n');

        // Count buttons in SHOP context
        const shopSaveBtn = await page.locator('button').filter({ hasText: /Zapisz zmiany/i }).count();
        const shopCancelLink = await page.locator('a').filter({ hasText: /Anuluj i wróć/i }).count();
        const shopSyncBtn = await page.locator('button').filter({ hasText: /Aktualizuj aktualny sklep/i }).count();
        const shopPullBtn = await page.locator('button').filter({ hasText: /Wczytaj z aktualnego sklepu/i }).count();

        console.log(`${ shopSaveBtn > 0 ? '✅' : '❌' } "Zapisz zmiany" button: ${shopSaveBtn} instance(s)`);
        console.log(`${ shopCancelLink > 0 ? '✅' : '❌' } "Anuluj i wróć" link: ${shopCancelLink} instance(s)`);
        console.log(`${ shopSyncBtn >= 2 ? '✅' : '⚠️' } "Aktualizuj aktualny" button: ${shopSyncBtn} instance(s) (expected: ≥2)`);
        console.log(`${ shopPullBtn >= 2 ? '✅' : '⚠️' } "Wczytaj z aktualnego" button: ${shopPullBtn} instance(s) (expected: ≥2)`);

        // ========================================
        // TEST 3: OLD BUTTONS REMOVED
        // ========================================
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('   TEST 3: OLD Buttons Removed');
        console.log('═══════════════════════════════════════════════════════════════\n');

        const oldButtons = await page.locator('button').filter({ hasText: /Zaktualizuj na sklepie|Zapisz wszystkie zmiany/i }).count();
        console.log(`${ oldButtons === 0 ? '✅' : '❌' } OLD buttons removed (found: ${oldButtons}, expected: 0)`);

        // ========================================
        // SUMMARY
        // ========================================
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('   TEST SUMMARY');
        console.log('═══════════════════════════════════════════════════════════════\n');

        const positionCheckDefault = categoriesBox && saveButtonBox && (saveButtonBox.y > categoriesBox.y + 100);
        const positionCheckShop = shopCategoriesBox && shopSaveButtonBox && (shopSaveButtonBox.y > shopCategoriesBox.y + 100);

        const results = {
            defaultPosition: positionCheckDefault,
            defaultButtons: defaultSaveBtn > 0 && defaultCancelLink > 0,
            shopPosition: positionCheckShop,
            shopButtons: shopSaveBtn > 0 && shopCancelLink > 0 && shopSyncBtn >= 2 && shopPullBtn >= 2,
            oldButtonsRemoved: oldButtons === 0
        };

        const passed = Object.values(results).filter(v => v === true).length;
        const total = Object.keys(results).length;
        const successRate = ((passed / total) * 100).toFixed(0);

        console.log(`✅ Tests passed: ${passed}/${total}`);
        console.log(`📊 Success Rate: ${successRate}%\n`);

        if (successRate === '100') {
            console.log('🎉 ALL TESTS PASSED! Buttons are at BOTTOM correctly!\n');
        } else {
            console.log('⚠️  Some tests failed. Check results above.\n');
        }

        console.log('═══════════════════════════════════════════════════════════════\n');

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        await page.screenshot({
            path: '_TOOLS/screenshots/buttons_bottom_error.png',
            fullPage: true
        });
    } finally {
        console.log('⏳ Keeping browser open for 10 seconds...');
        await page.waitForTimeout(10000);

        await browser.close();
        console.log('✅ Test complete\n');
    }
})();
