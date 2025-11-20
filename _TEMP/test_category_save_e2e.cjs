const { chromium } = require('playwright');

(async () => {
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('   E2E TEST: Category Save with Verification');
    console.log('═══════════════════════════════════════════════════════════════\n');

    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    // Track Livewire responses
    page.on('response', response => {
        const url = response.url();
        if (url.includes('/livewire/')) {
            console.log(`📡 Livewire response: ${response.status()}`);
        }
    });

    // Track console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    try {
        // STEP 1: Navigate to product 11034
        console.log('1️⃣ Navigating to product 11034 edit page...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log('   ✅ Product page loaded\n');
        await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_01_product_loaded.png', fullPage: true });

        // STEP 2: Click "B2B Test DEV" shop tab
        console.log('2️⃣ Looking for "B2B Test DEV" shop tab...');
        await page.waitForSelector('.shop-tab-active, .shop-tab-inactive', { timeout: 10000 });
        const shopTab = await page.locator('button').filter({ hasText: /B2B.*Test.*DEV/i }).first();
        const shopTabText = await shopTab.innerText();
        console.log(`   ✅ Found shop tab: "${shopTabText.trim()}"`);

        console.log('   Clicking shop tab...');
        await shopTab.click();
        console.log('   ✅ Shop tab activated\n');
        await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_02_shop_tab_clicked.png', fullPage: true });

        // STEP 3: Wait for data to load (może być opóźnienie z PrestaShop)
        console.log('3️⃣ Waiting for shop data to load from PrestaShop...');
        await page.waitForTimeout(3000); // Czekaj na wczytanie danych

        // Scroll to categories section
        console.log('   Scrolling to categories section...');
        const categoriesSection = await page.locator('text=/kategorie/i').first();
        await categoriesSection.scrollIntoViewIfNeeded();
        console.log('   ✅ Categories section visible\n');
        await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_03_categories_visible.png', fullPage: true });

        // STEP 4: Find and check PITGANG category
        console.log('4️⃣ Looking for "PITGANG" category...');
        const pitgangLabel = await page.locator('label').filter({ hasText: /PITGANG/i }).first();

        if (!pitgangLabel) {
            throw new Error('PITGANG category not found');
        }

        console.log('   ✅ Found "PITGANG" category');

        // Get checkbox (input sibling of label)
        const pitgangCheckbox = await pitgangLabel.locator('..').locator('input[type="checkbox"]').first();

        // Check current state
        const wasChecked = await pitgangCheckbox.isChecked();
        console.log(`   Current state: ${wasChecked ? 'CHECKED' : 'UNCHECKED'}`);

        // Toggle to ensure we make a change
        if (wasChecked) {
            console.log('   Unchecking PITGANG...');
            await pitgangCheckbox.uncheck();
            await page.waitForTimeout(500);
            console.log('   Re-checking PITGANG...');
            await pitgangCheckbox.check();
        } else {
            console.log('   Checking PITGANG...');
            await pitgangCheckbox.check();
        }

        await page.waitForTimeout(1000);
        console.log('   ✅ "PITGANG" category checked\n');
        await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_04_pitgang_checked.png', fullPage: true });

        // STEP 5: Click "Zapisz zmiany" button
        console.log('5️⃣ Looking for "Zapisz zmiany" button...');
        const saveButton = await page.locator('button').filter({ hasText: /Zapisz zmiany/i }).first();
        console.log('   ✅ Found save button');

        // Check activeJobStatus BEFORE clicking
        console.log('   Checking activeJobStatus...');
        const activeJobStatus = await page.evaluate(() => {
            return window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'))?.activeJobStatus;
        });
        console.log(`   activeJobStatus: ${activeJobStatus ?? 'null'}`);

        console.log('   Clicking "Zapisz zmiany"...');
        await saveButton.click();
        await page.waitForTimeout(2000);
        await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_05_after_save_click.png', fullPage: true });

        // STEP 6: VERIFY REDIRECT to /admin/products
        console.log('\n6️⃣ Verifying redirect to product list...');

        try {
            await page.waitForURL('**/admin/products', { timeout: 10000 });
            console.log('   ✅ Successfully redirected to /admin/products\n');
            await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_06_products_list.png', fullPage: true });
        } catch (error) {
            console.log('   ❌ FAILED: No redirect to /admin/products');
            console.log(`   Current URL: ${page.url()}\n`);
            await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_06_FAILED_no_redirect.png', fullPage: true });
            throw new Error('Save operation did not redirect to products list');
        }

        // STEP 7: Find product by SKU "Q-KAYO-EA70"
        console.log('7️⃣ Looking for product with SKU "Q-KAYO-EA70"...');

        // Wait for products table to load
        await page.waitForSelector('table, .product-list, [data-product]', { timeout: 10000 });

        // Find link/row with SKU
        const productLink = await page.locator('a, tr').filter({ hasText: /Q-KAYO-EA70/i }).first();

        if (!productLink) {
            throw new Error('Product with SKU Q-KAYO-EA70 not found in list');
        }

        console.log('   ✅ Found product Q-KAYO-EA70');
        console.log('   Clicking product...');
        await productLink.click();

        await page.waitForURL('**/admin/products/**/edit', { timeout: 10000 });
        console.log('   ✅ Product edit page loaded\n');
        await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_07_product_reopened.png', fullPage: true });

        // STEP 8: Repeat steps 2 & 3 - click shop tab and verify categories
        console.log('8️⃣ Re-clicking "B2B Test DEV" shop tab...');
        await page.waitForSelector('.shop-tab-active, .shop-tab-inactive', { timeout: 10000 });
        const shopTab2 = await page.locator('button').filter({ hasText: /B2B.*Test.*DEV/i }).first();
        await shopTab2.click();
        console.log('   ✅ Shop tab activated\n');

        // Wait for data to load
        console.log('   Waiting for shop data to reload...');
        await page.waitForTimeout(3000);

        // Scroll to categories
        console.log('   Scrolling to categories section...');
        const categoriesSection2 = await page.locator('text=/kategorie/i').first();
        await categoriesSection2.scrollIntoViewIfNeeded();
        await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_08_categories_after_reload.png', fullPage: true });

        // STEP 9: VERIFY PITGANG is still checked
        console.log('\n9️⃣ VERIFYING: Is PITGANG still checked?...');

        const pitgangLabel2 = await page.locator('label').filter({ hasText: /PITGANG/i }).first();
        const pitgangCheckbox2 = await pitgangLabel2.locator('..').locator('input[type="checkbox"]').first();

        const isStillChecked = await pitgangCheckbox2.isChecked();

        if (isStillChecked) {
            console.log('   ✅ SUCCESS: PITGANG is still CHECKED - Save worked!\n');
        } else {
            console.log('   ❌ FAILED: PITGANG is NOT checked - Save did NOT work!\n');
            await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_09_FAILED_not_saved.png', fullPage: true });
            throw new Error('Category save verification failed - PITGANG not checked after reload');
        }

        await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_09_final_verification.png', fullPage: true });

        // Check for console errors
        console.log('🔍 Checking browser console for errors...');
        if (consoleErrors.length > 0) {
            console.log('   ⚠️  Found console errors:');
            consoleErrors.forEach(err => console.log(`      ${err}`));
        } else {
            console.log('   ✅ No console errors\n');
        }

        console.log('═══════════════════════════════════════════════════════════════');
        console.log('   E2E TEST PASSED ✅');
        console.log('═══════════════════════════════════════════════════════════════\n');

        console.log('📊 SUMMARY:');
        console.log('   • Product loaded: SUCCESS');
        console.log('   • Shop tab clicked: SUCCESS');
        console.log('   • Category checked: SUCCESS');
        console.log('   • Save button clicked: SUCCESS');
        console.log('   • Redirect to list: SUCCESS');
        console.log('   • Product re-opened: SUCCESS');
        console.log('   • Category still checked: SUCCESS ✅\n');

        console.log('📁 Screenshots saved in _TOOLS/screenshots/');

    } catch (error) {
        console.log('\n❌ TEST FAILED:', error.message);
        await page.screenshot({ path: '_TOOLS/screenshots/test_e2e_ERROR.png', fullPage: true });

        console.log('\n📊 SUMMARY:');
        console.log('   • Test failed at step:', error.message);
        console.log('   • Console errors:', consoleErrors.length > 0 ? consoleErrors.join(', ') : 'None');

        await browser.close();
        process.exit(1);
    }

    await browser.close();
})();
