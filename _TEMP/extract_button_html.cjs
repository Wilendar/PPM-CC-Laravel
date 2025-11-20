const { chromium } = require('playwright');

(async () => {
    console.log('Extracting "Zapisz zmiany" button HTML...\n');

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    try {
        // Navigate to product
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit', {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        // Click shop tab
        await page.waitForSelector('.shop-tab-active, .shop-tab-inactive', { timeout: 10000 });
        const shopTab = await page.locator('button').filter({ hasText: /B2B.*Test.*DEV/i }).first();
        await shopTab.click();
        await page.waitForTimeout(2000);

        // Find "Zapisz zmiany" button
        const saveButton = await page.locator('button').filter({ hasText: /Zapisz zmiany/i }).first();

        // Extract HTML
        const buttonHTML = await saveButton.evaluate(el => el.outerHTML);
        const buttonWireClick = await saveButton.getAttribute('wire:click');
        const buttonType = await saveButton.getAttribute('type');
        const buttonXOnClick = await saveButton.getAttribute('x-on:click');
        const buttonOnClick = await saveButton.getAttribute('onclick');

        console.log('BUTTON HTML:');
        console.log(buttonHTML);
        console.log('\nBUTTON ATTRIBUTES:');
        console.log('  wire:click:', buttonWireClick);
        console.log('  type:', buttonType);
        console.log('  x-on:click:', buttonXOnClick);
        console.log('  onclick:', buttonOnClick);

    } catch (error) {
        console.log('ERROR:', error.message);
    }

    await browser.close();
})();
