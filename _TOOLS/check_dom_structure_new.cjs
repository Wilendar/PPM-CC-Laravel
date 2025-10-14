#!/usr/bin/env node
/**
 * Check DOM Structure - Playwright Script
 *
 * Analyzes DOM hierarchy to detect layout issues:
 * - Parent-child relationships
 * - Element positioning
 * - Container structure
 *
 * Part of /analizuj_strone diagnostic workflow
 *
 * Usage:
 *   node _TOOLS/check_dom_structure_new.cjs <url>
 */

const { chromium } = require('playwright');

async function checkDOMStructure(url) {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log(`\n=== DOM STRUCTURE ANALYSIS: ${url} ===\n`);

    try {
        await page.goto(url, {
            waitUntil: 'networkidle',
            timeout: 30000
        });

        console.log('✅ Page loaded\n');

        // Analyze DOM structure
        const domAnalysis = await page.evaluate(() => {
            // Helper: Get full parent path
            function getParentPath(element) {
                const path = [];
                let current = element;
                while (current && current !== document.body) {
                    const tag = current.tagName.toLowerCase();
                    const id = current.id ? `#${current.id}` : '';
                    const classes = current.className ? `.${current.className.split(' ').join('.')}` : '';
                    path.unshift(`${tag}${id}${classes}`);
                    current = current.parentElement;
                }
                return path;
            }

            // Helper: Get element info
            function getElementInfo(element, label) {
                if (!element) return null;

                const rect = element.getBoundingClientRect();
                const computed = window.getComputedStyle(element);

                return {
                    label,
                    exists: true,
                    tag: element.tagName,
                    id: element.id || null,
                    classes: element.className || null,
                    rect: {
                        x: Math.round(rect.x),
                        y: Math.round(rect.y),
                        width: Math.round(rect.width),
                        height: Math.round(rect.height)
                    },
                    computed: {
                        display: computed.display,
                        position: computed.position,
                        flexDirection: computed.flexDirection,
                        gap: computed.gap
                    },
                    parentPath: getParentPath(element),
                    directParent: element.parentElement ? {
                        tag: element.parentElement.tagName,
                        id: element.parentElement.id,
                        classes: element.parentElement.className
                    } : null
                };
            }

            // Find key elements - Product List specific
            const mainContent = document.querySelector('[class*="main-content"]');
            const productTable = document.querySelector('table');
            const syncStatusColumn = Array.from(document.querySelectorAll('th')).find(th => th.textContent.includes('PRESTASHOP SYNC'));

            // Get table structure
            let tableInfo = null;
            if (productTable) {
                const headers = Array.from(productTable.querySelectorAll('thead th')).map(th => th.textContent.trim());
                const rowCount = productTable.querySelectorAll('tbody tr').length;

                tableInfo = {
                    headers,
                    rowCount,
                    hasSyncColumn: headers.some(h => h.includes('SYNC') || h.includes('PRESTASHOP'))
                };
            }

            return {
                mainContent: getElementInfo(mainContent, 'Main Content'),
                productTable: getElementInfo(productTable, 'Product Table'),
                syncStatusColumn: getElementInfo(syncStatusColumn, 'Sync Status Column'),
                tableInfo,
                url: window.location.href,
                title: document.title
            };
        });

        // Display results
        console.log('--- PAGE INFO ---\n');
        console.log(`URL: ${domAnalysis.url}`);
        console.log(`Title: ${domAnalysis.title}\n`);

        console.log('--- KEY ELEMENTS ---\n');

        if (domAnalysis.mainContent) {
            console.log(`✅ Main Content: <${domAnalysis.mainContent.tag}>`);
            console.log(`   Size: ${domAnalysis.mainContent.rect.width}x${domAnalysis.mainContent.rect.height}\n`);
        }

        if (domAnalysis.productTable) {
            console.log(`✅ Product Table: <${domAnalysis.productTable.tag}>`);
            console.log(`   Size: ${domAnalysis.productTable.rect.width}x${domAnalysis.productTable.rect.height}\n`);
        }

        if (domAnalysis.tableInfo) {
            console.log('--- TABLE STRUCTURE ---\n');
            console.log(`Rows: ${domAnalysis.tableInfo.rowCount}`);
            console.log(`Columns: ${domAnalysis.tableInfo.headers.length}`);
            console.log(`Has Sync Column: ${domAnalysis.tableInfo.hasSyncColumn ? '✅' : '❌'}`);
            console.log('\nHeaders:');
            domAnalysis.tableInfo.headers.forEach((h, idx) => {
                const isSyncColumn = h.includes('SYNC') || h.includes('PRESTASHOP');
                console.log(`  ${idx + 1}. ${h} ${isSyncColumn ? '← SYNC STATUS' : ''}`);
            });
            console.log('');
        }

        console.log('✅ DOM ANALYSIS COMPLETE\n');

        return { success: true, analysis: domAnalysis };

    } catch (error) {
        console.error('❌ ERROR:', error.message);
        return { success: false, error: error.message };
    } finally {
        await browser.close();
    }
}

// Main execution
const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/products';
checkDOMStructure(url).then(result => {
    if (!result.success) {
        process.exit(1);
    }
});
