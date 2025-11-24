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

    await page.screenshot({ path: 'screenshots/debug_disabled_state.png', fullPage: true });
    console.log('Screenshot saved to screenshots/debug_disabled_state.png');

    await page.waitForTimeout(5000);
    await browser.close();
})();
