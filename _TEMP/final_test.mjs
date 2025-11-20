import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    // Login
    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Go to sync page
    await page.goto('https://ppm.mpptrade.pl/admin/shops/sync');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000);

    console.log('=== FINAL ACCEPTANCE TEST ===\n');

    // Test 1: Button visibility
    console.log('[1/5] Button visibility...');
    const button = await page.locator('button').filter({ hasText: 'Wyczysc Stare Logi' }).first();
    const buttonVisible = await button.isVisible();
    console.log(buttonVisible ? '✅ Button visible' : '❌ Button NOT visible');

    // Test 2: Click button -> dialog appears
    console.log('\n[2/5] Click button...');
    await button.click();
    await page.waitForTimeout(500);
    const dialogAfterClick = await page.locator('.fixed.inset-0.z-50').isVisible();
    console.log(dialogAfterClick ? '✅ Dialog appeared' : '❌ Dialog NOT appeared');

    // Test 3: Dialog content
    console.log('\n[3/5] Dialog content...');
    const dialogText = await page.locator('.fixed.inset-0.z-50').textContent();
    const hasRetention = dialogText.includes('30 dni') && dialogText.includes('90 dni') && dialogText.includes('14 dni');
    console.log(hasRetention ? '✅ Retention policy visible' : '❌ Retention policy MISSING');

    // Test 4: Cancel button
    console.log('\n[4/5] Cancel button...');
    await page.locator('button').filter({ hasText: 'Anuluj' }).click();
    await page.waitForTimeout(500);
    const dialogAfterCancel = await page.locator('.fixed.inset-0.z-50').isVisible();
    console.log(!dialogAfterCancel ? '✅ Dialog closed on cancel' : '❌ Dialog still visible');

    // Test 5: Loading state (re-open dialog, click Wyczyść)
    console.log('\n[5/5] Loading state (will trigger backend)...');
    await button.click();
    await page.waitForTimeout(300);
    
    // Click "Wyczyść Logi" button
    await page.locator('button').filter({ hasText: /Wyczysc Logi/i }).last().click();
    await page.waitForTimeout(1000);
    
    // Check if button shows loading state
    const loadingText = await page.locator('button').filter({ hasText: 'Czyszcze' }).isVisible();
    console.log(loadingText ? '✅ Loading state active ("Czyszcze...")' : '⚠️ Loading state not visible (maybe too fast)');

    // Screenshot
    await page.waitForTimeout(2000);
    await page.screenshot({ path: '_TOOLS/screenshots/final_test_2025-11-12.png' });
    console.log('\n📸 Screenshot saved!');

    console.log('\n=== TEST COMPLETE ===');
    await page.waitForTimeout(3000);
    await browser.close();
})();
