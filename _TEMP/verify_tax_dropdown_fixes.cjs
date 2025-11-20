// Verify Tax Rate Dropdown Fixes (3 fixes)
// 1. Type mismatch fix - WŁASNE instead of NIE ZMAPOWANE
// 2. No duplicate 23% options
// 3. Consistent colors (green inherited, orange custom)

const playwright = require('playwright');

(async () => {
    const browser = await playwright.chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log('\n=== TAX RATE DROPDOWN VERIFICATION ===\n');

    // Navigate to product edit
    console.log('[1/7] Navigating to product edit page...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit');
    await page.waitForLoadState('networkidle');
    console.log('✅ Page loaded');

    // Click Sklepy tab
    console.log('\n[2/7] Clicking Sklepy tab...');
    await page.click('button:has-text("Sklepy")');
    await page.waitForTimeout(2000);
    console.log('✅ Sklepy tab clicked');

    // Click first shop in list (B2B Test DEV)
    console.log('\n[3/7] Clicking shop: B2B Test DEV...');
    const shopCards = await page.locator('.shop-card').all();
    if (shopCards.length > 0) {
        await shopCards[0].click();
        await page.waitForTimeout(2000);
        console.log('✅ Shop card clicked');
    } else {
        console.log('❌ No shop cards found!');
        await browser.close();
        return;
    }

    // Find Tax Rate dropdown
    console.log('\n[4/7] Analyzing Tax Rate dropdown...');
    const taxDropdown = await page.locator('select#tax_rate');

    if (await taxDropdown.count() === 0) {
        console.log('❌ Tax Rate dropdown NOT found!');
        await browser.close();
        return;
    }

    const dropdownValue = await taxDropdown.inputValue();
    console.log(`Current dropdown value: "${dropdownValue}"`);

    // Get all options
    const options = await taxDropdown.locator('option').allTextContents();
    console.log('\n[5/7] Dropdown options:');
    options.forEach((opt, idx) => {
        console.log(`  ${idx + 1}. ${opt}`);
    });

    // CHECK FIX #2: No duplicate 23%
    console.log('\n[6/7] Checking for duplicate 23% options...');
    const options23 = options.filter(opt => opt.includes('23'));
    if (options23.length > 1) {
        console.log(`❌ FAIL: Found ${options23.length} options with "23%":`);
        options23.forEach(opt => console.log(`     - ${opt}`));
    } else {
        console.log(`✅ PASS: Only ${options23.length} option with "23%"`);
    }

    // Find status indicator
    console.log('\n[7/7] Checking status indicator...');
    const indicator = await page.locator('label:has-text("Stawka VAT") span.inline-flex').first();

    if (await indicator.count() === 0) {
        console.log('⚠️ Status indicator NOT found');
    } else {
        const indicatorText = await indicator.textContent();
        const indicatorClasses = await indicator.getAttribute('class');

        console.log(`Indicator text: "${indicatorText}"`);
        console.log(`Indicator classes: ${indicatorClasses}`);

        // CHECK FIX #1: WŁASNE instead of NIE ZMAPOWANE
        if (indicatorText.includes('NIE ZMAPOWANE')) {
            console.log('❌ FAIL: Still shows "NIE ZMAPOWANE" (type mismatch not fixed!)');
        } else if (indicatorText.includes('WŁASNE')) {
            console.log('✅ PASS: Shows "WŁASNE" (type mismatch fixed!)');
        } else if (indicatorText.includes('DZIEDZICZONE')) {
            console.log('✅ PASS: Shows "DZIEDZICZONE" (inherited from default)');
        }

        // CHECK FIX #3: Color consistency
        if (indicatorClasses.includes('status-label-inherited')) {
            console.log('✅ PASS: Uses .status-label-inherited (green color)');
        } else if (indicatorClasses.includes('status-label-different')) {
            console.log('✅ PASS: Uses .status-label-different (orange color)');
        } else if (indicatorClasses.includes('purple') || indicatorClasses.includes('yellow')) {
            console.log('❌ FAIL: Still uses old inline Tailwind colors (not CSS classes)');
        }
    }

    // Take screenshot
    console.log('\nTaking screenshot...');
    await page.screenshot({
        path: '_TOOLS/screenshots/tax_dropdown_verification_2025-11-17.png',
        fullPage: false
    });
    console.log('✅ Screenshot: _TOOLS/screenshots/tax_dropdown_verification_2025-11-17.png');

    console.log('\n=== VERIFICATION COMPLETE ===\n');

    await page.waitForTimeout(5000); // Keep browser open for 5s
    await browser.close();
})();
