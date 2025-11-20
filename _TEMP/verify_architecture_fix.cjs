const { chromium } = require('playwright');

(async () => {
    console.log('=== WERYFIKACJA ARCHITEKTURY: PrestaShop Categories Display ===\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    // Collect console
    const consoleMessages = [];
    page.on('console', msg => {
        consoleMessages.push({
            type: msg.type(),
            text: msg.text()
        });
    });

    // Login
    console.log('[1/8] Logging in...');
    await page.goto('https://ppm.mpptrade.pl/admin/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin', { timeout: 30000 });
    console.log('✅ Logged in\n');

    // Navigate to product
    console.log('[2/8] Navigating to product 11033...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit');
    await page.waitForLoadState('networkidle');
    console.log('✅ Product page loaded\n');

    // Screenshot BEFORE clicking shop
    console.log('[3/8] Screenshot BEFORE clicking shop (Default TAB)...');
    await page.screenshot({
        path: '_TOOLS/screenshots/architecture_fix_BEFORE_shop_click.png',
        fullPage: true
    });
    console.log('✅ Screenshot saved\n');

    // Find and click shop badge "Test KAYO"
    console.log('[4/8] Clicking shop badge "Test KAYO"...');
    const shopBadge = page.locator('button:has-text("Test KAYO")').first();
    const badgeExists = await shopBadge.count() > 0;

    if (!badgeExists) {
        console.log('🚨 CRITICAL: Shop badge "Test KAYO" NOT FOUND!');
        await browser.close();
        return;
    }

    await shopBadge.click();
    await page.waitForTimeout(3000); // Wait for Livewire update
    console.log('✅ Shop badge clicked (activeShopId should be set)\n');

    // Screenshot AFTER clicking shop
    console.log('[5/8] Screenshot AFTER clicking shop...');
    await page.screenshot({
        path: '_TOOLS/screenshots/architecture_fix_AFTER_shop_click.png',
        fullPage: true
    });
    console.log('✅ Screenshot saved\n');

    // Check for "Odśwież kategorie" button
    console.log('[6/8] Checking for "Odśwież kategorie" button...');
    const refreshButton = page.locator('button:has-text("Odśwież kategorie")');
    const buttonCount = await refreshButton.count();

    if (buttonCount > 0) {
        console.log(`✅ Button found! (${buttonCount} instance(s))`);

        // Test clicking the button
        console.log('\n[7/8] Testing button click...');
        await refreshButton.first().click();
        await page.waitForTimeout(3000); // Wait for API call + re-render

        // Check for flash message
        const flashMessage = await page.locator('text=Kategorie odświeżone z PrestaShop').count() > 0;
        console.log(`   Flash message: ${flashMessage ? '✅ APPEARED' : '❌ NOT FOUND'}`);

        // Screenshot AFTER refresh
        await page.screenshot({
            path: '_TOOLS/screenshots/architecture_fix_AFTER_refresh.png',
            fullPage: true
        });
        console.log('✅ Screenshot saved\n');

    } else {
        console.log('🚨 CRITICAL: Button "Odśwież kategorie" NOT FOUND after clicking shop!\n');
    }

    // Check category section content
    console.log('[8/8] Checking category section...');
    const categorySection = page.locator('text=Kategorie produktu').first();
    await categorySection.scrollIntoViewIfNeeded();

    // Look for category items
    const categoryItems = await page.locator('[wire\\:key^="categories-ctx-"]').count();
    console.log(`   Category items found: ${categoryItems > 0 ? '✅ ' + categoryItems : '❌ NONE'}`);

    // Final screenshot
    await page.screenshot({
        path: '_TOOLS/screenshots/architecture_fix_FINAL_state.png',
        fullPage: true
    });

    console.log('\n=== CONSOLE ERRORS CHECK ===');
    const errors = consoleMessages.filter(m => m.type === 'error');
    console.log(`Total errors: ${errors.length}`);
    if (errors.length > 0) {
        errors.forEach((err, i) => console.log(`${i + 1}. ${err.text}`));
    } else {
        console.log('✅ No console errors!');
    }

    console.log('\n=== VERIFICATION COMPLETE ===');
    console.log('Screenshots saved in _TOOLS/screenshots/');
    console.log('\nBrowser pozostaje otwarty - sprawdź manualnie kategorie.');
    console.log('SPRAWDŹ: Czy kategorie pokazane są z PrestaShop (nie PPM)?');
    console.log('Naciśnij CTRL+C aby zamknąć.\n');

    await page.waitForTimeout(120000); // Keep open 2 minutes

    await browser.close();
})();
