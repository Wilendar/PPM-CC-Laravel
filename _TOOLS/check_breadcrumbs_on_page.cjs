const { chromium } = require('playwright');

async function checkBreadcrumbsOnPage(url) {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log(`\n=== CHECKING BREADCRUMBS ON: ${url} ===\n`);

    await page.goto(url, {
        waitUntil: 'networkidle',
        timeout: 30000
    });

    await page.waitForTimeout(2000);

    const analysis = await page.evaluate(() => {
        // Search for breadcrumbs - multiple possible selectors
        const breadcrumbSelectors = [
            '.breadcrumbs',
            '.breadcrumb',
            'nav[aria-label="breadcrumb"]',
            '[class*="breadcrumb"]',
            '.flex.items-center.text-sm', // Common breadcrumb pattern
        ];

        let breadcrumbElement = null;
        let usedSelector = null;

        for (const selector of breadcrumbSelectors) {
            const el = document.querySelector(selector);
            if (el) {
                breadcrumbElement = el;
                usedSelector = selector;
                break;
            }
        }

        if (!breadcrumbElement) {
            return {
                found: false,
                message: 'No breadcrumbs found on this page'
            };
        }

        const rect = breadcrumbElement.getBoundingClientRect();
        const computed = window.getComputedStyle(breadcrumbElement);
        const parent = breadcrumbElement.parentElement;
        const parentComputed = parent ? window.getComputedStyle(parent) : null;

        return {
            found: true,
            selector: usedSelector,
            element: {
                tag: breadcrumbElement.tagName,
                classes: breadcrumbElement.className,
                position: computed.position,
                top: computed.top,
                left: computed.left,
                zIndex: computed.zIndex,
                location: {
                    x: Math.round(rect.x),
                    y: Math.round(rect.y),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height)
                }
            },
            parent: parent ? {
                tag: parent.tagName,
                classes: parent.className.substring(0, 100),
                position: parentComputed.position
            } : null,
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            }
        };
    });

    console.log(JSON.stringify(analysis, null, 2));

    if (analysis.found) {
        console.log('\n=== BREADCRUMB ANALYSIS ===');
        if (analysis.element.position === 'fixed' || analysis.element.position === 'absolute') {
            console.log(`🚨 Breadcrumb is ${analysis.element.position.toUpperCase()} positioned!`);
            console.log(`   Location: y=${analysis.element.location.y}, z-index=${analysis.element.zIndex}`);
        } else {
            console.log(`✅ Breadcrumb is ${analysis.element.position} positioned (normal flow)`);
        }
    }

    await browser.close();
}

// Check both pages
const urlToCheck = process.argv[2] || 'https://ppm.mpptrade.pl/admin/products/4/edit';
checkBreadcrumbsOnPage(urlToCheck).catch(console.error);
