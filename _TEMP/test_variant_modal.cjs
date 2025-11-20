#!/usr/bin/env node
/**
 * Test Variant Modal Interaction
 */

const { chromium } = require('playwright');

(async () => {
    console.log('=== VARIANT MODAL INTERACTION TEST ===\n');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    // Collect console errors
    const errors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push(msg.text());
            console.log(`❌ [ERROR] ${msg.text()}`);
        }
    });

    try {
        // Login
        console.log('[1/5] Logging in...');
        await page.goto('https://ppm.mpptrade.pl/login', { waitUntil: 'networkidle' });
        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✅ Logged in');

        // Navigate to product
        console.log('\n[2/5] Navigating to product...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/10969/edit', { waitUntil: 'networkidle' });
        console.log('✅ Product loaded');

        // Wait for Livewire
        console.log('\n[3/5] Waiting for Livewire...');
        await page.waitForFunction(() => window.Livewire !== undefined, { timeout: 10000 });
        console.log('✅ Livewire ready');

        // Click Warianty tab
        console.log('\n[4/5] Clicking Warianty tab...');
        const tab = page.locator('button.tab-enterprise:has-text("Warianty")').first();
        await tab.click();
        await page.waitForTimeout(2000);
        console.log('✅ Warianty tab active');

        // Click "Dodaj Wariant" button
        console.log('\n[5/5] Testing "Dodaj Wariant" button...');

        // Try to find button
        const addButton = page.locator('button:has-text("Dodaj Wariant")').first();

        const isVisible = await addButton.isVisible({ timeout: 5000 }).catch(() => false);

        if (!isVisible) {
            console.log('⚠️ "Dodaj Wariant" button NOT FOUND');
            console.log('   This is expected - button appears only when product.has_variants = true');
            console.log('   OR when creating first variant');
        } else {
            console.log('✅ "Dodaj Wariant" button found');

            // Click and wait for modal
            await addButton.click();
            await page.waitForTimeout(1000);

            // Check if modal opened
            const modal = page.locator('.variant-modal-overlay, [x-show*="showCreateModal"]').first();
            const modalVisible = await modal.isVisible({ timeout: 3000 }).catch(() => false);

            if (modalVisible) {
                console.log('✅ Create variant modal OPENED');

                // Take screenshot of modal
                await page.screenshot({
                    path: '_TOOLS/screenshots/variant_create_modal.png',
                    fullPage: false
                });
                console.log('✅ Screenshot: variant_create_modal.png');

                // Close modal
                const closeBtn = page.locator('button:has-text("Anuluj"), button:has-text("×")').first();
                if (await closeBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
                    await closeBtn.click();
                    console.log('✅ Modal closed');
                }
            } else {
                console.log('⚠️ Modal did NOT open');
            }
        }

    } catch (error) {
        console.error('\n❌ Test error:', error.message);
    }

    console.log('\n=== SUMMARY ===');
    console.log(`Console errors: ${errors.length}`);

    if (errors.length > 0) {
        console.log('\n🔴 ERRORS:');
        errors.forEach((err, i) => console.log(`${i + 1}. ${err}`));
    } else {
        console.log('✅ NO CONSOLE ERRORS');
    }

    await browser.close();
})();
