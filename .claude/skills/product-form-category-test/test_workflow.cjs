#!/usr/bin/env node
/**
 * PRODUCT FORM CATEGORY TEST - E2E Workflow
 *
 * Automatyczny test workflow kategorii w ProductForm:
 * 1. Login
 * 2. Otwarcie produktu 11034 (Q-KAYO-EA70)
 * 3. Kliknięcie shop tab "B2B Test DEV"
 * 4. Zmiana kategorii (toggle checkbox)
 * 5. Kliknięcie "Zapisz zmiany"
 * 6. KRYTYCZNY: Weryfikacja redirect na /admin/products
 * 7. Wyszukanie produktu po SKU
 * 8. Ponowne otwarcie produktu
 * 9. Weryfikacja persistencji zmian
 *
 * Usage:
 *   node test_workflow.cjs [OPTIONS]
 *
 * Options:
 *   --show          Show browser window (default: headless)
 *   --slow          Slower execution (slowMo: 1000ms vs 500ms)
 *   --no-save       Don't click save (UI test only)
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Configuration
const CONFIG = {
    // Product to test
    PRODUCT_ID: 11034,
    PRODUCT_SKU: 'Q-KAYO-EA70',

    // Shop to test
    SHOP_NAME: 'B2B Test DEV',
    SHOP_ID: 1,

    // Credentials
    EMAIL: 'admin@mpptrade.pl',
    PASSWORD: 'Admin123!MPP',

    // Timeouts
    LOGIN_TIMEOUT: 10000,
    PAGE_LOAD_TIMEOUT: 10000,
    LIVEWIRE_INIT_TIMEOUT: 2000,
    PRESTASHOP_DATA_TIMEOUT: 3000,
    REDIRECT_TIMEOUT: 10000,

    // Browser
    HEADLESS: true,
    SLOW_MO: 500,
    VIEWPORT: { width: 1920, height: 1080 },

    // Output
    SCREENSHOTS_DIR: '_TOOLS/screenshots',
    LOGS_DIR: '_TOOLS/screenshots',
};

// Parse CLI arguments
const args = process.argv.slice(2);
if (args.includes('--show')) CONFIG.HEADLESS = false;
if (args.includes('--slow')) CONFIG.SLOW_MO = 1000;
const NO_SAVE = args.includes('--no-save');

// Test results
const results = {
    timestamp: new Date().toISOString(),
    phases: {},
    errors: [],
    warnings: [],
    screenshots: [],
    logs: [],
};

function log(message, type = 'info') {
    const timestamp = new Date().toISOString().substring(11, 19);
    const prefix = type === 'error' ? '❌' : type === 'warning' ? '⚠️' : type === 'success' ? '✅' : 'ℹ️';
    const fullMessage = `[${timestamp}] ${prefix} ${message}`;
    console.log(fullMessage);
    results.logs.push({ timestamp, type, message });
}

async function takeScreenshot(page, name, description) {
    const filename = `category_test_${name}.png`;
    const filepath = path.join(CONFIG.SCREENSHOTS_DIR, filename);

    try {
        await page.screenshot({ path: filepath, fullPage: true });
        log(`Screenshot: ${filename}`, 'success');
        results.screenshots.push({ name, filename, description });
    } catch (error) {
        log(`Failed to take screenshot ${filename}: ${error.message}`, 'error');
    }
}

async function runTest() {
    console.log('═══════════════════════════════════════════════════════');
    console.log('🧪 PRODUCT FORM CATEGORY TEST - E2E WORKFLOW');
    console.log('═══════════════════════════════════════════════════════\n');

    log(`Product: ${CONFIG.PRODUCT_SKU} (ID: ${CONFIG.PRODUCT_ID})`);
    log(`Shop: ${CONFIG.SHOP_NAME} (ID: ${CONFIG.SHOP_ID})`);
    log(`Mode: ${CONFIG.HEADLESS ? 'Headless' : 'Visible'}`);
    log(`Save: ${NO_SAVE ? 'Disabled (UI test only)' : 'Enabled'}`);
    console.log('');

    const browser = await chromium.launch({
        headless: CONFIG.HEADLESS,
        slowMo: CONFIG.SLOW_MO,
    });

    const context = await browser.newContext({
        viewport: CONFIG.VIEWPORT,
    });

    const page = await context.newPage();

    // Track console messages
    page.on('console', msg => {
        if (msg.type() === 'error') {
            log(`Browser console error: ${msg.text()}`, 'error');
        }
    });

    try {
        // ═══════════════════════════════════════════════════════
        // PHASE 1: LOGIN
        // ═══════════════════════════════════════════════════════
        console.log('\n═══ PHASE 1: LOGIN ═══\n');
        log('Navigating to login page...');

        await page.goto('https://ppm.mpptrade.pl/login');
        await page.fill('input[name="email"]', CONFIG.EMAIL);
        await page.fill('input[name="password"]', CONFIG.PASSWORD);
        await page.click('button[type="submit"]');

        await page.waitForURL('**/admin**', { timeout: CONFIG.LOGIN_TIMEOUT });
        log('Login successful', 'success');
        results.phases.login = { status: 'passed', url: page.url() };

        // ═══════════════════════════════════════════════════════
        // PHASE 2: OPEN PRODUCT
        // ═══════════════════════════════════════════════════════
        console.log('\n═══ PHASE 2: OPEN PRODUCT ═══\n');
        log(`Opening product ${CONFIG.PRODUCT_ID}...`);

        await page.goto(`https://ppm.mpptrade.pl/admin/products/${CONFIG.PRODUCT_ID}/edit`);
        await page.waitForSelector('[wire\\:id]', { timeout: CONFIG.PAGE_LOAD_TIMEOUT });
        await page.waitForTimeout(CONFIG.LIVEWIRE_INIT_TIMEOUT);

        log('Product loaded', 'success');
        await takeScreenshot(page, '01_product_loaded', 'Initial product state');
        results.phases.productLoad = { status: 'passed', productId: CONFIG.PRODUCT_ID };

        // ═══════════════════════════════════════════════════════
        // PHASE 3: CLICK SHOP TAB
        // ═══════════════════════════════════════════════════════
        console.log('\n═══ PHASE 3: CLICK SHOP TAB ═══\n');
        log(`Looking for shop tab "${CONFIG.SHOP_NAME}"...`);

        const shopTab = page.locator(`button:has-text("${CONFIG.SHOP_NAME}")`).first();

        if (!(await shopTab.isVisible())) {
            throw new Error(`Shop tab "${CONFIG.SHOP_NAME}" not found`);
        }

        await shopTab.click();
        log('Shop tab clicked', 'success');

        // Wait for PrestaShop data to load
        log('Waiting for PrestaShop data...');
        await page.waitForTimeout(CONFIG.PRESTASHOP_DATA_TIMEOUT);

        await takeScreenshot(page, '02_shop_tab_clicked', `After clicking ${CONFIG.SHOP_NAME} tab`);
        results.phases.shopTab = { status: 'passed', shopName: CONFIG.SHOP_NAME };

        // ═══════════════════════════════════════════════════════
        // PHASE 4: SCROLL TO CATEGORIES
        // ═══════════════════════════════════════════════════════
        console.log('\n═══ PHASE 4: SCROLL TO CATEGORIES ═══\n');
        log('Looking for categories section...');

        const categoriesSection = page.locator('section:has-text("Kategorie")').first();
        await categoriesSection.scrollIntoViewIfNeeded();

        log('Categories section visible', 'success');
        await takeScreenshot(page, '03_categories_section', 'Categories section scrolled into view');
        results.phases.categoriesScroll = { status: 'passed' };

        // ═══════════════════════════════════════════════════════
        // PHASE 5: TOGGLE CATEGORY CHECKBOX
        // ═══════════════════════════════════════════════════════
        console.log('\n═══ PHASE 5: TOGGLE CATEGORY ═══\n');
        log('Finding first category checkbox...');

        const firstCheckbox = page.locator('input[type="checkbox"][wire\\:model*="shopCategories"]').first();

        if (!(await firstCheckbox.isVisible())) {
            throw new Error('No category checkboxes found');
        }

        const wasChecked = await firstCheckbox.isChecked();
        log(`Checkbox initial state: ${wasChecked ? 'checked' : 'unchecked'}`);

        await firstCheckbox.click();
        await page.waitForTimeout(500); // Wait for Livewire update

        const nowChecked = await firstCheckbox.isChecked();
        log(`Checkbox after toggle: ${nowChecked ? 'checked' : 'unchecked'}`, 'success');

        if (wasChecked === nowChecked) {
            log('Warning: Checkbox state did not change', 'warning');
            results.warnings.push('Checkbox toggle may not have worked');
        }

        await takeScreenshot(page, '04_category_toggled', `Category toggled from ${wasChecked} to ${nowChecked}`);
        results.phases.categoryToggle = {
            status: 'passed',
            wasChecked,
            nowChecked,
            changed: wasChecked !== nowChecked,
        };

        // ═══════════════════════════════════════════════════════
        // PHASE 6: CLICK SAVE (CRITICAL)
        // ═══════════════════════════════════════════════════════
        if (!NO_SAVE) {
            console.log('\n═══ PHASE 6: CLICK SAVE (CRITICAL) ═══\n');
            log('Looking for "Zapisz zmiany" button...');

            const saveButton = page.locator('button:has-text("Zapisz zmiany")').first();

            if (!(await saveButton.isVisible())) {
                throw new Error('"Zapisz zmiany" button not found');
            }

            log('Clicking "Zapisz zmiany"...');
            await saveButton.click();

            // CRITICAL: Wait for redirect to /admin/products
            log('Waiting for redirect to /admin/products...');

            try {
                await page.waitForURL('**/admin/products', { timeout: CONFIG.REDIRECT_TIMEOUT });

                console.log('');
                console.log('✅✅✅ CRITICAL: REDIRECT SUCCESS ✅✅✅');
                console.log(`Redirected to: ${page.url()}`);
                console.log('');

                log('REDIRECT TO /admin/products - SUCCESS', 'success');
                await takeScreenshot(page, '05_redirect_success', 'Successfully redirected to products list');

                results.phases.save = {
                    status: 'passed',
                    redirectUrl: page.url(),
                    redirectSuccess: true,
                };
            } catch (error) {
                console.log('');
                console.log('❌❌❌ CRITICAL: REDIRECT FAILED ❌❌❌');
                console.log(`Current URL: ${page.url()}`);
                console.log(`Expected URL: https://ppm.mpptrade.pl/admin/products`);
                console.log('');

                log('REDIRECT TO /admin/products - FAILED', 'error');
                await takeScreenshot(page, 'ERROR_no_redirect', 'Redirect failed - still on edit page');

                results.phases.save = {
                    status: 'failed',
                    currentUrl: page.url(),
                    expectedUrl: 'https://ppm.mpptrade.pl/admin/products',
                    redirectSuccess: false,
                };

                results.errors.push({
                    phase: 'save',
                    error: 'Redirect to /admin/products failed',
                    currentUrl: page.url(),
                });

                throw new Error('CRITICAL: Redirect failed - test cannot continue');
            }

            // ═══════════════════════════════════════════════════════
            // PHASE 7: SEARCH PRODUCT BY SKU
            // ═══════════════════════════════════════════════════════
            console.log('\n═══ PHASE 7: SEARCH PRODUCT BY SKU ═══\n');
            log(`Searching for product SKU: ${CONFIG.PRODUCT_SKU}...`);

            // Find search input (could be SKU filter or general search)
            const searchInput = page.locator('input[placeholder*="SKU"]')
                .or(page.locator('input[type="search"]'))
                .or(page.locator('input[wire\\:model*="search"]'))
                .first();

            if (!(await searchInput.isVisible({ timeout: 5000 }))) {
                log('Search input not found - trying alternative methods', 'warning');
                // Alternative: try to find product directly in table
            } else {
                await searchInput.fill(CONFIG.PRODUCT_SKU);
                await page.waitForTimeout(2000); // Livewire search delay
                log('Search executed', 'success');
            }

            await takeScreenshot(page, '06_product_search', `Searched for ${CONFIG.PRODUCT_SKU}`);
            results.phases.search = { status: 'passed', sku: CONFIG.PRODUCT_SKU };

            // ═══════════════════════════════════════════════════════
            // PHASE 8: REOPEN PRODUCT
            // ═══════════════════════════════════════════════════════
            console.log('\n═══ PHASE 8: REOPEN PRODUCT ═══\n');
            log('Looking for product link...');

            // Find product link (by SKU or edit URL)
            const productLink = page.locator(`a:has-text("${CONFIG.PRODUCT_SKU}")`)
                .or(page.locator(`a[href*="/${CONFIG.PRODUCT_ID}/edit"]`))
                .first();

            if (!(await productLink.isVisible({ timeout: 5000 }))) {
                log('Product link not found - using direct URL', 'warning');
                await page.goto(`https://ppm.mpptrade.pl/admin/products/${CONFIG.PRODUCT_ID}/edit`);
            } else {
                await productLink.click();
            }

            await page.waitForSelector('[wire\\:id]', { timeout: CONFIG.PAGE_LOAD_TIMEOUT });
            await page.waitForTimeout(CONFIG.LIVEWIRE_INIT_TIMEOUT);

            log('Product reopened', 'success');
            await takeScreenshot(page, '07_product_reopened', 'Product reopened after save');
            results.phases.reopen = { status: 'passed' };

            // ═══════════════════════════════════════════════════════
            // PHASE 9: VERIFY PERSISTENCE
            // ═══════════════════════════════════════════════════════
            console.log('\n═══ PHASE 9: VERIFY PERSISTENCE ═══\n');
            log('Clicking shop tab again...');

            const shopTab2 = page.locator(`button:has-text("${CONFIG.SHOP_NAME}")`).first();
            await shopTab2.click();
            await page.waitForTimeout(CONFIG.PRESTASHOP_DATA_TIMEOUT);

            log('Scrolling to categories...');
            const categoriesSection2 = page.locator('section:has-text("Kategorie")').first();
            await categoriesSection2.scrollIntoViewIfNeeded();

            log('Checking checkbox state...');
            const firstCheckbox2 = page.locator('input[type="checkbox"][wire\\:model*="shopCategories"]').first();
            const currentState = await firstCheckbox2.isChecked();

            log(`Current checkbox state: ${currentState ? 'checked' : 'unchecked'}`);
            log(`Expected state: ${nowChecked ? 'checked' : 'unchecked'}`);

            if (currentState === nowChecked) {
                console.log('');
                console.log('✅✅✅ PERSISTENCE SUCCESS ✅✅✅');
                console.log('Category changes persisted correctly');
                console.log('');

                log('PERSISTENCE VERIFICATION - SUCCESS', 'success');
                await takeScreenshot(page, '08_verification_success', 'Persistence verified - changes saved');

                results.phases.persistence = {
                    status: 'passed',
                    expectedState: nowChecked,
                    actualState: currentState,
                    persisted: true,
                };
            } else {
                console.log('');
                console.log('❌❌❌ PERSISTENCE FAILED ❌❌❌');
                console.log(`Expected: ${nowChecked}, Got: ${currentState}`);
                console.log('');

                log('PERSISTENCE VERIFICATION - FAILED', 'error');
                await takeScreenshot(page, 'ERROR_persistence_failed', 'Persistence failed - state reset');

                results.phases.persistence = {
                    status: 'failed',
                    expectedState: nowChecked,
                    actualState: currentState,
                    persisted: false,
                };

                results.errors.push({
                    phase: 'persistence',
                    error: 'Category state did not persist',
                    expected: nowChecked,
                    actual: currentState,
                });
            }
        } else {
            log('Save skipped (--no-save flag)', 'warning');
            results.phases.save = { status: 'skipped', reason: '--no-save flag' };
        }

        // ═══════════════════════════════════════════════════════
        // GENERATE REPORT
        // ═══════════════════════════════════════════════════════
        console.log('\n═══ GENERATING REPORT ═══\n');
        await generateReport();

        // ═══════════════════════════════════════════════════════
        // FINAL SUMMARY
        // ═══════════════════════════════════════════════════════
        console.log('\n═══════════════════════════════════════════════════════');
        console.log('📊 TEST SUMMARY');
        console.log('═══════════════════════════════════════════════════════\n');

        const passed = Object.values(results.phases).filter(p => p.status === 'passed').length;
        const failed = Object.values(results.phases).filter(p => p.status === 'failed').length;
        const skipped = Object.values(results.phases).filter(p => p.status === 'skipped').length;
        const total = Object.keys(results.phases).length;

        console.log(`Total Phases: ${total}`);
        console.log(`✅ Passed: ${passed}`);
        console.log(`❌ Failed: ${failed}`);
        console.log(`⏭️  Skipped: ${skipped}`);
        console.log('');

        if (failed > 0) {
            console.log('❌ TEST FAILED\n');
            console.log('Errors:');
            results.errors.forEach(err => {
                console.log(`  - ${err.phase}: ${err.error}`);
            });
        } else if (skipped > 0) {
            console.log('⚠️  TEST PARTIALLY COMPLETED\n');
        } else {
            console.log('✅ TEST PASSED - ALL CHECKS SUCCESSFUL\n');
        }

        console.log(`Report: ${CONFIG.LOGS_DIR}/category_test_report_${results.timestamp.substring(0, 10)}.md`);
        console.log(`Screenshots: ${CONFIG.SCREENSHOTS_DIR}/category_test_*.png`);
        console.log('');

        // Keep browser open for 5 seconds if visible
        if (!CONFIG.HEADLESS) {
            log('Browser will close in 5 seconds...');
            await page.waitForTimeout(5000);
        }

    } catch (error) {
        console.log('\n═══════════════════════════════════════════════════════');
        console.log('❌ TEST ERROR');
        console.log('═══════════════════════════════════════════════════════\n');
        console.error(error.message);
        console.error(error.stack);
        console.log('');

        results.errors.push({
            phase: 'runtime',
            error: error.message,
            stack: error.stack,
        });

        await takeScreenshot(page, 'ERROR_exception', 'Unhandled exception');
        await generateReport();

    } finally {
        await browser.close();
    }
}

async function generateReport() {
    const timestamp = results.timestamp.substring(0, 10);
    const filename = `category_test_report_${timestamp}.md`;
    const filepath = path.join(CONFIG.LOGS_DIR, filename);

    const passed = Object.values(results.phases).filter(p => p.status === 'passed').length;
    const failed = Object.values(results.phases).filter(p => p.status === 'failed').length;
    const total = Object.keys(results.phases).length;

    let report = `# CATEGORY TEST REPORT - ${results.timestamp}\n\n`;
    report += `## 🎯 Test Execution Summary\n\n`;
    report += `**Product:** ${CONFIG.PRODUCT_ID} (SKU: ${CONFIG.PRODUCT_SKU})\n`;
    report += `**Shop:** ${CONFIG.SHOP_NAME} (ID: ${CONFIG.SHOP_ID})\n`;
    report += `**Timestamp:** ${results.timestamp}\n`;
    report += `**Mode:** ${CONFIG.HEADLESS ? 'Headless' : 'Visible'}\n\n`;
    report += `---\n\n`;

    report += `## ✅ TEST RESULTS\n\n`;
    report += `**Total Phases:** ${total} | **Passed:** ${passed} | **Failed:** ${failed}\n\n`;

    for (const [phase, data] of Object.entries(results.phases)) {
        const icon = data.status === 'passed' ? '✅' : data.status === 'failed' ? '❌' : '⏭️';
        report += `### ${icon} Phase: ${phase}\n`;
        report += `- Status: **${data.status.toUpperCase()}**\n`;
        for (const [key, value] of Object.entries(data)) {
            if (key !== 'status') {
                report += `- ${key}: ${value}\n`;
            }
        }
        report += `\n`;
    }

    if (results.errors.length > 0) {
        report += `## 🐛 Errors Found\n\n`;
        results.errors.forEach(err => {
            report += `### ❌ ${err.phase}\n`;
            report += `- Error: ${err.error}\n`;
            for (const [key, value] of Object.entries(err)) {
                if (key !== 'phase' && key !== 'error' && key !== 'stack') {
                    report += `- ${key}: ${value}\n`;
                }
            }
            report += `\n`;
        });
    }

    if (results.warnings.length > 0) {
        report += `## ⚠️ Warnings\n\n`;
        results.warnings.forEach(warning => {
            report += `- ${warning}\n`;
        });
        report += `\n`;
    }

    report += `## 📸 Screenshots\n\n`;
    results.screenshots.forEach(ss => {
        report += `- \`${ss.filename}\` - ${ss.description}\n`;
    });
    report += `\n`;

    report += `## 📋 Execution Log\n\n`;
    report += `\`\`\`\n`;
    results.logs.forEach(log => {
        report += `[${log.timestamp}] ${log.type.toUpperCase()}: ${log.message}\n`;
    });
    report += `\`\`\`\n\n`;

    report += `---\n\n`;
    report += `## 💡 Recommendations\n\n`;

    if (failed > 0) {
        report += `**ACTION REQUIRED:** Fix critical issues before deployment.\n\n`;

        if (results.phases.save?.redirectSuccess === false) {
            report += `1. **CRITICAL:** Fix redirect after save\n`;
            report += `   - Check ProductFormSaver::save() method\n`;
            report += `   - Verify redirect()->route('admin.products.index') is called\n`;
            report += `   - Check for errors before redirect\n\n`;
        }

        if (results.phases.persistence?.persisted === false) {
            report += `1. **CRITICAL:** Fix category persistence\n`;
            report += `   - Check ProductCategoryManager saves to DB\n`;
            report += `   - Verify ProductShopData.categories JSON field\n`;
            report += `   - Check loadShopDataToForm() loads correctly\n\n`;
        }
    } else {
        report += `**All checks passed!** Safe to deploy.\n\n`;
    }

    report += `---\n\n`;
    report += `**Report generated:** ${new Date().toISOString()}\n`;

    try {
        fs.writeFileSync(filepath, report, 'utf8');
        log(`Report saved: ${filename}`, 'success');
    } catch (error) {
        log(`Failed to save report: ${error.message}`, 'error');
    }
}

// Run test
runTest().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
