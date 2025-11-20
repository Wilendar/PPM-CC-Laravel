const { chromium } = require('playwright');

(async () => {
    console.log('=== DIAGNOZA: Przycisk "Odśwież kategorie" ===\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

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

    // Check initial state
    console.log('[3/6] Checking initial state (no shop selected)...');
    const initialButton = await page.locator('button:has-text("Odśwież kategorie")').count();
    console.log(`   Przycisk "Odśwież kategorie" visible: ${initialButton > 0 ? '✅ TAK' : '❌ NIE'}`);

    // Find shop badge "Test KAYO"
    console.log('\n[4/6] Looking for shop badge "Test KAYO"...');
    const shopBadge = page.locator('button:has-text("Test KAYO")').first();
    const badgeExists = await shopBadge.count() > 0;
    console.log(`   Shop badge "Test KAYO" found: ${badgeExists ? '✅ TAK' : '❌ NIE'}`);

    if (!badgeExists) {
        console.log('\n🚨 PROBLEM: Shop badge "Test KAYO" nie znaleziony!');
        await browser.close();
        return;
    }

    // Click shop badge
    console.log('\n[5/6] Clicking shop badge "Test KAYO"...');
    await shopBadge.click();
    await page.waitForTimeout(2000); // Wait for Livewire update
    console.log('✅ Shop badge clicked\n');

    // Check for refresh button AFTER clicking shop
    console.log('[6/6] Checking for "Odśwież kategorie" button AFTER shop selection...');

    // Wait a bit for Livewire to update DOM
    await page.waitForTimeout(1000);

    const refreshButtonAfter = page.locator('button:has-text("Odśwież kategorie")');
    const buttonCount = await refreshButtonAfter.count();

    console.log(`   Przycisk "Odśwież kategorie" visible: ${buttonCount > 0 ? '✅ TAK' : '❌ NIE'}`);

    if (buttonCount > 0) {
        // Check button styling
        const button = refreshButtonAfter.first();
        const classes = await button.getAttribute('class');
        console.log(`   Button classes: ${classes}`);

        const isVisible = await button.isVisible();
        console.log(`   Is visible: ${isVisible ? '✅ TAK' : '❌ NIE'}`);

        // Take screenshot of button area
        const bbox = await button.boundingBox();
        if (bbox) {
            console.log(`   Position: x=${bbox.x}, y=${bbox.y}, width=${bbox.width}, height=${bbox.height}`);
        }

        // Screenshot
        await page.screenshot({
            path: '_TOOLS/screenshots/refresh_button_diagnostic.png',
            fullPage: true
        });
        console.log('\n✅ Screenshot saved: _TOOLS/screenshots/refresh_button_diagnostic.png');
    } else {
        console.log('\n🚨 PROBLEM: Przycisk "Odśwież kategorie" NIE pojawił się po kliknięciu shop badge!');

        // Debug: Check activeShopId value
        console.log('\n   DEBUG INFO:');
        const pageContent = await page.content();
        const hasActiveShop = pageContent.includes('activeShopId');
        console.log(`   - Page contains "activeShopId": ${hasActiveShop}`);

        // Check for category section
        const categorySection = await page.locator('text=Kategorie produktu').count();
        console.log(`   - "Kategorie produktu" section found: ${categorySection > 0}`);

        // Screenshot for debugging
        await page.screenshot({
            path: '_TOOLS/screenshots/refresh_button_missing.png',
            fullPage: true
        });
        console.log('\n   Screenshot saved: _TOOLS/screenshots/refresh_button_missing.png');
    }

    console.log('\n=== KONIEC DIAGNOZY ===');

    // Keep browser open for manual inspection
    console.log('\nBrowser pozostaje otwarty - sprawdź manualnie. Naciśnij CTRL+C aby zamknąć.');
    await page.waitForTimeout(60000);

    await browser.close();
})();
