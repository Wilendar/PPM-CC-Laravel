import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

    await page.goto('https://ppm.mpptrade.pl/login');
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.goto('https://ppm.mpptrade.pl/admin/shops/sync');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000);

    console.log('🔍 Finding button...');
    const button = await page.locator('button').filter({ hasText: 'Wyczysc Stare Logi' }).first();
    
    console.log('🖱️ Clicking button...');
    await button.click();
    await page.waitForTimeout(1000);

    console.log('🔍 Checking for confirmation dialog...');
    const dialog = await page.evaluate(() => {
        // Szukaj confirmation dialog
        const modal = Array.from(document.querySelectorAll('.fixed')).find(el => 
            el.classList.contains('inset-0') && 
            el.classList.contains('z-50')
        );
        
        if (modal) {
            const h4 = modal.querySelector('h4');
            const buttons = Array.from(modal.querySelectorAll('button')).map(btn => btn.textContent.trim());
            const visible = modal.style.display !== 'none';
            
            return {
                found: true,
                visible: visible,
                title: h4 ? h4.textContent.trim() : null,
                buttons: buttons,
                hasRetentionInfo: modal.innerHTML.includes('30 dni') && modal.innerHTML.includes('90 dni')
            };
        }
        return { found: false };
    });

    console.log('\n=== CONFIRMATION DIALOG ===');
    console.log('Found:', dialog.found);
    if (dialog.found) {
        console.log('Visible:', dialog.visible);
        console.log('Title:', dialog.title);
        console.log('Buttons:', dialog.buttons);
        console.log('Has retention info (30d/90d/14d):', dialog.hasRetentionInfo);
    }
    
    await page.waitForTimeout(500);
    await page.screenshot({ path: '_TOOLS/screenshots/confirmation_dialog_2025-11-12.png' });
    console.log('\n📸 Screenshot with dialog saved!');

    await page.waitForTimeout(5000);
    await browser.close();
})();
