const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ headless: false });
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    console.log('🔐 Logging in...');
    await page.goto('https://ppm.mpptrade.pl/login', { waitUntil: 'networkidle2' });
    await page.type('input[name="email"]', 'admin@mpptrade.pl');
    await page.type('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ waitUntil: 'networkidle2' });

    console.log('📄 Loading /admin/shops...');
    await page.goto('https://ppm.mpptrade.pl/admin/shops', { waitUntil: 'networkidle2' });
    await page.waitForTimeout(2000);

    console.log('🔍 Searching for "Ostatnie zadania synchronizacji"...');
    const recentJobsHeader = await page.evaluate(() => {
        const h3 = Array.from(document.querySelectorAll('h3')).find(el => 
            el.textContent.includes('Ostatnie zadania synchronizacji')
        );
        if (h3) {
            h3.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return {
                found: true,
                text: h3.textContent,
                html: h3.parentElement.innerHTML.substring(0, 500)
            };
        }
        return { found: false };
    });

    if (recentJobsHeader.found) {
        console.log('✅ Found header:', recentJobsHeader.text.trim());
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
                    classes: btn.className
                };
            }
            return { found: false };
        });

        if (button.found) {
            console.log('✅ BUTTON FOUND!');
            console.log('   Text:', button.text);
            console.log('   Visible:', button.visible);
            console.log('   Classes:', button.classes);

            const screenshotPath = '_TOOLS/screenshots/clear_button_verification_2025-11-12.png';
            await page.screenshot({ path: screenshotPath, fullPage: false });
            console.log('📸 Screenshot saved:', screenshotPath);
        } else {
            console.log('❌ BUTTON NOT FOUND!');
            console.log('Header HTML (first 500 chars):', recentJobsHeader.html);
        }
    } else {
        console.log('❌ Header "Ostatnie zadania synchronizacji" NOT FOUND!');
    }

    await page.waitForTimeout(2000);
    await browser.close();
})();
