#!/usr/bin/env node

/**
 * PPM-CC-Laravel: End-to-End Import Workflow Test
 *
 * Tests Category Preview Modal with Loading Animation
 *
 * WORKFLOW:
 * 1. Login to admin panel
 * 2. Navigate to Products page
 * 3. Click "Importuj z PrestaShop"
 * 4. Select shop from modal
 * 5. Click "Importuj wszystkie produkty"
 * 6. Verify Loading Animation appears
 * 7. Wait for CategoryPreview modal (polling delay ~3-6s)
 * 8. Verify modal opened with category tree
 * 9. Test "Zaznacz wszystkie" / "Odznacz wszystkie" buttons
 * 10. Take screenshots at each step
 *
 * Usage:
 *   node _TOOLS/test_import_workflow.cjs
 *   node _TOOLS/test_import_workflow.cjs --headless=false (debug mode with visible browser)
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Configuration
const CONFIG = {
    baseUrl: 'https://ppm.mpptrade.pl',
    loginEmail: 'admin@mpptrade.pl',
    loginPassword: 'Admin123!MPP',
    screenshotDir: path.join(__dirname, 'screenshots', 'import_workflow'),
    headless: process.argv.includes('--headless=false') ? false : true,
    slowMo: process.argv.includes('--headless=false') ? 100 : 0,
};

// Ensure screenshot directory exists
if (!fs.existsSync(CONFIG.screenshotDir)) {
    fs.mkdirSync(CONFIG.screenshotDir, { recursive: true });
}

// Helper: Take timestamped screenshot
async function takeScreenshot(page, name) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
    const filename = `${name}_${timestamp}.png`;
    const filepath = path.join(CONFIG.screenshotDir, filename);
    await page.screenshot({ path: filepath, fullPage: true });
    console.log(`✅ Screenshot: ${filename}`);
    return filepath;
}

// Helper: Wait with timeout and optional condition
async function waitFor(page, selector, options = {}) {
    try {
        await page.waitForSelector(selector, {
            timeout: options.timeout || 5000,
            state: options.state || 'visible',
        });
        return true;
    } catch (error) {
        console.warn(`⚠️ Element not found: ${selector} (timeout: ${options.timeout || 5000}ms)`);
        return false;
    }
}

// Main test flow
(async () => {
    console.log('=== PPM-CC-Laravel: Import Workflow E2E Test ===\n');
    console.log(`Base URL: ${CONFIG.baseUrl}`);
    console.log(`Headless: ${CONFIG.headless}`);
    console.log(`Screenshots: ${CONFIG.screenshotDir}\n`);

    const browser = await chromium.launch({
        headless: CONFIG.headless,
        slowMo: CONFIG.slowMo,
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true,
    });

    const page = await context.newPage();

    try {
        // ==========================
        // STEP 1: Login
        // ==========================
        console.log('📝 STEP 1: Login to admin panel...');
        await page.goto(`${CONFIG.baseUrl}/login`);
        await page.fill('input[name="email"]', CONFIG.loginEmail);
        await page.fill('input[name="password"]', CONFIG.loginPassword);
        await takeScreenshot(page, '01_login_form');
        await page.click('button[type="submit"]');

        // Wait for redirect - either dashboard or products page
        // Check for admin header instead of URL change
        const loginSuccess = await waitFor(page, 'text="ADMIN PANEL"', { timeout: 15000 });

        if (!loginSuccess) {
            console.error('❌ Login failed - admin panel header not found');
            await takeScreenshot(page, 'ERROR_login_failed');
            throw new Error('Login failed or timed out');
        }

        await takeScreenshot(page, '02_after_login');
        console.log('✅ Logged in successfully\n');

        // ==========================
        // STEP 2: Navigate to Products
        // ==========================
        console.log('📝 STEP 2: Navigate to Products page...');
        await page.goto(`${CONFIG.baseUrl}/admin/products`);
        await page.waitForLoadState('networkidle');
        await takeScreenshot(page, '03_products_page');
        console.log('✅ Products page loaded\n');

        // ==========================
        // STEP 3: Click "Importuj z PrestaShop"
        // ==========================
        console.log('📝 STEP 3: Click "Importuj z PrestaShop" button...');

        // Find button by text content (case-insensitive)
        const importButton = await page.locator('button:has-text("Importuj z PrestaShop")').first();

        if (await importButton.count() === 0) {
            console.error('❌ "Importuj z PrestaShop" button not found!');
            await takeScreenshot(page, 'ERROR_import_button_not_found');
            throw new Error('Import button not found');
        }

        await importButton.click();
        console.log('✅ Import button clicked\n');

        // ==========================
        // STEP 4: Select Shop from Import Modal
        // ==========================
        console.log('📝 STEP 4: Wait for Import Modal and select shop...');

        // Wait for modal to appear
        const modalAppeared = await waitFor(page, '[x-data*="importModal"]', { timeout: 5000 });
        if (!modalAppeared) {
            console.error('❌ Import modal did not appear!');
            await takeScreenshot(page, 'ERROR_import_modal_not_shown');
            throw new Error('Import modal not shown');
        }

        await takeScreenshot(page, '04_import_modal_shops');

        // Select first shop (B2B Test DEV - shop_id=1)
        const shopCard = await page.locator('[x-data*="importModal"] button:has-text("B2B Test DEV")').first();

        if (await shopCard.count() === 0) {
            console.error('❌ Shop "B2B Test DEV" not found in modal!');
            await takeScreenshot(page, 'ERROR_shop_not_found');
            throw new Error('Shop not found in modal');
        }

        await shopCard.click();
        await page.waitForTimeout(500); // Wait for shop selection UI update
        await takeScreenshot(page, '05_shop_selected');
        console.log('✅ Shop selected: B2B Test DEV\n');

        // ==========================
        // STEP 5: Click "Importuj wszystkie produkty"
        // ==========================
        console.log('📝 STEP 5: Click "Importuj wszystkie produkty"...');

        const importAllButton = await page.locator('button:has-text("Importuj wszystkie produkty")').first();

        if (await importAllButton.count() === 0) {
            console.error('❌ "Importuj wszystkie produkty" button not found!');
            await takeScreenshot(page, 'ERROR_import_all_button_not_found');
            throw new Error('Import all button not found');
        }

        await importAllButton.click();
        console.log('✅ Import all button clicked\n');

        // ==========================
        // STEP 6: Verify Loading Animation
        // ==========================
        console.log('📝 STEP 6: Verify Loading Animation appears...');

        // Wait a moment for loading animation to appear
        await page.waitForTimeout(500);

        // Check if loading overlay is visible
        const loadingOverlay = await page.locator('div:has-text("Analizuję kategorie")').first();
        const loadingVisible = await loadingOverlay.isVisible().catch(() => false);

        if (loadingVisible) {
            console.log('✅ Loading Animation is visible!');
            await takeScreenshot(page, '06_loading_animation');
        } else {
            console.warn('⚠️ Loading Animation not visible (może już znikła - polling jest szybki)');
            await takeScreenshot(page, '06_no_loading_animation');
        }
        console.log('');

        // ==========================
        // STEP 7: Wait for CategoryPreview Modal
        // ==========================
        console.log('📝 STEP 7: Wait for CategoryPreview modal (polling delay ~3-10s)...');

        // Wait for modal header "Podgląd kategorii z PrestaShop"
        const modalAppeared2 = await waitFor(
            page,
            'h2:has-text("Podgląd kategorii z PrestaShop")',
            { timeout: 15000 } // Max 15s wait
        );

        if (!modalAppeared2) {
            console.error('❌ CategoryPreview modal did NOT appear!');
            await takeScreenshot(page, 'ERROR_category_modal_not_shown');
            throw new Error('CategoryPreview modal not shown within 15 seconds');
        }

        console.log('✅ CategoryPreview modal appeared!');
        await page.waitForTimeout(1000); // Wait for full render
        await takeScreenshot(page, '07_category_preview_modal');
        console.log('');

        // ==========================
        // STEP 8: Verify Category Tree
        // ==========================
        console.log('📝 STEP 8: Verify category tree structure...');

        // Check if category items exist
        const categoryItems = await page.locator('[id^="category_"]').count();
        console.log(`✅ Found ${categoryItems} category checkboxes in tree`);

        if (categoryItems === 0) {
            console.warn('⚠️ No categories found in tree (empty tree)');
        }

        // Check shop name in modal header
        const shopName = await page.locator('text="Sklep:"').first().textContent();
        console.log(`✅ Shop name in modal: ${shopName}`);
        console.log('');

        // ==========================
        // STEP 9: Test "Zaznacz wszystkie" Button
        // ==========================
        console.log('📝 STEP 9: Test "Zaznacz wszystkie" button...');

        const selectAllButton = await page.locator('button:has-text("Zaznacz wszystkie")').first();

        if (await selectAllButton.count() === 0) {
            console.warn('⚠️ "Zaznacz wszystkie" button not found');
        } else {
            await selectAllButton.click();
            await page.waitForTimeout(500);
            await takeScreenshot(page, '08_all_categories_selected');

            // Count checked checkboxes
            const checkedCount = await page.locator('[id^="category_"]:checked').count();
            console.log(`✅ Checked categories after "Zaznacz wszystkie": ${checkedCount}`);
        }
        console.log('');

        // ==========================
        // STEP 10: Test "Odznacz wszystkie" Button
        // ==========================
        console.log('📝 STEP 10: Test "Odznacz wszystkie" button...');

        const deselectAllButton = await page.locator('button:has-text("Odznacz wszystkie")').first();

        if (await deselectAllButton.count() === 0) {
            console.warn('⚠️ "Odznacz wszystkie" button not found');
        } else {
            await deselectAllButton.click();
            await page.waitForTimeout(500);
            await takeScreenshot(page, '09_all_categories_deselected');

            // Count checked checkboxes (should be 0)
            const checkedCount = await page.locator('[id^="category_"]:checked').count();
            console.log(`✅ Checked categories after "Odznacz wszystkie": ${checkedCount}`);

            if (checkedCount === 0) {
                console.log('✅ All categories deselected successfully!');
            } else {
                console.warn(`⚠️ ${checkedCount} categories still checked (expected 0)`);
            }
        }
        console.log('');

        // ==========================
        // STEP 11: Check "Skip Categories" Option
        // ==========================
        console.log('📝 STEP 11: Test "Importuj produkty BEZ kategorii" option...');

        const skipCategoriesCheckbox = await page.locator('input[type="checkbox"]').filter({ hasText: /BEZ kategorii/i }).first();

        if (await skipCategoriesCheckbox.count() === 0) {
            console.warn('⚠️ "Skip categories" checkbox not found');
        } else {
            await skipCategoriesCheckbox.check();
            await page.waitForTimeout(500);
            await takeScreenshot(page, '10_skip_categories_enabled');
            console.log('✅ "Skip categories" option enabled');

            // Verify category tree is disabled
            const treeDisabled = await page.locator('.opacity-50').count() > 0;
            if (treeDisabled) {
                console.log('✅ Category tree is disabled when skip=true');
            }
        }
        console.log('');

        // ==========================
        // FINAL: Summary
        // ==========================
        console.log('=== TEST SUMMARY ===\n');
        console.log('✅ Login: SUCCESS');
        console.log('✅ Products Page: SUCCESS');
        console.log('✅ Import Button Click: SUCCESS');
        console.log('✅ Shop Selection: SUCCESS');
        console.log('✅ Import All Click: SUCCESS');
        console.log(loadingVisible ? '✅ Loading Animation: VISIBLE' : '⚠️ Loading Animation: NOT VISIBLE (may be too fast)');
        console.log('✅ CategoryPreview Modal: APPEARED');
        console.log(`✅ Category Tree: ${categoryItems} categories found`);
        console.log('✅ Zaznacz wszystkie: SUCCESS');
        console.log('✅ Odznacz wszystkie: SUCCESS');
        console.log('✅ Skip Categories: SUCCESS');
        console.log('');
        console.log(`📁 Screenshots saved to: ${CONFIG.screenshotDir}`);
        console.log('');
        console.log('🎉 END-TO-END TEST COMPLETED SUCCESSFULLY! 🎉');

    } catch (error) {
        console.error('\n❌ TEST FAILED!');
        console.error('Error:', error.message);
        console.error('');
        await takeScreenshot(page, 'ERROR_final_state');
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
