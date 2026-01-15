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
    console.log('3. Finding shop select with wire:model="importShopId"...');
    const shopSelect = await page.locator('select[wire\\:model\\.live="importShopId"]').first();

    if (await shopSelect.count() > 0) {
      // Get options from shop select
      const options = await shopSelect.evaluate(sel => {
        return Array.from(sel.options).map(o => ({value: o.value, text: o.textContent.trim()}));
      });
      console.log('Shop options:', JSON.stringify(options, null, 2));

      // Find B2B Test DEV option
      const b2bOption = options.find(o => o.text.includes('B2B Test DEV'));
      if (b2bOption) {
        console.log('4. Selecting B2B Test DEV (value:', b2bOption.value, ')');
        await shopSelect.selectOption(b2bOption.value);
        await page.waitForTimeout(3000);

        console.log('5. Screenshot after shop selection...');
        await page.screenshot({ path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_b2b_selected.png', fullPage: true });

        // Look for "Rozpocznij import wszystkich produktow" button
        console.log('6. Looking for import button...');
        const importBtn = await page.locator('button:has-text("Rozpocznij import")').first();

        if (await importBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
          console.log('7. Found import button - CLICKING...');
          await importBtn.click();

          // Wait and observe - should NOT block UI
          console.log('8. Waiting 5s to observe non-blocking behavior...');
          await page.waitForTimeout(5000);

          console.log('9. Screenshot during import...');
          await page.screenshot({ path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_import_running.png', fullPage: true });

          // Wait more for background analysis
          console.log('10. Waiting 15 more seconds for background analysis...');
          await page.waitForTimeout(15000);

          console.log('11. Final screenshot...');
          await page.screenshot({ path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_import_progress.png', fullPage: true });

          // Check for progress bar
          const progressBar = await page.locator('[wire\\:poll]').first();
          if (await progressBar.count() > 0) {
            console.log('12. Progress bar detected!');
          }
        } else {
          console.log('7. Import button not visible, checking what is visible...');
          await page.screenshot({ path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_after_shop.png', fullPage: true });
        }
      } else {
        console.log('B2B Test DEV not found in options');
      }
    } else {
      console.log('Shop select not found with wire:model');
      // Fallback - find any select in modal
      const allSelects = await page.$$eval('select', sels => sels.map((s, i) => ({
        index: i,
        wireModel: s.getAttribute('wire:model.live') || s.getAttribute('wire:model'),
        optionCount: s.options.length
      })));
      console.log('All selects on page:', allSelects);
    }

    console.log('\n=== TEST COMPLETE ===');
  } catch (error) {
    console.error('ERROR:', error.message);
    await page.screenshot({ path: 'D:/OneDrive - MPP TRADE/Skrypty/PPM-CC-Laravel/_TOOLS/screenshots/etap07c_error.png' });
  } finally {
    await browser.close();
  }
})();
