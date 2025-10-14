const { chromium } = require('playwright');

async function checkHTMLLinks() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log('\n=== CHECKING HTML CSS LINKS ===\n');

    await page.goto('https://ppm.mpptrade.pl/admin/products', {
        waitUntil: 'networkidle',
        timeout: 30000
    });

    const cssLinks = await page.evaluate(() => {
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        return Array.from(links).map(link => ({
            href: link.href,
            media: link.media || 'all'
        }));
    });

    console.log('CSS Links in <head>:');
    cssLinks.forEach((link, i) => {
        console.log(`${i + 1}. ${link.href}`);
    });

    await browser.close();
}

checkHTMLLinks().catch(console.error);
