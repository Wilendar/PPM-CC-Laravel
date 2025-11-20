/**
 * SIMPLIFIED: Check if sidepanel bulk action buttons exist
 */

const { chromium } = require('playwright');

(async () => {
    console.log('=== CHECKING SIDEPANEL BUTTONS ===\n');

    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    // Login
    console.log('[1/4] Logging in...');
    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForSelector('text=Dashboard', { timeout: 15000 });
    console.log('OK\n');

    // Navigate to product
    console.log('[2/4] Loading product 11033...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/11033/edit');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    console.log('OK\n');

    // Check buttons
    console.log('[3/4] Checking for buttons...\n');

    const btn1 = await page.locator('button', { hasText: 'Aktualizuj sklepy' }).count();
    const btn2 = await page.locator('button', { hasText: 'Wczytaj ze sklepów' }).count();

    console.log('Button "Aktualizuj sklepy": ' + (btn1 > 0 ? 'FOUND' : 'NOT FOUND'));
    console.log('Button "Wczytaj ze sklepów": ' + (btn2 > 0 ? 'FOUND' : 'NOT FOUND'));
    console.log('');

    if (btn1 === 0 && btn2 === 0) {
        console.log('CRITICAL: Both buttons MISSING from DOM!');
        console.log('');
        console.log('ROOT CAUSE HYPOTHESIS: Blade file not deployed or conditional rendering issue');
        await browser.close();
        return;
    }

    // Check wire:click
    console.log('[4/4] Checking wire:click attributes...\n');

    if (btn1 > 0) {
        const wireClick1 = await page.locator('button', { hasText: 'Aktualizuj sklepy' }).first().getAttribute('wire:click');
        console.log('Button #1 wire:click: ' + (wireClick1 || 'MISSING'));
    }

    if (btn2 > 0) {
        const wireClick2 = await page.locator('button', { hasText: 'Wczytaj ze sklepów' }).first().getAttribute('wire:click');
        console.log('Button #2 wire:click: ' + (wireClick2 || 'MISSING'));
    }

    console.log('\nKeeping browser open for 10s...');
    await page.waitForTimeout(10000);
    await browser.close();
})();
