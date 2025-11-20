/**
 * Test Edit Variant Modal Fix
 * Tests if edit button properly loads variant data into modal
 */

const puppeteer = require('puppeteer');

(async () => {
  console.log('=== TEST EDIT VARIANT MODAL ===\n');

  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1920, height: 1080 });

  // Enable console logging
  page.on('console', msg => {
    const type = msg.type();
    if (type === 'error') {
      console.log(`❌ [error] ${msg.text()}`);
    } else if (type === 'warning') {
      console.log(`⚠️ [warning] ${msg.text()}`);
    } else if (type === 'log') {
      console.log(`ℹ️ [log] ${msg.text()}`);
    }
  });

  try {
    // Step 1: Login
    console.log('[1/6] Logging in...');
    await page.goto('https://ppm.mpptrade.pl/admin/products', { waitUntil: 'networkidle0' });
    await page.waitForSelector('input[name=email]', { timeout: 5000 });
    await page.type('input[name=email]', 'admin@mpptrade.pl');
    await page.type('input[name=password]', 'Admin123!MPP');
    await page.click('button[type=submit]');
    await page.waitForNavigation({ waitUntil: 'networkidle0' });
    console.log('✅ Logged in\n');

    // Step 2: Find first product with "Master" badge and click edit
    console.log('[2/6] Finding product with variants (Master badge)...');
    await page.waitForSelector('table tbody tr', { timeout: 5000 });

    // Click first edit icon in actions column
    const editIcon = await page.$('table tbody tr:first-child .fas.fa-edit');
    if (editIcon) {
      await editIcon.click();
      await page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 10000 });
      const currentUrl = page.url();
      console.log(`✅ Navigated to: ${currentUrl}\n`);
    } else {
      throw new Error('No edit icon found');
    }

    // Step 3: Click Warianty tab
    console.log('[3/6] Clicking "Warianty" tab...');
    await page.waitForSelector('text="Warianty"', { timeout: 5000 });
    const wariantyTab = await page.$('text="Warianty"');
    if (wariantyTab) {
      await wariantyTab.click();
      await page.waitForTimeout(2000); // Wait for tab content to load
      console.log('✅ Warianty tab clicked\n');
    } else {
      console.log('⚠️ No Warianty tab found\n');
    }

    // Step 4: Find and click "Edytuj" button (edit variant)
    console.log('[4/6] Looking for variant "Edytuj" button...');
    await page.waitForTimeout(1000);

    // Find first "Edytuj" button in variant row
    const edytujButton = await page.$('button .fa-edit');
    if (edytujButton) {
      console.log('✅ Found "Edytuj" button, clicking...');
      await edytujButton.click();
      await page.waitForTimeout(2000); // Wait for modal to open
      console.log('✅ Clicked "Edytuj" button\n');
    } else {
      console.log('⚠️ No "Edytuj" button found\n');
    }

    // Step 5: Check if modal is visible and has data
    console.log('[5/6] Checking modal state...');
    const modalVisible = await page.$('div[x-show="showEditModal"]');
    if (modalVisible) {
      console.log('✅ Modal element exists');

      // Check if inputs have values
      const skuValue = await page.$eval('input[wire\\:model="variantData.sku"]', el => el.value).catch(() => null);
      const nameValue = await page.$eval('input[wire\\:model="variantData.name"]', el => el.value).catch(() => null);

      console.log(`📋 SKU field value: "${skuValue}"`);
      console.log(`📋 Name field value: "${nameValue}"`);

      if (skuValue && nameValue) {
        console.log('✅ Modal has data - FIX WORKS!\n');
      } else {
        console.log('❌ Modal is empty - FIX FAILED!\n');
      }
    } else {
      console.log('❌ Modal not found\n');
    }

    // Step 6: Screenshot
    console.log('[6/6] Taking screenshot...');
    await page.screenshot({
      path: '_TOOLS/screenshots/test_edit_modal_' + new Date().toISOString().replace(/:/g, '-').split('.')[0] + '.png',
      fullPage: true
    });
    console.log('✅ Screenshot saved\n');

  } catch (error) {
    console.error('❌ Test failed:', error.message);
  } finally {
    await browser.close();
    console.log('\n=== TEST COMPLETE ===');
  }
})();
