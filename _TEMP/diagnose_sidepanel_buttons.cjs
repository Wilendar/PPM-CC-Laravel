/**
 * DIAGNOSTIC TOOL: Check if sidepanel bulk action buttons exist and are clickable
 *
 * Purpose: Verify wire:click binding on "Aktualizuj sklepy" and "Wczytaj ze sklepów"
 */

const { chromium } = require('playwright');

(async () => {
    console.log('=== SIDEPANEL BUTTONS DIAGNOSTIC ===\n');

    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    // Login
    console.log('[1/5] Logging in...');
    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin');
    console.log('✅ Logged in\n');

    // Navigate to product edit
    console.log('[2/5] Navigating to product edit...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit');
    await page.waitForLoadState('networkidle');
    console.log('✅ Product edit page loaded\n');

    // Wait for Livewire
    console.log('[3/5] Waiting for Livewire initialization...');
    await page.waitForTimeout(2000);
    console.log('✅ Livewire ready\n');

    // Check for sidepanel
    console.log('[4/5] Looking for sidepanel...');
    const sidepanel = await page.locator('.category-form-sidepanel').first();
    const sidepanelExists = await sidepanel.count() > 0;
    console.log(`Sidepanel exists: ${sidepanelExists ? '✅ YES' : '❌ NO'}`);

    if (!sidepanelExists) {
        console.log('❌ CRITICAL: Sidepanel not found! Exiting...');
        await browser.close();
        process.exit(1);
    }

    // Check for "Szybkie akcje" section
    const quickActionsHeader = await page.locator('text=Szybkie akcje').first();
    const quickActionsExists = await quickActionsHeader.count() > 0;
    console.log(`"Szybkie akcje" section: ${quickActionsExists ? '✅ YES' : '❌ NO'}\n`);

    // Check for button #1: "Aktualizuj sklepy"
    console.log('[5/5] Checking buttons...\n');

    console.log('Button #1: "Aktualizuj sklepy"');
    const updateButton = await page.locator('button:has-text("Aktualizuj sklepy")').first();
    const updateButtonExists = await updateButton.count() > 0;
    console.log(`  Exists: ${updateButtonExists ? '✅ YES' : '❌ NO'}`);

    if (updateButtonExists) {
        const updateButtonHTML = await updateButton.evaluate(el => el.outerHTML);
        console.log(`  HTML (first 300 chars): ${updateButtonHTML.substring(0, 300)}...\n`);

        // Check wire:click attribute
        const wireClick = await updateButton.getAttribute('wire:click');
        console.log(`  wire:click attribute: ${wireClick ? `✅ "${wireClick}"` : '❌ MISSING'}`);

        // Check type attribute
        const typeAttr = await updateButton.getAttribute('type');
        console.log(`  type attribute: ${typeAttr ? `✅ "${typeAttr}"` : '❌ MISSING'}`);

        // Check disabled state
        const isDisabled = await updateButton.isDisabled();
        console.log(`  Disabled: ${isDisabled ? '❌ YES (button disabled)' : '✅ NO (button enabled)'}`);

        // Check visibility
        const isVisible = await updateButton.isVisible();
        console.log(`  Visible: ${isVisible ? '✅ YES' : '❌ NO'}`);

        // Check x-data attribute
        const xData = await updateButton.getAttribute('x-data');
        console.log(`  x-data attribute: ${xData ? `✅ "${xData}"` : '❌ MISSING'}\n`);
    } else {
        console.log('  ❌ CRITICAL: Button not found in DOM!\n');
    }

    // Check for button #2: "Wczytaj ze sklepów"
    console.log('Button #2: "Wczytaj ze sklepów"');
    const pullButton = await page.locator('button:has-text("Wczytaj ze sklepów")').first();
    const pullButtonExists = await pullButton.count() > 0;
    console.log(`  Exists: ${pullButtonExists ? '✅ YES' : '❌ NO'}`);

    if (pullButtonExists) {
        const pullButtonHTML = await pullButton.evaluate(el => el.outerHTML);
        console.log(`  HTML (first 300 chars): ${pullButtonHTML.substring(0, 300)}...\n`);

        // Check wire:click attribute
        const wireClick = await pullButton.getAttribute('wire:click');
        console.log(`  wire:click attribute: ${wireClick ? `✅ "${wireClick}"` : '❌ MISSING'}`);

        // Check type attribute
        const typeAttr = await pullButton.getAttribute('type');
        console.log(`  type attribute: ${typeAttr ? `✅ "${typeAttr}"` : '❌ MISSING'}`);

        // Check disabled state
        const isDisabled = await pullButton.isDisabled();
        console.log(`  Disabled: ${isDisabled ? '❌ YES (button disabled)' : '✅ NO (button enabled)'}`);

        // Check visibility
        const isVisible = await pullButton.isVisible();
        console.log(`  Visible: ${isVisible ? '✅ YES' : '❌ NO'}`);

        // Check x-data attribute
        const xData = await pullButton.getAttribute('x-data');
        console.log(`  x-data attribute: ${xData ? `✅ "${xData}"` : '❌ MISSING'}\n`);
    } else {
        console.log('  ❌ CRITICAL: Button not found in DOM!\n');
    }

    // Check Livewire component properties
    console.log('=== LIVEWIRE COMPONENT PROPERTIES ===\n');
    const livewireData = await page.evaluate(() => {
        const component = window.Livewire.all()[0]; // First component on page
        if (!component) return null;

        return {
            activeJobId: component.get('activeJobId'),
            activeJobStatus: component.get('activeJobStatus'),
            activeJobType: component.get('activeJobType'),
            jobCreatedAt: component.get('jobCreatedAt'),
            jobResult: component.get('jobResult'),
        };
    });

    if (livewireData) {
        console.log('Livewire properties:');
        console.log(`  activeJobId: ${livewireData.activeJobId ?? 'null'}`);
        console.log(`  activeJobStatus: ${livewireData.activeJobStatus ?? 'null'}`);
        console.log(`  activeJobType: ${livewireData.activeJobType ?? 'null'}`);
        console.log(`  jobCreatedAt: ${livewireData.jobCreatedAt ?? 'null'}`);
        console.log(`  jobResult: ${livewireData.jobResult ?? 'null'}\n`);
    } else {
        console.log('❌ CRITICAL: Cannot access Livewire component!\n');
    }

    // Try clicking "Aktualizuj sklepy" button
    if (updateButtonExists) {
        console.log('=== ATTEMPTING BUTTON CLICK ===\n');
        console.log('Clicking "Aktualizuj sklepy"...');

        try {
            await updateButton.click();
            console.log('✅ Click executed (no error)\n');

            // Wait for Livewire request
            await page.waitForTimeout(2000);

            // Check if properties changed
            const livewireDataAfter = await page.evaluate(() => {
                const component = window.Livewire.all()[0];
                if (!component) return null;

                return {
                    activeJobId: component.get('activeJobId'),
                    activeJobStatus: component.get('activeJobStatus'),
                    activeJobType: component.get('activeJobType'),
                    jobCreatedAt: component.get('jobCreatedAt'),
                    jobResult: component.get('jobResult'),
                };
            });

            console.log('Livewire properties AFTER click:');
            console.log(`  activeJobId: ${livewireDataAfter.activeJobId ?? 'null'}`);
            console.log(`  activeJobStatus: ${livewireDataAfter.activeJobStatus ?? 'null'}`);
            console.log(`  activeJobType: ${livewireDataAfter.activeJobType ?? 'null'}`);
            console.log(`  jobCreatedAt: ${livewireDataAfter.jobCreatedAt ?? 'null'}`);
            console.log(`  jobResult: ${livewireDataAfter.jobResult ?? 'null'}\n');

            // Check for changes
            if (livewireDataAfter.activeJobType === 'sync' && livewireDataAfter.jobCreatedAt) {
                console.log('✅ SUCCESS: Button click triggered bulkUpdateShops() method!');
                console.log('  activeJobType = "sync" ✅');
                console.log('  jobCreatedAt set ✅');
            } else {
                console.log('❌ FAILURE: Button click DID NOT trigger method!');
                console.log('  Properties unchanged after click');
            }

        } catch (error) {
            console.log(`❌ Click error: ${error.message}`);
        }
    }

    console.log('\n=== DIAGNOSTIC COMPLETE ===');
    await page.waitForTimeout(5000); // Keep browser open
    await browser.close();
})();
