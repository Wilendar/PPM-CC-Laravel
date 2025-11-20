/**
 * Diagnostic Script: "Zapisz zmiany" Button Investigation
 *
 * PURPOSE: Diagnose why save button doesn't save category changes
 *
 * Tests:
 * 1. Load product edit page
 * 2. Switch to shop TAB
 * 3. Toggle category checkbox
 * 4. Click "Zapisz zmiany" button
 * 5. Monitor console logs and network requests
 * 6. Verify if Livewire request is sent
 * 7. Check for errors or failed requests
 */

const { chromium } = require('playwright');

(async () => {
    console.log('\n╔═══════════════════════════════════════════════════════════════╗');
    console.log('║   DIAGNOSTIC: "Zapisz zmiany" Button Investigation           ║');
    console.log('╚═══════════════════════════════════════════════════════════════╝\n');

    const browser = await chromium.launch({ headless: false }); // Show browser
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // Storage for collected data
    const consoleLogs = [];
    const networkRequests = [];
    const livewireRequests = [];

    // Monitor console
    page.on('console', msg => {
        const text = msg.text();
        consoleLogs.push({
            type: msg.type(),
            text: text,
            timestamp: new Date().toISOString()
        });

        if (text.includes('Livewire') || text.includes('wire:') || text.includes('saveAllPendingChanges')) {
            console.log(`🔍 [CONSOLE ${msg.type().toUpperCase()}] ${text}`);
        }
    });

    // Monitor network requests
    page.on('request', request => {
        const url = request.url();

        // Track all POST requests
        if (request.method() === 'POST') {
            networkRequests.push({
                url: url,
                method: request.method(),
                timestamp: new Date().toISOString()
            });
        }

        // Track Livewire requests specifically
        if (url.includes('/livewire/') || request.postDataJSON()?.components) {
            console.log(`📡 [LIVEWIRE REQUEST] ${request.method()} ${url}`);
            livewireRequests.push({
                url: url,
                method: request.method(),
                payload: request.postDataJSON(),
                timestamp: new Date().toISOString()
            });
        }
    });

    // Monitor network responses
    page.on('response', async response => {
        const url = response.url();

        if (url.includes('/livewire/') || response.request().postDataJSON()?.components) {
            const status = response.status();
            console.log(`📨 [LIVEWIRE RESPONSE] ${status} ${url}`);

            if (status !== 200) {
                console.log(`❌ [ERROR] Non-200 response: ${status}`);
                try {
                    const body = await response.text();
                    console.log(`Response body: ${body.substring(0, 500)}`);
                } catch (e) {
                    console.log('Cannot read response body');
                }
            }
        }
    });

    try {
        console.log('📍 STEP 1: Navigating to product edit page...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit', {
            waitUntil: 'networkidle',
            timeout: 60000
        });
        console.log('✅ Page loaded\n');

        // Take initial screenshot
        await page.screenshot({
            path: '_TOOLS/screenshots/save_button_diagnostic_initial.png',
            fullPage: true
        });

        // Wait for Livewire to initialize
        console.log('⏳ Waiting for Livewire initialization...');
        await page.waitForTimeout(2000);

        // Find shop tabs
        console.log('\n📍 STEP 2: Looking for shop TABs...');
        const shopTabs = await page.locator('[role="tab"]').filter({ hasText: /Pitbike|Test|KAYO/ }).all();

        if (shopTabs.length === 0) {
            console.log('⚠️  No shop tabs found');
            await page.screenshot({
                path: '_TOOLS/screenshots/save_button_diagnostic_no_tabs.png',
                fullPage: true
            });
        } else {
            console.log(`✅ Found ${shopTabs.length} shop tabs`);

            // Click first shop tab
            const firstTab = shopTabs[0];
            const tabText = await firstTab.textContent();
            console.log(`🖱️  Clicking tab: "${tabText}"`);

            await firstTab.click();
            await page.waitForTimeout(1500); // Wait for tab switch

            console.log('✅ Tab switched\n');

            await page.screenshot({
                path: '_TOOLS/screenshots/save_button_diagnostic_after_tab.png',
                fullPage: true
            });
        }

        // Find category checkboxes
        console.log('📍 STEP 3: Looking for category checkboxes...');
        const categoryCheckboxes = await page.locator('input[type="checkbox"][id^="category_"]').all();

        if (categoryCheckboxes.length === 0) {
            console.log('⚠️  No category checkboxes found');
        } else {
            console.log(`✅ Found ${categoryCheckboxes.length} category checkboxes`);

            // Find first unchecked checkbox
            let targetCheckbox = null;
            let checkboxId = null;

            for (const checkbox of categoryCheckboxes) {
                const isChecked = await checkbox.isChecked();
                if (!isChecked) {
                    targetCheckbox = checkbox;
                    checkboxId = await checkbox.getAttribute('id');
                    break;
                }
            }

            if (targetCheckbox) {
                console.log(`🖱️  Toggling checkbox: ${checkboxId}`);

                // Clear previous Livewire requests
                livewireRequests.length = 0;

                await targetCheckbox.click();
                await page.waitForTimeout(1000); // Wait for Livewire update

                console.log('✅ Checkbox toggled');

                // Check if Livewire request was sent for toggle
                if (livewireRequests.length > 0) {
                    console.log(`✅ Livewire request sent after toggle (${livewireRequests.length} requests)`);
                } else {
                    console.log('⚠️  NO Livewire request after toggle - possible issue!');
                }

                await page.screenshot({
                    path: '_TOOLS/screenshots/save_button_diagnostic_after_toggle.png',
                    fullPage: true
                });
            } else {
                console.log('⚠️  All checkboxes are checked, cannot test toggle');
            }
        }

        // Find "Zapisz zmiany" button
        console.log('\n📍 STEP 4: Looking for "Zapisz zmiany" button...');
        const saveButton = page.locator('button').filter({ hasText: 'Zapisz zmiany' }).first();

        const saveButtonExists = await saveButton.count() > 0;

        if (!saveButtonExists) {
            console.log('❌ "Zapisz zmiany" button NOT FOUND');

            // Try to find any save-related buttons
            const allButtons = await page.locator('button').all();
            console.log(`\nFound ${allButtons.length} buttons on page:`);
            for (const btn of allButtons.slice(0, 10)) {
                const text = await btn.textContent();
                console.log(`  - "${text.trim()}"`);
            }
        } else {
            console.log('✅ "Zapisz zmiany" button found');

            // Check wire:click attribute
            const wireClick = await saveButton.getAttribute('wire:click');
            const wireTarget = await saveButton.getAttribute('wire:target');

            console.log(`📋 Button attributes:`);
            console.log(`   wire:click = "${wireClick}"`);
            console.log(`   wire:target = "${wireTarget}"`);

            // Check if button is disabled
            const isDisabled = await saveButton.isDisabled();
            console.log(`   disabled = ${isDisabled}`);

            if (!wireClick) {
                console.log('❌ CRITICAL: Button has NO wire:click attribute!');
            } else if (wireClick !== 'saveAllPendingChanges') {
                console.log(`⚠️  WARNING: wire:click="${wireClick}" (expected "saveAllPendingChanges")`);
            }

            // Clear previous Livewire requests
            livewireRequests.length = 0;

            console.log('\n🖱️  Clicking "Zapisz zmiany" button...');
            await saveButton.click();

            // Wait for potential Livewire request
            await page.waitForTimeout(3000);

            console.log('✅ Button clicked\n');

            await page.screenshot({
                path: '_TOOLS/screenshots/save_button_diagnostic_after_click.png',
                fullPage: true
            });

            // Analyze what happened after click
            console.log('═══════════════════════════════════════════════════════════════');
            console.log('   ANALYSIS AFTER BUTTON CLICK');
            console.log('═══════════════════════════════════════════════════════════════\n');

            if (livewireRequests.length === 0) {
                console.log('❌ CRITICAL: NO Livewire request sent after button click!');
                console.log('   Possible causes:');
                console.log('   1. wire:click binding not working');
                console.log('   2. JavaScript error preventing request');
                console.log('   3. Button click event not propagating');
                console.log('   4. Livewire not initialized properly');
            } else {
                console.log(`✅ Livewire request(s) sent: ${livewireRequests.length}`);

                livewireRequests.forEach((req, index) => {
                    console.log(`\n📦 Request ${index + 1}:`);
                    console.log(`   Method: ${req.method}`);
                    console.log(`   URL: ${req.url}`);
                    if (req.payload?.updates) {
                        console.log(`   Updates: ${JSON.stringify(req.payload.updates, null, 2)}`);
                    }
                    if (req.payload?.calls) {
                        console.log(`   Calls: ${JSON.stringify(req.payload.calls, null, 2)}`);
                    }
                });
            }
        }

        // Check for JavaScript errors
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('   CONSOLE ERRORS');
        console.log('═══════════════════════════════════════════════════════════════\n');

        const errors = consoleLogs.filter(log => log.type === 'error');

        if (errors.length === 0) {
            console.log('✅ No JavaScript errors detected');
        } else {
            console.log(`❌ Found ${errors.length} JavaScript errors:\n`);
            errors.forEach((err, index) => {
                console.log(`${index + 1}. [${err.timestamp}] ${err.text}`);
            });
        }

        // Summary
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('   DIAGNOSTIC SUMMARY');
        console.log('═══════════════════════════════════════════════════════════════\n');

        console.log(`Total console logs: ${consoleLogs.length}`);
        console.log(`Total network requests: ${networkRequests.length}`);
        console.log(`Total Livewire requests: ${livewireRequests.length}`);
        console.log(`JavaScript errors: ${errors.length}`);

        console.log('\n📸 Screenshots saved:');
        console.log('   - _TOOLS/screenshots/save_button_diagnostic_initial.png');
        console.log('   - _TOOLS/screenshots/save_button_diagnostic_after_tab.png');
        console.log('   - _TOOLS/screenshots/save_button_diagnostic_after_toggle.png');
        console.log('   - _TOOLS/screenshots/save_button_diagnostic_after_click.png');

    } catch (error) {
        console.error('\n❌ ERROR during diagnostic:', error.message);
        console.error(error.stack);

        await page.screenshot({
            path: '_TOOLS/screenshots/save_button_diagnostic_error.png',
            fullPage: true
        });
    } finally {
        console.log('\n⏳ Keeping browser open for 10 seconds for manual inspection...');
        await page.waitForTimeout(10000);

        await browser.close();
        console.log('\n✅ Diagnostic complete\n');
    }
})();
