// Diagnose Tax Rate Dropdown CSS Classes
const playwright = require('playwright');

(async () => {
    const browser = await playwright.chromium.launch({ headless: false });
    const page = await browser.newPage();

    console.log('\n=== TAX RATE CSS CLASSES DIAGNOSTIC ===\n');

    await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit');
    await page.waitForLoadState('networkidle');
    console.log('✅ Page loaded');

    // Click Sklepy tab
    await page.click('button:has-text("Sklepy")');
    await page.waitForTimeout(3000);
    console.log('✅ Sklepy tab clicked');

    // Wait for Tax Rate field to be visible (Livewire needs time to load shop data)
    await page.waitForSelector('select#tax_rate', { timeout: 10000 });
    console.log('✅ Tax Rate field loaded\n');

    // Get indicator (label) classes
    const indicator = await page.locator('label:has-text("Stawka VAT") span.inline-flex').first();

    if (await indicator.count() > 0) {
        const indicatorText = await indicator.textContent();
        const indicatorClasses = await indicator.getAttribute('class');

        console.log('📛 LABEL (Status Indicator):');
        console.log(`   Text: "${indicatorText}"`);
        console.log(`   Classes: ${indicatorClasses}\n`);
    }

    // Get dropdown classes
    const dropdown = await page.locator('select#tax_rate');

    if (await dropdown.count() > 0) {
        const dropdownClasses = await dropdown.getAttribute('class');

        console.log('📦 DROPDOWN (Input Field):');
        console.log(`   Classes: ${dropdownClasses}\n`);

        // Check for specific CSS classes
        console.log('🔍 CSS CLASS ANALYSIS:\n');

        if (dropdownClasses.includes('field-status-inherited')) {
            console.log('   ✅ Dropdown has: field-status-inherited (GREEN border)');
        } else if (dropdownClasses.includes('field-status-different')) {
            console.log('   ✅ Dropdown has: field-status-different (ORANGE border)');
        } else if (dropdownClasses.includes('field-status-same')) {
            console.log('   ✅ Dropdown has: field-status-same (GREEN border)');
        } else if (dropdownClasses.includes('border-green')) {
            console.log('   ⚠️ Dropdown has: Tailwind border-green (OLD hardcoded)');
        } else if (dropdownClasses.includes('border-orange')) {
            console.log('   ⚠️ Dropdown has: Tailwind border-orange (OLD hardcoded)');
        } else {
            console.log('   ❌ Dropdown has: NO recognized status class!');
        }

        // Check indicator classes
        if (indicatorClasses.includes('status-label-inherited')) {
            console.log('   ✅ Label has: status-label-inherited (GREEN)');
        } else if (indicatorClasses.includes('status-label-different')) {
            console.log('   ✅ Label has: status-label-different (ORANGE)');
        } else if (indicatorClasses.includes('status-label-same')) {
            console.log('   ✅ Label has: status-label-same (GREEN)');
        }

        // Color consistency check
        console.log('\n🎨 COLOR CONSISTENCY CHECK:\n');

        const labelGreen = indicatorClasses.includes('status-label-inherited') || indicatorClasses.includes('status-label-same');
        const fieldGreen = dropdownClasses.includes('field-status-inherited') || dropdownClasses.includes('field-status-same');

        const labelOrange = indicatorClasses.includes('status-label-different');
        const fieldOrange = dropdownClasses.includes('field-status-different');

        if ((labelGreen && fieldGreen) || (labelOrange && fieldOrange)) {
            console.log('   ✅ PASS: Label and field colors MATCH!');
        } else {
            console.log('   ❌ FAIL: Label and field colors DO NOT MATCH!');
            console.log(`      Label: ${labelGreen ? 'GREEN' : (labelOrange ? 'ORANGE' : 'UNKNOWN')}`);
            console.log(`      Field: ${fieldGreen ? 'GREEN' : (fieldOrange ? 'ORANGE' : 'UNKNOWN')}`);
        }
    }

    // Take screenshot
    console.log('\n📸 Taking screenshot...');
    await page.screenshot({
        path: '_TOOLS/screenshots/tax_css_diagnostic_2025-11-17.png',
        fullPage: false
    });
    console.log('✅ Screenshot: _TOOLS/screenshots/tax_css_diagnostic_2025-11-17.png');

    console.log('\n=== DIAGNOSTIC COMPLETE ===\n');

    await page.waitForTimeout(3000);
    await browser.close();
})();
