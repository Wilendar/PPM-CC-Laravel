/**
 * Verify Tax Rate field in Basic tab (FAZA 5.2 Phase 4 Deployment)
 *
 * Checks:
 * 1. Tax Rate field EXISTS in Basic tab
 * 2. Tax Rate field REMOVED from Physical tab
 * 3. Dropdown options visible
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
    console.log('\n=== TAX RATE FIELD VERIFICATION (FAZA 5.2) ===\n');

    // Navigate to Create Product page
    console.log('Loading: https://ppm.mpptrade.pl/admin/products/create');
    await page.goto('https://ppm.mpptrade.pl/admin/products/create', {
      waitUntil: 'networkidle',
      timeout: 60000
    });
    console.log('✅ Page loaded\n');

    // VERIFICATION 1: Basic tab - Tax Rate field should EXIST
    console.log('--- BASIC TAB VERIFICATION ---');

    // Check if Basic tab is active (should be default)
    const basicTabActive = await page.locator('[role="tab"]:has-text("Informacje podstawowe")').getAttribute('aria-selected');
    console.log(`Basic tab active: ${basicTabActive === 'true' ? '✅ YES' : '❌ NO'}`);

    // Check for Tax Rate label
    const taxRateLabel = page.locator('label:has-text("Stawka VAT")');
    const taxRateLabelExists = await taxRateLabel.count() > 0;
    console.log(`Tax Rate label in Basic tab: ${taxRateLabelExists ? '✅ EXISTS' : '❌ NOT FOUND'}`);

    if (taxRateLabelExists) {
      // Check for dropdown (select element)
      const taxRateSelect = page.locator('select#tax_rate, select[wire\\:model\\.live="selectedTaxRateOption"]');
      const selectExists = await taxRateSelect.count() > 0;
      console.log(`Tax Rate dropdown exists: ${selectExists ? '✅ YES' : '❌ NO'}`);

      if (selectExists) {
        // Check dropdown options
        const options = await taxRateSelect.locator('option').allTextContents();
        console.log(`\nDropdown options (${options.length}):`);
        options.forEach((opt, idx) => {
          console.log(`  ${idx + 1}. ${opt.trim()}`);
        });

        // Take screenshot of Tax Rate field area
        const taxRateField = page.locator('label:has-text("Stawka VAT")').locator('..');
        await taxRateField.screenshot({
          path: '_TOOLS/screenshots/tax_rate_basic_tab_2025-11-14.png'
        });
        console.log('\n✅ Screenshot saved: _TOOLS/screenshots/tax_rate_basic_tab_2025-11-14.png');
      }
    }

    // VERIFICATION 2: Physical tab - Tax Rate field should NOT EXIST
    console.log('\n--- PHYSICAL TAB VERIFICATION ---');

    // Click Physical Properties tab
    const physicalTab = page.locator('[role="tab"]:has-text("Właściwości fizyczne")');
    const physicalTabExists = await physicalTab.count() > 0;

    if (physicalTabExists) {
      await physicalTab.click();
      await page.waitForTimeout(1000); // Wait for tab content to load

      // Check if Tax Rate field exists in Physical tab (should NOT)
      const taxRateInPhysical = await page.locator('label:has-text("Stawka VAT")').count();
      console.log(`Tax Rate field in Physical tab: ${taxRateInPhysical === 0 ? '✅ REMOVED (correct)' : '❌ STILL EXISTS (error!)'}`);

      if (taxRateInPhysical === 0) {
        // Take screenshot of Physical tab (proof of removal)
        await page.screenshot({
          path: '_TOOLS/screenshots/tax_rate_physical_tab_removed_2025-11-14.png',
          fullPage: false
        });
        console.log('✅ Screenshot saved: _TOOLS/screenshots/tax_rate_physical_tab_removed_2025-11-14.png');
      }
    } else {
      console.log('❌ Physical Properties tab not found');
    }

    // SUMMARY
    console.log('\n=== VERIFICATION SUMMARY ===');
    const basicExists = taxRateLabelExists;
    const physicalRemoved = (await page.locator('label:has-text("Stawka VAT")').count()) === 0;

    if (basicExists && physicalRemoved) {
      console.log('✅ DEPLOYMENT SUCCESSFUL:');
      console.log('   - Tax Rate field EXISTS in Basic tab');
      console.log('   - Tax Rate field REMOVED from Physical tab');
    } else {
      console.log('❌ DEPLOYMENT ISSUES:');
      if (!basicExists) console.log('   - Tax Rate field NOT FOUND in Basic tab');
      if (!physicalRemoved) console.log('   - Tax Rate field STILL in Physical tab');
    }

  } catch (error) {
    console.error('❌ ERROR:', error.message);
  } finally {
    await browser.close();
  }
})();
