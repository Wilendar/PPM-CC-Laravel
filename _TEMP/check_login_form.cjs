/**
 * Quick check: Login form structure
 */

const playwright = require('playwright');

(async () => {
    const browser = await playwright.chromium.launch({ headless: false });
    const page = await browser.newPage();

    try {
        console.log('Loading login page...');
        await page.goto('https://ppm.mpptrade.pl/admin/login');
        await page.waitForLoadState('networkidle');

        console.log('\n=== Checking form structure ===');

        // Try various selectors
        const selectors = [
            'input[type="email"]',
            'input[name="email"]',
            'input#email',
            'input[placeholder*="email" i]',
            'input[type="text"]',
            'input[name="username"]',
            'input[name="login"]'
        ];

        for (const selector of selectors) {
            const count = await page.locator(selector).count();
            console.log(`${selector}: ${count} found`);
        }

        // Get all input elements
        const allInputs = await page.locator('input').all();
        console.log(`\nTotal inputs found: ${allInputs.length}`);

        for (let i = 0; i < allInputs.length; i++) {
            const input = allInputs[i];
            const type = await input.getAttribute('type');
            const name = await input.getAttribute('name');
            const id = await input.getAttribute('id');
            const placeholder = await input.getAttribute('placeholder');
            console.log(`Input ${i}: type="${type}" name="${name}" id="${id}" placeholder="${placeholder}"`);
        }

        // Take screenshot
        await page.screenshot({
            path: '_TEMP/login_form_structure.png',
            fullPage: true
        });
        console.log('\n📸 Screenshot: login_form_structure.png');

    } finally {
        await page.waitForTimeout(3000);
        await browser.close();
    }
})();
