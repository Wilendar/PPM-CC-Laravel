const { chromium } = require('@playwright/test');

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

    console.log('🔍 Searching for "Ostatnie zadania synchronizacji"...');
    const recentJobsHeader = await page.evaluate(() => {
        const h3 = Array.from(document.querySelectorAll('h3')).find(el => 
            el.textContent.includes('Ostatnie zadania synchronizacji')
        );
        if (h3) {
            return {
                found: true,
                text: h3.textContent.trim()
            };
        }
        return { found: false };
    });

    if (recentJobsHeader.found) {
        console.log('✅ Found header:', recentJobsHeader.text);

        // Scroll to header
        await page.evaluate(() => {
            const h3 = Array.from(document.querySelectorAll('h3')).find(el => 
                el.textContent.includes('Ostatnie zadania synchronizacji')
            );
            if (h3) h3.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        await page.waitForTimeout(1000);

        console.log('🔍 Looking for "Wyczyść Stare Logi" button...');
        const button = await page.evaluate(() => {
            const btn = Array.from(document.querySelectorAll('button')).find(el => 
                el.textContent.includes('Wyczysc Stare Logi') || el.textContent.includes('Wyczyść Stare Logi')
            );
            if (btn) {
                return {
                    found: true,
                    text: btn.textContent.trim(),
                    visible: btn.offsetParent !== null,
                    classes: btn.className,
                    title: btn.title
                };
            }
            return { found: false };
        });

        if (button.found) {
            console.log('✅ BUTTON FOUND!');
            console.log('   Text:', button.text);
            console.log('   Visible:', button.visible);
            console.log('   Title:', button.title);
            console.log('   Classes:', button.classes);

            const screenshotPath = '_TOOLS/screenshots/clear_button_verification_2025-11-12T10-25.png';
            await page.screenshot({ path: screenshotPath });
            console.log('📸 Screenshot saved:', screenshotPath);
        } else {
            console.log('❌ BUTTON NOT FOUND!');
            
            // Debug: show all buttons
            const allButtons = await page.evaluate(() => {
                return Array.from(document.querySelectorAll('button')).map(btn => ({
                    text: btn.textContent.trim().substring(0, 50),
                    visible: btn.offsetParent !== null
                })).filter(b => b.visible);
            });
            console.log('All visible buttons:', allButtons);
        }
    } else {
        console.log('❌ Header "Ostatnie zadania synchronizacji" NOT FOUND!');
    }

    await page.waitForTimeout(3000);
    await browser.close();
})();
