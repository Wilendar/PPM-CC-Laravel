const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

  try {
    console.log('1. Navigating to products...');
    await page.goto('https://ppm.mpptrade.pl/admin/products', { waitUntil: 'networkidle' });
    await page.waitForTimeout(3000);

    console.log('2. Opening import modal...');
    await page.click('button:has-text("Importuj z PrestaShop")');
    await page.waitForTimeout(2000);

    // Find the shop select using wire:model attribute
    console.log('3. Selecting B2B Test DEV shop...');
    const shopSelect = await page.locator('select[wire\\:model\\.live="importShopId"]').first();

    if (await shopSelect.count() > 0) {
      const options = await shopSelect.evaluate(sel => {
        return Array.from(sel.options).map(o => ({value: o.value, text: o.textContent.trim()}));
      });

      const b2bOption = options.find(o => o.text.includes('B2B Test DEV'));
      if (b2bOption) {
        await shopSelect.selectOption(b2bOption.value);
        await page.waitForTimeout(3000);

        console.log('4. Starting import...');
        const importBtn = await page.locator('button:has-text("Rozpocznij import")').first();

        if (await importBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
          await importBtn.click();
          console.log('5. Import triggered, waiting for progress bar...');
          await page.waitForTimeout(5000);

          // Check if progress bar appeared
          const progressBar = await page.locator('[wire\\:poll]').first();
          if (await progressBar.count() > 0) {
            console.log('6. Progress bar detected!');

            // Screenshot of progress bar
            await page.screenshot({
              path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_faza2_progress_bar.jpg',
              type: 'jpeg',
              quality: 85
            });

            // Wait for more progress
            await page.waitForTimeout(10000);

            // Try to find and click the expand button (chevron)
            const expandBtn = await page.locator('button:has(svg.rotate-180), button:has(path[d="M19 9l-7 7-7-7"])').first();
            if (await expandBtn.count() > 0) {
              console.log('7. Found expand button - clicking...');
              await expandBtn.click();
              await page.waitForTimeout(1000);

              await page.screenshot({
                path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_faza2_accordion_expanded.jpg',
                type: 'jpeg',
                quality: 85
              });
              console.log('8. Accordion expanded - screenshot saved!');
            } else {
              console.log('7. Expand button not found, taking final screenshot...');
            }

            // Final screenshot
            await page.screenshot({
              path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_faza2_final.jpg',
              type: 'jpeg',
              quality: 85,
              fullPage: true
            });
            console.log('9. Final screenshot saved!');
          } else {
            console.log('6. Progress bar not found yet...');
            await page.screenshot({
              path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_faza2_no_progress.jpg',
              type: 'jpeg',
              quality: 85,
              fullPage: true
            });
          }
        } else {
          console.log('5. Import button not visible...');
          await page.screenshot({
            path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_faza2_no_import_btn.jpg',
            type: 'jpeg',
            quality: 85,
            fullPage: true
          });
        }
      }
    }

    console.log('\n=== TEST COMPLETE ===');
  } catch (error) {
    console.error('ERROR:', error.message);
    await page.screenshot({ path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_faza2_error.jpg' });
  } finally {
    await browser.close();
  }
})();
