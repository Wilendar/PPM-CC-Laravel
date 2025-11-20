const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });
    const page = await context.newPage();

    // Capture all network requests
    const livewireRequests = [];
    page.on('request', request => {
        const url = request.url();
        if (url.includes('/livewire/update') || url.includes('clearOldLogs') || url.includes('archiveOldLogs')) {
            livewireRequests.push({
                url: url,
                method: request.method(),
                postData: request.postData()
            });
            console.log(`[LIVEWIRE REQUEST] ${request.method()} ${url}`);
        }
    });

    page.on('response', async response => {
        const url = response.url();
        if (url.includes('/livewire/update')) {
            console.log(`[LIVEWIRE RESPONSE] ${response.status()} ${url}`);
            try {
                const body = await response.text();
                if (body.includes('clearOldLogs') || body.includes('archiveOldLogs')) {
                    console.log(`[RESPONSE BODY] ${body.substring(0, 500)}`);
                }
            } catch (e) {
                // Response already consumed
            }
        }
    });

    // Console logging
    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        if (type === 'error' || text.includes('Livewire') || text.includes('wire') || text.includes('clearOldLogs')) {
            console.log(`[BROWSER ${type.toUpperCase()}] ${text}`);
        }
    });

    // Page errors
    page.on('pageerror', error => {
        console.log(`[PAGE ERROR] ${error.message}`);
        console.log(error.stack);
    });

    console.log('[1/7] Navigating...');
    await page.goto('https://ppm.mpptrade.pl/admin/shops/sync', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    console.log('[2/7] Scrolling to buttons...');
    await page.evaluate(() => {
        const h3Elements = Array.from(document.querySelectorAll('h3'));
        const h3 = h3Elements.find(el => el.textContent.includes('Ostatnie zadania synchronizacji'));
        if (h3) h3.scrollIntoView({ block: 'center' });
    });
    await page.waitForTimeout(1000);

    console.log('[3/7] Clicking "Wyczysc Stare Logi" button...');
    await page.locator('button:has-text("Wyczysc Stare Logi")').first().click();
    await page.waitForTimeout(1000);

    console.log('[4/7] Modal should be open. Taking screenshot...');
    await page.screenshot({
        path: '_TOOLS/screenshots/modal_before_confirm_2025-11-12.png',
        fullPage: false
    });

    console.log('[5/7] Checking Alpine.js context...');
    const alpineContext = await page.evaluate(() => {
        const button = Array.from(document.querySelectorAll('button')).find(b =>
            b.textContent.includes('Wyczysc Stare Logi')
        );
        if (!button) return { error: 'Button not found' };

        const wrapper = button.closest('[x-data]');
        if (!wrapper) return { error: 'x-data wrapper not found' };

        // Try to access Alpine data
        if (window.Alpine && wrapper._x_dataStack) {
            const data = wrapper._x_dataStack[0];
            return {
                hasAlpine: true,
                data: {
                    showModal: data.showModal,
                    selectedType: data.selectedType,
                    daysThreshold: data.daysThreshold,
                    clearAllAges: data.clearAllAges
                }
            };
        }

        return { error: 'Alpine not accessible' };
    });

    console.log('Alpine context:', JSON.stringify(alpineContext, null, 2));

    console.log('[6/7] Clicking CONFIRM button and monitoring Livewire calls...');

    // Clear previous requests
    livewireRequests.length = 0;

    await page.locator('button:has-text("Wyczysc Logi")').first().click();
    await page.waitForTimeout(3000); // Wait for potential Livewire call

    console.log(`\n[7/7] LIVEWIRE CALLS CAPTURED: ${livewireRequests.length}`);
    if (livewireRequests.length > 0) {
        console.log(JSON.stringify(livewireRequests, null, 2));
    } else {
        console.log('❌ NO Livewire requests were made!');
        console.log('\nThis means the $wire.clearOldLogs() call is NOT reaching the server.');
        console.log('Possible causes:');
        console.log('  1. JavaScript error preventing execution');
        console.log('  2. Incorrect $wire syntax');
        console.log('  3. Livewire component not properly initialized');
        console.log('  4. Alpine.js variable scope issue');
    }

    await page.screenshot({
        path: '_TOOLS/screenshots/modal_after_confirm_detailed_2025-11-12.png',
        fullPage: false
    });

    console.log('\n=== TEST COMPLETE ===');
    await browser.close();
})();
