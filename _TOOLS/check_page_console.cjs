#!/usr/bin/env node
/**
 * Check console errors for specific page
 */

const { chromium } = require('playwright');

const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/products/categories';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    const errors = [];
    const warnings = [];
    const cssFiles = [];

    page.on('console', msg => {
        if (msg.type() === 'error') errors.push(msg.text());
        if (msg.type() === 'warning') warnings.push(msg.text());
    });

    page.on('response', response => {
        const url = response.url();
        if (url.includes('.css')) {
            cssFiles.push({
                url: url,
                status: response.status(),
                ok: response.ok()
            });
        }
    });

    try {
        console.log(`Loading: ${url}\n`);
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
        await page.waitForTimeout(2000);

        console.log('=== CSS FILES LOADED ===');
        cssFiles.forEach(file => {
            const status = file.ok ? '✅' : '❌';
            const filename = file.url.split('/').pop();
            console.log(`${status} ${file.status} - ${filename}`);
        });

        console.log('\n=== CONSOLE ERRORS ===');
        if (errors.length === 0) {
            console.log('✅ No errors');
        } else {
            errors.forEach(err => console.log('❌', err));
        }

        console.log('\n=== CONSOLE WARNINGS ===');
        if (warnings.length === 0) {
            console.log('✅ No warnings');
        } else {
            warnings.slice(0, 5).forEach(warn => console.log('⚠️', warn));
        }

    } catch (error) {
        console.error('ERROR:', error.message);
    } finally {
        await browser.close();
    }
})();
