// Check DOM parent relationship
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
        const main = document.querySelector('.category-form-main-container');
        const left = document.querySelector('.category-form-left-column');
        const right = document.querySelector('.category-form-right-column');

        return {
            mainExists: !!main,
            leftExists: !!left,
            rightExists: !!right,
            leftParentIsMain: left && left.parentElement === main,
            rightParentIsMain: right && right.parentElement === main,
            leftParentClass: left ? left.parentElement.className : 'N/A',
            rightParentClass: right ? right.parentElement.className : 'N/A',
            mainDirectChildren: main ? Array.from(main.children).map(c => c.className) : []
        };
    });

    console.log('\n=== DOM STRUCTURE CHECK ===\n');
    console.log('Main container exists:', result.mainExists);
    console.log('Left column exists:', result.leftExists);
    console.log('Right column exists:', result.rightExists);
    console.log('\nLeft column parent IS main container:', result.leftParentIsMain);
    console.log('Right column parent IS main container:', result.rightParentIsMain);
    console.log('\nLeft parent class:', result.leftParentClass);
    console.log('Right parent class:', result.rightParentClass);
    console.log('\nMain container direct children:');
    result.mainDirectChildren.forEach((className, i) => {
        console.log(`  [${i}]:`, className || '(no class)');
    });

    await browser.close();
})();