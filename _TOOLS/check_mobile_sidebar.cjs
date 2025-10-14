const { chromium } = require('playwright');

async function checkMobile() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 375, height: 667 }, // iPhone SE size
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
    });
    const page = await context.newPage();

    await page.goto('https://ppm.mpptrade.pl/admin/products', {
        waitUntil: 'networkidle',
        timeout: 30000
    });

    await page.waitForTimeout(1500);

    // Check sidebar on mobile
    const sidebarStyles = await page.evaluate(() => {
        const sidebar = document.querySelector('aside');

        if (!sidebar) {
            return { error: 'Sidebar not found' };
        }

        const computed = window.getComputedStyle(sidebar);
        const rect = sidebar.getBoundingClientRect();

        return {
            position: computed.position,
            top: computed.top,
            left: computed.left,
            width: computed.width,
            transform: computed.transform,
            display: computed.display,
            visibility: computed.visibility,
            zIndex: computed.zIndex,
            classes: sidebar.className.substring(0, 200),
            viewportWidth: window.innerWidth,
            boundingRect: {
                x: rect.x,
                y: rect.y,
                width: rect.width,
                height: rect.height
            }
        };
    });

    console.log('\n=== MOBILE SIDEBAR STYLES (375px viewport) ===\n');
    console.log(JSON.stringify(sidebarStyles, null, 2));

    // Check if hamburger menu button exists
    const hamburgerExists = await page.evaluate(() => {
        const hamburger = document.querySelector('button[class*="lg:hidden"]');
        return hamburger !== null;
    });

    console.log('\n--- MOBILE MENU CHECK ---');
    console.log(`Hamburger button exists: ${hamburgerExists ? '✅' : '❌'}`);
    console.log(`Sidebar position: ${sidebarStyles.position} (expected: fixed)`);
    console.log(`Sidebar initially hidden: ${sidebarStyles.transform.includes('matrix') && sidebarStyles.boundingRect.x < 0 ? '✅' : '❌'}`);

    await browser.close();
}

checkMobile();
