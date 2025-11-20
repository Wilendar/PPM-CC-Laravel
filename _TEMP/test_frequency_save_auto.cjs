const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log('=== AUTO TEST: Frequency Save Bug ===\n');

    try {
        // 1. Login
        console.log('1. Logging in...');
        await page.goto('https://ppm.mpptrade.pl/login');
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle', { timeout: 15000 });
        console.log('   ✅ Logged in (URL: ' + page.url() + ')\n');

        // 2. Navigate to sync panel
        console.log('2. Opening sync panel...');
        await page.goto('https://ppm.mpptrade.pl/admin/shops/sync');
        await page.waitForLoadState('networkidle');
        console.log('   ✅ Sync panel loaded\n');

        // 3. Expand configuration
        console.log('3. Expanding sync configuration...');
        const configButton = page.locator('button:has-text("Pokaż konfigurację"), button:has-text("Ukryj konfigurację")').first();
        await configButton.click();
        await page.waitForTimeout(1000);
        console.log('   ✅ Configuration expanded\n');

        // 4. Get current frequency value
        console.log('4. Reading current frequency...');
        const frequencySelect = page.locator('select[wire\\:model\\.live="autoSyncFrequency"]');
        const currentValue = await frequencySelect.inputValue();
        console.log(`   Current value: ${currentValue}\n`);

        // 5. Change to different value
        const newValue = currentValue === 'hourly' ? 'daily' : 'hourly';
        console.log(`5. Changing frequency to: ${newValue}...`);
        await frequencySelect.selectOption(newValue);
        await page.waitForTimeout(500); // wire:model.live sync
        console.log('   ✅ Value changed\n');

        // 6. Save configuration
        console.log('6. Saving configuration...');
        const saveButton = page.locator('button:has-text("Zapisz konfigurację")');
        await saveButton.click();
        await page.waitForTimeout(2000); // Wait for save + re-render

        // 7. Check if success message appeared
        const successMessage = page.locator('text=pomyślnie, text=zapisana').first();
        const hasSuccess = await successMessage.isVisible().catch(() => false);

        if (hasSuccess) {
            console.log('   ✅ Success message displayed\n');
        } else {
            console.log('   ⚠️  No success message (check manually)\n');
        }

        // 8. Verify value persisted (CRITICAL TEST)
        console.log('7. Verifying value persisted after save...');
        await page.waitForTimeout(1000);
        const valueAfterSave = await frequencySelect.inputValue();

        if (valueAfterSave === newValue) {
            console.log(`   ✅ VALUE PERSISTED! (${newValue})\n`);
        } else {
            console.log(`   ❌ VALUE REVERTED! Expected: ${newValue}, Got: ${valueAfterSave}\n`);
        }

        // 9. Refresh page and verify persistence
        console.log('8. Refreshing page to verify database persistence...');
        await page.reload();
        await page.waitForLoadState('networkidle');

        // Re-expand configuration
        const configButton2 = page.locator('button:has-text("Pokaż konfigurację")').first();
        await configButton2.click();
        await page.waitForTimeout(1000);

        const frequencySelect2 = page.locator('select[wire\\:model\\.live="autoSyncFrequency"]');
        const valueAfterRefresh = await frequencySelect2.inputValue();

        if (valueAfterRefresh === newValue) {
            console.log(`   ✅ VALUE PERSISTED AFTER REFRESH! (${newValue})\n`);
        } else {
            console.log(`   ❌ VALUE LOST AFTER REFRESH! Expected: ${newValue}, Got: ${valueAfterRefresh}\n`);
        }

        // 10. Take screenshot
        await page.screenshot({
            path: '_TOOLS/screenshots/frequency_test_auto_' + new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5) + '.png',
            fullPage: true
        });
        console.log('   ✅ Screenshot saved\n');

        // Summary
        console.log('=== TEST SUMMARY ===');
        console.log(`Original value: ${currentValue}`);
        console.log(`Changed to: ${newValue}`);
        console.log(`After save: ${valueAfterSave} ${valueAfterSave === newValue ? '✅' : '❌'}`);
        console.log(`After refresh: ${valueAfterRefresh} ${valueAfterRefresh === newValue ? '✅' : '❌'}`);

        const testPassed = (valueAfterSave === newValue) && (valueAfterRefresh === newValue);
        console.log(`\nTEST RESULT: ${testPassed ? '✅ PASSED' : '❌ FAILED'}\n`);

    } catch (error) {
        console.error('❌ TEST FAILED:', error.message);
        await page.screenshot({ path: '_TOOLS/screenshots/frequency_test_error.png', fullPage: true });
    } finally {
        await browser.close();
    }
})();
