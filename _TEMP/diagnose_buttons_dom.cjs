const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });
    const page = await context.newPage();

    console.log('[1/4] Navigating...');
    await page.goto('https://ppm.mpptrade.pl/admin/shops/sync', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    console.log('[2/4] Finding buttons section...');

    // Get the header container structure
    const headerInfo = await page.evaluate(() => {
        // Find H3 by text content (not pseudo-selector)
        const h3Elements = Array.from(document.querySelectorAll('h3'));
        const h3 = h3Elements.find(el => el.textContent.includes('Ostatnie zadania synchronizacji'));

        if (!h3) return { error: 'H3 not found' };

        const flexParent = h3.closest('.flex.items-center.justify-between');
        if (!flexParent) return { error: 'Flex parent not found', h3Found: true };

        return {
            h3Text: h3.textContent.trim(),
            flexParentClasses: flexParent.className,
            flexParentChildren: Array.from(flexParent.children).map(child => ({
                tag: child.tagName,
                classes: child.className,
                text: child.textContent.substring(0, 50)
            }))
        };
    });

    console.log('\n=== HEADER STRUCTURE ===');
    console.log(JSON.stringify(headerInfo, null, 2));

    console.log('\n[3/4] Finding buttons...');

    const buttonsInfo = await page.evaluate(() => {
        // Find buttons by text content
        const buttons = Array.from(document.querySelectorAll('button'));
        const clearBtn = buttons.find(btn => btn.textContent.includes('Wyczysc Stare Logi'));
        const archiveBtn = buttons.find(btn => btn.textContent.includes('Archiwizuj'));

        return {
            clearButton: clearBtn ? {
                text: clearBtn.textContent.trim().substring(0, 50),
                classes: clearBtn.className,
                parentClasses: clearBtn.parentElement?.className,
                computedStyle: {
                    display: getComputedStyle(clearBtn).display,
                    visibility: getComputedStyle(clearBtn).visibility,
                    position: getComputedStyle(clearBtn).position,
                    transform: getComputedStyle(clearBtn).transform
                },
                boundingBox: clearBtn.getBoundingClientRect()
            } : 'NOT FOUND',
            archiveButton: archiveBtn ? {
                text: archiveBtn.textContent.trim().substring(0, 50),
                classes: archiveBtn.className,
                parentClasses: archiveBtn.parentElement?.className,
                computedStyle: {
                    display: getComputedStyle(archiveBtn).display,
                    visibility: getComputedStyle(archiveBtn).visibility,
                    position: getComputedStyle(archiveBtn).position,
                    transform: getComputedStyle(archiveBtn).transform
                },
                boundingBox: archiveBtn.getBoundingClientRect()
            } : 'NOT FOUND'
        };
    });

    console.log('\n=== BUTTONS INFO ===');
    console.log(JSON.stringify(buttonsInfo, null, 2));

    console.log('\n[4/4] Taking screenshot of buttons area...');

    // Scroll to buttons if they exist
    await page.evaluate(() => {
        const h3Elements = Array.from(document.querySelectorAll('h3'));
        const h3 = h3Elements.find(el => el.textContent.includes('Ostatnie zadania synchronizacji'));
        if (h3) h3.scrollIntoView({ block: 'center' });
    });
    await page.waitForTimeout(500);

    await page.screenshot({
        path: '_TOOLS/screenshots/buttons_area_diagnostic_2025-11-12.png',
        fullPage: false
    });

    console.log('✅ Screenshot saved');

    await browser.close();
})();
