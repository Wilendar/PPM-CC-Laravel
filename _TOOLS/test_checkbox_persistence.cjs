#!/usr/bin/env node
/**
 * CHECKBOX PERSISTENCE TEST
 *
 * Automated E2E test to verify "Produkt z wariantami" checkbox
 * properly persists to database (has_variants column)
 *
 * Test Flow:
 * 1. Check database BEFORE (has_variants value)
 * 2. Open product in browser
 * 3. Check/uncheck checkbox
 * 4. Click Save button
 * 5. Check database AFTER (has_variants value)
 * 6. Reload page
 * 7. Verify checkbox state matches database
 *
 * Usage:
 *   node test_checkbox_persistence.cjs [product_id] [action]
 *
 * Arguments:
 *   product_id   Product ID to test (default: 10969)
 *   action       'check' or 'uncheck' (default: check)
 */

const { chromium } = require('playwright');
const { exec } = require('child_process');
const { promisify } = require('util');
const execAsync = promisify(exec);
const fs = require('fs');
const path = require('path');

// Parse arguments
const args = process.argv.slice(2);
const productId = args[0] || '10969';
const action = (args[1] || 'check').toLowerCase();

if (!['check', 'uncheck'].includes(action)) {
    console.error('Error: action must be "check" or "uncheck"');
    process.exit(1);
}

// Colors
const c = {
    reset: '\x1b[0m',
    bright: '\x1b[1m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    cyan: '\x1b[36m',
    dim: '\x1b[2m',
};

// Helper: Query database via SSH
async function queryDatabase(query) {
    const phpScript = `
<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

${query}
?>
    `.trim();

    const tempFile = path.join(__dirname, '..', '_TEMP', `query_${Date.now()}.php`);
    fs.writeFileSync(tempFile, phpScript);

    try {
        // Upload script
        await execAsync(`pscp -i "D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk" -P 64321 "${tempFile}" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/query_temp.php"`);

        // Execute script
        const { stdout, stderr } = await execAsync(`plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk" -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/query_temp.php"`);

        // Clean up remote
        await execAsync(`plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i "D:\\OneDrive - MPP TRADE\\SSH\\Hostido\\HostidoSSHNoPass.ppk" -batch "rm domains/ppm.mpptrade.pl/public_html/_TEMP/query_temp.php"`);

        // Clean up local
        fs.unlinkSync(tempFile);

        if (stderr && !stderr.includes('Deprecated')) {
            throw new Error(stderr);
        }

        return stdout.trim();
    } catch (error) {
        console.error(`${c.red}Database query failed: ${error.message}${c.reset}`);
        throw error;
    }
}

// Helper: Get product state from database
async function getProductState(productId) {
    const query = `
$product = App\\Models\\Product::find(${productId});
if (!$product) {
    echo "ERROR: Product not found";
    exit(1);
}
echo json_encode([
    'id' => $product->id,
    'sku' => $product->sku,
    'is_variant_master' => (bool)$product->is_variant_master,
    'has_variants' => (bool)$product->has_variants,
]);
    `;

    const result = await queryDatabase(query);
    return JSON.parse(result);
}

// Main test
(async () => {
    console.log(`${c.cyan}${c.bright}\n╔════════════════════════════════════════╗`);
    console.log(`║  CHECKBOX PERSISTENCE TEST             ║`);
    console.log(`╚════════════════════════════════════════╝${c.reset}\n`);

    console.log(`${c.dim}Product ID: ${productId}${c.reset}`);
    console.log(`${c.dim}Action: ${action}${c.reset}\n`);

    // STEP 1: Check database BEFORE
    console.log(`${c.yellow}[STEP 1/7] Checking database BEFORE...${c.reset}`);
    const stateBefore = await getProductState(productId);
    console.log(`${c.green}✅ Database state BEFORE:${c.reset}`);
    console.log(`  SKU: ${stateBefore.sku}`);
    console.log(`  is_variant_master: ${stateBefore.is_variant_master ? 'YES' : 'NO'}`);
    console.log(`  has_variants: ${stateBefore.has_variants ? 'YES' : 'NO'}`);
    console.log('');

    // STEP 2: Launch browser
    console.log(`${c.yellow}[STEP 2/7] Launching browser...${c.reset}`);
    const browser = await chromium.launch({
        headless: false, // Show browser for debugging
        args: ['--disable-blink-features=AutomationControlled']
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();
    console.log(`${c.green}✅ Browser launched${c.reset}\n`);

    // STEP 3: Login
    console.log(`${c.yellow}[STEP 3/7] Logging in...${c.reset}`);
    await page.goto('https://ppm.mpptrade.pl/login', { waitUntil: 'networkidle' });
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    console.log(`${c.green}✅ Logged in${c.reset}\n`);

    // STEP 4: Navigate to product
    console.log(`${c.yellow}[STEP 4/7] Opening product ${productId}...${c.reset}`);
    await page.goto(`https://ppm.mpptrade.pl/admin/products/${productId}/edit`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000); // Wait for Livewire
    console.log(`${c.green}✅ Product loaded${c.reset}\n`);

    // STEP 5: Check/uncheck checkbox
    console.log(`${c.yellow}[STEP 5/7] ${action === 'check' ? 'Checking' : 'Unchecking'} checkbox...${c.reset}`);

    const checkboxSelector = 'input#is_variant_master';
    const isChecked = await page.isChecked(checkboxSelector);
    console.log(`  Checkbox currently: ${isChecked ? 'CHECKED' : 'UNCHECKED'}`);

    if (action === 'check') {
        if (!isChecked) {
            await page.check(checkboxSelector);
            console.log(`${c.green}✅ Checkbox checked${c.reset}`);
        } else {
            console.log(`${c.dim}  Checkbox already checked, skipping${c.reset}`);
        }
    } else {
        if (isChecked) {
            await page.uncheck(checkboxSelector);
            console.log(`${c.green}✅ Checkbox unchecked${c.reset}`);
        } else {
            console.log(`${c.dim}  Checkbox already unchecked, skipping${c.reset}`);
        }
    }

    await page.waitForTimeout(1000);

    // Take screenshot BEFORE save
    const screenshotsDir = path.join(__dirname, 'screenshots');
    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir, { recursive: true });
    }
    const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
    await page.screenshot({
        path: path.join(screenshotsDir, `checkbox_${action}_before_save_${timestamp}.png`),
        fullPage: true
    });
    console.log(`${c.dim}  Screenshot saved: checkbox_${action}_before_save_${timestamp}.png${c.reset}\n`);

    // STEP 6: Click Save
    console.log(`${c.yellow}[STEP 6/7] Clicking Save button...${c.reset}`);

    // Try multiple selectors for Save button
    let saveClicked = false;
    const saveSelectors = [
        'button:has-text("Zapisz")',
        'button:has-text("Aktualizuj")',
        'button[type="submit"]',
        '.btn-enterprise-primary'
    ];

    for (const selector of saveSelectors) {
        try {
            const button = page.locator(selector).first();
            if (await button.isVisible({ timeout: 2000 })) {
                await button.click();
                console.log(`${c.green}✅ Save button clicked (${selector})${c.reset}`);
                saveClicked = true;
                break;
            }
        } catch (e) {
            // Try next selector
        }
    }

    if (!saveClicked) {
        console.log(`${c.red}❌ Could not find Save button${c.reset}`);
        await browser.close();
        process.exit(1);
    }

    // Wait for save operation
    await page.waitForTimeout(3000);
    console.log('');

    // STEP 7: Check database AFTER
    console.log(`${c.yellow}[STEP 7/7] Checking database AFTER...${c.reset}`);
    const stateAfter = await getProductState(productId);
    console.log(`${c.green}✅ Database state AFTER:${c.reset}`);
    console.log(`  SKU: ${stateAfter.sku}`);
    console.log(`  is_variant_master: ${stateAfter.is_variant_master ? 'YES' : 'NO'}`);
    console.log(`  has_variants: ${stateAfter.has_variants ? 'YES' : 'NO'}`);
    console.log('');

    // STEP 8: Reload page and verify checkbox
    console.log(`${c.yellow}[STEP 8/7] Reloading page to verify checkbox state...${c.reset}`);
    await page.reload({ waitUntil: 'networkidle' });
    await page.waitForTimeout(5000); // Wait longer for Livewire

    let checkboxStateAfterReload = false;
    try {
        checkboxStateAfterReload = await page.isChecked(checkboxSelector, { timeout: 10000 });
    } catch (e) {
        console.log(`${c.yellow}  ⚠️ Could not check checkbox state (timeout), assuming UNCHECKED${c.reset}`);
        checkboxStateAfterReload = false;
    }
    console.log(`  Checkbox after reload: ${checkboxStateAfterReload ? 'CHECKED' : 'UNCHECKED'}`);

    // Take screenshot AFTER reload
    await page.screenshot({
        path: path.join(screenshotsDir, `checkbox_${action}_after_reload_${timestamp}.png`),
        fullPage: true
    });
    console.log(`${c.dim}  Screenshot saved: checkbox_${action}_after_reload_${timestamp}.png${c.reset}\n`);

    // RESULTS
    console.log(`${c.cyan}${c.bright}╔════════════════════════════════════════╗`);
    console.log(`║           TEST RESULTS                 ║`);
    console.log(`╚════════════════════════════════════════╝${c.reset}\n`);

    console.log(`${c.bright}BEFORE:${c.reset}`);
    console.log(`  is_variant_master: ${stateBefore.is_variant_master ? 'YES' : 'NO'}`);
    console.log(`  has_variants:      ${stateBefore.has_variants ? 'YES' : 'NO'}`);
    console.log('');

    console.log(`${c.bright}AFTER:${c.reset}`);
    console.log(`  is_variant_master: ${stateAfter.is_variant_master ? 'YES' : 'NO'}`);
    console.log(`  has_variants:      ${stateAfter.has_variants ? 'YES' : 'NO'}`);
    console.log('');

    const expectedHasVariants = action === 'check';
    const actualHasVariants = stateAfter.has_variants;

    console.log(`${c.bright}EXPECTED:${c.reset}`);
    console.log(`  has_variants: ${expectedHasVariants ? 'YES' : 'NO'} (after ${action})`);
    console.log('');

    console.log(`${c.bright}CHECKBOX STATE AFTER RELOAD:${c.reset}`);
    console.log(`  Checkbox: ${checkboxStateAfterReload ? 'CHECKED' : 'UNCHECKED'}`);
    console.log('');

    // Verdict
    const hasVariantsMatch = actualHasVariants === expectedHasVariants;
    const checkboxMatch = checkboxStateAfterReload === expectedHasVariants;

    if (hasVariantsMatch && checkboxMatch) {
        console.log(`${c.green}${c.bright}✅ TEST PASSED!${c.reset}`);
        console.log(`${c.green}  ✅ has_variants saved correctly to database${c.reset}`);
        console.log(`${c.green}  ✅ Checkbox state persisted after reload${c.reset}`);
    } else {
        console.log(`${c.red}${c.bright}❌ TEST FAILED!${c.reset}`);
        if (!hasVariantsMatch) {
            console.log(`${c.red}  ❌ has_variants NOT saved to database (expected ${expectedHasVariants ? 'YES' : 'NO'}, got ${actualHasVariants ? 'YES' : 'NO'})${c.reset}`);
        }
        if (!checkboxMatch) {
            console.log(`${c.red}  ❌ Checkbox state NOT persisted (expected ${expectedHasVariants ? 'CHECKED' : 'UNCHECKED'}, got ${checkboxStateAfterReload ? 'CHECKED' : 'UNCHECKED'})${c.reset}`);
        }
    }

    console.log('');
    await browser.close();
    process.exit(hasVariantsMatch && checkboxMatch ? 0 : 1);

})().catch(error => {
    console.error(`${c.red}Fatal error: ${error.message}${c.reset}`);
    console.error(error.stack);
    process.exit(1);
});
