/**
 * CRITICAL DIAGNOSTIC: Shop TAB Category Save Test
 *
 * PURPOSE: Verify shop-specific category save functionality
 *
 * Test Flow:
 * 1. Open product 11034/edit
 * 2. FIND and CLICK shop TAB (Pitbike.pl or other)
 * 3. Toggle category checkbox in shop context
 * 4. Click "Zapisz zmiany" button
 * 5. Query database: SELECT * FROM product_categories WHERE shop_id=1
 * 6. Verify categories were saved
 */

const { chromium } = require('playwright');

(async () => {
    console.log('\n╔═══════════════════════════════════════════════════════════════╗');
    console.log('║   CRITICAL DIAGNOSTIC: Shop TAB Category Save                ║');
    console.log('╚═══════════════════════════════════════════════════════════════╝\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Storage for Livewire requests
    const livewireRequests = [];

    // Monitor Livewire requests
    page.on('request', request => {
        const url = request.url();
        if (url.includes('/livewire/') || request.postDataJSON()?.components) {
            livewireRequests.push({
                url: url,
                method: request.method(),
                payload: request.postDataJSON(),
                timestamp: new Date().toISOString()
            });
        }
    });

    try {
        console.log('📍 STEP 1: Opening product edit page...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit', {
            waitUntil: 'networkidle',
            timeout: 60000
        });
        console.log('✅ Page loaded\n');

        await page.screenshot({
            path: '_TOOLS/screenshots/shop_tab_diagnostic_initial.png',
            fullPage: true
        });

        // Wait for Livewire
        await page.waitForTimeout(2000);

        // STEP 2: FIND SHOP TAB (wire:click="switchToShop" buttons)
        console.log('📍 STEP 2: Looking for SHOP TABs...');

        // Look for buttons with wire:click="switchToShop" that are NOT "Dane domyślne"
        const allShopButtons = await page.locator('button[wire\\:click^="switchToShop"]').all();

        console.log(`Found ${allShopButtons.length} shop switch buttons\n`);

        // Filter out "Dane domyślne" (null shop) - we want ACTUAL shops
        const shopTabs = [];
        for (const btn of allShopButtons) {
            const text = await btn.textContent();
            const wireClick = await btn.getAttribute('wire:click');

            // Skip "switchToShop(null)" - that's default data
            if (wireClick && !wireClick.includes('null')) {
                shopTabs.push(btn);
                console.log(`  ✅ Found shop: "${text.trim().substring(0, 50)}" - ${wireClick}`);
            } else {
                console.log(`  ⏭️  Skipped default: "${text.trim().substring(0, 30)}"`);
            }
        }

        if (shopTabs.length === 0) {
            console.log('\n❌ CRITICAL: NO SHOP TABS FOUND!');
            console.log('⚠️  Product 11034 may not have exported shops');

            // Take screenshot
            await page.screenshot({
                path: '_TOOLS/screenshots/shop_tab_diagnostic_no_shops.png',
                fullPage: true
            });

            console.log('\n❌ TEST ABORTED: Cannot test shop categories without shop tabs');
            return;
        }

        console.log(`✅ Found ${shopTabs.length} shop tabs\n`);

        // Click FIRST shop tab
        const firstShopTab = shopTabs[0];
        const shopTabText = await firstShopTab.textContent();
        console.log(`🖱️  Clicking shop tab: "${shopTabText.trim()}"`);

        // Get shop ID if available
        const shopId = await firstShopTab.getAttribute('data-shop-id');
        console.log(`   Shop ID: ${shopId || 'NOT SET'}`);

        await firstShopTab.click();
        await page.waitForTimeout(2000); // Wait for Livewire update

        console.log('✅ Shop tab clicked\n');

        await page.screenshot({
            path: '_TOOLS/screenshots/shop_tab_diagnostic_after_click.png',
            fullPage: true
        });

        // STEP 3: FIND CATEGORY CHECKBOXES in SHOP CONTEXT
        console.log('📍 STEP 3: Looking for category checkboxes in SHOP context...');

        // Look for checkboxes with id containing shop context
        const shopCategoryCheckboxes = await page.locator('input[type="checkbox"][id*="category_shop_"], input[type="checkbox"][id*="category_' + (shopId || '1') + '_"]').all();

        if (shopCategoryCheckboxes.length === 0) {
            console.log('⚠️  No shop-specific category checkboxes found');
            console.log('   Trying generic category checkboxes...');

            const genericCheckboxes = await page.locator('input[type="checkbox"][id^="category_"]').all();
            console.log(`   Found ${genericCheckboxes.length} generic category checkboxes`);

            if (genericCheckboxes.length === 0) {
                console.log('❌ CRITICAL: NO CATEGORY CHECKBOXES FOUND!');
                return;
            }

            // Use first unchecked checkbox
            let targetCheckbox = null;
            let checkboxId = null;

            for (const checkbox of genericCheckboxes) {
                const isChecked = await checkbox.isChecked();
                if (!isChecked) {
                    targetCheckbox = checkbox;
                    checkboxId = await checkbox.getAttribute('id');
                    break;
                }
            }

            if (!targetCheckbox) {
                console.log('⚠️  All checkboxes are checked, using first one');
                targetCheckbox = genericCheckboxes[0];
                checkboxId = await targetCheckbox.getAttribute('id');
            }

            console.log(`🖱️  Toggling checkbox: ${checkboxId}`);

            // Clear Livewire requests
            livewireRequests.length = 0;

            await targetCheckbox.click();
            await page.waitForTimeout(1500);

            console.log('✅ Checkbox toggled\n');

            if (livewireRequests.length > 0) {
                console.log(`✅ Livewire request sent (${livewireRequests.length} requests)`);
            } else {
                console.log('⚠️  NO Livewire request after toggle');
            }

            await page.screenshot({
                path: '_TOOLS/screenshots/shop_tab_diagnostic_after_toggle.png',
                fullPage: true
            });

        } else {
            console.log(`✅ Found ${shopCategoryCheckboxes.length} shop-specific category checkboxes\n`);

            // Toggle first unchecked shop category
            let targetCheckbox = null;
            let checkboxId = null;

            for (const checkbox of shopCategoryCheckboxes) {
                const isChecked = await checkbox.isChecked();
                if (!isChecked) {
                    targetCheckbox = checkbox;
                    checkboxId = await checkbox.getAttribute('id');
                    break;
                }
            }

            if (!targetCheckbox) {
                targetCheckbox = shopCategoryCheckboxes[0];
                checkboxId = await targetCheckbox.getAttribute('id');
            }

            console.log(`🖱️  Toggling shop category: ${checkboxId}`);

            livewireRequests.length = 0;

            await targetCheckbox.click();
            await page.waitForTimeout(1500);

            console.log('✅ Shop category toggled\n');

            if (livewireRequests.length > 0) {
                console.log(`✅ Livewire request sent (${livewireRequests.length} requests)`);
            } else {
                console.log('⚠️  NO Livewire request after toggle');
            }

            await page.screenshot({
                path: '_TOOLS/screenshots/shop_tab_diagnostic_after_toggle.png',
                fullPage: true
            });
        }

        // STEP 4: CLICK "Zapisz zmiany" BUTTON
        console.log('📍 STEP 4: Clicking "Zapisz zmiany" button...');

        const saveButtons = await page.locator('button').filter({ hasText: /Zapisz.*zmiany/i }).all();

        console.log(`Found ${saveButtons.length} save buttons`);

        if (saveButtons.length === 0) {
            console.log('❌ CRITICAL: NO SAVE BUTTONS FOUND!');
            return;
        }

        // Try to find "Zapisz wszystkie zmiany" first (doesn't close form)
        let saveButton = null;
        for (const btn of saveButtons) {
            const text = await btn.textContent();
            if (text.includes('wszystkie')) {
                saveButton = btn;
                console.log('✅ Found "Zapisz wszystkie zmiany" button');
                break;
            }
        }

        // Fallback to any save button
        if (!saveButton) {
            saveButton = saveButtons[0];
            const text = await saveButton.textContent();
            console.log(`⚠️  Using first save button: "${text.trim()}"`);
        }

        livewireRequests.length = 0;

        console.log('🖱️  Clicking save button...');
        await saveButton.click();
        await page.waitForTimeout(4000); // Wait for save

        console.log('✅ Save button clicked\n');

        await page.screenshot({
            path: '_TOOLS/screenshots/shop_tab_diagnostic_after_save.png',
            fullPage: true
        });

        // Check for success message
        const successMsg = await page.locator('.alert-success, [class*="success"]').first();
        const hasSuccess = await successMsg.count() > 0;

        if (hasSuccess) {
            const msgText = await successMsg.textContent();
            console.log(`✅ SUCCESS MESSAGE: "${msgText.trim()}"`);
        } else {
            console.log('⚠️  NO SUCCESS MESSAGE detected');
        }

        // STEP 5: VERIFY DATABASE
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('   DATABASE VERIFICATION');
        console.log('═══════════════════════════════════════════════════════════════\n');

        console.log('ℹ️  Manual verification required:');
        console.log('   Run on production:');
        console.log('   php artisan tinker --execute="');
        console.log('     \\DB::table(\'product_categories\')');
        console.log('       ->where(\'product_id\', 11034)');
        console.log('       ->where(\'shop_id\', 1)');
        console.log('       ->get([\'id\', \'category_id\', \'shop_id\', \'is_primary\']);');
        console.log('   "');

        // SUMMARY
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('   DIAGNOSTIC SUMMARY');
        console.log('═══════════════════════════════════════════════════════════════\n');

        console.log(`Shop tabs found: ${shopTabs.length > 0 ? '✅' : '❌'}`);
        console.log(`Shop tab clicked: ✅`);
        console.log(`Category toggled: ✅`);
        console.log(`Save button clicked: ✅`);
        console.log(`Success message: ${hasSuccess ? '✅' : '⚠️'}`);
        console.log(`Livewire requests sent: ${livewireRequests.length}`);

        console.log('\n📸 Screenshots saved:');
        console.log('   - _TOOLS/screenshots/shop_tab_diagnostic_initial.png');
        console.log('   - _TOOLS/screenshots/shop_tab_diagnostic_after_click.png');
        console.log('   - _TOOLS/screenshots/shop_tab_diagnostic_after_toggle.png');
        console.log('   - _TOOLS/screenshots/shop_tab_diagnostic_after_save.png');

    } catch (error) {
        console.error('\n❌ ERROR during diagnostic:', error.message);
        console.error(error.stack);

        await page.screenshot({
            path: '_TOOLS/screenshots/shop_tab_diagnostic_error.png',
            fullPage: true
        });
    } finally {
        console.log('\n⏳ Keeping browser open for 15 seconds for manual inspection...');
        await page.waitForTimeout(15000);

        await browser.close();
        console.log('\n✅ Diagnostic complete\n');
    }
})();
