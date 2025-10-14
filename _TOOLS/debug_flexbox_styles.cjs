// PPM-CC-Laravel Flexbox Debug Script
const { chromium } = require('playwright');

(async () => {
    console.log('\n=== FLEXBOX COMPUTED STYLES DEBUG ===\n');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    console.log('Loading page...');
    await page.goto('https://ppm.mpptrade.pl/admin/products/4/edit', {
        waitUntil: 'networkidle',
        timeout: 30000
    });
    await page.waitForTimeout(2000);

    console.log('Analyzing flexbox styles...\n');

    const analysis = await page.evaluate(() => {
        const mainContainer = document.querySelector('.category-form-main-container');
        const leftColumn = document.querySelector('.category-form-left-column');
        const rightColumn = document.querySelector('.category-form-right-column');

        const getStyles = (element, name) => {
            if (!element) return { exists: false, name };

            const computed = window.getComputedStyle(element);
            const rect = element.getBoundingClientRect();

            return {
                exists: true,
                name,
                computed: {
                    display: computed.display,
                    flexDirection: computed.flexDirection,
                    flexWrap: computed.flexWrap,
                    gap: computed.gap,
                    position: computed.position,
                    width: computed.width,
                    minWidth: computed.minWidth,
                    maxWidth: computed.maxWidth,
                    flex: computed.flex,
                    flexShrink: computed.flexShrink,
                    flexGrow: computed.flexGrow,
                    flexBasis: computed.flexBasis,
                    alignSelf: computed.alignSelf,
                    top: computed.top,
                    overflow: computed.overflow,
                    minHeight: computed.minHeight
                },
                rect: {
                    width: Math.round(rect.width),
                    height: Math.round(rect.height),
                    top: Math.round(rect.top),
                    left: Math.round(rect.left)
                },
                classes: element.className
            };
        };

        return {
            mainContainer: getStyles(mainContainer, 'category-form-main-container'),
            leftColumn: getStyles(leftColumn, 'category-form-left-column'),
            rightColumn: getStyles(rightColumn, 'category-form-right-column'),
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            }
        };
    });

    // Display results
    console.log('VIEWPORT:', analysis.viewport.width, 'x', analysis.viewport.height);
    console.log('\n--- MAIN CONTAINER (.category-form-main-container) ---');
    if (analysis.mainContainer.exists) {
        console.log('Classes:', analysis.mainContainer.classes);
        console.log('Computed Styles:');
        console.log('  display:', analysis.mainContainer.computed.display);
        console.log('  flex-direction:', analysis.mainContainer.computed.flexDirection);
        console.log('  gap:', analysis.mainContainer.computed.gap);
        console.log('  min-height:', analysis.mainContainer.computed.minHeight);
        console.log('  overflow:', analysis.mainContainer.computed.overflow);
        console.log('Position:', analysis.mainContainer.rect.left, ',', analysis.mainContainer.rect.top);
        console.log('Size:', analysis.mainContainer.rect.width, 'x', analysis.mainContainer.rect.height);
    } else {
        console.log('  ❌ ELEMENT NOT FOUND');
    }

    console.log('\n--- LEFT COLUMN (.category-form-left-column) ---');
    if (analysis.leftColumn.exists) {
        console.log('Classes:', analysis.leftColumn.classes);
        console.log('Computed Styles:');
        console.log('  flex:', analysis.leftColumn.computed.flex);
        console.log('  width:', analysis.leftColumn.computed.width);
        console.log('  min-width:', analysis.leftColumn.computed.minWidth);
        console.log('  max-width:', analysis.leftColumn.computed.maxWidth);
        console.log('Position:', analysis.leftColumn.rect.left, ',', analysis.leftColumn.rect.top);
        console.log('Size:', analysis.leftColumn.rect.width, 'x', analysis.leftColumn.rect.height);
    } else {
        console.log('  ❌ ELEMENT NOT FOUND');
    }

    console.log('\n--- RIGHT COLUMN (.category-form-right-column) ---');
    if (analysis.rightColumn.exists) {
        console.log('Classes:', analysis.rightColumn.classes);
        console.log('Computed Styles:');
        console.log('  display:', analysis.rightColumn.computed.display);
        console.log('  position:', analysis.rightColumn.computed.position);
        console.log('  top:', analysis.rightColumn.computed.top);
        console.log('  align-self:', analysis.rightColumn.computed.alignSelf);
        console.log('  flex:', analysis.rightColumn.computed.flex);
        console.log('  flex-shrink:', analysis.rightColumn.computed.flexShrink);
        console.log('  width:', analysis.rightColumn.computed.width);
        console.log('  min-width:', analysis.rightColumn.computed.minWidth);
        console.log('Position:', analysis.rightColumn.rect.left, ',', analysis.rightColumn.rect.top);
        console.log('Size:', analysis.rightColumn.rect.width, 'x', analysis.rightColumn.rect.height);
    } else {
        console.log('  ❌ ELEMENT NOT FOUND');
    }

    console.log('\n===LAYOUT DIAGNOSIS ===');
    if (analysis.mainContainer.computed.display !== 'flex') {
        console.log('❌ Main container is NOT flexbox!');
        console.log('   Expected: flex, Got:', analysis.mainContainer.computed.display);
    } else if (analysis.mainContainer.computed.flexDirection !== 'row') {
        console.log('❌ Flex direction is NOT row!');
        console.log('   Expected: row, Got:', analysis.mainContainer.computed.flexDirection);
    } else if (analysis.rightColumn.rect.top > analysis.leftColumn.rect.top + analysis.leftColumn.rect.height - 100) {
        console.log('❌ Right column is BELOW left column (vertical layout)');
        console.log('   Left bottom:', analysis.leftColumn.rect.top + analysis.leftColumn.rect.height);
        console.log('   Right top:', analysis.rightColumn.rect.top);
    } else {
        console.log('✅ Layout appears correct!');
        console.log('   Left column: x=' + analysis.leftColumn.rect.left);
        console.log('   Right column: x=' + analysis.rightColumn.rect.left);
    }

    console.log('\n=== DEBUG COMPLETE ===\n');

    await browser.close();
})();