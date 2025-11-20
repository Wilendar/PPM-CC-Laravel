const { chromium } = require('playwright');

(async () => {
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('   TEST: saveAndClose() via Browser Console (No Alpine.js)');
    console.log('═══════════════════════════════════════════════════════════════\n');

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    // Track network
    page.on('response', async response => {
        if (response.url().includes('/livewire/')) {
            const status = response.status();
            console.log(`📡 Livewire: ${response.request().method()} ${status}`);
        }
    });

    try {
        // Navigate
        console.log('1️⃣ Navigating...');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log('   ✅ Loaded\n');

        // Click shop tab
        console.log('2️⃣ Clicking shop tab...');
        await page.waitForSelector('.shop-tab-active, .shop-tab-inactive', { timeout: 10000 });
        const shopTab = await page.locator('button').filter({ hasText: /B2B.*Test.*DEV/i }).first();
        await shopTab.click();
        await page.waitForTimeout(3000);
        console.log('   ✅ Tab clicked\n');

        // Check PITGANG
        console.log('3️⃣ Checking PITGANG...');
        const pitgangLabel = await page.locator('label').filter({ hasText: /PITGANG/i }).first();
        const pitgangCheckbox = await pitgangLabel.locator('..').locator('input[type="checkbox"]').first();
        await pitgangCheckbox.check();
        await page.waitForTimeout(1000);
        console.log('   ✅ Checked\n');

        console.log('4️⃣ Getting current URL before saveAndClose()...');
        const urlBefore = page.url();
        console.log(`   URL before: ${urlBefore}\n`);

        console.log('5️⃣ Calling saveAndClose() DIRECTLY via console (bypass Alpine.js)...');

        // Call saveAndClose directly via JavaScript
        await page.evaluate(() => {
            const wireEl = document.querySelector('[wire\\:id]');
            if (!wireEl) {
                throw new Error('No Livewire component found');
            }
            const component = window.Livewire.find(wireEl.getAttribute('wire:id'));
            if (!component) {
                throw new Error('Livewire component not initialized');
            }
            console.log('Calling saveAndClose()...');
            component.saveAndClose();
        });

        console.log('   ✅ Method called via console\n');

        // Wait and check URL
        console.log('6️⃣ Waiting for redirect (15 seconds)...');
        try {
            await page.waitForURL('**/admin/products', { timeout: 15000 });
            console.log('   ✅ SUCCESS! Redirected to /admin/products\n');

            const urlAfter = page.url();
            console.log(`   URL after: ${urlAfter}\n`);

            console.log('═══════════════════════════════════════════════════════════════');
            console.log('   ✅ TEST PASSED - Redirect WORKS!');
            console.log('═══════════════════════════════════════════════════════════════');

        } catch (error) {
            const urlAfter = page.url();
            console.log(`   ❌ FAILED: No redirect`);
            console.log(`   URL after 15s: ${urlAfter}\n`);

            console.log('═══════════════════════════════════════════════════════════════');
            console.log('   ❌ TEST FAILED - No redirect occurred');
            console.log('═══════════════════════════════════════════════════════════════');
        }

    } catch (error) {
        console.log('\n❌ ERROR:', error.message);
    }

    await browser.close();
})();
