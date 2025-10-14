const { chromium } = require('playwright');

async function checkStyles() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();
    
    await page.goto('https://ppm.mpptrade.pl/admin/products', {
        waitUntil: 'networkidle',
        timeout: 30000
    });
    
    await page.waitForTimeout(1500);
    
    // Find sidebar by looking for aside element
    const sidebarStyles = await page.evaluate(() => {
        const sidebar = document.querySelector('aside');

        if (!sidebar) {
            return {
                error: 'Sidebar <aside> element not found',
                foundAsideTags: document.querySelectorAll('aside').length
            };
        }
        
        const computed = window.getComputedStyle(sidebar);
        
        return {
            position: computed.position,
            top: computed.top,
            left: computed.left,
            bottom: computed.bottom,
            width: computed.width,
            zIndex: computed.zIndex,
            transform: computed.transform,
            display: computed.display,
            classes: sidebar.className.substring(0, 200),
            viewportWidth: window.innerWidth
        };
    });
    
    console.log('\n=== SIDEBAR COMPUTED STYLES ===\n');
    console.log(JSON.stringify(sidebarStyles, null, 2));
    
    await browser.close();
}

checkStyles();
