// Check Prices & Stock Tabs UI
// 2025-11-07

const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ headless: false, defaultViewport: { width: 1920, height: 1080 } });
    const page = await browser.newPage();

    console.log('[1] Navigating to login page...');
    await page.goto('https://ppm.mpptrade.pl/login', { waitUntil: 'networkidle2' });

    console.log('[2] Logging in...');
    await page.type('input[type="email"]', 'admin@mpptrade.pl');
    await page.type('input[type="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ waitUntil: 'networkidle2' });

    console.log('[3] Navigating to product edit page...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/10980/edit', { waitUntil: 'networkidle2' });
    await page.waitForTimeout(2000);

    console.log('[4] Clicking "Ceny" tab...');
    const cenyTab = await page.evaluateHandle(() => {
        const tabs = Array.from(document.querySelectorAll('.tab-enterprise'));
        return tabs.find(tab => tab.textContent.includes('Ceny'));
    });
    if (cenyTab) {
        await cenyTab.asElement().click();
        await page.waitForTimeout(1000);
        console.log('   ✓ Ceny tab clicked');
        await page.screenshot({ path: '_TEMP/screenshot_ceny_tab.png', fullPage: true });
        console.log('   ✓ Screenshot: _TEMP/screenshot_ceny_tab.png');
    } else {
        console.log('   ✗ Ceny tab not found!');
    }

    console.log('[5] Clicking "Stany magazynowe" tab...');
    const stockTab = await page.evaluateHandle(() => {
        const tabs = Array.from(document.querySelectorAll('.tab-enterprise'));
        return tabs.find(tab => tab.textContent.includes('Stany magazynowe'));
    });
    if (stockTab) {
        await stockTab.asElement().click();
        await page.waitForTimeout(1000);
        console.log('   ✓ Stany magazynowe tab clicked');
        await page.screenshot({ path: '_TEMP/screenshot_stock_tab.png', fullPage: true });
        console.log('   ✓ Screenshot: _TEMP/screenshot_stock_tab.png');
    } else {
        console.log('   ✗ Stany magazynowe tab not found!');
    }

    console.log('[6] Done! Check screenshots in _TEMP/');
    await page.waitForTimeout(3000);
    await browser.close();
})();
