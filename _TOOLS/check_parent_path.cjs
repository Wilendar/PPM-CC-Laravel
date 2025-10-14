// Check full parent path of right-column
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    await page.goto('https://ppm.mpptrade.pl/admin/products/4/edit', {
        waitUntil: 'networkidle',
        timeout: 30000
    });
    await page.waitForTimeout(2000);

    const result = await page.evaluate(() => {
        const right = document.querySelector('.category-form-right-column');

        if (!right) return { exists: false };

        const path = [];
        let current = right.parentElement;

        while (current && current !== document.body) {
            path.push({
                tag: current.tagName.toLowerCase(),
                classes: current.className || '(no class)',
                id: current.id || '(no id)'
            });
            current = current.parentElement;
        }

        return {
            exists: true,
            parentPath: path
        };
    });

    console.log('\n=== RIGHT-COLUMN PARENT PATH ===\n');

    if (!result.exists) {
        console.log('❌ Right-column NOT FOUND');
    } else {
        console.log('✅ Right-column found\n');
        console.log('Parent hierarchy (from immediate parent to body):');
        result.parentPath.forEach((p, i) => {
            console.log(`  [${i}] <${p.tag}> class="${p.classes}"`);
        });
    }

    await browser.close();
})();