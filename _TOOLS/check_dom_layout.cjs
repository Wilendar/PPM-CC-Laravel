#!/usr/bin/env node

const playwright = require('playwright');

async function checkDOMLayout(url) {
    console.log(`\n=== DOM LAYOUT ANALYSIS: ${url} ===\n`);

    const browser = await playwright.chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    try {
        console.log('Loading page...');
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

        // Check body dimensions
        const bodySize = await page.evaluate(() => {
            const body = document.body;
            return {
                width: body.scrollWidth,
                height: body.scrollHeight,
                clientHeight: body.clientHeight
            };
        });
        console.log(`\nBODY DIMENSIONS:`);
        console.log(`  Width: ${bodySize.width}px`);
        console.log(`  Height: ${bodySize.height}px (scroll height)`);
        console.log(`  Client Height: ${bodySize.clientHeight}px`);

        // Check modal overlays
        const modalOverlays = await page.evaluate(() => {
            const overlays = document.querySelectorAll('.modal-overlay');
            return Array.from(overlays).map(overlay => {
                const rect = overlay.getBoundingClientRect();
                const computed = window.getComputedStyle(overlay);
                return {
                    className: overlay.className,
                    display: computed.display,
                    visibility: computed.visibility,
                    opacity: computed.opacity,
                    zIndex: computed.zIndex,
                    position: computed.position,
                    width: rect.width,
                    height: rect.height,
                    top: rect.top,
                    left: rect.left,
                    xShow: overlay.getAttribute('x-show'),
                    xData: overlay.getAttribute('x-data')
                };
            });
        });

        console.log(`\nMODAL OVERLAYS FOUND: ${modalOverlays.length}`);
        modalOverlays.forEach((modal, index) => {
            console.log(`\n  Modal ${index + 1}:`);
            console.log(`    Display: ${modal.display}`);
            console.log(`    Visibility: ${modal.visibility}`);
            console.log(`    Opacity: ${modal.opacity}`);
            console.log(`    Z-Index: ${modal.zIndex}`);
            console.log(`    Position: ${modal.position}`);
            console.log(`    Size: ${modal.width}x${modal.height}`);
            console.log(`    x-show: ${modal.xShow}`);
            console.log(`    x-data: ${modal.xData ? modal.xData.substring(0, 50) + '...' : 'null'}`);
            console.log(`    ⚠️ BLOCKING: ${modal.display !== 'none' ? 'YES' : 'NO'}`);
        });

        // Check main layout containers
        const layoutInfo = await page.evaluate(() => {
            const main = document.querySelector('main');
            const sidebar = document.querySelector('aside');
            const header = document.querySelector('.admin-header');

            const getInfo = (el, name) => {
                if (!el) return { name, exists: false };
                const rect = el.getBoundingClientRect();
                const computed = window.getComputedStyle(el);
                return {
                    name,
                    exists: true,
                    display: computed.display,
                    position: computed.position,
                    width: rect.width,
                    height: rect.height,
                    top: rect.top,
                    left: rect.left,
                    zIndex: computed.zIndex
                };
            };

            return {
                main: getInfo(main, 'MAIN'),
                sidebar: getInfo(sidebar, 'SIDEBAR'),
                header: getInfo(header, 'HEADER')
            };
        });

        console.log(`\nMAIN LAYOUT CONTAINERS:`);
        Object.values(layoutInfo).forEach(info => {
            console.log(`\n  ${info.name}:`);
            if (info.exists) {
                console.log(`    Display: ${info.display}`);
                console.log(`    Position: ${info.position}`);
                console.log(`    Size: ${info.width}x${info.height}`);
                console.log(`    Location: top=${info.top}, left=${info.left}`);
                console.log(`    Z-Index: ${info.zIndex}`);
            } else {
                console.log(`    ❌ NOT FOUND`);
            }
        });

        // Check Alpine.js initialization
        const alpineStatus = await page.evaluate(() => {
            return {
                alpineLoaded: typeof window.Alpine !== 'undefined',
                livewireLoaded: typeof window.Livewire !== 'undefined',
                alpineVersion: window.Alpine?.version || 'not loaded'
            };
        });

        console.log(`\nFRAMEWORK STATUS:`);
        console.log(`  Alpine.js: ${alpineStatus.alpineLoaded ? '✅ Loaded (v' + alpineStatus.alpineVersion + ')' : '❌ NOT LOADED'}`);
        console.log(`  Livewire: ${alpineStatus.livewireLoaded ? '✅ Loaded' : '❌ NOT LOADED'}`);

        // Check sidebar state
        const sidebarState = await page.evaluate(() => {
            const sidebarContainer = document.querySelector('[x-data*="sidebarOpen"]');
            if (!sidebarContainer) return { exists: false };

            // Try to get Alpine data
            const alpineData = sidebarContainer.__x?.$data || {};

            return {
                exists: true,
                sidebarOpen: alpineData.sidebarOpen,
                sidebarCollapsed: alpineData.sidebarCollapsed,
                xDataAttr: sidebarContainer.getAttribute('x-data')
            };
        });

        console.log(`\nSIDEBAR STATE:`);
        if (sidebarState.exists) {
            console.log(`  sidebarOpen: ${sidebarState.sidebarOpen}`);
            console.log(`  sidebarCollapsed: ${sidebarState.sidebarCollapsed}`);
        } else {
            console.log(`  ❌ Sidebar Alpine data not found`);
        }

        console.log(`\n✅ DOM ANALYSIS COMPLETE\n`);

    } catch (error) {
        console.error(`\n❌ ERROR: ${error.message}\n`);
    } finally {
        await browser.close();
    }
}

// Run analysis
const url = process.argv[2] || 'https://ppm.mpptrade.pl/admin/features/vehicles';
checkDOMLayout(url);
