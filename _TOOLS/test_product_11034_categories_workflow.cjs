const { chromium } = require('playwright');

(async () => {
    console.log('=== TEST PRODUCT 11034 CATEGORIES - WORKFLOW ===\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    try {
        // Step 1: Login
        console.log('STEP 1: Login...');
        await page.goto('https://ppm.mpptrade.pl/login');
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin**', { timeout: 10000 });
        console.log('✅ Logged in\n');

        // Step 2: Open product 11034
        console.log('STEP 2: Opening product 11034...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
        await page.waitForSelector('[wire\\:id]', { timeout: 10000 });
        await page.waitForTimeout(2000);
        console.log('✅ Product loaded\n');

        // Screenshot: Initial state
        await page.screenshot({
            path: 'screenshots/test_cat_01_initial.png',
            fullPage: true
        });
        console.log('📸 Screenshot: test_cat_01_initial.png\n');

        // Step 3: Click "B2B Test DEV" shop tab
        console.log('STEP 3: Clicking shop tab "B2B Test DEV"...');

        // Find the shop tab button - it should contain the shop name
        const shopTabButton = await page.locator('button:has-text("B2B Test DEV")').first();

        if (await shopTabButton.isVisible()) {
            await shopTabButton.click();
            console.log('✅ Clicked shop tab\n');
        } else {
            console.log('❌ Shop tab not found!\n');
            throw new Error('Shop tab not visible');
        }

        // Wait for Livewire to update
        await page.waitForTimeout(3000);

        // Screenshot: After shop tab click
        await page.screenshot({
            path: 'screenshots/test_cat_02_shop_tab.png',
            fullPage: true
        });
        console.log('📸 Screenshot: test_cat_02_shop_tab.png\n');

        // Step 4: Check categories
        console.log('STEP 4: Checking selected categories...\n');

        // Find all category checkboxes (look for wire:model containing "shopCategories")
        const categories = await page.evaluate(() => {
            const selected = [];
            const primary = { id: null, name: null };

            // Find checkboxes
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');

            checkboxes.forEach(cb => {
                // Check if it's a category checkbox (has wire:model with shopCategories)
                const wireModel = cb.getAttribute('wire:model') || cb.getAttribute('x-model');

                if (wireModel && wireModel.includes('shopCategories') && cb.checked) {
                    // Get category name from label
                    const label = cb.closest('label') || cb.parentElement;
                    const labelText = label ? label.textContent.trim() : '';

                    // Extract category ID from wire:model (e.g., "shopCategories.1.selected.32")
                    const match = wireModel.match(/selected\.(\d+)/);
                    const catId = match ? match[1] : 'unknown';

                    selected.push({
                        id: catId,
                        name: labelText,
                        checked: true
                    });
                }
            });

            // Find primary category radio button
            const radios = document.querySelectorAll('input[type="radio"]');
            radios.forEach(radio => {
                const wireModel = radio.getAttribute('wire:model') || radio.getAttribute('x-model');

                if (wireModel && wireModel.includes('primary') && radio.checked) {
                    const label = radio.closest('label') || radio.parentElement;
                    const labelText = label ? label.textContent.trim() : '';

                    const match = wireModel.match(/primary\.(\d+)|shopCategories\.1\.primary/);
                    const catId = radio.value || 'unknown';

                    primary.id = catId;
                    primary.name = labelText;
                }
            });

            return { selected, primary };
        });

        console.log('SELECTED CATEGORIES:');
        if (categories.selected.length > 0) {
            categories.selected.forEach(cat => {
                console.log(`  ✅ [ID: ${cat.id}] ${cat.name}`);
            });
        } else {
            console.log('  ❌ NO CATEGORIES SELECTED');
        }
        console.log('');

        console.log('PRIMARY CATEGORY:');
        if (categories.primary.id) {
            console.log(`  ⭐ [ID: ${categories.primary.id}] ${categories.primary.name}`);
        } else {
            console.log('  ❌ NO PRIMARY SELECTED');
        }
        console.log('');

        // Expected results
        const expectedCategories = [
            { id: '2', name: 'Wszystko' },
            { id: '32', name: 'PITGANG' },
            { id: '34', name: 'Pit Bike' },
            { id: '33', name: 'Pojazdy' },
            { id: '57', name: 'Quad' }
        ];

        const expectedPrimary = { id: '34', name: 'Pit Bike' };

        console.log('EXPECTED CATEGORIES:');
        expectedCategories.forEach(cat => {
            console.log(`  - [ID: ${cat.id}] ${cat.name}`);
        });
        console.log('');

        console.log('EXPECTED PRIMARY:');
        console.log(`  ⭐ [ID: ${expectedPrimary.id}] ${expectedPrimary.name}`);
        console.log('');

        // Comparison
        console.log('=== COMPARISON ===\n');

        const selectedIds = categories.selected.map(c => c.id);
        const expectedIds = expectedCategories.map(c => c.id);

        const missing = expectedIds.filter(id => !selectedIds.includes(id));
        const extra = selectedIds.filter(id => !expectedIds.includes(id));

        let allCorrect = true;

        if (missing.length > 0) {
            console.log('❌ MISSING CATEGORIES:');
            missing.forEach(id => {
                const expected = expectedCategories.find(c => c.id === id);
                console.log(`  - [ID: ${id}] ${expected ? expected.name : 'Unknown'}`);
            });
            console.log('');
            allCorrect = false;
        }

        if (extra.length > 0) {
            console.log('❌ EXTRA CATEGORIES (should not be selected):');
            extra.forEach(id => {
                const cat = categories.selected.find(c => c.id === id);
                console.log(`  - [ID: ${id}] ${cat ? cat.name : 'Unknown'}`);
            });
            console.log('');
            allCorrect = false;
        }

        if (categories.primary.id !== expectedPrimary.id) {
            console.log(`❌ PRIMARY MISMATCH:`);
            console.log(`  Expected: [ID: ${expectedPrimary.id}] ${expectedPrimary.name}`);
            console.log(`  Got: [ID: ${categories.primary.id || 'none'}] ${categories.primary.name || 'none'}`);
            console.log('');
            allCorrect = false;
        }

        if (allCorrect && missing.length === 0 && extra.length === 0) {
            console.log('✅✅✅ ALL CATEGORIES CORRECT! ✅✅✅');
            console.log('✅ All 5 categories selected');
            console.log('✅ Primary category correct (Pit Bike)');
            console.log('✅ No ghost categories');
        } else {
            console.log('❌ TEST FAILED - Categories do not match expected values');
        }

        console.log('');

        // Final screenshot
        await page.screenshot({
            path: 'screenshots/test_cat_03_final.png',
            fullPage: true
        });
        console.log('📸 Screenshot: test_cat_03_final.png\n');

        console.log('=== TEST COMPLETE ===');

        // Keep browser open for 10 seconds
        console.log('\nBrowser will close in 10 seconds...');
        await page.waitForTimeout(10000);

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        console.error(error.stack);

        await page.screenshot({
            path: 'screenshots/test_cat_error.png',
            fullPage: true
        });
        console.log('📸 Error screenshot: test_cat_error.png');

    } finally {
        await browser.close();
    }
})();
