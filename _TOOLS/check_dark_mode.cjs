#!/usr/bin/env node
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    await page.goto('https://ppm.mpptrade.pl/admin/products/10969/edit', {
        waitUntil: 'networkidle'
    });

    // Check if dark class is applied
    const htmlClass = await page.locator('html').getAttribute('class');
    const bodyClass = await page.locator('body').getAttribute('class');

    // Check localStorage
    const localStorageTheme = await page.evaluate(() => {
        return localStorage.getItem('theme_dark');
    });

    console.log('=== DARK MODE CHECK ===');
    console.log('HTML class:', htmlClass);
    console.log('Body class:', bodyClass);
    console.log('localStorage theme_dark:', localStorageTheme);
    console.log('Dark mode active:', htmlClass && htmlClass.includes('dark') ? '✅ YES' : '❌ NO');

    await browser.close();
})();
