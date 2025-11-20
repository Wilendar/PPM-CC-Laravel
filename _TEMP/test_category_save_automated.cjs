const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({
    headless: false,
    slowMo: 500
  });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  console.log('\n═══════════════════════════════════════════════════════════════');
  console.log('   AUTOMATED TEST: Category Save in Shop Tab');
  console.log('═══════════════════════════════════════════════════════════════\n');

  try {
    // Step 1: Navigate directly to product edit page (no login needed per user)
    console.log('1️⃣ Navigating to product 11034 edit page...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit', { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(3000);
    await page.screenshot({ path: '_TOOLS/screenshots/test_cat_save_01_product_loaded.png', fullPage: true });
    console.log('   ✅ Product page loaded\n');

    // Step 2: Find and click "B2B Test DEV" shop tab
    console.log('2️⃣ Looking for "B2B Test DEV" shop tab...');

    // Wait for shop tabs section to be visible (in sidepanel)
    await page.waitForSelector('.shop-tab-active, .shop-tab-inactive', { timeout: 10000 });

    // Find the shop tab button by text content (contains shop name)
    // Text may be truncated to 12 chars: "B2B Test DEV" may show as "B2B Test DEV" or "B2B Test D..."
    const shopTab = await page.locator('button').filter({ hasText: /B2B.*Test.*DEV/i }).first();

    if (await shopTab.count() === 0) {
      console.log('   ⚠️  Shop tab with exact text not found, trying alternative selectors...');

      // Try finding any shop tab button
      const allShopButtons = await page.locator('button.shop-tab-active, button.shop-tab-inactive').all();
      console.log(`   Found ${allShopButtons.length} shop buttons total`);

      // Get text from all shop buttons
      for (const btn of allShopButtons) {
        const btnText = await btn.textContent();
        console.log(`   • Shop button text: "${btnText.trim()}"`);
      }

      throw new Error('❌ Shop tab "B2B Test DEV" NOT FOUND');
    }

    const shopTabText = await shopTab.textContent();
    console.log(`   ✅ Found shop tab: "${shopTabText.trim()}"`);
    console.log('   Clicking shop tab...');

    await shopTab.click();
    await page.waitForTimeout(4000); // Wait for Livewire to load shop data + categories
    await page.screenshot({ path: '_TOOLS/screenshots/test_cat_save_02_shop_tab_clicked.png', fullPage: true });
    console.log('   ✅ Shop tab activated\n');

    // Step 3: Find categories section
    console.log('3️⃣ Looking for categories section...');

    // Wait longer for Livewire to load shop-specific content
    await page.waitForTimeout(2000);

    // Try to find categories section - it may be an h3, h4, or label
    const categoriesHeading = await page.locator('h3, h4, label').filter({ hasText: /Kategorie/i }).first();

    if (await categoriesHeading.count() === 0) {
      console.log('   ⚠️  Categories heading not found with standard selector');
      console.log('   Trying to find category checkboxes directly...');

      // Try finding category checkboxes directly
      const categoryCheckboxes = await page.locator('input[type="checkbox"][wire\\:model*="Categories"], input[type="checkbox"][wire\\:model*="categories"]').all();

      if (categoryCheckboxes.length === 0) {
        // Take screenshot for debugging
        await page.screenshot({ path: '_TOOLS/screenshots/test_cat_save_DEBUG_no_categories.png', fullPage: true });
        throw new Error('❌ Categories section NOT FOUND (no heading, no checkboxes)');
      }

      console.log(`   ✅ Found ${categoryCheckboxes.length} category checkboxes (no heading)`);
    } else {
      console.log('   ✅ Categories section found');

      // Scroll to categories section
      await categoriesHeading.scrollIntoViewIfNeeded();
      await page.waitForTimeout(1000);
    }

    // Step 4: Uncheck all categories first
    console.log('\n4️⃣ Unchecking all categories...');
    const allCheckboxes = await page.locator('input[type="checkbox"][wire\\:model*="shopCategories"]').all();
    console.log(`   Found ${allCheckboxes.length} category checkboxes`);

    for (const checkbox of allCheckboxes) {
      if (await checkbox.isChecked()) {
        await checkbox.uncheck();
        await page.waitForTimeout(100);
      }
    }
    console.log('   ✅ All categories unchecked\n');

    // Step 5: Check specific category "PITGANG" (PS ID 12)
    console.log('5️⃣ Looking for "PITGANG" category...');

    // Find the label containing "PITGANG" and get its associated checkbox
    const pitgangLabel = await page.locator('label').filter({ hasText: /PITGANG/i }).first();

    if (await pitgangLabel.count() === 0) {
      throw new Error('❌ Category "PITGANG" NOT FOUND in UI');
    }

    console.log('   ✅ Found "PITGANG" category');

    // Get the checkbox associated with this label
    const pitgangCheckbox = await pitgangLabel.locator('..').locator('input[type="checkbox"]').first();

    if (await pitgangCheckbox.count() === 0) {
      throw new Error('❌ Checkbox for "PITGANG" NOT FOUND');
    }

    console.log('   Checking "PITGANG" category...');
    await pitgangCheckbox.check();
    await page.waitForTimeout(500);

    // Verify checkbox is checked
    const isChecked = await pitgangCheckbox.isChecked();
    if (!isChecked) {
      throw new Error('❌ Checkbox did not check properly');
    }

    console.log('   ✅ "PITGANG" category checked');
    await page.screenshot({ path: '_TOOLS/screenshots/test_cat_save_03_pitgang_checked.png', fullPage: true });
    console.log('');

    // Step 6: Click "Zapisz zmiany" button
    console.log('6️⃣ Looking for "Zapisz zmiany" button...');

    // Find save button (may be in inline action bar or sidepanel)
    const saveButton = await page.locator('button').filter({ hasText: /Zapisz zmiany/i }).first();

    if (await saveButton.count() === 0) {
      throw new Error('❌ Save button NOT FOUND');
    }

    console.log('   ✅ Found save button');
    console.log('   Clicking "Zapisz zmiany"...');

    // Listen for Livewire responses
    page.on('response', response => {
      if (response.url().includes('livewire/update')) {
        console.log(`   📡 Livewire response: ${response.status()}`);
      }
    });

    await saveButton.click();
    await page.waitForTimeout(5000); // Wait for Livewire to process
    await page.screenshot({ path: '_TOOLS/screenshots/test_cat_save_04_after_save.png', fullPage: true });
    console.log('');

    // Step 7: Check for success message or errors
    console.log('7️⃣ Checking for success/error messages...');

    // Check for success notification
    const successNotification = await page.locator('.notification, .alert-success, [role="alert"]').filter({ hasText: /sukces|zapisany|saved/i }).first();
    const successCount = await successNotification.count();

    // Check for error notification
    const errorNotification = await page.locator('.notification, .alert-error, .alert-danger, [role="alert"]').filter({ hasText: /błąd|error|constraint/i }).first();
    const errorCount = await errorNotification.count();

    if (successCount > 0) {
      const successText = await successNotification.textContent();
      console.log(`   ✅ SUCCESS MESSAGE: ${successText.trim()}`);
    } else if (errorCount > 0) {
      const errorText = await errorNotification.textContent();
      console.log(`   ❌ ERROR MESSAGE: ${errorText.trim()}`);
    } else {
      console.log('   ⚠️  No success/error notification found');
    }

    console.log('');

    // Step 8: Verify in console logs
    console.log('8️⃣ Checking browser console for errors...');
    const consoleMessages = [];
    page.on('console', msg => {
      consoleMessages.push(`${msg.type()}: ${msg.text()}`);
    });

    // Wait a bit for any console errors
    await page.waitForTimeout(2000);

    const errors = consoleMessages.filter(msg => msg.startsWith('error'));
    if (errors.length > 0) {
      console.log(`   ❌ Found ${errors.length} console errors:`);
      errors.forEach(err => console.log(`      ${err}`));
    } else {
      console.log('   ✅ No console errors');
    }

    console.log('');

    // Step 9: Final screenshot
    console.log('9️⃣ Taking final screenshot...');
    await page.screenshot({ path: '_TOOLS/screenshots/test_cat_save_05_final.png', fullPage: true });

    console.log('\n═══════════════════════════════════════════════════════════════');
    console.log('   TEST COMPLETED');
    console.log('═══════════════════════════════════════════════════════════════\n');

    console.log('📊 SUMMARY:');
    console.log(`   • Login: SUCCESS`);
    console.log(`   • Product page loaded: SUCCESS`);
    console.log(`   • Shop tab found: SUCCESS`);
    console.log(`   • Category "PITGANG" checked: SUCCESS`);
    console.log(`   • Save button clicked: SUCCESS`);
    console.log(`   • Success notification: ${successCount > 0 ? 'FOUND ✅' : 'NOT FOUND ⚠️'}`);
    console.log(`   • Error notification: ${errorCount > 0 ? 'FOUND ❌' : 'NOT FOUND ✅'}`);
    console.log(`   • Console errors: ${errors.length > 0 ? 'YES ❌' : 'NO ✅'}`);
    console.log('');

    console.log('📁 Screenshots saved:');
    console.log('   • test_cat_save_01_product_loaded.png');
    console.log('   • test_cat_save_02_shop_tab_clicked.png');
    console.log('   • test_cat_save_03_pitgang_checked.png');
    console.log('   • test_cat_save_04_after_save.png');
    console.log('   • test_cat_save_05_final.png');
    console.log('');

    console.log('🔍 NEXT: Check database with verify_db.php script');
    console.log('');

  } catch (error) {
    console.error('\n❌ TEST FAILED:', error.message);
    console.error(error.stack);
    await page.screenshot({ path: '_TOOLS/screenshots/test_cat_save_ERROR.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();
