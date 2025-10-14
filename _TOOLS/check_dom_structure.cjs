const { chromium } = require('playwright');

async function checkDOMStructure() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log('\n=== LOADING PAGE ===');
    await page.goto('https://ppm.mpptrade.pl/admin/products', {
        waitUntil: 'networkidle',
        timeout: 30000
    });

    await page.waitForTimeout(2000);

    console.log('\n=== DOM STRUCTURE ANALYSIS ===\n');

    const domAnalysis = await page.evaluate(() => {
        const results = {
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            gridContainer: null,
            sidebar: null,
            mainContent: null,
            productList: null,
            issues: []
        };

        // 1. Check for grid container (parent of sidebar + main)
        const gridContainers = document.querySelectorAll('div.lg\\:grid, .pt-28.lg\\:grid');
        console.log('Grid containers found:', gridContainers.length);

        if (gridContainers.length > 0) {
            const grid = gridContainers[0];
            const computed = window.getComputedStyle(grid);
            results.gridContainer = {
                selector: 'Found',
                classes: grid.className.substring(0, 150),
                display: computed.display,
                gridTemplateColumns: computed.gridTemplateColumns,
                directChildren: grid.children.length,
                childrenTypes: Array.from(grid.children).map(child => ({
                    tag: child.tagName,
                    classes: child.className.substring(0, 100)
                }))
            };
        } else {
            results.issues.push('❌ Grid container NOT FOUND');
        }

        // 2. Check sidebar (aside element)
        const sidebar = document.querySelector('aside');
        if (sidebar) {
            const computed = window.getComputedStyle(sidebar);
            const rect = sidebar.getBoundingClientRect();
            results.sidebar = {
                exists: true,
                position: computed.position,
                display: computed.display,
                width: computed.width,
                location: {
                    x: Math.round(rect.x),
                    y: Math.round(rect.y),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height)
                }
            };
        } else {
            results.issues.push('❌ Sidebar <aside> NOT FOUND');
        }

        // 3. Check main content element
        const main = document.querySelector('main');
        if (main) {
            const computed = window.getComputedStyle(main);
            const rect = main.getBoundingClientRect();
            results.mainContent = {
                exists: true,
                display: computed.display,
                width: computed.width,
                minWidth: computed.minWidth,
                padding: computed.padding,
                location: {
                    x: Math.round(rect.x),
                    y: Math.round(rect.y),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height)
                },
                directChildren: main.children.length,
                childrenSummary: Array.from(main.children).slice(0, 5).map(child => ({
                    tag: child.tagName,
                    classes: child.className.substring(0, 80),
                    visible: child.offsetWidth > 0 && child.offsetHeight > 0
                }))
            };

            // Check if main is visible
            if (rect.width === 0 || rect.height === 0) {
                results.issues.push('❌ Main content has ZERO width or height');
            }

            // Check if main is positioned correctly (should be after sidebar)
            if (sidebar && rect.x < sidebar.getBoundingClientRect().width) {
                results.issues.push('⚠️ Main content X position overlaps with sidebar');
            }
        } else {
            results.issues.push('❌ Main content <main> NOT FOUND');
        }

        // 4. Check for product list component (Livewire)
        const productList = document.querySelector('[wire\\:id*="product-list"], .product-list-container, table');
        if (productList) {
            const rect = productList.getBoundingClientRect();
            results.productList = {
                exists: true,
                tag: productList.tagName,
                visible: rect.width > 0 && rect.height > 0,
                location: {
                    x: Math.round(rect.x),
                    y: Math.round(rect.y),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height)
                }
            };

            if (!results.productList.visible) {
                results.issues.push('❌ Product list exists but has ZERO dimensions (not visible)');
            }
        } else {
            results.issues.push('❌ Product list component NOT FOUND');
        }

        // 5. Parent hierarchy check
        if (main && sidebar) {
            const mainParent = main.parentElement;
            const sidebarParent = sidebar.parentElement;

            if (mainParent !== sidebarParent) {
                results.issues.push('❌ CRITICAL: Main and Sidebar do NOT share the same parent');
                results.mainContent.parentTag = mainParent ? mainParent.tagName : 'null';
                results.sidebar.parentTag = sidebarParent ? sidebarParent.tagName : 'null';
            } else {
                results.gridContainer.verifiedParent = '✅ Main and Sidebar share same parent';
            }
        }

        return results;
    });

    console.log(JSON.stringify(domAnalysis, null, 2));

    // Summary
    console.log('\n=== ISSUES SUMMARY ===');
    if (domAnalysis.issues.length === 0) {
        console.log('✅ No issues detected');
    } else {
        domAnalysis.issues.forEach(issue => console.log(issue));
    }

    await browser.close();
}

checkDOMStructure().catch(console.error);
