const { chromium } = require('playwright');

(async () => {
    console.log('=== WERYFIKACJA: Przycisk "Odśwież kategorie" PO FIXIE ===\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    // Collect console messages
    const consoleMessages = [];
    page.on('console', msg => {
        consoleMessages.push({
            type: msg.type(),
            text: msg.text()
        });
    });

    // Login
    console.log('[1/6] Logging in...');
    await page.goto('https://ppm.mpptrade.pl/admin/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin');
    console.log('✅ Logged in\n');

    // Navigate to product
    console.log('[2/6] Navigating to product 11033...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit');
    await page.waitForLoadState('networkidle');
    console.log('✅ Product page loaded\n');

    // Check for Alpine.js errors in console
    console.log('[3/6] Checking console errors...');
    const alpineErrors = consoleMessages.filter(m =>
        m.text.includes('Alpine Expression Error') ||
        m.text.includes('wire:loading')
    );

    if (alpineErrors.length > 0) {
        console.log('🚨 Alpine.js errors found:');
        alpineErrors.forEach(err => console.log(`   - ${err.text}`));
    } else {
        console.log('✅ No Alpine.js errors (wire:loading fix successful!)');
    }
    console.log('');

    // Find and click shop badge "Test KAYO"
    console.log('[4/6] Clicking shop badge "Test KAYO"...');
    const shopBadge = page.locator('button:has-text("Test KAYO")').first();
    const badgeExists = await shopBadge.count() > 0;

    if (!badgeExists) {
        console.log('🚨 PROBLEM: Shop badge "Test KAYO" nie znaleziony!');
        await browser.close();
        return;
    }

    await shopBadge.click();
    await page.waitForTimeout(2000); // Wait for Livewire update
    console.log('✅ Shop badge clicked (activeShopId should be set)\n');

    // Check for refresh button
    console.log('[5/6] Checking for "Odśwież kategorie" button...');
    await page.waitForTimeout(1000); // Extra wait for DOM update

    const refreshButton = page.locator('button:has-text("Odśwież kategorie")');
    const buttonCount = await refreshButton.count();

    if (buttonCount > 0) {
        console.log(`✅ Przycisk "Odśwież kategorie" found! (${buttonCount} instance(s))`);

        const button = refreshButton.first();
        const classes = await button.getAttribute('class');
        console.log(`   Classes: ${classes}`);

        const isVisible = await button.isVisible();
        console.log(`   Visible: ${isVisible ? '✅ TAK' : '❌ NIE'}`);

        // Check styling - should have btn-enterprise-secondary
        const hasCorrectClass = classes.includes('btn-enterprise-secondary');
        console.log(`   Has btn-enterprise-secondary: ${hasCorrectClass ? '✅ TAK' : '❌ NIE (STILL BROKEN)'}`);

        // Test wire:click
        console.log('\n   Testing wire:click handler...');
        await button.click();
        await page.waitForTimeout(2000); // Wait for Livewire action

        // Check for flash message
        const flashMessage = await page.locator('text=Kategorie odświeżone z PrestaShop').count() > 0;
        console.log(`   Flash message appeared: ${flashMessage ? '✅ TAK (wire:click DZIAŁA!)' : '⚠️ NIE (może error lub timeout)'}`);

    } else {
        console.log('🚨 PROBLEM: Przycisk "Odśwież kategorie" NIE pojawił się!');
        console.log('   Możliwe przyczyny:');
        console.log('   - activeShopId nie został ustawiony po kliknięciu badge');
        console.log('   - Warunek @if($activeShopId) nie jest spełniony');
    }

    // Screenshot
    console.log('\n[6/6] Taking screenshot...');
    await page.screenshot({
        path: '_TOOLS/screenshots/refresh_button_verification_2025-11-19.png',
        fullPage: true
    });
    console.log('✅ Screenshot saved: _TOOLS/screenshots/refresh_button_verification_2025-11-19.png\n');

    console.log('=== FINAL CONSOLE ERROR CHECK ===');
    const errors = consoleMessages.filter(m => m.type === 'error');
    console.log(`Total errors in console: ${errors.length}`);
    errors.forEach(err => console.log(`   - ${err.text}`));

    console.log('\n=== KONIEC WERYFIKACJI ===');

    // Keep browser open for manual inspection
    console.log('\nBrowser pozostaje otwarty - sprawdź manualnie. Naciśnij CTRL+C aby zamknąć.');
    await page.waitForTimeout(60000);

    await browser.close();
})();
