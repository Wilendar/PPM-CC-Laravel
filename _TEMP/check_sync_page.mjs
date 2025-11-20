import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    console.log('📄 Loading /admin/shops/sync...');
    await page.goto('https://ppm.mpptrade.pl/admin/shops/sync');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000); // Livewire + wire:poll

    const result = await page.evaluate(() => {
        // Szukaj nagłówka
        const h3 = Array.from(document.querySelectorAll('h3')).find(el => 
            el.textContent.includes('Ostatnie zadania synchronizacji')
        );
        
        if (h3) {
            h3.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Szukaj przycisku
        const btn = Array.from(document.querySelectorAll('button')).find(el => {
            const text = el.textContent;
            return text.includes('Wyczysc') || text.includes('Wyczyść') || text.includes('Stare Logi');
        });
        
        return {
            headerFound: !!h3,
            headerText: h3 ? h3.textContent.trim() : null,
            buttonFound: !!btn,
            buttonText: btn ? btn.textContent.trim() : null,
            buttonVisible: btn ? btn.offsetParent !== null : false,
            buttonTitle: btn ? btn.getAttribute('title') : null,
            buttonClasses: btn ? btn.className : null
        };
    });

    console.log('\n=== RESULTS ===');
    console.log('✅ Header found:', result.headerFound);
    if (result.headerFound) {
        console.log('   Text:', result.headerText);
    }
    console.log('✅ Button found:', result.buttonFound);
    if (result.buttonFound) {
        console.log('   Text:', result.buttonText);
        console.log('   Visible:', result.buttonVisible);
        console.log('   Title:', result.buttonTitle);
        console.log('   Classes:', result.buttonClasses);
    }
    
    await page.waitForTimeout(1000);
    await page.screenshot({ path: '_TOOLS/screenshots/sync_page_with_button_2025-11-12.png' });
    console.log('\n📸 Screenshot saved!');

    await page.waitForTimeout(3000);
    await browser.close();
})();
