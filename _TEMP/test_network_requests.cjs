const { chromium } = require('playwright');

(async () => {
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('   NETWORK REQUESTS TEST');
    console.log('═══════════════════════════════════════════════════════════════\n');

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    const livewireRequests = [];

    // Track ALL network requests
    page.on('request', request => {
        if (request.url().includes('/livewire/')) {
            livewireRequests.push({
                url: request.url(),
                method: request.method(),
                postData: request.postData()
            });
        }
    });

    // Track responses
    page.on('response', async response => {
        if (response.url().includes('/livewire/')) {
            const status = response.status();
            console.log(`📡 Livewire: ${response.request().method()} ${status}`);

            if (status >= 400) {
                const body = await response.text();
                console.log(`   ❌ ERROR RESPONSE: ${body.substring(0, 200)}`);
            }
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

        console.log('4️⃣ Calling saveAndClose()...');

        // Clear previous requests
        livewireRequests.length = 0;

        // Call method
        await page.evaluate(() => {
            const wireEl = document.querySelector('[wire\\:id]');
            const component = window.Livewire.find(wireEl.getAttribute('wire:id'));
            component.saveAndClose();
        });

        await page.waitForTimeout(3000);
        console.log('   ✅ Method called\n');

        console.log('5️⃣ Analyzing Livewire requests after saveAndClose():');
        console.log(`   Total requests: ${livewireRequests.length}\n`);

        if (livewireRequests.length === 0) {
            console.log('   ❌ NO LIVEWIRE REQUESTS! Method did not trigger backend call!\n');
        } else {
            livewireRequests.forEach((req, idx) => {
                console.log(`   Request #${idx + 1}:`);
                console.log(`     Method: ${req.method}`);
                console.log(`     URL: ${req.url.substring(0, 80)}...`);

                if (req.postData) {
                    try {
                        const data = JSON.parse(req.postData);
                        console.log(`     Payload (first 500 chars):`, JSON.stringify(data).substring(0, 500));

                        // Check for saveAndClose in payload
                        const payloadStr = JSON.stringify(data);
                        if (payloadStr.includes('saveAndClose')) {
                            console.log(`     ✅ Contains "saveAndClose" method call`);
                        } else {
                            console.log(`     ❌ Does NOT contain "saveAndClose"`);
                        }
                    } catch (e) {
                        console.log(`     Payload (raw): ${req.postData.substring(0, 100)}`);
                    }
                }
                console.log('');
            });
        }

        console.log('═══════════════════════════════════════════════════════════════');
        console.log('   TEST COMPLETED');
        console.log('═══════════════════════════════════════════════════════════════');

    } catch (error) {
        console.log('\n❌ ERROR:', error.message);
    }

    await browser.close();
})();
