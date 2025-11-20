// Screenshot Tax Rate Dropdown - FAZA 5.2 UI Fix
// Capture dropdown in Shop Mode with dynamic color verification
// Date: 2025-11-14

const playwright = require('playwright');
const path = require('path');
const fs = require('fs');

(async () => {
    console.log('\n=== TAX RATE DROPDOWN SCREENSHOT (FAZA 5.2 UI Fix) ===\n');

    const browser = await playwright.chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true,
    });
    const page = await context.newPage();

    const url = 'https://ppm.mpptrade.pl/admin/products/11033/edit';
    console.log(`Loading: ${url}\n`);

    await page.goto(url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000); // Wait for Livewire

    console.log('✅ Page loaded');

    // Check if we're already in Shop Mode or need to switch
    const shopTabButton = await page.$('button:has-text("B2B Test DEV")');

    if (shopTabButton) {
        console.log('🔄 Switching to Shop Mode (B2B Test DEV)...');
        await shopTabButton.click();
        await page.waitForTimeout(1500); // Wait for Livewire to load shop data
        console.log('✅ Shop Mode activated');
    } else {
        console.log('⚠️  Shop tab button not found - may already be in Shop Mode');
    }

    // Navigate to Basic tab (where Tax Rate dropdown is)
    const basicTabButton = await page.$('button:has-text("Basic")');
    if (basicTabButton) {
        console.log('🔄 Navigating to Basic tab...');
        await basicTabButton.click();
        await page.waitForTimeout(500);
        console.log('✅ Basic tab active');
    }

    // Take screenshot of full page
    const screenshotsDir = path.join(__dirname, '..', '_TOOLS', 'screenshots');
    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir, { recursive: true });
    }

    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
    const fullPagePath = path.join(screenshotsDir, `tax_dropdown_dynamic_full_${timestamp}.png`);
    const viewportPath = path.join(screenshotsDir, `tax_dropdown_dynamic_viewport_${timestamp}.png`);

    await page.screenshot({ path: fullPagePath, fullPage: true });
    console.log(`\n✅ Full page screenshot: ${fullPagePath}`);

    await page.screenshot({ path: viewportPath, fullPage: false });
    console.log(`✅ Viewport screenshot: ${viewportPath}`);

    // Analyze Tax Rate dropdown
    console.log('\n--- TAX RATE DROPDOWN ANALYSIS ---');

    const taxRateSelect = await page.$('select#tax_rate');
    if (taxRateSelect) {
        const classes = await taxRateSelect.evaluate(el => el.className);
        const computedStyle = await taxRateSelect.evaluate(el => {
            const style = window.getComputedStyle(el);
            return {
                borderColor: style.borderColor,
                backgroundColor: style.backgroundColor,
                borderWidth: style.borderWidth,
            };
        });

        console.log(`Classes: ${classes}`);
        console.log(`Border Color: ${computedStyle.borderColor}`);
        console.log(`Background Color: ${computedStyle.backgroundColor}`);
        console.log(`Border Width: ${computedStyle.borderWidth}`);

        // Check for dynamic classes
        if (classes.includes('border-green-600')) {
            console.log('✅ GREEN BORDER DETECTED (matches PrestaShop mapping)');
        } else if (classes.includes('border-yellow-600')) {
            console.log('⚠️  YELLOW BORDER DETECTED (unmapped/override)');
        } else {
            console.log('ℹ️  No dynamic border class detected (default state)');
        }
    } else {
        console.log('⚠️  Tax Rate dropdown not found');
    }

    // Check indicator badge
    const indicatorBadge = await page.$('label:has-text("Stawka VAT") span[class*="bg-green"], label:has-text("Stawka VAT") span[class*="bg-yellow"]');
    if (indicatorBadge) {
        const badgeText = await indicatorBadge.textContent();
        const badgeClasses = await indicatorBadge.evaluate(el => el.className);
        console.log(`\nIndicator Badge: "${badgeText}"`);
        console.log(`Badge Classes: ${badgeClasses}`);
    }

    await browser.close();
    console.log('\n✅ SCREENSHOT COMPLETE\n');
})();
