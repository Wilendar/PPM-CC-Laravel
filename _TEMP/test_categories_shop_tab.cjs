const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
    const page = await context.newPage();

    console.log('Logging in...');
    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin');

    console.log('Opening product 11034...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
    await page.waitForTimeout(2000);

    console.log('Clicking B2B Test DEV button...');
    await page.click('text=B2B Test DEV');
    await page.waitForTimeout(3000);

    console.log('Taking screenshot...');
    await page.screenshot({
        path: '_TOOLS/screenshots/product_11034_shop_tab_categories.png',
        fullPage: true
    });

    console.log('Success! Screenshot saved.');

    await page.waitForTimeout(5000);
    await browser.close();
})();
