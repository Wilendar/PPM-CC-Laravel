// Final Verification: All 3 Tax Rate Dropdown Fixes
// 1. Type mismatch fix - WŁASNE instead of NIE ZMAPOWANE
// 2. No duplicate 23% options
// 3. Color consistency - label matches input field

const playwright = require('playwright');

(async () => {
    const browser = await playwright.chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log('\n=== FINAL TAX RATE DROPDOWN VERIFICATION ===\n');

    // Navigate to product edit
    console.log('[1/8] Navigating to product edit page...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit');
    await page.waitForLoadState('networkidle');
    console.log('✅ Page loaded');

    // Click Sklepy tab
    console.log('\n[2/8] Clicking Sklepy tab...');
    await page.click('button:has-text("Sklepy")');
    await page.waitForTimeout(2000);
    console.log('✅ Sklepy tab clicked');

    // Wait for shop cards to load
    console.log('\n[3/8] Waiting for shop cards...');
    await page.waitForSelector('.shop-comparison-card', { timeout: 10000 });
    const shopCards = await page.locator('.shop-comparison-card').all();
    console.log(`✅ Found ${shopCards.length} shop card(s)`);

    if (shopCards.length === 0) {
        console.log('❌ No shop cards found!');
        await browser.close();
        return;
    }

    // Click first shop card
    console.log('\n[4/8] Clicking first shop card...');
    await shopCards[0].click();
    await page.waitForTimeout(2000);
    console.log('✅ Shop card clicked');

    // Find Tax Rate dropdown
    console.log('\n[5/8] Analyzing Tax Rate dropdown...');
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
    console.log('\n[6/8] Dropdown options:');
    options.forEach((opt, idx) => {
        console.log(`  ${idx + 1}. ${opt}`);
    });

    // CHECK FIX #2: No duplicate 23%
    console.log('\n[7/8] VERIFICATION CHECKS:');
    console.log('\nCHECK #2: Duplicate 23% options');
    const options23 = options.filter(opt => opt.includes('23'));
    if (options23.length > 1) {
        console.log(`❌ FAIL: Found ${options23.length} options with "23%":`);
        options23.forEach(opt => console.log(`     - ${opt}`));
    } else {
        console.log(`✅ PASS: Only ${options23.length} option with "23%"`);
    }

    // Find status indicator
    console.log('\nCHECK #1: Status indicator text (WŁASNE vs NIE ZMAPOWANE)');
    const indicator = await page.locator('label:has-text("Stawka VAT") span.inline-flex').first();

    if (await indicator.count() === 0) {
        console.log('⚠️ Status indicator NOT found');
    } else {
        const indicatorText = await indicator.textContent();
        const indicatorClasses = await indicator.getAttribute('class');

        console.log(`Indicator text: "${indicatorText}"`);
        console.log(`Indicator classes: ${indicatorClasses}`);

        if (indicatorText.includes('NIE ZMAPOWANE')) {
            console.log('❌ FAIL: Still shows "NIE ZMAPOWANE" (type mismatch not fixed!)');
        } else if (indicatorText.includes('WŁASNE')) {
            console.log('✅ PASS: Shows "WŁASNE" (type mismatch fixed!)');
        } else if (indicatorText.includes('DZIEDZICZONE')) {
            console.log('✅ PASS: Shows "DZIEDZICZONE" (inherited from default)');
        }

        // CHECK FIX #3: Color consistency
        console.log('\nCHECK #3: Color consistency (label vs input field)');

        // Get dropdown classes
        const dropdownClasses = await taxDropdown.getAttribute('class');
        console.log(`Dropdown classes: ${dropdownClasses}`);

        if (indicatorClasses.includes('status-label-inherited') && dropdownClasses.includes('field-status-inherited')) {
            console.log('✅ PASS: Label (.status-label-inherited) matches field (.field-status-inherited) - GREEN');
        } else if (indicatorClasses.includes('status-label-different') && dropdownClasses.includes('field-status-different')) {
            console.log('✅ PASS: Label (.status-label-different) matches field (.field-status-different) - ORANGE');
        } else if (indicatorClasses.includes('status-label-same') && dropdownClasses.includes('field-status-same')) {
            console.log('✅ PASS: Label (.status-label-same) matches field (.field-status-same) - GREEN');
        } else {
            console.log('❌ FAIL: Label and field classes DO NOT MATCH!');
            console.log(`   Label has: ${indicatorClasses}`);
            console.log(`   Field has: ${dropdownClasses}`);
        }
    }

    // Take screenshot
    console.log('\n[8/8] Taking screenshot...');
    await page.screenshot({
        path: '_TOOLS/screenshots/tax_dropdown_final_verification_2025-11-17.png',
        fullPage: false
    });
    console.log('✅ Screenshot: _TOOLS/screenshots/tax_dropdown_final_verification_2025-11-17.png');

    console.log('\n=== VERIFICATION COMPLETE ===\n');

    await page.waitForTimeout(3000);
    await browser.close();
})();
