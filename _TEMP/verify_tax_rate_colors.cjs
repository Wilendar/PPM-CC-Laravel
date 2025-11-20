// Tax Rate Field Color Verification
const playwright = require('playwright');

(async () => {
    const browser = await playwright.chromium.launch({ headless: false });
    const page = await browser.newPage();

    console.log('\n=== TAX RATE COLOR VERIFICATION ===\n');

    // Navigate to product edit page
    await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    console.log('✅ Product edit page loaded');

    // Click Sklepy tab
    const sklepyTab = await page.locator('button:has-text("Sklepy")');
    await sklepyTab.click();
    console.log('✅ Clicked Sklepy tab');

    // Wait for Livewire to load shop data
    await page.waitForTimeout(4000);

    // Scroll to Tax Rate field
    const taxRateLabel = await page.locator('label:has-text("Stawka VAT")');
    await taxRateLabel.scrollIntoViewIfNeeded();
    await page.waitForTimeout(1000);
    console.log('✅ Scrolled to Stawka VAT field\n');

    // Get field and label details
    const dropdown = await page.locator('select#tax_rate');
    const indicator = await page.locator('label:has-text("Stawka VAT") span.inline-flex').first();

    if (await dropdown.count() > 0) {
        const dropdownClasses = await dropdown.getAttribute('class');
        console.log('📦 DROPDOWN CLASSES:');
        console.log(`   ${dropdownClasses}\n`);

        // Check for status classes
        const hasInherited = dropdownClasses.includes('field-status-inherited');
        const hasDifferent = dropdownClasses.includes('field-status-different');
        const hasSame = dropdownClasses.includes('field-status-same');

        console.log('🎨 DROPDOWN STATUS CLASS:');
        if (hasInherited) {
            console.log('   ✅ field-status-inherited (should be PURPLE)');
        } else if (hasDifferent) {
            console.log('   ⚠️ field-status-different (ORANGE)');
        } else if (hasSame) {
            console.log('   ✅ field-status-same (GREEN)');
        } else {
            console.log('   ❌ NO STATUS CLASS!');
        }
    }

    if (await indicator.count() > 0) {
        const indicatorText = await indicator.textContent();
        const indicatorClasses = await indicator.getAttribute('class');

        console.log('\n📛 LABEL (Status Indicator):');
        console.log(`   Text: "${indicatorText}"`);
        console.log(`   Classes: ${indicatorClasses}\n`);

        const hasInheritedLabel = indicatorClasses.includes('status-label-inherited');
        const hasDifferentLabel = indicatorClasses.includes('status-label-different');
        const hasSameLabel = indicatorClasses.includes('status-label-same');

        console.log('🎨 LABEL STATUS CLASS:');
        if (hasInheritedLabel) {
            console.log('   ✅ status-label-inherited (should be PURPLE/GREEN?)');
        } else if (hasDifferentLabel) {
            console.log('   ⚠️ status-label-different (ORANGE)');
        } else if (hasSameLabel) {
            console.log('   ✅ status-label-same (GREEN)');
        } else {
            console.log('   ❌ NO STATUS CLASS!');
        }
    }

    // Get computed styles from browser
    const dropdownStyles = await dropdown.evaluate(el => {
        const computed = window.getComputedStyle(el);
        return {
            borderColor: computed.borderColor,
            boxShadow: computed.boxShadow,
            backgroundColor: computed.backgroundColor
        };
    });

    console.log('\n💅 DROPDOWN COMPUTED STYLES:');
    console.log(`   Border Color: ${dropdownStyles.borderColor}`);
    console.log(`   Box Shadow: ${dropdownStyles.boxShadow}`);
    console.log(`   Background: ${dropdownStyles.backgroundColor}\n`);

    // Color analysis
    const isPurple = dropdownStyles.boxShadow.includes('168, 85, 247') || // #a855f7
                     dropdownStyles.boxShadow.includes('139, 92, 246') || // #8b5cf6
                     dropdownStyles.borderColor.includes('168, 85, 247');

    const isOrange = dropdownStyles.boxShadow.includes('224, 172, 126') || // #e0ac7e
                     dropdownStyles.borderColor.includes('224, 172, 126');

    const isGreen = dropdownStyles.boxShadow.includes('5, 150, 105') || // #059669
                    dropdownStyles.borderColor.includes('5, 150, 105');

    console.log('🔍 COLOR DETECTION:');
    if (isPurple) {
        console.log('   ✅ PURPLE detected - CORRECT for DZIEDZICZONE!');
    } else if (isOrange) {
        console.log('   ⚠️ ORANGE detected - Wrong color!');
    } else if (isGreen) {
        console.log('   ❌ GREEN detected - OLD conflicting CSS!');
    } else {
        console.log('   ❓ Unknown color');
    }

    // Take focused screenshot
    console.log('\n📸 Taking focused screenshot...');
    const taxRateSection = await page.locator('label:has-text("Stawka VAT")').locator('..').locator('..');
    await taxRateSection.screenshot({
        path: '_TOOLS/screenshots/tax_rate_field_colors_2025-11-17.png'
    });
    console.log('✅ Screenshot: _TOOLS/screenshots/tax_rate_field_colors_2025-11-17.png');

    console.log('\n=== VERIFICATION COMPLETE ===\n');

    await page.waitForTimeout(3000);
    await browser.close();
})();
