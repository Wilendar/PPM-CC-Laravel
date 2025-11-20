/**
 * Simple verification: Tax Rate field in Basic tab
 * (FAZA 5.2 Phase 4 Deployment)
 */

const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    ignoreHTTPSErrors: true
  });
  const page = await context.newPage();

  try {
    console.log('\n=== TAX RATE FIELD VERIFICATION ===\n');

    await page.goto('https://ppm.mpptrade.pl/admin/products/create', {
      waitUntil: 'networkidle',
      timeout: 60000
    });
    console.log('✅ Page loaded\n');

    // Simple check: Count "Stawka VAT" labels on page
    const taxRateLabels = await page.locator('label:has-text("Stawka VAT")').count();
    console.log(`Tax Rate labels found: ${taxRateLabels}`);

    if (taxRateLabels === 0) {
      console.log('❌ NO Tax Rate field found - DEPLOYMENT FAILED?');

      // Check if Blade template is cached
      console.log('\n⚠️ Possible issue: Blade cache not cleared?');
      console.log('Try: php artisan view:clear on production');

    } else if (taxRateLabels === 1) {
      console.log('✅ CORRECT: 1 Tax Rate field (should be in Basic tab)');

      // Get field details
      const label = page.locator('label:has-text("Stawka VAT")');
      const labelText = await label.textContent();
      console.log(`\nLabel text: "${labelText.trim()}"`);

      // Check for dropdown
      const hasDropdown = await page.locator('select#tax_rate, select[wire\\:model\\.live="selectedTaxRateOption"]').count() > 0;
      console.log(`Dropdown exists: ${hasDropdown ? '✅ YES' : '❌ NO'}`);

      if (hasDropdown) {
        const select = page.locator('select#tax_rate, select[wire\\:model\\.live="selectedTaxRateOption"]').first();
        const options = await select.locator('option').allTextContents();
        console.log(`\nDropdown options (${options.length}):`);
        options.forEach((opt, idx) => {
          console.log(`  ${idx + 1}. ${opt.trim()}`);
        });
      }

      // Take screenshot
      const fieldContainer = label.locator('..').first();
      await fieldContainer.screenshot({
        path: '_TOOLS/screenshots/tax_rate_field_verification_2025-11-14.png'
      });
      console.log('\n✅ Screenshot: _TOOLS/screenshots/tax_rate_field_verification_2025-11-14.png');

    } else {
      console.log(`⚠️ WARNING: ${taxRateLabels} Tax Rate fields found (expected 1)`);
      console.log('Possible issue: Field exists in BOTH Basic and Physical tabs?');
    }

    // Take full page screenshot
    await page.screenshot({
      path: '_TOOLS/screenshots/tax_rate_full_page_2025-11-14.png',
      fullPage: true
    });
    console.log('✅ Full page screenshot: _TOOLS/screenshots/tax_rate_full_page_2025-11-14.png');

  } catch (error) {
    console.error('❌ ERROR:', error.message);
  } finally {
    await browser.close();
  }
})();
