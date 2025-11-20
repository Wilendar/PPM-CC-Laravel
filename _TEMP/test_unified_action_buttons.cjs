/**
 * TEST: Unified Action Buttons (Partials Instance)
 *
 * PURPOSE: Verify that action buttons are reusable partials:
 * - DEFAULT TAB: "Zapisz zmiany" + "Anuluj i wróć"
 * - SHOP TAB: + "Aktualizuj aktualny sklep" + "Wczytaj z aktualnego sklepu"
 * - SIDEPANEL: Same buttons (instances)
 * - NO OLD BUTTONS: "Zaktualizuj na sklepie", "Zapisz wszystkie zmiany" removed
 */

const { chromium } = require('playwright');

(async () => {
    console.log('\n╔═══════════════════════════════════════════════════════════════╗');
    console.log('║   TEST: Unified Action Buttons (Partials)                    ║');
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
        // TEST 1: DEFAULT TAB Buttons
        // ========================================
        console.log('═══════════════════════════════════════════════════════════════');
        console.log('   TEST 1: DEFAULT TAB - Inline Action Bar');
        console.log('═══════════════════════════════════════════════════════════════\n');

        // Check for "Zapisz zmiany" button (from save-and-close-button partial)
        const defaultSaveBtn = await page.locator('button').filter({ hasText: /Zapisz zmiany|Utwórz produkt/i }).first();
        const hasSaveBtn = await defaultSaveBtn.count() > 0;
        console.log(`${ hasSaveBtn ? '✅' : '❌' } "Zapisz zmiany" button found`);

        // Check for "Anuluj i wróć" link (from cancel-link partial)
        const defaultCancelLink = await page.locator('a').filter({ hasText: /Anuluj i wróć/i }).first();
        const hasCancelLink = await defaultCancelLink.count() > 0;
        console.log(`${ hasCancelLink ? '✅' : '❌' } "Anuluj i wróć" link found`);

        // Check that OLD buttons are NOT present
        const oldButtons = await page.locator('button').filter({ hasText: /Zaktualizuj na sklepie|Zapisz wszystkie zmiany/i }).count();
        console.log(`${ oldButtons === 0 ? '✅' : '❌' } OLD buttons removed (found: ${oldButtons}, expected: 0)`);

        await page.screenshot({
            path: '_TOOLS/screenshots/unified_buttons_default_tab.png',
            fullPage: true
        });
        console.log('\n📸 Screenshot saved: unified_buttons_default_tab.png\n');

        // ========================================
        // TEST 2: SHOP TAB Buttons
        // ========================================
        console.log('═══════════════════════════════════════════════════════════════');
        console.log('   TEST 2: SHOP TAB - Inline Action Bar + Shop Actions');
        console.log('═══════════════════════════════════════════════════════════════\n');

        console.log('📍 STEP 2: Switching to shop TAB "B2B Test DEV"...');
        const shopButton = page.locator('button[wire\\:click="switchToShop(1)"]').first();
        await shopButton.click();
        await page.waitForTimeout(2000);
        console.log('✅ Shop TAB clicked\n');

        // Check for "Zapisz zmiany" button (same partial, different context)
        const shopSaveBtn = await page.locator('button').filter({ hasText: /Zapisz zmiany|Utwórz produkt/i }).first();
        const hasShopSaveBtn = await shopSaveBtn.count() > 0;
        console.log(`${ hasShopSaveBtn ? '✅' : '❌' } "Zapisz zmiany" button found (shop context)`);

        // Check for "Anuluj i wróć" link (same partial)
        const shopCancelLink = await page.locator('a').filter({ hasText: /Anuluj i wróć/i }).first();
        const hasShopCancelLink = await shopCancelLink.count() > 0;
        console.log(`${ hasShopCancelLink ? '✅' : '❌' } "Anuluj i wróć" link found (shop context)`);

        // Check for "Aktualizuj aktualny sklep" button (shop-sync-button partial)
        const syncBtn = await page.locator('button').filter({ hasText: /Aktualizuj aktualny sklep/i }).all();
        console.log(`${ syncBtn.length > 0 ? '✅' : '❌' } "Aktualizuj aktualny sklep" button found (instances: ${syncBtn.length})`);

        // Check for "Wczytaj z aktualnego sklepu" button (shop-pull-button partial)
        const pullBtn = await page.locator('button').filter({ hasText: /Wczytaj z aktualnego sklepu/i }).all();
        console.log(`${ pullBtn.length > 0 ? '✅' : '❌' } "Wczytaj z aktualnego sklepu" button found (instances: ${pullBtn.length})`);

        await page.screenshot({
            path: '_TOOLS/screenshots/unified_buttons_shop_tab.png',
            fullPage: true
        });
        console.log('\n📸 Screenshot saved: unified_buttons_shop_tab.png\n');

        // ========================================
        // TEST 3: SIDEPANEL Buttons
        // ========================================
        console.log('═══════════════════════════════════════════════════════════════');
        console.log('   TEST 3: SIDEPANEL - Akcje boczne (Same Partials)');
        console.log('═══════════════════════════════════════════════════════════════\n');

        // Scroll to sidepanel (right side)
        await page.evaluate(() => {
            const sidepanel = document.querySelector('.space-y-6.lg\\:w-80');
            if (sidepanel) sidepanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        await page.waitForTimeout(1000);

        // Count "Zapisz zmiany" instances (should be 2: inline + sidepanel)
        const allSaveButtons = await page.locator('button').filter({ hasText: /Zapisz zmiany|Utwórz produkt/i }).all();
        console.log(`${ allSaveButtons.length >= 2 ? '✅' : '⚠️' } "Zapisz zmiany" instances: ${allSaveButtons.length} (expected: ≥2)`);

        // Count "Anuluj i wróć" instances (should be 1: only inline - sidepanel doesn't duplicate cancel)
        const allCancelLinks = await page.locator('a').filter({ hasText: /Anuluj i wróć/i }).all();
        console.log(`${ allCancelLinks.length === 1 ? '✅' : '⚠️' } "Anuluj i wróć" instances: ${allCancelLinks.length} (expected: 1)`);

        // Count shop action instances (should be 2: inline + compact)
        const allSyncButtons = await page.locator('button').filter({ hasText: /Aktualizuj aktualny sklep/i }).all();
        console.log(`${ allSyncButtons.length >= 2 ? '✅' : '⚠️' } "Aktualizuj aktualny" instances: ${allSyncButtons.length} (expected: ≥2)`);

        const allPullButtons = await page.locator('button').filter({ hasText: /Wczytaj z aktualnego/i }).all();
        console.log(`${ allPullButtons.length >= 2 ? '✅' : '⚠️' } "Wczytaj z aktualnego" instances: ${allPullButtons.length} (expected: ≥2)`);

        await page.screenshot({
            path: '_TOOLS/screenshots/unified_buttons_sidepanel.png',
            fullPage: false // viewport only for sidepanel focus
        });
        console.log('\n📸 Screenshot saved: unified_buttons_sidepanel.png\n');

        // ========================================
        // TEST 4: Console & Network Check
        // ========================================
        console.log('═══════════════════════════════════════════════════════════════');
        console.log('   TEST 4: Console & Network Errors');
        console.log('═══════════════════════════════════════════════════════════════\n');

        // Listen to console errors
        const consoleErrors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });

        // Refresh page to check for errors
        await page.reload({ waitUntil: 'networkidle' });
        await page.waitForTimeout(2000);

        console.log(`${ consoleErrors.length === 0 ? '✅' : '⚠️' } Console errors: ${consoleErrors.length}`);
        if (consoleErrors.length > 0) {
            console.log('\n⚠️  Console errors found:');
            consoleErrors.slice(0, 5).forEach(err => console.log(`   - ${err.substring(0, 100)}...`));
        }

        // ========================================
        // SUMMARY
        // ========================================
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('   TEST SUMMARY');
        console.log('═══════════════════════════════════════════════════════════════\n');

        const results = {
            defaultSave: hasSaveBtn,
            defaultCancel: hasCancelLink,
            oldButtonsRemoved: oldButtons === 0,
            shopSave: hasShopSaveBtn,
            shopCancel: hasShopCancelLink,
            shopSync: syncBtn.length > 0,
            shopPull: pullBtn.length > 0,
            saveInstances: allSaveButtons.length >= 2,
            shopSyncInstances: allSyncButtons.length >= 2,
            shopPullInstances: allPullButtons.length >= 2,
            noConsoleErrors: consoleErrors.length === 0
        };

        const passed = Object.values(results).filter(v => v === true).length;
        const total = Object.keys(results).length;
        const successRate = ((passed / total) * 100).toFixed(0);

        console.log(`✅ Tests passed: ${passed}/${total}`);
        console.log(`📊 Success Rate: ${successRate}%\n`);

        if (successRate === '100') {
            console.log('🎉 ALL TESTS PASSED! Unified action buttons work correctly!\n');
        } else {
            console.log('⚠️  Some tests failed. Check results above.\n');
        }

        console.log('═══════════════════════════════════════════════════════════════\n');

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        await page.screenshot({
            path: '_TOOLS/screenshots/unified_buttons_error.png',
            fullPage: true
        });
    } finally {
        console.log('⏳ Keeping browser open for 10 seconds...');
        await page.waitForTimeout(10000);

        await browser.close();
        console.log('✅ Test complete\n');
    }
})();
