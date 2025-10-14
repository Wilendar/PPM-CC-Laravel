const { chromium } = require('playwright');

async function checkBreadcrumbsSpacing() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log('\n=== CHECKING BREADCRUMBS & SPACING ===\n');

    await page.goto('https://ppm.mpptrade.pl/admin/products', {
        waitUntil: 'networkidle',
        timeout: 30000
    });

    await page.waitForTimeout(2000);

    const analysis = await page.evaluate(() => {
        const results = {};

        // 1. Header
        const header = document.querySelector('header');
        if (header) {
            const headerRect = header.getBoundingClientRect();
            const computed = window.getComputedStyle(header);
            results.header = {
                position: computed.position,
                top: computed.top,
                height: headerRect.height,
                zIndex: computed.zIndex,
                location: {
                    x: Math.round(headerRect.x),
                    y: Math.round(headerRect.y),
                    width: Math.round(headerRect.width),
                    height: Math.round(headerRect.height)
                }
            };
        }

        // 2. Dev banner
        const devBanner = document.querySelector('.bg-orange-600');
        if (devBanner) {
            const rect = devBanner.getBoundingClientRect();
            const computed = window.getComputedStyle(devBanner);
            results.devBanner = {
                position: computed.position,
                height: rect.height,
                location: {
                    y: Math.round(rect.y),
                    height: Math.round(rect.height)
                }
            };
        }

        // 3. Breadcrumbs
        const breadcrumbs = document.querySelector('.breadcrumbs, nav[aria-label="breadcrumb"], .breadcrumb');
        if (breadcrumbs) {
            const rect = breadcrumbs.getBoundingClientRect();
            const computed = window.getComputedStyle(breadcrumbs);
            results.breadcrumbs = {
                exists: true,
                position: computed.position,
                top: computed.top,
                zIndex: computed.zIndex,
                classes: breadcrumbs.className.substring(0, 150),
                location: {
                    x: Math.round(rect.x),
                    y: Math.round(rect.y),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height)
                }
            };
        } else {
            results.breadcrumbs = { exists: false };
        }

        // 4. Grid container (pt-28)
        const gridContainer = document.querySelector('.pt-28');
        if (gridContainer) {
            const rect = gridContainer.getBoundingClientRect();
            const computed = window.getComputedStyle(gridContainer);
            results.gridContainer = {
                paddingTop: computed.paddingTop,
                marginTop: computed.marginTop,
                location: {
                    y: Math.round(rect.y),
                    height: Math.round(rect.height)
                }
            };
        }

        // 5. Main content
        const main = document.querySelector('main');
        if (main) {
            const rect = main.getBoundingClientRect();
            const computed = window.getComputedStyle(main);
            results.mainContent = {
                paddingTop: computed.paddingTop,
                marginTop: computed.marginTop,
                location: {
                    x: Math.round(rect.x),
                    y: Math.round(rect.y),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height)
                }
            };
        }

        // 6. First content element in main
        const firstContent = document.querySelector('main > *');
        if (firstContent) {
            const rect = firstContent.getBoundingClientRect();
            results.firstContentElement = {
                tag: firstContent.tagName,
                classes: firstContent.className.substring(0, 100),
                location: {
                    y: Math.round(rect.y)
                }
            };
        }

        // 7. Calculate gaps
        results.gaps = {};
        if (results.devBanner && results.header) {
            results.gaps.devBannerToHeader = results.header.location.y - (results.devBanner.location.y + results.devBanner.location.height);
        }
        if (results.header && results.gridContainer) {
            results.gaps.headerToGridContainer = results.gridContainer.location.y - (results.header.location.y + results.header.location.height);
        }
        if (results.mainContent && results.firstContentElement) {
            results.gaps.mainToFirstContent = results.firstContentElement.location.y - results.mainContent.location.y;
        }

        return results;
    });

    console.log(JSON.stringify(analysis, null, 2));

    // Issues
    console.log('\n=== ISSUES DETECTED ===');
    const issues = [];

    if (analysis.breadcrumbs && analysis.breadcrumbs.exists && analysis.breadcrumbs.position === 'fixed') {
        issues.push('⚠️ Breadcrumbs are FIXED positioned (floating)');
    }

    if (analysis.gaps) {
        if (analysis.gaps.headerToGridContainer > 150) {
            issues.push(`⚠️ Large gap between header and content: ${analysis.gaps.headerToGridContainer}px`);
        }
        if (analysis.gaps.mainToFirstContent > 100) {
            issues.push(`⚠️ Large gap in main content: ${analysis.gaps.mainToFirstContent}px`);
        }
    }

    if (issues.length === 0) {
        console.log('✅ No issues detected');
    } else {
        issues.forEach(issue => console.log(issue));
    }

    await browser.close();
}

checkBreadcrumbsSpacing().catch(console.error);
