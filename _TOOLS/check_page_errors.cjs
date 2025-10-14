const { chromium } = require('playwright');

async function checkPageErrors() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    const errors = [];
    const consoleMessages = [];

    // Capture console messages
    page.on('console', msg => {
        consoleMessages.push({
            type: msg.type(),
            text: msg.text()
        });
    });

    // Capture page errors
    page.on('pageerror', error => {
        errors.push({
            type: 'pageerror',
            message: error.message,
            stack: error.stack
        });
    });

    // Capture failed requests
    page.on('requestfailed', request => {
        errors.push({
            type: 'requestfailed',
            url: request.url(),
            failure: request.failure().errorText
        });
    });

    console.log('\n=== LOADING PAGE ===\n');

    try {
        await page.goto('https://ppm.mpptrade.pl/admin/products', {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        await page.waitForTimeout(2000);

        console.log('Page loaded successfully');
        console.log(`\nTitle: ${await page.title()}`);

        // Get HTML snippet
        const htmlSnippet = await page.evaluate(() => {
            return document.head.innerHTML.substring(0, 1000);
        });

        console.log('\n=== HEAD HTML SNIPPET (first 1000 chars) ===');
        console.log(htmlSnippet);

    } catch (e) {
        errors.push({
            type: 'navigation',
            message: e.message
        });
    }

    console.log('\n=== ERRORS ===');
    if (errors.length === 0) {
        console.log('No errors detected');
    } else {
        errors.forEach((err, i) => {
            console.log(`\n${i + 1}. ${err.type}:`);
            console.log(JSON.stringify(err, null, 2));
        });
    }

    console.log('\n=== CONSOLE MESSAGES (errors only) ===');
    const errorMessages = consoleMessages.filter(m => m.type === 'error' || m.type === 'warning');
    if (errorMessages.length === 0) {
        console.log('No console errors');
    } else {
        errorMessages.forEach(msg => {
            console.log(`[${msg.type}] ${msg.text}`);
        });
    }

    await browser.close();
}

checkPageErrors().catch(console.error);
