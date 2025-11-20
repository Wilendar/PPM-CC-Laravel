const { chromium } = require('playwright');

(async () => {
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('   DIRECT LIVEWIRE CALL TEST');
    console.log('═══════════════════════════════════════════════════════════════\n');

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    // Track console
    page.on('console', msg => {
        console.log(`[Browser Console ${msg.type()}]:`, msg.text());
    });

    try {
        // Navigate to product
        console.log('1️⃣ Navigating to product...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log('   ✅ Product loaded\n');

        // Click shop tab
        console.log('2️⃣ Clicking shop tab...');
        await page.waitForSelector('.shop-tab-active, .shop-tab-inactive', { timeout: 10000 });
        const shopTab = await page.locator('button').filter({ hasText: /B2B.*Test.*DEV/i }).first();
        await shopTab.click();
        await page.waitForTimeout(3000);
        console.log('   ✅ Shop tab clicked\n');

        // Check PITGANG
        console.log('3️⃣ Checking PITGANG...');
        const pitgangLabel = await page.locator('label').filter({ hasText: /PITGANG/i }).first();
        const pitgangCheckbox = await pitgangLabel.locator('..').locator('input[type="checkbox"]').first();
        await pitgangCheckbox.check();
        await page.waitForTimeout(1000);
        console.log('   ✅ PITGANG checked\n');

        // Check Livewire state
        console.log('4️⃣ Checking Livewire component state...');
        const livewireState = await page.evaluate(() => {
            const wireEl = document.querySelector('[wire\\:id]');
            if (!wireEl) return { error: 'No wire:id element found' };

            const wireId = wireEl.getAttribute('wire:id');
            const component = window.Livewire?.find(wireId);

            if (!component) return { error: 'Livewire component not found', wireId };

            return {
                wireId,
                hasComponent: true,
                activeJobStatus: component.activeJobStatus,
                hasMethod: typeof component.saveAndClose === 'function'
            };
        });

        console.log('   Livewire state:', JSON.stringify(livewireState, null, 2));

        if (livewireState.error) {
            throw new Error(livewireState.error);
        }

        if (!livewireState.hasMethod) {
            throw new Error('saveAndClose method not found on Livewire component');
        }

        // DIRECT CALL to Livewire method
        console.log('\n5️⃣ Calling $wire.saveAndClose() DIRECTLY...');

        await page.evaluate(() => {
            const wireEl = document.querySelector('[wire\\:id]');
            const wireId = wireEl.getAttribute('wire:id');
            const component = window.Livewire.find(wireId);

            console.log('Calling saveAndClose()...');
            component.saveAndClose();
            console.log('saveAndClose() called');
        });

        await page.waitForTimeout(2000);
        console.log('   ✅ Method called\n');

        // Check for redirect
        console.log('6️⃣ Checking for redirect...');
        await page.waitForTimeout(3000);

        const currentURL = page.url();
        console.log(`   Current URL: ${currentURL}`);

        if (currentURL.includes('/admin/products/11034')) {
            console.log('   ❌ STILL on product edit page - NO REDIRECT\n');
        } else if (currentURL.includes('/admin/products')) {
            console.log('   ✅ Successfully redirected to products list\n');
        } else {
            console.log(`   ⚠️  Unexpected URL: ${currentURL}\n`);
        }

        console.log('═══════════════════════════════════════════════════════════════');
        console.log('   TEST COMPLETED');
        console.log('═══════════════════════════════════════════════════════════════');

    } catch (error) {
        console.log('\n❌ TEST FAILED:', error.message);
    }

    await browser.close();
})();
