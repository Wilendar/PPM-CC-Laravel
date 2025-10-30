/**
 * Manual Modal Testing Tool - Interactive Browser Session
 * Opens browser for manual testing of modal interactions
 */

const { chromium } = require('playwright');

const args = process.argv.slice(2);
const url = args[0] || 'https://ppm.mpptrade.pl/admin/variants';
const selector = args[1] || 'button.btn-enterprise-sm';

(async () => {
    console.log('=== MANUAL MODAL TESTING ===');
    console.log(`URL: ${url}`);
    console.log(`Selector: ${selector}`);
    console.log('\nOpening browser in headed mode for manual testing...\n');

    const browser = await chromium.launch({
        headless: false,  // Show browser
        slowMo: 100       // Slow down actions
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    try {
        // Load page
        console.log('Loading page...');
        await page.goto(url, { waitUntil: 'networkidle', timeout: 15000 });
        console.log('✅ Page loaded');

        // Find and click trigger
        console.log(`\nLooking for trigger: ${selector}`);
        const trigger = await page.locator(selector).first();

        if (!await trigger.isVisible()) {
            console.log('❌ Trigger not found');
            return;
        }

        console.log('✅ Trigger found - clicking...');
        await trigger.click();
        await page.waitForTimeout(1000);

        console.log('\n✅ Modal opened - Browser ready for manual testing');
        console.log('\n📋 MANUAL TEST CHECKLIST:');
        console.log('  1. Press ESC key - does modal close?');
        console.log('  2. Re-open modal and click dark overlay - does modal close?');
        console.log('  3. Open browser console (F12) and check for errors');
        console.log('\nBrowser will stay open for manual testing...');
        console.log('Press Ctrl+C in terminal to close browser\n');

        // Wait indefinitely for manual testing
        await page.waitForTimeout(600000); // 10 minutes

    } catch (error) {
        console.error('ERROR:', error.message);
    } finally {
        console.log('\nClosing browser...');
        await browser.close();
    }
})();
