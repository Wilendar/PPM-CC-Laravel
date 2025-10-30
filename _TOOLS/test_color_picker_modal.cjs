#!/usr/bin/env node
/**
 * Test Color Picker Modal - Dedicated test for AttributeValueManager color picker styling
 */

const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        console.log('=== COLOR PICKER MODAL TEST ===');
        console.log('URL: https://ppm.mpptrade.pl/admin/variants\n');

        // Navigate to page
        console.log('Loading page...');
        await page.goto('https://ppm.mpptrade.pl/admin/variants', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        console.log('✅ Page loaded\n');

        // Click "Wartości" button (Kolor group)
        console.log('Step 1: Clicking "Wartości" button...');
        await page.click('button:has-text("Wartości")');
        await page.waitForTimeout(1500);
        console.log('✅ Wartości modal opened\n');

        // Click "Dodaj Wartosc" button
        console.log('Step 2: Clicking "Dodaj Wartosc" button...');
        await page.click('button:has-text("Dodaj Wartosc")');
        await page.waitForTimeout(2000);
        console.log('✅ Add Value form opened\n');

        // Take screenshot
        const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
        const screenshotPath = `D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel\\_TOOLS\\screenshots\\color_picker_modal_${timestamp}.png`;

        await page.screenshot({
            path: screenshotPath,
            fullPage: false
        });

        console.log('📸 Screenshot saved:', screenshotPath);

        // Analyze color picker component
        console.log('\n=== COLOR PICKER ANALYSIS ===');

        const colorPickerExists = await page.locator('.color-picker-container').count();
        console.log('Color picker container found:', colorPickerExists > 0 ? '✅ YES' : '❌ NO');

        if (colorPickerExists > 0) {
            // Check color picker component styling
            const componentBg = await page.locator('.color-picker-component').evaluate(el => {
                const styles = window.getComputedStyle(el);
                return {
                    backgroundColor: styles.backgroundColor,
                    padding: styles.padding,
                    borderRadius: styles.borderRadius,
                    gap: styles.gap
                };
            });

            console.log('\n🎨 Color Picker Component Styles:');
            console.log('  Background:', componentBg.backgroundColor);
            console.log('  Padding:', componentBg.padding);
            console.log('  Border Radius:', componentBg.borderRadius);
            console.log('  Gap:', componentBg.gap);

            // Check input styling
            const inputBg = await page.locator('.color-input').evaluate(el => {
                const styles = window.getComputedStyle(el);
                return {
                    backgroundColor: styles.backgroundColor,
                    color: styles.color,
                    borderColor: styles.borderColor
                };
            });

            console.log('\n📝 Color Input Styles:');
            console.log('  Background:', inputBg.backgroundColor);
            console.log('  Text Color:', inputBg.color);
            console.log('  Border Color:', inputBg.borderColor);

            // Check vanilla-colorful presence
            const vanillaColorfulExists = await page.locator('hex-color-picker').count();
            console.log('\n🎨 vanilla-colorful Web Component:', vanillaColorfulExists > 0 ? '✅ LOADED' : '❌ NOT FOUND');
        }

        console.log('\n✅ TEST COMPLETE');

    } catch (error) {
        console.error('❌ ERROR:', error.message);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
