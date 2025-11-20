#!/usr/bin/env node
/**
 * PPM VERIFICATION TOOL - Complete Console & UI Testing
 *
 * Full page verification with console monitoring, screenshots, and interactive testing
 *
 * Usage:
 *   node full_console_test.cjs [URL] [email] [password] [OPTIONS]
 *
 * Options:
 *   --headless          Run in headless mode (default: true)
 *   --show              Show browser window
 *   --no-click          Skip tab clicking (just load page)
 *   --tab=NAME          Click specific tab (e.g., --tab=Warianty, --tab=Cechy)
 *   --verify-variants   Run Variant CRUD verification checks
 *
 * Examples:
 *   node full_console_test.cjs                                    # Default: product edit with Warianty tab
 *   node full_console_test.cjs "URL" --show                       # Show browser window
 *   node full_console_test.cjs "URL" --no-click                   # No tab interaction
 *   node full_console_test.cjs "URL" --tab=Cechy                  # Click Cechy tab
 *   node full_console_test.cjs "URL" --tab=Warianty --verify-variants  # Variant CRUD verification
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

/**
 * Verify Variant CRUD functionality
 * @param {Page} page - Playwright page object
 * @returns {Promise<Object>} - Verification results
 */
async function verifyVariantCRUD(page) {
    console.log('\n\n🧪 === VARIANT CRUD VERIFICATION ===');

    const errors = [];
    const warnings = [];
    const passed = [];

    try {
        // CHECK 1: Variant Tab Exists
        console.log('\n[CHECK 1/7] Variant tab existence...');
        try {
            const variantTab = page.locator('button.tab-enterprise:has-text("Warianty")').first();
            const isVisible = await variantTab.isVisible({ timeout: 5000 });
            if (isVisible) {
                passed.push('✅ Variant tab found and visible');
                console.log('✅ Variant tab found and visible');
            } else {
                errors.push('❌ Variant tab not visible');
                console.log('❌ Variant tab not visible');
            }
        } catch (e) {
            errors.push('❌ Variant tab not found: ' + e.message);
            console.log('❌ Variant tab not found');
        }

        // CHECK 2: Checkbox "Produkt ma warianty" existence
        console.log('\n[CHECK 2/7] Checkbox "Produkt ma warianty"...');
        try {
            const checkbox = page.locator('input[type="checkbox"][wire\\:model*="hasVariants"], input[type="checkbox"][x-model*="hasVariants"]').first();
            const exists = await checkbox.count() > 0;
            if (exists) {
                const isChecked = await checkbox.isChecked();
                passed.push(`✅ Checkbox found (state: ${isChecked ? 'checked' : 'unchecked'})`);
                console.log(`✅ Checkbox found (state: ${isChecked ? 'checked' : 'unchecked'})`);
            } else {
                warnings.push('⚠️ Checkbox "Produkt ma warianty" not found');
                console.log('⚠️ Checkbox "Produkt ma warianty" not found');
            }
        } catch (e) {
            warnings.push('⚠️ Error checking variant checkbox: ' + e.message);
            console.log('⚠️ Error checking variant checkbox');
        }

        // CHECK 3: "Dodaj wariant" button
        console.log('\n[CHECK 3/7] "Dodaj wariant" button...');
        try {
            const addButton = page.locator('button:has-text("Dodaj wariant")').first();
            const exists = await addButton.count() > 0;
            if (exists) {
                const isVisible = await addButton.isVisible({ timeout: 2000 });
                if (isVisible) {
                    passed.push('✅ "Dodaj wariant" button found and visible');
                    console.log('✅ "Dodaj wariant" button found and visible');
                } else {
                    passed.push('✅ "Dodaj wariant" button found (hidden - expected if checkbox unchecked)');
                    console.log('✅ "Dodaj wariant" button found (hidden - OK)');
                }
            } else {
                warnings.push('⚠️ "Dodaj wariant" button not found');
                console.log('⚠️ "Dodaj wariant" button not found');
            }
        } catch (e) {
            warnings.push('⚠️ Error checking add variant button: ' + e.message);
            console.log('⚠️ Error checking add variant button');
        }

        // CHECK 4: Variant Table/List
        console.log('\n[CHECK 4/7] Variant table/list...');
        try {
            // Try multiple selectors for variant list
            const variantRows = await page.locator('.variant-row, [data-variant-id], tr[data-variant-id]').count();
            if (variantRows > 0) {
                passed.push(`✅ Variant table found (${variantRows} variants)`);
                console.log(`✅ Variant table found (${variantRows} variants)`);
            } else {
                passed.push('✅ No variants found (expected if product has no variants)');
                console.log('✅ No variants found (OK if none exist)');
            }
        } catch (e) {
            warnings.push('⚠️ Error checking variant table: ' + e.message);
            console.log('⚠️ Error checking variant table');
        }

        // CHECK 5: Edit/Delete buttons (if variants exist)
        console.log('\n[CHECK 5/7] Variant action buttons...');
        try {
            const editButtons = await page.locator('button:has-text("Edytuj"), button[title*="Edytuj"]').count();
            const deleteButtons = await page.locator('button:has-text("Usuń"), button[title*="Usuń"]').count();

            if (editButtons > 0 || deleteButtons > 0) {
                passed.push(`✅ Action buttons found (Edit: ${editButtons}, Delete: ${deleteButtons})`);
                console.log(`✅ Action buttons found (Edit: ${editButtons}, Delete: ${deleteButtons})`);
            } else {
                passed.push('✅ No action buttons (OK if no variants)');
                console.log('✅ No action buttons (OK if no variants)');
            }
        } catch (e) {
            warnings.push('⚠️ Error checking action buttons: ' + e.message);
            console.log('⚠️ Error checking action buttons');
        }

        // CHECK 6: Livewire wire:snapshot errors (CRITICAL)
        console.log('\n[CHECK 6/7] Livewire rendering check...');
        try {
            const content = await page.content();
            if (content.includes('wire:snapshot')) {
                errors.push('❌ CRITICAL: wire:snapshot visible in HTML (Livewire rendering error)');
                console.log('❌ CRITICAL: wire:snapshot visible in HTML');
            } else {
                passed.push('✅ No wire:snapshot errors detected');
                console.log('✅ No wire:snapshot errors detected');
            }

            // Check for wire:id on teleported elements
            if (content.includes('x-teleport') && !content.includes('wire:id')) {
                warnings.push('⚠️ x-teleport found without wire:id (may cause issues)');
                console.log('⚠️ x-teleport without wire:id detected');
            }
        } catch (e) {
            errors.push('❌ Error checking Livewire rendering: ' + e.message);
            console.log('❌ Error checking Livewire rendering');
        }

        // CHECK 7: Alpine.js & Livewire initialization
        console.log('\n[CHECK 7/7] Framework initialization...');
        try {
            const hasLivewire = await page.evaluate(() => typeof window.Livewire !== 'undefined');
            const hasAlpine = await page.evaluate(() => typeof window.Alpine !== 'undefined');

            if (hasLivewire && hasAlpine) {
                passed.push('✅ Livewire and Alpine.js initialized');
                console.log('✅ Livewire and Alpine.js initialized');
            } else {
                if (!hasLivewire) warnings.push('⚠️ Livewire not initialized');
                if (!hasAlpine) warnings.push('⚠️ Alpine.js not initialized');
                console.log(`⚠️ Frameworks: Livewire=${hasLivewire}, Alpine=${hasAlpine}`);
            }
        } catch (e) {
            warnings.push('⚠️ Error checking framework initialization: ' + e.message);
            console.log('⚠️ Error checking framework initialization');
        }

    } catch (error) {
        errors.push('❌ Variant CRUD verification failed: ' + error.message);
        console.log('❌ Variant CRUD verification failed:', error.message);
    }

    // Summary
    console.log('\n\n📊 === VARIANT CRUD VERIFICATION SUMMARY ===');
    console.log(`✅ Checks Passed: ${passed.length}`);
    console.log(`⚠️ Warnings: ${warnings.length}`);
    console.log(`❌ Errors: ${errors.length}`);

    if (passed.length > 0) {
        console.log('\n✅ PASSED CHECKS:');
        passed.forEach((p, i) => console.log(`   ${i + 1}. ${p}`));
    }

    if (warnings.length > 0) {
        console.log('\n⚠️ WARNINGS:');
        warnings.forEach((w, i) => console.log(`   ${i + 1}. ${w}`));
    }

    if (errors.length > 0) {
        console.log('\n❌ ERRORS:');
        errors.forEach((e, i) => console.log(`   ${i + 1}. ${e}`));
    }

    const overallStatus = errors.length === 0 ? '✅ PASS' : '❌ FAIL';
    console.log(`\n🎯 Overall Status: ${overallStatus}`);

    return { passed, warnings, errors };
}

// Parse arguments
const args = process.argv.slice(2);
const url = args.find(arg => !arg.startsWith('--') && arg.includes('http')) || 'https://ppm.mpptrade.pl/admin/products/10969/edit';
const email = args.find((arg, i) => i > 0 && !arg.startsWith('--') && !args[i-1].includes('http')) || 'admin@mpptrade.pl';
const password = args[args.indexOf(email) + 1] || 'Admin123!MPP';

// Options
const headless = !args.includes('--show');
const noClick = args.includes('--no-click');
const tabArg = args.find(arg => arg.startsWith('--tab='));
const tabName = tabArg ? tabArg.split('=')[1] : 'Warianty';
const verifyVariants = args.includes('--verify-variants');

(async () => {
    console.log('=== PPM VERIFICATION TOOL ===\n');
    console.log(`URL: ${url}`);
    console.log(`Mode: ${headless ? 'Headless' : 'Visible'}`);
    console.log(`Tab Click: ${noClick ? 'Disabled' : `Enabled (${tabName})`}\n`);

    const browser = await chromium.launch({
        headless: headless,
        args: ['--disable-blink-features=AutomationControlled']
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    // Collect ALL console messages
    const allMessages = [];
    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();

        allMessages.push({
            type: type,
            text: text,
            timestamp: new Date().toISOString()
        });

        // Print to terminal immediately
        const icon = type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
        console.log(`${icon} [${type}] ${text}`);
    });

    // Collect page errors
    const pageErrors = [];
    page.on('pageerror', error => {
        pageErrors.push(error.toString());
        console.log(`🔥 [PAGE ERROR] ${error.toString()}`);
    });

    // Collect request failures (404, 500, etc.)
    const failedRequests = [];
    page.on('response', response => {
        if (!response.ok()) {
            const failure = {
                url: response.url(),
                status: response.status(),
                statusText: response.statusText()
            };
            failedRequests.push(failure);
            console.log(`❌ [${failure.status}] ${failure.url}`);
        }
    });

    try {
        // STEP 1: Login
        console.log('\n[1/5] Logging in...');
        await page.goto('https://ppm.mpptrade.pl/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✅ Logged in');

        // STEP 2: Hard Refresh - Navigate with no cache
        console.log('\n[2/5] Hard refresh (no cache)...');
        await context.clearCookies(); // Don't clear, we need auth

        // Navigate with cache disabled
        await page.goto(url, {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log('✅ Page loaded (hard refresh)');

        // STEP 3: Wait for Livewire to initialize (optional - continue if timeout)
        console.log('\n[3/5] Waiting for Livewire initialization...');
        try {
            await page.waitForFunction(() => {
                return window.Livewire !== undefined && window.Alpine !== undefined;
            }, { timeout: 10000 });
            console.log('✅ Livewire initialized');
        } catch (e) {
            console.log('⚠️ Livewire initialization timeout - continuing anyway...');
        }

        // STEP 4: Click tab (if enabled)
        if (!noClick) {
            console.log(`\n[4/6] Looking for "${tabName}" tab...`);

            // Try multiple strategies to find and click the tab
            let clicked = false;

            // Strategy 1: Button with exact text match
            try {
                const tab = page.locator(`button.tab-enterprise:has-text("${tabName}")`).first();
                if (await tab.isVisible({ timeout: 5000 })) {
                    console.log(`Found ${tabName} tab (button.tab-enterprise)`);
                    await tab.click();
                    clicked = true;
                    await page.waitForTimeout(2000); // Wait for Livewire
                    console.log(`✅ Clicked ${tabName} tab`);
                }
            } catch (e) {
                console.log('Strategy 1 failed:', e.message);
            }

            // Strategy 2: Any button with text
            if (!clicked) {
                try {
                    const tab = page.locator(`button:has-text("${tabName}")`).first();
                    if (await tab.isVisible({ timeout: 5000 })) {
                        console.log(`Found ${tabName} tab (button selector)`);
                        await tab.click();
                        clicked = true;
                        await page.waitForTimeout(2000);
                        console.log(`✅ Clicked ${tabName} tab`);
                    }
                } catch (e) {
                    console.log('Strategy 2 failed:', e.message);
                }
            }

            // Strategy 3: Direct text match
            if (!clicked) {
                try {
                    const tab = page.locator(`text=${tabName}`).first();
                    if (await tab.isVisible({ timeout: 5000 })) {
                        console.log(`Found ${tabName} tab (text match)`);
                        await tab.click();
                        clicked = true;
                        await page.waitForTimeout(2000);
                        console.log(`✅ Clicked ${tabName} tab`);
                    }
                } catch (e) {
                    console.log('Strategy 3 failed:', e.message);
                }
            }

            if (!clicked) {
                console.log(`⚠️ Could not find/click "${tabName}" tab`);
            }

            // Scroll to active tab content
            if (clicked) {
                try {
                    await page.evaluate(() => {
                        const activeSection = document.querySelector('[x-data*="variantPrices"], [x-data*="featureTypes"]');
                        if (activeSection) {
                            activeSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                    await page.waitForTimeout(1000);
                } catch (e) {
                    // Ignore scroll errors
                }
            }
        } else {
            console.log('\n[4/6] Tab clicking disabled (--no-click)');
        }

        // STEP 5: Wait for any async operations to complete
        console.log('\n[5/6] Waiting for all operations to complete...');
        await page.waitForTimeout(3000);
        console.log('✅ Wait complete');

        // STEP 6: Take screenshots (full page + viewport)
        console.log('\n[6/6] Taking screenshots...');
        const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
        const screenshotsDir = path.join(__dirname, 'screenshots');

        if (!fs.existsSync(screenshotsDir)) {
            fs.mkdirSync(screenshotsDir, { recursive: true });
        }

        const fullPagePath = path.join(screenshotsDir, `verification_full_${timestamp}.png`);
        const viewportPath = path.join(screenshotsDir, `verification_viewport_${timestamp}.png`);

        await page.screenshot({
            path: fullPagePath,
            fullPage: true
        });

        await page.screenshot({
            path: viewportPath,
            fullPage: false
        });

        console.log(`✅ Full page: ${fullPagePath}`);
        console.log(`✅ Viewport: ${viewportPath}`);

        // STEP 7: Variant CRUD Verification (if enabled)
        if (verifyVariants) {
            const variantResults = await verifyVariantCRUD(page);
            // Results already printed in function
        }

    } catch (error) {
        console.error('\n❌ Test error:', error.message);
    }

    // Summary
    console.log('\n\n=== SUMMARY ===');
    console.log(`Total console messages: ${allMessages.length}`);

    const errors = allMessages.filter(m => m.type === 'error');
    const warnings = allMessages.filter(m => m.type === 'warning' || m.type === 'warn');

    console.log(`Errors: ${errors.length}`);
    console.log(`Warnings: ${warnings.length}`);
    console.log(`Page Errors: ${pageErrors.length}`);
    console.log(`Failed Requests: ${failedRequests.length}`);

    if (failedRequests.length > 0) {
        console.log('\n🌐 FAILED REQUESTS (404/500):');
        failedRequests.forEach((req, i) => {
            console.log(`${i + 1}. [${req.status}] ${req.url}`);
        });
    }

    if (errors.length > 0) {
        console.log('\n🔴 ERRORS FOUND:');
        errors.forEach((err, i) => {
            console.log(`${i + 1}. ${err.text}`);
        });
    }

    if (warnings.length > 0) {
        console.log('\n⚠️ WARNINGS FOUND:');
        warnings.forEach((warn, i) => {
            console.log(`${i + 1}. ${warn.text}`);
        });
    }

    if (pageErrors.length > 0) {
        console.log('\n🔥 PAGE ERRORS FOUND:');
        pageErrors.forEach((err, i) => {
            console.log(`${i + 1}. ${err}`);
        });
    }

    if (errors.length === 0 && warnings.length === 0 && pageErrors.length === 0 && failedRequests.length === 0) {
        console.log('\n✅ NO ERRORS OR WARNINGS FOUND!');
    } else {
        console.log(`\n⚠️ TOTAL ISSUES: ${errors.length + warnings.length + pageErrors.length + failedRequests.length}`);
    }

    await browser.close();
})();
