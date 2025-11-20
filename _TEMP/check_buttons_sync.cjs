const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });
    const page = await context.newPage();

    console.log('[1/5] Navigating to sync page...');
    await page.goto('https://ppm.mpptrade.pl/admin/shops/sync', { waitUntil: 'networkidle' });

    console.log('[2/5] Waiting for page load...');
    await page.waitForTimeout(2000);

    console.log('[3/5] Scrolling to bottom to find buttons...');
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(1000);

    console.log('[4/5] Looking for buttons...');

    // Search for "Wyczyść Stare Logi" button
    const clearButton = await page.locator('button:has-text("Wyczysc Stare Logi")').count();
    console.log(`   - "Wyczyść Stare Logi" button found: ${clearButton > 0 ? 'YES' : 'NO'} (${clearButton} instances)`);

    // Search for "Archiwizuj" button
    const archiveButton = await page.locator('button:has-text("Archiwizuj")').count();
    console.log(`   - "Archiwizuj" button found: ${archiveButton > 0 ? 'YES' : 'NO'} (${archiveButton} instances)`);

    // Get their position and styling if found
    if (clearButton > 0) {
        const clearBtnBox = await page.locator('button:has-text("Wyczysc Stare Logi")').first().boundingBox();
        console.log(`   - Clear button position: ${JSON.stringify(clearBtnBox)}`);
    }

    if (archiveButton > 0) {
        const archiveBtnBox = await page.locator('button:has-text("Archiwizuj")').first().boundingBox();
        console.log(`   - Archive button position: ${JSON.stringify(archiveBtnBox)}`);
    }

    console.log('[5/5] Taking screenshot...');
    await page.screenshot({
        path: '_TOOLS/screenshots/sync_buttons_check_2025-11-12.png',
        fullPage: true
    });

    console.log('✅ Screenshot saved: _TOOLS/screenshots/sync_buttons_check_2025-11-12.png');

    await browser.close();
})();
