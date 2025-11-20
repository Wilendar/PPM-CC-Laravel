const { chromium } = require('playwright');

(async () => {
    console.log('=== MANUAL VERIFICATION: Otwieranie przeglądarki ===\n');
    console.log('KROKI DO WYKONANIA:');
    console.log('1. Zaloguj się jako admin@mpptrade.pl');
    console.log('2. Przejdź do produktu 11033');
    console.log('3. Kliknij shop badge "Test KAYO" (zielony)');
    console.log('4. SPRAWDŹ: Czy kategorie są z PrestaShop (NIE PPM)');
    console.log('5. Kliknij przycisk "Odśwież kategorie"');
    console.log('6. SPRAWDŹ: Czy pojawia się flash message');
    console.log('7. SPRAWDŹ: Czy kategorie się reload\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 100
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    // Go to login page
    console.log('Opening login page...');
    await page.goto('https://ppm.mpptrade.pl/admin/login');

    console.log('\n✅ Browser ready - execute manual steps above');
    console.log('Press CTRL+C when done\n');

    // Keep browser open indefinitely
    await page.waitForTimeout(600000); // 10 minutes

    await browser.close();
})();
