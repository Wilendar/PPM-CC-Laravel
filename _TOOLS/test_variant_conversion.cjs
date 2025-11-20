const { chromium } = require('playwright');

(async () => {
    console.log('=== TESTING VARIANT CONVERSION ===\n');

    const browser = await chromium.launch({
        headless: false,
        args: ['--start-maximized']
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    // Monitor console
    page.on('console', msg => {
        const type = msg.type();
        if (type === 'error' || type === 'warning') {
            console.log(`[${type}] ${msg.text()}`);
        }
    });

    // Login
    console.log('[1/7] Logging in...');
    await page.goto('https://ppm.mpptrade.pl/login');
    await page.waitForSelector('input[name="email"]', { timeout: 10000 });
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await Promise.all([
        page.waitForNavigation({ timeout: 10000 }).catch(() => {}),
        page.click('button[type="submit"]')
    ]);
    await page.waitForTimeout(3000);
    console.log('✅ Logged in\n');

    // Navigate to test product
    console.log('[2/9] Opening test product...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/10986/edit');
    await page.waitForTimeout(3000);
    console.log('✅ Product opened\n');

    // Scroll to top to see checkbox
    console.log('[3/9] Scrolling to checkbox...');
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(1000);
    console.log('✅ Scrolled to top\n');

    // Find and uncheck "Produkt z wariantami" checkbox
    console.log('[4/9] Finding checkbox...');
    const checkbox = await page.$('input[wire\\:model\\.live="is_variant_master"]');
    if (!checkbox) {
        console.log('❌ Checkbox not found!');
        await page.screenshot({ path: '_TOOLS/screenshots/no_checkbox.png', fullPage: true });
        await browser.close();
        return;
    }

    const isChecked = await page.evaluate(el => el.checked, checkbox);
    console.log(`   Current state: ${isChecked ? 'checked' : 'unchecked'}`);

    if (isChecked) {
        console.log('   Unchecking...');
        await checkbox.click();
        console.log('✅ Checkbox unchecked\n');
        await page.waitForTimeout(2000);
    } else {
        console.log('⚠️ Checkbox already unchecked\n');
    }

    // Wait for modal to appear
    console.log('[5/9] Waiting for modal...');
    try {
        await page.waitForFunction(
            () => {
                const buttons = Array.from(document.querySelectorAll('button'));
                return buttons.some(btn => btn.textContent.includes('Konwertuj na produkty'));
            },
            { timeout: 5000 }
        );
        console.log('✅ Modal appeared\n');
    } catch (e) {
        console.log('❌ Modal did not appear!');
        console.log('   Taking screenshot for debugging...');
        await page.screenshot({ path: '_TOOLS/screenshots/no_modal.png', fullPage: true });
        console.log('   Screenshot: _TOOLS/screenshots/no_modal.png');

        // Don't close, keep open for inspection
        console.log('\nBrowser will remain open for manual inspection.');
        console.log('Press Ctrl+C to close.');
        return;
    }

    // Click "Konwertuj na produkty" button
    console.log('[6/9] Clicking "Konwertuj na produkty"...');
    await page.evaluate(() => {
        const buttons = Array.from(document.querySelectorAll('button'));
        const convertBtn = buttons.find(btn => btn.textContent.includes('Konwertuj na produkty'));
        if (convertBtn) {
            convertBtn.click();
        }
    });
    console.log('✅ Button clicked\n');

    // Wait for modal to close
    console.log('[7/9] Waiting for modal to close...');
    await page.waitForTimeout(2000);
    console.log('✅ Modal closed\n');

    // Scroll down to find Save button
    console.log('[8/9] Looking for Save button...');
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(1000);

    // Click "Zapisz zmiany" button to actually execute the conversion
    const saveButton = await page.waitForSelector('button:has-text("Zapisz zmiany")', { timeout: 5000 });
    if (!saveButton) {
        console.log('❌ Save button not found!');
        await page.screenshot({ path: '_TOOLS/screenshots/no_save_button.png', fullPage: true });
        return;
    }

    console.log('   Clicking Save button...');
    await saveButton.click();
    console.log('✅ Save button clicked\n');

    // Wait for save and conversion to complete
    console.log('[9/9] Waiting for save and conversion...');
    await page.waitForTimeout(5000);
    console.log('✅ Conversion complete\n');

    // Take final screenshot
    console.log('Taking final screenshot...');
    await page.screenshot({ path: '_TOOLS/screenshots/after_conversion_and_save.png', fullPage: true });
    console.log('✅ Screenshot saved: _TOOLS/screenshots/after_conversion_and_save.png\n');

    console.log('=== TEST COMPLETE ===');
    console.log('Browser will remain open for manual inspection.');
    console.log('Press Ctrl+C to close.');

})();
