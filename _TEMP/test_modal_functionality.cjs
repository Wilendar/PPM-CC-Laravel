const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });
    const page = await context.newPage();

    // Listen to console messages
    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        if (type === 'error' || text.includes('Alpine') || text.includes('Livewire') || text.includes('wire')) {
            console.log(`[BROWSER ${type.toUpperCase()}] ${text}`);
        }
    });

    // Listen to page errors
    page.on('pageerror', error => {
        console.log(`[PAGE ERROR] ${error.message}`);
    });

    console.log('[1/8] Navigating to sync page...');
    await page.goto('https://ppm.mpptrade.pl/admin/shops/sync', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    console.log('[2/8] Scrolling to buttons section...');
    await page.evaluate(() => {
        const h3Elements = Array.from(document.querySelectorAll('h3'));
        const h3 = h3Elements.find(el => el.textContent.includes('Ostatnie zadania synchronizacji'));
        if (h3) h3.scrollIntoView({ block: 'center' });
    });
    await page.waitForTimeout(1000);

    console.log('[3/8] Testing "Wyczysc Stare Logi" button...');

    // Check if button exists
    const clearBtnCount = await page.locator('button:has-text("Wyczysc Stare Logi")').count();
    console.log(`   - Button found: ${clearBtnCount > 0 ? 'YES' : 'NO'}`);

    if (clearBtnCount > 0) {
        console.log('[4/8] Clicking "Wyczysc Stare Logi" button...');
        await page.locator('button:has-text("Wyczysc Stare Logi")').first().click();
        await page.waitForTimeout(1000);

        // Check if modal appeared
        const modalVisible = await page.evaluate(() => {
            // Look for modal overlay (fixed inset-0 z-50)
            const modals = Array.from(document.querySelectorAll('[class*="fixed"][class*="inset-0"][class*="z-50"]'));
            const visibleModals = modals.filter(m => {
                const style = window.getComputedStyle(m);
                return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
            });
            return {
                totalModals: modals.length,
                visibleModals: visibleModals.length,
                modalClasses: visibleModals.map(m => m.className)
            };
        });

        console.log(`   - Modals in DOM: ${modalVisible.totalModals}`);
        console.log(`   - Visible modals: ${modalVisible.visibleModals}`);
        if (modalVisible.visibleModals > 0) {
            console.log(`   - Modal classes: ${JSON.stringify(modalVisible.modalClasses)}`);
        }

        if (modalVisible.visibleModals > 0) {
            console.log('[5/8] Modal opened successfully! Taking screenshot...');
            await page.screenshot({
                path: '_TOOLS/screenshots/modal_clear_opened_2025-11-12.png',
                fullPage: false
            });

            console.log('[6/8] Checking modal form elements...');
            const formElements = await page.evaluate(() => {
                // Find radio buttons
                const radios = Array.from(document.querySelectorAll('input[type="radio"]'));
                const radioInfo = radios.map(r => ({
                    name: r.getAttribute('x-model') || r.name,
                    value: r.value,
                    checked: r.checked
                }));

                // Find inputs
                const inputs = Array.from(document.querySelectorAll('input[type="number"], input[type="text"]'));
                const inputInfo = inputs.map(i => ({
                    type: i.type,
                    xModel: i.getAttribute('x-model'),
                    value: i.value
                }));

                // Find checkboxes
                const checkboxes = Array.from(document.querySelectorAll('input[type="checkbox"]'));
                const checkboxInfo = checkboxes.map(c => ({
                    xModel: c.getAttribute('x-model'),
                    checked: c.checked
                }));

                // Find confirmation button
                const buttons = Array.from(document.querySelectorAll('button'));
                const confirmBtn = buttons.find(b =>
                    b.textContent.includes('Wyczysc Logi') &&
                    b.getAttribute('@click')
                );

                return {
                    radios: radioInfo,
                    inputs: inputInfo,
                    checkboxes: checkboxInfo,
                    confirmButton: confirmBtn ? {
                        text: confirmBtn.textContent.trim(),
                        onClick: confirmBtn.getAttribute('@click'),
                        classes: confirmBtn.className
                    } : null
                };
            });

            console.log('   - Form elements:');
            console.log(JSON.stringify(formElements, null, 2));

            if (formElements.confirmButton) {
                console.log('[7/8] Clicking confirmation button in modal...');

                // Click the confirmation button
                await page.locator('button:has-text("Wyczysc Logi")').first().click();
                await page.waitForTimeout(2000);

                console.log('[8/8] Checking if backend was called...');

                // Check for notification or success message
                const notificationVisible = await page.evaluate(() => {
                    const notifications = Array.from(document.querySelectorAll('[class*="notification"], [role="alert"], [class*="toast"]'));
                    return notifications.map(n => ({
                        text: n.textContent.trim().substring(0, 100),
                        classes: n.className
                    }));
                });

                console.log('   - Notifications found:', notificationVisible.length);
                if (notificationVisible.length > 0) {
                    console.log('   - Notification content:', JSON.stringify(notificationVisible, null, 2));
                }

                // Take screenshot after clicking
                await page.screenshot({
                    path: '_TOOLS/screenshots/modal_clear_after_confirm_2025-11-12.png',
                    fullPage: false
                });
            } else {
                console.log('❌ Confirmation button not found in modal!');
            }
        } else {
            console.log('❌ Modal did not open after clicking button!');

            // Take screenshot to see what happened
            await page.screenshot({
                path: '_TOOLS/screenshots/modal_clear_failed_to_open_2025-11-12.png',
                fullPage: false
            });
        }
    } else {
        console.log('❌ "Wyczysc Stare Logi" button not found!');
    }

    console.log('\n=== TEST COMPLETE ===');
    await browser.close();
})();
