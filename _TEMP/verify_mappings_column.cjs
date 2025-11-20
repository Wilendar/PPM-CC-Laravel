const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 2560, height: 1440 } // Wider viewport
    });
    const page = await context.newPage();

    console.log('Navigating to https://ppm.mpptrade.pl/admin/shops');
    await page.goto('https://ppm.mpptrade.pl/admin/shops', { waitUntil: 'networkidle' });

    // Wait for table
    await page.waitForSelector('table thead', { timeout: 10000 });

    // Get all header columns
    const headers = await page.$$eval('table thead th', ths =>
        ths.map(th => th.textContent.trim())
    );

    console.log('\n=== TABLE HEADERS ===');
    headers.forEach((header, index) => {
        console.log(`${index + 1}. ${header}`);
    });

    // Check if "Mapowania" exists
    const hasMappings = headers.some(h => h.toLowerCase().includes('mapowania'));
    console.log(`\n✅ "Mapowania" column exists: ${hasMappings}`);

    // Get first row data for Mapowania column
    if (hasMappings) {
        const mappingsIndex = headers.findIndex(h => h.toLowerCase().includes('mapowania'));
        const firstRowMappings = await page.$$eval(`table tbody tr:first-child td:nth-child(${mappingsIndex + 1})`,
            tds => tds[0]?.textContent.trim()
        );
        console.log(`First row Mapowania data: ${firstRowMappings}`);
    }

    // Take screenshot
    const screenshotPath = '_TOOLS/screenshots/mappings_verification_2025-11-13.png';
    await page.screenshot({ path: screenshotPath, fullPage: true });
    console.log(`\n✅ Screenshot saved: ${screenshotPath}`);

    await browser.close();
})();
