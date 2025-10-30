#!/usr/bin/env node

const playwright = require('playwright');

async function checkGridLayout(url) {
    console.log(`\n=== GRID LAYOUT ANALYSIS: ${url} ===\n`);

    const browser = await playwright.chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

        const gridAnalysis = await page.evaluate(() => {
            // Find the grid container (should be parent of aside and main)
            const aside = document.querySelector('aside');
            const main = document.querySelector('main');

            if (!aside || !main) {
                return { error: 'Aside or Main not found' };
            }

            const gridContainer = aside.parentElement;
            const gridContainerComputed = window.getComputedStyle(gridContainer);

            const asideComputed = window.getComputedStyle(aside);
            const mainComputed = window.getComputedStyle(main);

            return {
                gridContainer: {
                    tagName: gridContainer.tagName,
                    className: gridContainer.className,
                    display: gridContainerComputed.display,
                    gridTemplateColumns: gridContainerComputed.gridTemplateColumns,
                    gap: gridContainerComputed.gap,
                    position: gridContainerComputed.position,
                    width: gridContainer.getBoundingClientRect().width,
                    height: gridContainer.getBoundingClientRect().height
                },
                aside: {
                    display: asideComputed.display,
                    position: asideComputed.position,
                    width: asideComputed.width,
                    height: asideComputed.height,
                    gridColumn: asideComputed.gridColumn,
                    float: asideComputed.float,
                    actualWidth: aside.getBoundingClientRect().width,
                    actualHeight: aside.getBoundingClientRect().height
                },
                main: {
                    display: mainComputed.display,
                    position: mainComputed.position,
                    width: mainComputed.width,
                    height: mainComputed.height,
                    gridColumn: mainComputed.gridColumn,
                    float: mainComputed.float,
                    actualWidth: main.getBoundingClientRect().width,
                    actualHeight: main.getBoundingClientRect().height
                }
            };
        });

        if (gridAnalysis.error) {
            console.log(`❌ ERROR: ${gridAnalysis.error}\n`);
            return;
        }

        console.log('GRID CONTAINER:');
        console.log(`  Tag: <${gridAnalysis.gridContainer.tagName}>`);
        console.log(`  Class: "${gridAnalysis.gridContainer.className}"`);
        console.log(`  Display: ${gridAnalysis.gridContainer.display}`);
        console.log(`  Grid Template Columns: ${gridAnalysis.gridContainer.gridTemplateColumns}`);
        console.log(`  Gap: ${gridAnalysis.gridContainer.gap}`);
        console.log(`  Size: ${gridAnalysis.gridContainer.width}x${gridAnalysis.gridContainer.height}`);

        const isGrid = gridAnalysis.gridContainer.display.includes('grid');
        console.log(`  🔍 Is Grid: ${isGrid ? '✅ YES' : '❌ NO (should be grid!)'}`);

        console.log('\nASIDE (SIDEBAR):');
        console.log(`  Display: ${gridAnalysis.aside.display}`);
        console.log(`  Position: ${gridAnalysis.aside.position}`);
        console.log(`  Width (CSS): ${gridAnalysis.aside.width}`);
        console.log(`  Height (CSS): ${gridAnalysis.aside.height}`);
        console.log(`  Actual Size: ${gridAnalysis.aside.actualWidth}x${gridAnalysis.aside.actualHeight}`);
        console.log(`  Grid Column: ${gridAnalysis.aside.gridColumn}`);

        console.log('\nMAIN (CONTENT):');
        console.log(`  Display: ${gridAnalysis.main.display}`);
        console.log(`  Position: ${gridAnalysis.main.position}`);
        console.log(`  Width (CSS): ${gridAnalysis.main.width}`);
        console.log(`  Height (CSS): ${gridAnalysis.main.height}`);
        console.log(`  Actual Size: ${gridAnalysis.main.actualWidth}x${gridAnalysis.main.actualHeight}`);
        console.log(`  Grid Column: ${gridAnalysis.main.gridColumn}`);

        // Diagnosis
        console.log('\n=== DIAGNOSIS ===');
        if (!isGrid) {
            console.log('❌ CRITICAL: Grid container is NOT using display:grid!');
            console.log('   Expected: display: grid');
            console.log(`   Actual: display: ${gridAnalysis.gridContainer.display}`);
            console.log('\n💡 FIX: Check Tailwind classes on grid container');
            console.log('   Should have: lg:grid');
            console.log('   Current class: ' + gridAnalysis.gridContainer.className);
        } else {
            console.log('✅ Grid container using display:grid');
            console.log(`   Grid columns: ${gridAnalysis.gridContainer.gridTemplateColumns}`);
        }

        if (gridAnalysis.aside.actualHeight > 20000) {
            console.log('\n❌ CRITICAL: Sidebar height is ABSURD!');
            console.log(`   Actual: ${gridAnalysis.aside.actualHeight}px`);
            console.log('   Expected: ~600-1000px');
            console.log('\n💡 Likely cause: Content inside sidebar causing vertical expansion');
        }

        console.log('\n✅ GRID ANALYSIS COMPLETE\n');

    } catch (error) {
        console.error(`\n❌ ERROR: ${error.message}\n`);
    } finally {
        await browser.close();
    }
}

const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/features/vehicles';
checkGridLayout(url);
