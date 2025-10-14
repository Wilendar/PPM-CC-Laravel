const { chromium } = require('playwright');

async function checkHeaderOverlap() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log('\n=== CHECKING HEADER/DEV BANNER OVERLAP ===\n');

    await page.goto('https://ppm.mpptrade.pl/admin/products', {
        waitUntil: 'networkidle',
        timeout: 30000
    });

    await page.waitForTimeout(2000);

    const analysis = await page.evaluate(() => {
        const results = {};

        // 1. Dev banner
        const devBanner = document.querySelector('.bg-orange-600');
        if (devBanner) {
            const rect = devBanner.getBoundingClientRect();
            const computed = window.getComputedStyle(devBanner);
            results.devBanner = {
                position: computed.position,
                top: computed.top,
                zIndex: computed.zIndex,
                location: {
                    y: Math.round(rect.y),
                    height: Math.round(rect.height),
                    yEnd: Math.round(rect.y + rect.height)
                }
            };
        }

        // 2. Admin header (check by class or position)
        const adminHeader = document.querySelector('.admin-header') ||
                           document.querySelector('[class*="fixed"][class*="top-0"]');
        if (adminHeader) {
            const rect = adminHeader.getBoundingClientRect();
            const computed = window.getComputedStyle(adminHeader);
            results.adminHeader = {
                selector: adminHeader.className.substring(0, 100),
                position: computed.position,
                top: computed.top,
                height: computed.height,
                zIndex: computed.zIndex,
                location: {
                    y: Math.round(rect.y),
                    height: Math.round(rect.height),
                    yEnd: Math.round(rect.y + rect.height)
                }
            };
        }

        // 3. Check overlap
        if (results.devBanner && results.adminHeader) {
            const devEnd = results.devBanner.location.yEnd;
            const headerStart = results.adminHeader.location.y;
            const headerEnd = results.adminHeader.location.yEnd;

            results.overlap = {
                devBannerEndsAt: devEnd,
                headerStartsAt: headerStart,
                headerEndsAt: headerEnd,
                isOverlapping: headerStart < devEnd,
                overlapAmount: headerStart < devEnd ? (devEnd - headerStart) : 0
            };
        }

        // 4. Grid container
        const gridContainer = document.querySelector('.pt-28');
        if (gridContainer) {
            const rect = gridContainer.getBoundingClientRect();
            const computed = window.getComputedStyle(gridContainer);
            const parent = gridContainer.parentElement;
            const parentRect = parent ? parent.getBoundingClientRect() : null;

            results.gridContainer = {
                paddingTop: computed.paddingTop,
                location: {
                    y: Math.round(rect.y),
                },
                parent: parent ? {
                    tag: parent.tagName,
                    y: parentRect ? Math.round(parentRect.y) : null
                } : null
            };
        }

        // 5. Calculate what SHOULD be
        if (results.devBanner && results.adminHeader) {
            const devHeight = results.devBanner.location.height;
            const headerHeight = results.adminHeader.location.height;

            results.expected = {
                devBannerHeight: devHeight,
                headerHeight: headerHeight,
                totalHeaderSpace: devHeight + headerHeight,
                note: 'Grid container should start AFTER dev banner + header'
            };
        }

        return results;
    });

    console.log(JSON.stringify(analysis, null, 2));

    // Issues summary
    console.log('\n=== ISSUES SUMMARY ===');

    if (analysis.overlap && analysis.overlap.isOverlapping) {
        console.log(`🚨 HEADER OVERLAYS DEV BANNER by ${analysis.overlap.overlapAmount}px!`);
        console.log(`   Dev banner: y=0 to y=${analysis.overlap.devBannerEndsAt}`);
        console.log(`   Header: y=${analysis.overlap.headerStartsAt} (should be y=${analysis.overlap.devBannerEndsAt})`);
    }

    if (analysis.expected && analysis.gridContainer) {
        const expectedContentStart = analysis.expected.totalHeaderSpace;
        const actualContentStart = analysis.gridContainer.location.y;
        const gap = actualContentStart - expectedContentStart;

        console.log(`\n📏 SPACING ANALYSIS:`);
        console.log(`   Expected content start: y=${expectedContentStart} (dev ${analysis.expected.devBannerHeight}px + header ${analysis.expected.headerHeight}px)`);
        console.log(`   Actual content start: y=${actualContentStart}`);
        console.log(`   Gap: ${gap}px ${gap > 20 ? '⚠️ TOO LARGE' : '✅ OK'}`);
    }

    await browser.close();
}

checkHeaderOverlap().catch(console.error);
