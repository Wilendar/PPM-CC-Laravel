const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
    await page.waitForSelector('[wire\\:id]', { timeout: 15000 });
    await page.waitForTimeout(2000);

    const shopTab = page.locator('button:has-text("B2B Test DEV")').first();
    await shopTab.click();
    await page.waitForTimeout(3000);

    // Check HTML attributes
    const checkboxInfo = await page.evaluate(() => {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][x-model="isSelected"]');
        const samples = Array.from(checkboxes).slice(0, 5).map(cb => ({
            id: cb.id,
            disabled: cb.disabled,
            hasDisabledAttr: cb.hasAttribute('disabled'),
            wireLoading: cb.getAttribute('wire:loading.attr'),
            classList: Array.from(cb.classList).join(' ')
        }));
        return samples;
    });

    console.log('=== CHECKBOX HTML ATTRIBUTES ===');
    console.log(JSON.stringify(checkboxInfo, null, 2));

    await page.waitForTimeout(5000);
    await browser.close();
})();
