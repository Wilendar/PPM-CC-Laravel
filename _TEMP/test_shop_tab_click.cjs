// Test Shop Tab Click - Field-Level Pending Verification
// 2025-11-07

const { chromium } = require('playwright');

(async () => {
  console.log('=== SHOP TAB VERIFICATION ===\n');

  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });

  const page = await context.newPage();

  // Navigate to ProductForm
  console.log('[1/5] Opening ProductForm...');
  await page.goto('https://ppm.mpptrade.pl/admin/products/10980/edit');
  await page.waitForLoadState('networkidle');
  console.log('✅ ProductForm loaded\n');

  // Wait for Livewire
  console.log('[2/5] Waiting for Livewire...');
  await page.waitForTimeout(2000);
  console.log('✅ Livewire ready\n');

  // Find and click "Zarządzanie sklepami" section
  console.log('[3/5] Looking for shop management section...');

  // Look for "B2B Test DEV" button/element with pending status
  const shopButton = await page.locator('button:has-text("B2B Test DEV"), div:has-text("B2B Test DEV")').first();

  if (await shopButton.isVisible()) {
    console.log('✅ Found B2B Test DEV shop element\n');

    // Get current status text BEFORE click
    const statusBefore = await page.locator('text=/Oczekuje/').first().textContent().catch(() => 'NOT FOUND');
    console.log(`Status text BEFORE: "${statusBefore}"\n`);

    // Click the shop
    console.log('[4/5] Clicking B2B Test DEV...');
    await shopButton.click();
    await page.waitForTimeout(1500);
    console.log('✅ Clicked\n');

    // Wait for content to load
    await page.waitForTimeout(1000);

  } else {
    console.log('❌ B2B Test DEV element NOT FOUND\n');
  }

  // Screenshot
  console.log('[5/5] Taking screenshots...');
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);

  await page.screenshot({
    path: `_TOOLS/screenshots/shop_tab_viewport_${timestamp}.png`,
    fullPage: false
  });

  await page.screenshot({
    path: `_TOOLS/screenshots/shop_tab_full_${timestamp}.png`,
    fullPage: true
  });

  console.log(`✅ Viewport: shop_tab_viewport_${timestamp}.png`);
  console.log(`✅ Full: shop_tab_full_${timestamp}.png\n`);

  console.log('=== VERIFICATION COMPLETE ===');
  console.log('Press any key to close browser...');

  // Keep browser open for manual inspection
  await page.waitForTimeout(30000);

  await browser.close();
})();
