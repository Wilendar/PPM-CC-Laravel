const puppeteer = require('puppeteer');

(async () => {
  console.log('=== TEST PRODUCT 11034 CATEGORIES ===\n');

  const browser = await puppeteer.launch({
    headless: false,
    defaultViewport: null,
    args: ['--start-maximized']
  });

  const page = await browser.newPage();

  try {
    // Step 1: Login
    console.log('STEP 1: Logging in...');
    await page.goto('https://ppm.mpptrade.pl/login');
    await page.type('input[name="email"]', 'admin@mpptrade.pl');
    await page.type('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForNavigation();
    console.log('✅ Logged in\n');

    // Step 2: Open product 11034
    console.log('STEP 2: Opening product 11034...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
    await page.waitForSelector('[wire\\:id]', { timeout: 10000 });
    await page.waitForTimeout(2000);
    console.log('✅ Product loaded\n');

    // Step 3: Click "B2B Test DEV" shop tab
    console.log('STEP 3: Clicking shop tab "B2B Test DEV"...');

    // Find shop tab button
    const shopTabSelector = 'button[wire\\:click*="switchToShop"][wire\\:click*="1"]';
    await page.waitForSelector(shopTabSelector, { timeout: 5000 });

    await page.click(shopTabSelector);
    console.log('✅ Clicked shop tab\n');

    // Wait for Livewire to update
    await page.waitForTimeout(3000);

    // Step 4: Check categories
    console.log('STEP 4: Checking selected categories...\n');

    // Take screenshot BEFORE checking
    await page.screenshot({ path: '_TOOLS/screenshots/test_categories_before.png', fullPage: true });
    console.log('Screenshot saved: _TOOLS/screenshots/test_categories_before.png\n');

    // Find all checked category checkboxes
    const checkedCategories = await page.evaluate(() => {
      const checkboxes = document.querySelectorAll('input[type="checkbox"][wire\\:model*="shopCategories"]');
      const selected = [];

      checkboxes.forEach(cb => {
        if (cb.checked) {
          const label = cb.closest('label') || cb.parentElement;
          const text = label.textContent.trim();
          selected.push(text);
        }
      });

      return selected;
    });

    console.log('SELECTED CATEGORIES:');
    if (checkedCategories.length > 0) {
      checkedCategories.forEach(cat => console.log(`  ✅ ${cat}`));
    } else {
      console.log('  ❌ NO CATEGORIES SELECTED');
    }
    console.log('');

    // Expected categories
    const expectedCategories = [
      'Wszystko',
      'PITGANG',
      'Pit Bike',
      'Pojazdy',
      'Quad'
    ];

    console.log('EXPECTED CATEGORIES:');
    expectedCategories.forEach(cat => console.log(`  - ${cat}`));
    console.log('');

    // Compare
    console.log('COMPARISON:');
    const missing = expectedCategories.filter(exp =>
      !checkedCategories.some(sel => sel.includes(exp))
    );

    const extra = checkedCategories.filter(sel =>
      !expectedCategories.some(exp => sel.includes(exp))
    );

    if (missing.length === 0 && extra.length === 0) {
      console.log('✅ ALL CATEGORIES CORRECT!\n');
    } else {
      if (missing.length > 0) {
        console.log('❌ MISSING:');
        missing.forEach(cat => console.log(`  - ${cat}`));
      }
      if (extra.length > 0) {
        console.log('❌ EXTRA (should not be selected):');
        extra.forEach(cat => console.log(`  - ${cat}`));
      }
      console.log('');
    }

    // Check primary category
    console.log('CHECKING PRIMARY CATEGORY...');
    const primaryCategory = await page.evaluate(() => {
      const radios = document.querySelectorAll('input[type="radio"][wire\\:model*="primary"]');
      for (const radio of radios) {
        if (radio.checked) {
          const label = radio.closest('label') || radio.parentElement;
          return label.textContent.trim();
        }
      }
      return 'NONE';
    });

    console.log(`Primary: ${primaryCategory}`);
    if (primaryCategory.includes('Pit Bike')) {
      console.log('✅ Primary category correct!\n');
    } else {
      console.log('❌ Primary should be "Pit Bike"\n');
    }

    // Take final screenshot
    await page.screenshot({ path: '_TOOLS/screenshots/test_categories_after.png', fullPage: true });
    console.log('Screenshot saved: _TOOLS/screenshots/test_categories_after.png');

    console.log('\n=== TEST COMPLETE ===');

    // Keep browser open for 5 seconds
    await page.waitForTimeout(5000);

  } catch (error) {
    console.error('ERROR:', error.message);
    await page.screenshot({ path: '_TOOLS/screenshots/test_categories_error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();
