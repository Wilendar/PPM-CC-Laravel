// Diagnose ProductForm Issues
// 2025-11-07

const { chromium } = require('playwright');

(async () => {
  console.log('=== PRODUCTFORM DIAGNOSTICS ===\n');

  const browser = await chromium.launch({
    headless: false,
    slowMo: 500
  });

  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });

  const page = await context.newPage();

  // Open ProductForm
  console.log('[1] Opening ProductForm...');
  await page.goto('https://ppm.mpptrade.pl/admin/products/10980/edit');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  console.log('✅ Loaded\n');

  // PROBLEM 1: Check "OCZEKUJE NA SYNCHRONIZACJĘ" badges
  console.log('[2] Checking "OCZEKUJE NA SYNCHRONIZACJĘ" badges...');
  const badges = await page.locator('span:has-text("OCZEKUJE NA SYNCHRONIZACJĘ")').all();
  console.log(`   Found ${badges.length} badges with "OCZEKUJE NA SYNCHRONIZACJĘ"`);

  for (let i = 0; i < Math.min(badges.length, 5); i++) {
    const text = await badges[i].textContent();
    const color = await badges[i].evaluate(el => window.getComputedStyle(el).color);
    const bg = await badges[i].evaluate(el => window.getComputedStyle(el).backgroundColor);
    console.log(`   Badge ${i+1}: "${text.trim()}" | color: ${color} | bg: ${bg}`);
  }
  console.log('');

  // PROBLEM 2: Check if Warianty tab appears after checkbox
  console.log('[3] Testing "Produkt z wariantami" checkbox...');

  // Find checkbox
  const variantCheckbox = await page.locator('input[type="checkbox"]').filter({ hasText: /produkt.*wariant/i }).first();
  const isVisible = await variantCheckbox.isVisible().catch(() => false);

  if (isVisible) {
    console.log('   ✅ Found "Produkt z wariantami" checkbox');

    // Check if already checked
    const isChecked = await variantCheckbox.isChecked();
    console.log(`   Current state: ${isChecked ? 'CHECKED' : 'UNCHECKED'}`);

    // Count tabs BEFORE
    const tabsBefore = await page.locator('[role="tab"], .tab-button, button:has-text("Warianty")').all();
    console.log(`   Tabs visible BEFORE: ${tabsBefore.length}`);

    // Toggle checkbox
    if (!isChecked) {
      console.log('   Clicking checkbox...');
      await variantCheckbox.click();
      await page.waitForTimeout(1000);
    }

    // Count tabs AFTER
    const tabsAfter = await page.locator('[role="tab"], .tab-button, button:has-text("Warianty")').all();
    console.log(`   Tabs visible AFTER: ${tabsAfter.length}`);

    // Check specifically for Warianty tab
    const variantyTab = await page.locator('button:has-text("Warianty"), [role="tab"]:has-text("Warianty")').first();
    const variantyVisible = await variantyTab.isVisible().catch(() => false);
    console.log(`   Warianty tab visible: ${variantyVisible ? 'YES ✅' : 'NO ❌'}`);

  } else {
    console.log('   ❌ "Produkt z wariantami" checkbox NOT FOUND');
  }
  console.log('');

  // PROBLEM 3: Check B2B Test DEV shop tab
  console.log('[4] Opening B2B Test DEV shop tab...');

  const shopButton = await page.locator('button:has-text("B2B Test DEV"), div:has-text("B2B Test DEV")').first();
  const shopVisible = await shopButton.isVisible().catch(() => false);

  if (shopVisible) {
    console.log('   ✅ Found B2B Test DEV button');
    await shopButton.click();
    await page.waitForTimeout(2000);
    console.log('   ✅ Clicked - waiting for form...');

    // Take screenshot of shop form
    await page.screenshot({
      path: '_TOOLS/screenshots/shop_form_diagnostic.png',
      fullPage: true
    });
    console.log('   ✅ Screenshot: shop_form_diagnostic.png');

    // Check for "OCZEKUJE NA SYNCHRONIZACJĘ" in shop form
    const shopBadges = await page.locator('span:has-text("OCZEKUJE NA SYNCHRONIZACJĘ")').all();
    console.log(`   Shop form badges: ${shopBadges.length}`);

  } else {
    console.log('   ❌ B2B Test DEV button NOT FOUND');
  }
  console.log('');

  // PROBLEM 4: Check database structure - prices & stock
  console.log('[5] Checking if price/stock fields exist in form...');

  const priceFields = await page.locator('input[name*="price"], input[id*="price"]').all();
  const stockFields = await page.locator('input[name*="stock"], input[id*="quantity"]').all();

  console.log(`   Price input fields found: ${priceFields.length}`);
  console.log(`   Stock input fields found: ${stockFields.length}`);
  console.log('');

  console.log('=== DIAGNOSTICS COMPLETE ===');
  console.log('Browser will stay open for 30 seconds for manual inspection...');

  await page.waitForTimeout(30000);
  await browser.close();
})();
