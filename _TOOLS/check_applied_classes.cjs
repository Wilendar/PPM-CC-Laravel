const { chromium } = require('playwright');

async function checkAppliedClasses() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log('\n=== CHECKING APPLIED CLASSES ===\n');

    await page.goto('https://ppm.mpptrade.pl/admin/products', {
        waitUntil: 'networkidle',
        timeout: 30000
    });

    await page.waitForTimeout(2000);

    const analysis = await page.evaluate(() => {
        const gridContainer = document.querySelector('.pt-28');

        if (!gridContainer) {
            return { error: 'Grid container not found' };
        }

        const computed = window.getComputedStyle(gridContainer);

        return {
            element: 'Grid Container',
            htmlClasses: gridContainer.className,
            computedDisplay: computed.display,
            computedGridTemplateColumns: computed.gridTemplateColumns,
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            mediaQueryLg: window.matchMedia('(min-width: 1024px)').matches,
            cssFiles: Array.from(document.styleSheets).map(sheet => {
                try {
                    return sheet.href || 'inline';
                } catch (e) {
                    return 'CORS blocked';
                }
            })
        };
    });

    console.log(JSON.stringify(analysis, null, 2));

    await browser.close();
}

checkAppliedClasses().catch(console.error);
