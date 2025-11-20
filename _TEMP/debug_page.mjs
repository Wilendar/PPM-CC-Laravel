import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.goto('https://ppm.mpptrade.pl/admin/shops');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000); // Więcej czasu na Livewire

    const debug = await page.evaluate(() => {
        const allH3 = Array.from(document.querySelectorAll('h3')).map(h => h.textContent.trim());
        return {
            h3Count: allH3.length,
            h3Texts: allH3,
            pageHTML: document.body.innerHTML.substring(0, 1000)
        };
    });

    console.log('H3 Count:', debug.h3Count);
    console.log('H3 Texts:', debug.h3Texts);
    
    await page.screenshot({ path: '_TOOLS/screenshots/debug_page_2025-11-12.png', fullPage: true });
    console.log('Screenshot saved');

    await page.waitForTimeout(3000);
    await browser.close();
})();
