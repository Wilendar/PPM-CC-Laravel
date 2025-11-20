/**
 * Tax Dropdown UI Deep Diagnostic Tool
 *
 * Analyzes:
 * - Livewire snapshot data
 * - DOM state vs Livewire state
 * - Network requests during shop switch
 * - Alpine.js data binding
 * - Reactivity issues
 */

const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
    });
    const page = await context.newPage();

    // Capture console logs
    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();

        if (text.includes('Livewire') || text.includes('wire:') || text.includes('Alpine')) {
            console.log(`[BROWSER ${type.toUpperCase()}] ${text}`);
        }
    });

    // Capture network requests (Livewire AJAX)
    const livewireRequests = [];
    page.on('response', async response => {
        const url = response.url();
        if (url.includes('/livewire/message/')) {
            const status = response.status();
            try {
                const json = await response.json();
                livewireRequests.push({
                    url,
                    status,
                    timestamp: new Date().toISOString(),
                    response: json,
                });
                console.log(`\n[LIVEWIRE AJAX] Status: ${status}`);
                console.log('[RESPONSE SNAPSHOT]', JSON.stringify(json.effects?.html, null, 2)?.substring(0, 200));
            } catch (e) {
                // Not JSON response
            }
        }
    });

    try {
        console.log('=== TAX DROPDOWN UI DEEP DIAGNOSTIC ===\n');

        // Navigate to test product
        const testUrl = 'https://ppm.mpptrade.pl/admin/products/11033/edit';
        console.log(`Navigating to: ${testUrl}`);
        await page.goto(testUrl, { waitUntil: 'networkidle' });
        await page.waitForTimeout(2000);

        // PHASE 1: Initial State Analysis
        console.log('\n=== PHASE 1: INITIAL STATE (DEFAULT TAB) ===');

        const initialDropdownValue = await page.locator('#tax_rate').inputValue();
        console.log(`Dropdown value (DEFAULT): ${initialDropdownValue}`);

        // Extract Livewire snapshot
        const wireSnapshot = await page.evaluate(() => {
            const component = document.querySelector('[wire\\:id]');
            if (!component) return null;

            const wireId = component.getAttribute('wire:id');
            const wireSnapshot = component.getAttribute('wire:snapshot');

            return {
                wireId,
                snapshot: wireSnapshot ? JSON.parse(wireSnapshot) : null
            };
        });

        console.log('\n[LIVEWIRE SNAPSHOT - DEFAULT]');
        if (wireSnapshot?.snapshot?.data) {
            const data = wireSnapshot.snapshot.data;
            console.log(`  selectedTaxRateOption: ${data.selectedTaxRateOption}`);
            console.log(`  tax_rate: ${data.tax_rate}`);
            console.log(`  activeShopId: ${data.activeShopId}`);
            console.log(`  availableTaxRuleGroups keys: ${Object.keys(data.availableTaxRuleGroups || {})}`);
        }

        // PHASE 2: Click Shop Tab (PitGang)
        console.log('\n=== PHASE 2: CLICK SHOP TAB (B2B Test DEV) ===');

        // Find B2B Test DEV shop button (using more flexible selector)
        const shopButton = page.locator('button[wire\\:click*="switchToShop"]', { hasText: 'B2B Test DEV' }).first();
        const shopButtonExists = await shopButton.count() > 0;

        if (!shopButtonExists) {
            console.log('❌ B2B Test DEV shop button NOT FOUND - looking for available shops...');
            const allShopButtons = await page.locator('button[wire\\:click*="switchToShop"]').allTextContents();
            console.log('Available shops:', allShopButtons);
            throw new Error('No B2B Test DEV shop tab');
        }

        console.log('Clicking B2B Test DEV shop tab (shop_id=1)...');
        await shopButton.click();

        // Wait for Livewire to process
        await page.waitForTimeout(3000);

        // PHASE 3: Post-Switch State Analysis
        console.log('\n=== PHASE 3: POST-SWITCH STATE (SHOP TAB) ===');

        const postSwitchDropdownValue = await page.locator('#tax_rate').inputValue();
        console.log(`Dropdown value (SHOP): ${postSwitchDropdownValue}`);

        // Get dropdown options
        const dropdownOptions = await page.locator('#tax_rate option').allTextContents();
        console.log('\nDropdown options rendered:');
        dropdownOptions.forEach((opt, idx) => {
            console.log(`  [${idx}] ${opt}`);
        });

        // Get selected option
        const selectedOption = await page.locator('#tax_rate option:checked').textContent();
        console.log(`\nSelected option text: ${selectedOption}`);

        // Extract Livewire snapshot AFTER switch
        const postSwitchSnapshot = await page.evaluate(() => {
            const component = document.querySelector('[wire\\:id]');
            if (!component) return null;

            const wireSnapshot = component.getAttribute('wire:snapshot');
            return wireSnapshot ? JSON.parse(wireSnapshot) : null;
        });

        console.log('\n[LIVEWIRE SNAPSHOT - AFTER SHOP SWITCH]');
        if (postSwitchSnapshot?.data) {
            const data = postSwitchSnapshot.data;
            console.log(`  selectedTaxRateOption: ${data.selectedTaxRateOption}`);
            console.log(`  tax_rate: ${data.tax_rate}`);
            console.log(`  activeShopId: ${data.activeShopId}`);
            console.log(`  shopTaxRateOverrides: ${JSON.stringify(data.shopTaxRateOverrides)}`);
            console.log(`  availableTaxRuleGroups[${data.activeShopId}]: ${JSON.stringify(data.availableTaxRuleGroups?.[data.activeShopId])}`);
        }

        // PHASE 4: Livewire Reactivity Test
        console.log('\n=== PHASE 4: LIVEWIRE REACTIVITY TEST ===');

        // Check if wire:model.live binding works
        console.log('Manually changing dropdown to test reactivity...');
        await page.locator('#tax_rate').selectOption('use_default');
        await page.waitForTimeout(1000);

        const afterManualChange = await page.locator('#tax_rate').inputValue();
        console.log(`After manual change: ${afterManualChange}`);

        // PHASE 5: Database vs UI Comparison
        console.log('\n=== PHASE 5: DATABASE CHECK ===');
        console.log('Database value for shop_id=1 (B2B Test DEV): tax_rate_override = 5.00');
        console.log('Expected dropdown value: "5.00" (VAT 5%)');
        console.log(`Actual dropdown value: ${postSwitchDropdownValue}`);

        // PHASE 6: Summary
        console.log('\n=== DIAGNOSTIC SUMMARY ===');
        console.log(`Initial dropdown value (DEFAULT): ${initialDropdownValue}`);
        console.log(`Post-switch dropdown value (SHOP): ${postSwitchDropdownValue}`);
        console.log(`Livewire snapshot selectedTaxRateOption: ${postSwitchSnapshot?.data?.selectedTaxRateOption}`);
        console.log(`DOM selected option: ${selectedOption}`);

        if (postSwitchSnapshot?.data?.selectedTaxRateOption !== postSwitchDropdownValue) {
            console.log('\n🔴 MISMATCH DETECTED!');
            console.log('Livewire property !== DOM value');
            console.log('This indicates a REACTIVITY ISSUE');
        } else {
            console.log('\n✅ Livewire property === DOM value');
            console.log('Reactivity working, but may be setting WRONG value');
        }

        // Save network requests
        console.log(`\nCaptured ${livewireRequests.length} Livewire AJAX requests`);

        // Take screenshots
        await page.screenshot({
            path: '_TOOLS/screenshots/tax_dropdown_diagnostic_full_2025-11-17.png',
            fullPage: true
        });
        await page.screenshot({
            path: '_TOOLS/screenshots/tax_dropdown_diagnostic_viewport_2025-11-17.png'
        });
        console.log('\nScreenshots saved to _TOOLS/screenshots/');

        // Keep browser open for manual inspection
        console.log('\n⏸️  Browser kept open for manual inspection...');
        console.log('Press Ctrl+C to close');
        await page.waitForTimeout(300000); // 5 minutes

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        console.error(error.stack);
    } finally {
        await browser.close();
    }
})();
