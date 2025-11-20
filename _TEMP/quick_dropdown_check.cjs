// Quick Tax Rate Dropdown Options Check
const playwright = require('playwright');

(async () => {
    const browser = await playwright.chromium.launch({ headless: false });
    const page = await browser.newPage();

    console.log('\n=== QUICK TAX DROPDOWN CHECK (POST-CACHE-CLEAR) ===\n');

    await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit');
    await page.waitForLoadState('networkidle');
    console.log('✅ Page loaded');

    // Click Sklepy tab
    await page.click('button:has-text("Sklepy")');
    await page.waitForTimeout(3000);
    console.log('✅ Sklepy tab clicked');

    // Get dropdown options
    const options = await page.locator('select#tax_rate option').allTextContents();
    console.log(`\n📋 Dropdown has ${options.length} options:\n`);
    options.forEach((opt, idx) => {
        const clean = opt.replace(/\s+/g, ' ').trim();
        console.log(`  [${idx}] ${clean}`);
    });

    // Count 23% options
    const count23 = options.filter(opt => opt.includes('23')).length;
    console.log(`\n🔍 Options containing "23": ${count23}`);

    if (count23 > 1) {
        console.log('❌ FAIL: Duplicate 23% options still present!');
    } else if (count23 === 1) {
        console.log('✅ PASS: Only one 23% option (Fix #2 working!)');
    } else {
        console.log('⚠️ WARNING: No 23% options found');
    }

    await page.waitForTimeout(3000);
    await browser.close();
})();
