/**
 * Manual Testing Script: Tax Rules UI Workflow
 * Tests: Fill form → Test Connection → Verify Tax Rules section appears
 */

const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    console.log('=== TAX RULES WORKFLOW TEST ===\n');

    try {
        // Step 1: Login (same mechanism as full_console_test.cjs)
        console.log('[1/7] Logging in...');
        await page.goto('https://ppm.mpptrade.pl/login', {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        await page.fill('input[name="email"]', 'admin@mpptrade.pl');
        await page.fill('input[name="password"]', 'Admin123!MPP');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        console.log('✅ Logged in\n');

        // Step 2: Navigate to Add Shop
        console.log('[2/7] Navigating to Add Shop...');
        await page.goto('https://ppm.mpptrade.pl/admin/shops/add');
        await page.waitForLoadState('networkidle');
        console.log('✅ Page loaded\n');

        // Step 3: Fill Step 1 (Basic Info) - using ID selectors
        console.log('[3/7] Filling Step 1 (Basic Info)...');
        await page.fill('#shopName', 'Tax Rules Test Shop');
        await page.fill('#shopUrl', 'https://test.example.com');
        console.log('✅ Step 1 filled\n');

        // Step 4: Navigate to Step 2
        console.log('[4/7] Navigating to Step 2 (API Credentials)...');
        await page.click('button:has-text("Następny krok")');
        await page.waitForTimeout(1500);
        console.log('✅ Step 2 visible\n');

        // Step 5: Fill Step 2 (Version + API Credentials) - using ID selectors
        console.log('[5/7] Filling API Credentials...');
        await page.selectOption('#prestashopVersion', '9');
        await page.fill('#apiKey', 'PBFXWBHN61TQCQ8PA8WH66BRX4C4WZD1');
        console.log('✅ API Credentials filled\n');

        // Step 5a: Navigate to Step 3 (Connection Test)
        console.log('[5a/7] Navigating to Step 3 (Connection Test)...');
        await page.click('button:has-text("Następny krok")');
        await page.waitForTimeout(1500);
        console.log('✅ Step 3 visible\n');

        // Step 6: Click Test Connection and wait for response
        console.log('[6/7] Testing connection...');

        // Wait for Test Connection button (using wire:click selector)
        await page.waitForSelector('button[wire\\:click="testConnection"]', { state: 'visible' });

        // Click Test Connection (using wire:click selector)
        await page.click('button[wire\\:click="testConnection"]');

        // Wait for success message OR error
        try {
            await page.waitForSelector('.alert-success:has-text("Połączenie pomyślne")', { timeout: 15000 });
            console.log('✅ Connection successful\n');
        } catch (err) {
            console.log('❌ Connection failed or timeout');
            const errorMsg = await page.locator('.alert-danger').textContent().catch(() => 'No error message');
            console.log(`Error: ${errorMsg}\n`);
            throw err;
        }

        // Step 7: Verify Tax Rules section appeared
        console.log('[7/7] Verifying Tax Rules section...');

        // Wait for section to appear
        await page.waitForTimeout(2000); // Give Livewire time to update DOM

        // Check if section exists
        const taxRulesSection = await page.locator('h4:has-text("Mapowanie Grup Podatkowych")').count();
        if (taxRulesSection === 0) {
            console.log('❌ Tax Rules section NOT FOUND!\n');
            throw new Error('Tax Rules section missing');
        }
        console.log('✅ Tax Rules section visible\n');

        // Verify dropdowns present
        const dropdownLabels = ['23% VAT', '8% VAT', '5% VAT', '0% VAT'];
        for (const label of dropdownLabels) {
            const dropdown = await page.locator(`label:has-text("${label}") + select`).count();
            if (dropdown === 0) {
                console.log(`❌ Dropdown for "${label}" NOT FOUND!`);
                throw new Error(`Missing dropdown: ${label}`);
            }
            console.log(`✅ Dropdown "${label}" found`);
        }
        console.log('');

        // Verify info card
        const infoCard = await page.locator('.bg-blue-50:has-text("Grupy podatkowe zostaną")').count();
        if (infoCard > 0) {
            console.log('✅ Info card visible\n');
        } else {
            console.log('⚠️ Info card not visible\n');
        }

        // Check for "Wybrano" indicators (smart defaults)
        const selectedIndicators = await page.locator('.text-green-600:has-text("Wybrano")').count();
        console.log(`✅ Smart defaults selected: ${selectedIndicators} groups\n`);

        // Take screenshot
        console.log('[Screenshot] Capturing Tax Rules section...');
        await page.screenshot({
            path: 'D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel\\_TOOLS\\screenshots\\tax_rules_workflow_2025-11-14.png',
            fullPage: true
        });
        console.log('✅ Screenshot saved\n');

        console.log('=== SUMMARY ===');
        console.log('✅ Form filled successfully');
        console.log('✅ Connection test passed');
        console.log('✅ Tax Rules section appeared');
        console.log('✅ All 4 dropdowns present');
        console.log(`✅ ${selectedIndicators} smart defaults selected`);
        console.log('\n🎉 TAX RULES WORKFLOW TEST PASSED!\n');

    } catch (error) {
        console.error('\n❌ TEST FAILED:');
        console.error(error.message);

        // Take error screenshot
        await page.screenshot({
            path: 'D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel\\_TOOLS\\screenshots\\tax_rules_error_2025-11-14.png',
            fullPage: true
        });
        console.log('Error screenshot saved\n');
    } finally {
        await browser.close();
    }
})();
