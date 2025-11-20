const { chromium } = require('playwright');

(async () => {
    console.log('=== QUICK VERIFICATION: HTTP 500 FIX ===\n');

    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    // Collect console errors
    const consoleMessages = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleMessages.push(msg.text());
        }
    });

    try {
        // Navigate directly to product page
        console.log('[1/4] Navigating to product 11033...');
        const response = await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit', {
            waitUntil: 'networkidle',
            timeout: 60000
        });

        // Check HTTP status
        const status = response.status();
        console.log(`   HTTP Status: ${status === 200 ? '✅ 200 OK' : '❌ ' + status}`);

        if (status !== 200) {
            console.log('\n🚨 CRITICAL: Page returned HTTP ' + status);
            await page.screenshot({ path: '_TOOLS/screenshots/verification_viewport_' + new Date().toISOString().replace(/:/g, '-').slice(0, 19) + '.png' });
            await browser.close();
            process.exit(1);
        }

        await page.waitForTimeout(3000);

        // Screenshot BEFORE
        console.log('\n[2/4] Screenshot BEFORE clicking shop...');
        await page.screenshot({
            path: '_TOOLS/screenshots/verification_viewport_' + new Date().toISOString().replace(/:/g, '-').slice(0, 19) + '.png',
            fullPage: false
        });
        console.log('   ✅ Screenshot saved');

        // Find and click shop badge
        console.log('\n[3/4] Looking for shop badge "Test KAYO"...');
        const shopBadge = page.locator('button:has-text("Test KAYO")').first();
        const badgeCount = await shopBadge.count();

        if (badgeCount === 0) {
            console.log('   ⚠️ Shop badge not found - checking if logged in...');
            const loginForm = await page.locator('input[name="email"]').count();
            if (loginForm > 0) {
                console.log('   ℹ️ Login required - please login manually');
                console.log('   Keeping browser open for manual testing...\n');
                await page.waitForTimeout(120000); // 2 minutes
                await browser.close();
                return;
            }
        } else {
            console.log(`   ✅ Found ${badgeCount} shop badge(s)`);
            await shopBadge.click();
            await page.waitForTimeout(3000);
            console.log('   ✅ Shop badge clicked');
        }

        // Screenshot AFTER
        console.log('\n[4/4] Final screenshot...');
        await page.screenshot({
            path: '_TOOLS/screenshots/architecture_fix_AFTER_shop_click_' + new Date().toISOString().replace(/:/g, '-').slice(0, 19) + '.png',
            fullPage: true
        });
        console.log('   ✅ Screenshot saved');

        // Console errors summary
        console.log('\n=== CONSOLE ERRORS ===');
        const errors = consoleMessages.filter(msg => !msg.includes('favicon.ico'));
        console.log(`Total errors: ${errors.length === 0 ? '✅ 0 (clean!)' : '⚠️ ' + errors.length}`);
        if (errors.length > 0) {
            errors.forEach((err, i) => console.log(`${i + 1}. ${err}`));
        }

        console.log('\n✅ VERIFICATION COMPLETE - HTTP 200 OK');
        console.log('Browser pozostaje otwarty - sprawdź manualnie kategorie.');
        console.log('Naciśnij CTRL+C aby zamknąć.\n');

        await page.waitForTimeout(120000); // Keep open 2 minutes

    } catch (error) {
        console.log('\n❌ ERROR:', error.message);
        await page.screenshot({ path: '_TOOLS/screenshots/verification_ERROR_' + new Date().toISOString().replace(/:/g, '-').slice(0, 19) + '.png' });
    } finally {
        await browser.close();
    }
})();
