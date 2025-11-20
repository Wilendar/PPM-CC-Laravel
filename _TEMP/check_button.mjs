import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    console.log('🔐 Logging in...');
    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    console.log('📄 Loading /admin/shops...');
    await page.goto('https://ppm.mpptrade.pl/admin/shops');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    console.log('🔍 Searching for Recent Sync Jobs section...');
    const result = await page.evaluate(() => {
        const h3 = Array.from(document.querySelectorAll('h3')).find(el => 
            el.textContent.includes('Ostatnie zadania synchronizacji')
        );
        
        if (!h3) return { headerFound: false };
        
        h3.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        const btn = Array.from(document.querySelectorAll('button')).find(el => 
            el.textContent.includes('Wyczysc') || el.textContent.includes('Wyczyść')
        );
        
        return {
            headerFound: true,
            buttonFound: !!btn,
            buttonText: btn ? btn.textContent.trim() : null,
            buttonVisible: btn ? btn.offsetParent !== null : false,
            buttonTitle: btn ? btn.title : null
        };
    });

    console.log('\n=== RESULTS ===');
    console.log('Header found:', result.headerFound);
    console.log('Button found:', result.buttonFound);
    if (result.buttonFound) {
        console.log('Button text:', result.buttonText);
        console.log('Button visible:', result.buttonVisible);
        console.log('Button title:', result.buttonTitle);
        
        await page.waitForTimeout(500);
        await page.screenshot({ path: '_TOOLS/screenshots/clear_button_2025-11-12.png' });
        console.log('✅ Screenshot saved!');
    }

    await page.waitForTimeout(2000);
    await browser.close();
})();
