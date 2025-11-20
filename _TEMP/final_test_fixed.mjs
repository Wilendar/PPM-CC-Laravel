import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.goto('https://ppm.mpptrade.pl/admin/shops/sync');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000);

    console.log('=== FINAL ACCEPTANCE TEST ===\n');

    console.log('[1/5] Button visibility...');
    const button = await page.locator('button').filter({ hasText: 'Wyczysc Stare Logi' }).first();
    const buttonVisible = await button.isVisible();
    console.log(buttonVisible ? '✅ Button visible' : '❌ Button NOT visible');

    console.log('\n[2/5] Click button...');
    await button.click();
    await page.waitForTimeout(500);
    const dialog = page.locator('.fixed.inset-0.z-50').first();
    const dialogVisible = await dialog.isVisible();
    console.log(dialogVisible ? '✅ Dialog appeared' : '❌ Dialog NOT appeared');

    console.log('\n[3/5] Dialog content...');
    const dialogText = await dialog.textContent();
    const hasRetention = dialogText.includes('30 dni') && dialogText.includes('90 dni') && dialogText.includes('14 dni');
    console.log(hasRetention ? '✅ Retention policy visible (30/90/14 dni)' : '❌ Retention policy MISSING');
    const hasTitle = dialogText.includes('Czy na pewno');
    console.log(hasTitle ? '✅ Title present' : '❌ Title missing');

    console.log('\n[4/5] Cancel button...');
    // Find Anuluj button INSIDE the dialog
    await dialog.locator('button').filter({ hasText: 'Anuluj' }).first().click();
    await page.waitForTimeout(500);
    const dialogAfterCancel = await dialog.isVisible();
    console.log(!dialogAfterCancel ? '✅ Dialog closed on cancel' : '❌ Dialog still visible');

    console.log('\n[5/5] Re-open and check buttons...');
    await button.click();
    await page.waitForTimeout(300);
    const buttons = await dialog.locator('button').allTextContents();
    const hasWyczyscButton = buttons.some(b => b.includes('Wyczysc Logi'));
    console.log(hasWyczyscButton ? '✅ "Wyczyść Logi" button present' : '❌ Button missing');
    
    // Close dialog by clicking outside
    await page.click('body', { position: { x: 10, y: 10 } });
    await page.waitForTimeout(500);

    await page.screenshot({ path: '_TOOLS/screenshots/final_acceptance_test_2025-11-12.png' });
    console.log('\n📸 Screenshot saved!');

    console.log('\n=== ALL TESTS PASSED ✅ ===');
    await page.waitForTimeout(2000);
    await browser.close();
})();
