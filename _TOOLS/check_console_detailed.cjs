const { chromium } = require('playwright');

(async () => {
    const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/products/edit/10969';
    const email = process.argv[3] || 'admin@mpptrade.pl';
    const password = process.argv[4] || 'Admin123!MPP';

    console.log(`Loading: ${url}\n`);

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    // Collect ALL console messages
    const consoleMessages = {
        log: [],
        info: [],
        warn: [],
        error: [],
        debug: []
    };

    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();

        if (consoleMessages[type]) {
            consoleMessages[type].push(text);
        } else {
            consoleMessages.log.push(`[${type}] ${text}`);
        }
    });

    // Collect network errors
    const networkErrors = [];
    page.on('requestfailed', request => {
        networkErrors.push({
            url: request.url(),
            error: request.failure().errorText
        });
    });

    // Collect page errors
    const pageErrors = [];
    page.on('pageerror', error => {
        pageErrors.push(error.toString());
    });

    // Login
    try {
        await page.goto('https://ppm.mpptrade.pl/login', { waitUntil: 'networkidle', timeout: 30000 });
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        // Navigate to product edit page
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

        // Wait for Livewire to initialize
        await page.waitForTimeout(3000);

        // Try clicking "Warianty" tab if exists
        const variantsTabExists = await page.evaluate(() => {
            // Try multiple selectors
            const selectors = [
                'button:has-text("Warianty")',
                '[x-on\\:click*="warianty"]',
                '.tab-button:has-text("Warianty")'
            ];

            for (const selector of selectors) {
                const tab = document.querySelector(selector);
                if (tab) {
                    tab.click();
                    return true;
                }
            }
            return false;
        });

        if (variantsTabExists) {
            console.log('✅ Clicked "Warianty" tab\n');
            await page.waitForTimeout(2000);
        } else {
            console.log('⚠️ Could not find "Warianty" tab\n');
        }

    } catch (error) {
        console.error('Navigation error:', error.message);
    }

    // Print results
    console.log('=== CONSOLE LOGS ===');
    if (consoleMessages.log.length > 0) {
        console.log('LOG:', consoleMessages.log.join('\n'));
    } else {
        console.log('✅ No log messages');
    }

    console.log('\n=== CONSOLE INFO ===');
    if (consoleMessages.info.length > 0) {
        console.log('INFO:', consoleMessages.info.join('\n'));
    } else {
        console.log('✅ No info messages');
    }

    console.log('\n=== CONSOLE WARNINGS ===');
    if (consoleMessages.warn.length > 0) {
        console.log('WARN:', consoleMessages.warn.join('\n'));
    } else {
        console.log('✅ No warnings');
    }

    console.log('\n=== CONSOLE ERRORS ===');
    if (consoleMessages.error.length > 0) {
        console.log('ERROR:', consoleMessages.error.join('\n'));
    } else {
        console.log('✅ No console errors');
    }

    console.log('\n=== PAGE ERRORS (JavaScript) ===');
    if (pageErrors.length > 0) {
        pageErrors.forEach((err, i) => {
            console.log(`${i + 1}. ${err}`);
        });
    } else {
        console.log('✅ No page errors');
    }

    console.log('\n=== NETWORK ERRORS ===');
    if (networkErrors.length > 0) {
        networkErrors.forEach((err, i) => {
            console.log(`${i + 1}. ${err.url}`);
            console.log(`   Error: ${err.error}`);
        });
    } else {
        console.log('✅ No network errors');
    }

    await browser.close();
})();
