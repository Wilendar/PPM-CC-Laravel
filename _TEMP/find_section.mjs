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
    
    // Czekaj dłużej na wire:poll
    console.log('Waiting for Livewire polling...');
    await page.waitForTimeout(8000);

    const result = await page.evaluate(() => {
        // Szukaj tekstu gdziekolwiek
        const bodyText = document.body.innerHTML;
        const hasRecentJobs = bodyText.includes('Ostatnie zadania') || bodyText.includes('Recent Sync');
        
        // Wszystkie h3
        const allH3 = Array.from(document.querySelectorAll('h3')).map(h => ({
            text: h.textContent.trim(),
            visible: h.offsetParent !== null
        }));
        
        // Scroll w dół
        window.scrollTo(0, document.body.scrollHeight);
        
        return {
            hasRecentJobsText: hasRecentJobs,
            h3Elements: allH3,
            bodyHeight: document.body.scrollHeight
        };
    });

    console.log('Has "Ostatnie zadania" text:', result.hasRecentJobsText);
    console.log('Body height:', result.bodyHeight);
    console.log('H3 elements:', result.h3Elements);
    
    await page.waitForTimeout(2000);
    await page.screenshot({ path: '_TOOLS/screenshots/find_section_2025-11-12.png', fullPage: true });
    console.log('Screenshot saved (full page, scrolled)');

    await page.waitForTimeout(3000);
    await browser.close();
})();
