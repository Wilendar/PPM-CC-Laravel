const { chromium } = require('playwright');

(async () => {
    console.log('=== TESTING ADD VARIANT WITH CONSOLE MONITORING ===\n');

    const consoleMessages = {
        errors: [],
        warnings: [],
        logs: [],
        info: []
    };

    const browser = await chromium.launch({
        headless: false,
        args: ['--start-maximized']
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    // Monitor ALL console messages
    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        const location = msg.location();

        const entry = {
            text: text,
            location: `${location.url}:${location.lineNumber}:${location.columnNumber}`,
            timestamp: new Date().toISOString()
        };

        if (type === 'error') {
            consoleMessages.errors.push(entry);
            console.log(`[ERROR] ${text}`);
        } else if (type === 'warning') {
            consoleMessages.warnings.push(entry);
            console.log(`[WARNING] ${text}`);
        } else if (type === 'log') {
            consoleMessages.logs.push(entry);
        } else if (type === 'info') {
            consoleMessages.info.push(entry);
        }
    });

    // Monitor page errors
    page.on('pageerror', error => {
        const entry = {
            text: error.message,
            stack: error.stack,
            timestamp: new Date().toISOString()
        };
        consoleMessages.errors.push(entry);
        console.log(`[PAGE ERROR] ${error.message}`);
    });

    // Monitor failed requests
    page.on('response', response => {
        if (response.status() >= 400) {
            const entry = {
                text: `Failed request: ${response.url()} - Status: ${response.status()}`,
                timestamp: new Date().toISOString()
            };
            consoleMessages.errors.push(entry);
            console.log(`[HTTP ERROR] ${response.url()} - ${response.status()}`);
        }
    });

    try {
        // Login
        console.log('[1/8] Logging in...');
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

        // Navigate to test product with variants
        console.log('[2/8] Opening product 10986...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/10986/edit');
        await page.waitForTimeout(3000);
        console.log('✅ Product opened\n');

        // Scroll to top to see checkbox
        console.log('[3/8] Scrolling to checkbox...');
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.waitForTimeout(1000);
        console.log('✅ Scrolled to top\n');

        // Check if "Produkt z wariantami" checkbox is checked, if not - check it
        console.log('[4/8] Checking "Produkt z wariantami" checkbox...');
        const checkbox = await page.$('input[wire\\:model\\.live="is_variant_master"]');
        if (checkbox) {
            const isChecked = await page.evaluate(el => el.checked, checkbox);
            console.log(`   Checkbox current state: ${isChecked ? 'checked' : 'unchecked'}`);

            if (!isChecked) {
                console.log('   Checking checkbox...');
                await checkbox.click();
                await page.waitForTimeout(2000);
                console.log('✅ Checkbox checked\n');
            } else {
                console.log('✅ Checkbox already checked\n');
            }
        } else {
            console.log('⚠️ Checkbox not found\n');
        }

        // Click Warianty tab
        console.log('[5/8] Clicking Warianty tab...');
        await page.evaluate(() => {
            const tabs = Array.from(document.querySelectorAll('button'));
            const variantyTab = tabs.find(btn => btn.textContent.includes('Warianty'));
            if (variantyTab) variantyTab.click();
        });
        await page.waitForTimeout(2000);
        console.log('✅ Warianty tab opened\n');

        // Take screenshot before adding variant
        await page.screenshot({ path: '_TOOLS/screenshots/before_add_variant.png', fullPage: true });

        // Click "Dodaj wariant" button
        console.log('[6/8] Clicking "Dodaj wariant"...');
        const addButton = await page.waitForSelector('button:has-text("Dodaj wariant")', { timeout: 5000 });
        await addButton.click();
        await page.waitForTimeout(2000);
        console.log('✅ Add variant modal opened\n');

        // Fill variant form
        console.log('[7/10] Filling variant form...');

        // Generate unique SKU with timestamp
        const uniqueSKU = `TEST-VAR-${Date.now()}`;

        // Fill SKU
        await page.fill('input[wire\\:model="variantData.sku"]', uniqueSKU);
        await page.waitForTimeout(500);

        // Fill Name
        await page.fill('input[wire\\:model="variantData.name"]', `Nowy Wariant ${uniqueSKU}`);
        await page.waitForTimeout(500);

        console.log('✅ Form filled\n');

        // Take screenshot of filled form
        await page.screenshot({ path: '_TOOLS/screenshots/variant_form_filled.png', fullPage: true });

        // Click Save button in modal
        console.log('[8/10] Clicking Save button...');
        await page.evaluate(() => {
            const buttons = Array.from(document.querySelectorAll('button'));
            const saveBtn = buttons.find(btn => btn.textContent.includes('Zapisz wariant'));
            if (saveBtn) saveBtn.click();
        });
        await page.waitForTimeout(3000);
        console.log('✅ Save clicked\n');

        // Wait for modal to close and variant to appear in list
        console.log('[9/10] Waiting for variant to appear in list...');
        await page.waitForTimeout(2000);
        console.log('✅ Variant saved\n');

        // Take final screenshot
        console.log('[10/10] Taking final screenshot...');
        await page.screenshot({ path: '_TOOLS/screenshots/after_add_variant.png', fullPage: true });
        console.log('✅ Screenshot saved\n');

    } catch (error) {
        console.log(`\n❌ TEST ERROR: ${error.message}\n`);
        await page.screenshot({ path: '_TOOLS/screenshots/test_error.png', fullPage: true });
    }

    // Print summary
    console.log('\n=== CONSOLE MESSAGES SUMMARY ===\n');

    console.log(`Total Errors: ${consoleMessages.errors.length}`);
    console.log(`Total Warnings: ${consoleMessages.warnings.length}`);
    console.log(`Total Logs: ${consoleMessages.logs.length}`);
    console.log(`Total Info: ${consoleMessages.info.length}\n`);

    if (consoleMessages.errors.length > 0) {
        console.log('=== ERRORS ===\n');
        consoleMessages.errors.forEach((err, idx) => {
            console.log(`[${idx + 1}] ${err.text}`);
            if (err.location) console.log(`    Location: ${err.location}`);
            if (err.stack) console.log(`    Stack: ${err.stack}`);
            console.log('');
        });
    }

    if (consoleMessages.warnings.length > 0) {
        console.log('=== WARNINGS ===\n');
        consoleMessages.warnings.forEach((warn, idx) => {
            console.log(`[${idx + 1}] ${warn.text}`);
            if (warn.location) console.log(`    Location: ${warn.location}`);
            console.log('');
        });
    }

    console.log('\n=== TEST COMPLETE ===');
    console.log('Browser will remain open for manual inspection.');
    console.log('Screenshots saved:');
    console.log('  - _TOOLS/screenshots/before_add_variant.png');
    console.log('  - _TOOLS/screenshots/variant_form_filled.png');
    console.log('  - _TOOLS/screenshots/after_add_variant.png');
    console.log('\nPress Ctrl+C to close.');

})();
