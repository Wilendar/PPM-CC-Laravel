// verify_mappings_count_fix.cjs
// Verifies that mappings count displays correctly after withCount() backend fix
// Tests both desktop table and mobile cards

const { chromium } = require('playwright');

(async () => {
  console.log('🔍 Starting Mappings Count Verification...\n');

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  try {
    // Navigate to shops page
    console.log('📍 Navigating to: https://ppm.mpptrade.pl/admin/shops');
    await page.goto('https://ppm.mpptrade.pl/admin/shops', { waitUntil: 'networkidle', timeout: 30000 });

    // Wait for table to be visible (more specific selector)
    await page.waitForSelector('table thead', { timeout: 15000 });
    await page.waitForTimeout(2000); // Additional wait for data to load

    console.log('✅ Page loaded successfully\n');

    // Check desktop table - Mapowania header
    console.log('🖥️  DESKTOP TABLE VERIFICATION:');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

    const mappingsHeader = await page.locator('th:has-text("Mapowania")').count();
    console.log(`   Mapowania header visible: ${mappingsHeader > 0 ? '✅ YES' : '❌ NO'}`);

    // Check first shop row mappings
    const firstShopRow = page.locator('tbody tr').first();
    const mappingsCell = firstShopRow.locator('td').nth(4); // 5th column (0-indexed)
    const mappingsCellText = await mappingsCell.textContent();

    console.log(`\n   First shop mappings cell content:`);
    console.log(`   ${mappingsCellText.trim().replace(/\s+/g, ' ')}`);

    // Extract counts
    const priceMatch = mappingsCellText.match(/Ceny:\s*(\d+)/);
    const warehouseMatch = mappingsCellText.match(/Magazyny:\s*(\d+)/);

    if (priceMatch && warehouseMatch) {
      const priceCount = parseInt(priceMatch[1]);
      const warehouseCount = parseInt(warehouseMatch[1]);

      console.log(`\n   ✅ Price mappings: ${priceCount}`);
      console.log(`   ✅ Warehouse mappings: ${warehouseCount}`);

      if (priceCount > 0 || warehouseCount > 0) {
        console.log(`\n   🎉 SUCCESS: Non-zero counts detected!`);
      } else {
        console.log(`\n   ⚠️  WARNING: Both counts are 0 (may be expected if no mappings)`);
      }
    } else {
      console.log(`\n   ❌ ERROR: Could not extract counts from cell`);
    }

    // Check all shops counts
    console.log(`\n   📊 ALL SHOPS MAPPINGS:`);
    const allRows = await page.locator('tbody tr').all();

    for (let i = 0; i < Math.min(5, allRows.length); i++) {
      const row = allRows[i];
      const shopName = await row.locator('td').first().textContent();
      const mappings = await row.locator('td').nth(4).textContent();

      const shopNameClean = shopName.trim().split('\n')[0];
      const mappingsClean = mappings.trim().replace(/\s+/g, ' ');

      console.log(`   ${i + 1}. ${shopNameClean}: ${mappingsClean}`);
    }

    // Check mobile cards (scroll down first)
    console.log(`\n\n📱 MOBILE CARDS VERIFICATION:`);
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    await page.reload({ waitUntil: 'networkidle' });

    // Check if mobile cards exist
    const mobileCards = await page.locator('.bg-gray-800.rounded-lg.p-5').count();
    console.log(`   Mobile cards detected: ${mobileCards}`);

    if (mobileCards > 0) {
      // Check first mobile card
      const firstCard = page.locator('.bg-gray-800.rounded-lg.p-5').first();
      const mappingsSection = await firstCard.locator('div:has-text("Mapowania:")').textContent();

      console.log(`\n   First card mappings:`);
      console.log(`   ${mappingsSection.trim().replace(/\s+/g, ' ')}`);

      const mobilePriceMatch = mappingsSection.match(/Ceny:\s*(\d+)/);
      const mobileWarehouseMatch = mappingsSection.match(/Magazyny:\s*(\d+)/);

      if (mobilePriceMatch && mobileWarehouseMatch) {
        console.log(`\n   ✅ Mobile Price mappings: ${mobilePriceMatch[1]}`);
        console.log(`   ✅ Mobile Warehouse mappings: ${mobileWarehouseMatch[1]}`);
      } else {
        console.log(`\n   ❌ ERROR: Could not extract mobile counts`);
      }
    } else {
      console.log(`   ℹ️  No mobile cards found (may be using desktop layout)`);
    }

    // Check for console errors
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });

    await page.reload({ waitUntil: 'networkidle' });

    console.log(`\n\n🔍 CONSOLE ERRORS CHECK:`);
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

    if (consoleErrors.length === 0) {
      console.log('   ✅ No console errors detected');
    } else {
      console.log(`   ⚠️  Console errors found:`);
      consoleErrors.forEach(err => console.log(`      - ${err}`));
    }

    console.log(`\n\n${'='.repeat(50)}`);
    console.log('✅ VERIFICATION COMPLETE');
    console.log('='.repeat(50));

  } catch (error) {
    console.error(`\n❌ ERROR during verification:`);
    console.error(error.message);
    console.error(error.stack);
  } finally {
    await browser.close();
  }
})();
