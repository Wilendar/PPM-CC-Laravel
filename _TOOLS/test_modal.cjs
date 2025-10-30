#!/usr/bin/env node
/**
 * Modal Testing Tool - PPM-CC-Laravel
 * Automated modal interaction and verification using Playwright
 *
 * Usage:
 *   node _TOOLS/test_modal.cjs <page_url> <modal_trigger_selector> [options]
 *
 * Examples:
 *   node _TOOLS/test_modal.cjs https://ppm.mpptrade.pl/admin/variants "button.btn-enterprise-sm"
 *   node _TOOLS/test_modal.cjs https://ppm.mpptrade.pl/admin/variants "button:has-text('Edit')" --wait 2000
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Configuration
const VIEWPORT = { width: 1920, height: 1080 };
const SCREENSHOTS_DIR = path.join(__dirname, 'screenshots');
const DEFAULT_TIMEOUT = 30000;

// Admin credentials for PPM-CC-Laravel
const ADMIN_CREDENTIALS = {
    email: 'admin@mpptrade.pl',
    password: 'Admin123!MPP'
};

/**
 * Parse command line arguments
 */
function parseArgs() {
    const args = process.argv.slice(2);

    if (args.length < 2) {
        console.error('\n❌ ERROR: Missing required arguments\n');
        console.log('Usage: node test_modal.cjs <page_url> <modal_trigger_selector> [options]\n');
        console.log('Examples:');
        console.log('  node test_modal.cjs https://ppm.mpptrade.pl/admin/variants ".btn-edit"');
        console.log('  node test_modal.cjs https://ppm.mpptrade.pl/admin/variants "[wire\\\\:click=\'editAttributeType\']" --wait 2000\n');
        process.exit(1);
    }

    const config = {
        url: args[0],
        selector: args[1],
        waitAfterClick: 1000,
        skipLogin: false
    };

    // Parse optional flags
    for (let i = 2; i < args.length; i++) {
        if (args[i] === '--wait' && args[i + 1]) {
            config.waitAfterClick = parseInt(args[i + 1]);
            i++;
        }
        if (args[i] === '--skip-login') {
            config.skipLogin = true;
        }
    }

    return config;
}

/**
 * Login to PPM-CC-Laravel admin panel
 */
async function login(page) {
    console.log('\n🔐 Logging in as admin...');

    try {
        // Navigate to login page
        await page.goto('https://ppm.mpptrade.pl/login', {
            waitUntil: 'networkidle',
            timeout: DEFAULT_TIMEOUT
        });

        // Fill login form
        await page.fill('input[name="email"]', ADMIN_CREDENTIALS.email);
        await page.fill('input[name="password"]', ADMIN_CREDENTIALS.password);

        // Submit form
        console.log('  Clicking submit button...');
        await page.click('button[type="submit"]');

        // Wait for navigation away from login page
        console.log('  Waiting for URL change...');
        try {
            await page.waitForURL(url => !url.includes('/login'), {
                timeout: 30000
            });
        } catch (e) {
            console.log('  ⚠️  Timeout waiting for URL change');
        }

        // Wait for page to settle
        await page.waitForTimeout(3000);

        // Check if login successful
        const currentUrl = page.url();
        console.log(`  Current URL: ${currentUrl}`);

        if (currentUrl.includes('/login')) {
            throw new Error('Login failed - still on login page');
        }

        console.log('✅ Login successful');
        return true;
    } catch (error) {
        console.error('❌ Login failed:', error.message);
        return false;
    }
}

/**
 * Take screenshot with timestamp
 */
function getScreenshotPath(prefix) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
    return path.join(SCREENSHOTS_DIR, `${prefix}_${timestamp}.png`);
}

/**
 * Check if modal overlay covers full viewport
 */
async function checkOverlayDimensions(page) {
    const overlayInfo = await page.evaluate(() => {
        // Find overlay (dark background) - more specific selectors
        const overlays = document.querySelectorAll('.bg-black\\/70, .absolute.inset-0, [class*="bg-black"]');

        if (overlays.length === 0) {
            return { found: false, count: 0 };
        }

        // Find the actual overlay that should cover viewport
        let actualOverlay = null;
        for (const el of overlays) {
            const computedStyle = window.getComputedStyle(el);
            const rect = el.getBoundingClientRect();

            if (computedStyle.position === 'absolute' || computedStyle.position === 'fixed') {
                if (!actualOverlay || rect.width > 0) {
                    actualOverlay = el;
                }
            }
        }

        if (!actualOverlay) {
            actualOverlay = overlays[0]; // fallback to first
        }

        const rect = actualOverlay.getBoundingClientRect();
        const computedStyle = window.getComputedStyle(actualOverlay);

        return {
            found: true,
            count: overlays.length,
            width: rect.width,
            height: rect.height,
            top: rect.top,
            left: rect.left,
            position: computedStyle.position,
            zIndex: computedStyle.zIndex,
            display: computedStyle.display,
            visibility: computedStyle.visibility,
            opacity: computedStyle.opacity,
            backgroundColor: computedStyle.backgroundColor,
            coversViewport: rect.width === window.innerWidth && rect.height === window.innerHeight && rect.top === 0 && rect.left === 0
        };
    });

    return overlayInfo;
}

/**
 * Get modal structure information
 */
async function getModalStructure(page) {
    const structure = await page.evaluate(() => {
        // Find modal container (usually x-teleport or fixed positioned)
        const modalContainers = document.querySelectorAll('.fixed.inset-0, [x-data*="show"]');

        const modals = Array.from(modalContainers).map(container => {
            const rect = container.getBoundingClientRect();
            const computedStyle = window.getComputedStyle(container);

            // Find overlay and content within
            const overlay = container.querySelector('[class*="bg-black"], [class*="backdrop"]');
            const content = container.querySelector('[class*="modal"], [class*="rounded-lg"], [class*="bg-gray-800"]');

            return {
                position: computedStyle.position,
                zIndex: computedStyle.zIndex,
                width: rect.width,
                height: rect.height,
                hasOverlay: !!overlay,
                hasContent: !!content,
                overlayPosition: overlay ? window.getComputedStyle(overlay).position : null,
                overlayZIndex: overlay ? window.getComputedStyle(overlay).zIndex : null,
                contentPosition: content ? window.getComputedStyle(content).position : null,
                contentZIndex: content ? window.getComputedStyle(content).zIndex : null
            };
        });

        return modals;
    });

    return structure;
}

/**
 * Test ESC key closing
 */
async function testEscapeKey(page) {
    console.log('\n⌨️  Testing ESC key...');

    // Check if modal is visible before ESC
    const beforeEsc = await page.evaluate(() => {
        const modals = document.querySelectorAll('.fixed.inset-0');
        return Array.from(modals).some(m => window.getComputedStyle(m).display !== 'none');
    });

    if (!beforeEsc) {
        console.log('⚠️  Modal not visible before ESC test');
        return false;
    }

    // Press ESC
    await page.keyboard.press('Escape');
    await page.waitForTimeout(2000);  // Increased wait time for Livewire request

    // Check if modal closed
    const afterEsc = await page.evaluate(() => {
        const modals = document.querySelectorAll('.fixed.inset-0');
        return Array.from(modals).some(m => window.getComputedStyle(m).display !== 'none');
    });

    const success = !afterEsc;
    console.log(success ? '✅ ESC key closes modal' : '❌ ESC key does NOT close modal');
    return success;
}

/**
 * Test overlay click closing
 */
async function testOverlayClick(page) {
    console.log('\n🖱️  Testing overlay click...');

    // Find and click overlay
    const overlayClicked = await page.evaluate(() => {
        const overlay = document.querySelector('[class*="bg-black/70"], [class*="bg-black/50"]');
        if (overlay) {
            overlay.click();
            return true;
        }
        return false;
    });

    if (!overlayClicked) {
        console.log('⚠️  Overlay not found for click test');
        return false;
    }

    await page.waitForTimeout(2000);  // Increased wait time for Livewire request

    // Check if modal closed
    const modalClosed = await page.evaluate(() => {
        const modals = document.querySelectorAll('.fixed.inset-0');
        return Array.from(modals).every(m => window.getComputedStyle(m).display === 'none');
    });

    console.log(modalClosed ? '✅ Overlay click closes modal' : '❌ Overlay click does NOT close modal');
    return modalClosed;
}

/**
 * Main testing function
 */
async function testModal(config) {
    console.log('\n=== MODAL TESTING TOOL ===');
    console.log(`URL: ${config.url}`);
    console.log(`Selector: ${config.selector}`);
    console.log(`Viewport: ${VIEWPORT.width}x${VIEWPORT.height}\n`);

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    await page.setViewportSize(VIEWPORT);

    // Log console errors
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
            console.log(`🔴 Console Error: ${msg.text()}`);
        }
    });

    try {
        // Step 1: Login (unless skipped)
        if (!config.skipLogin) {
            const loginSuccess = await login(page);
            if (!loginSuccess) {
                throw new Error('Login failed');
            }
        }

        // Step 2: Navigate to target page
        console.log('\n📄 Loading page...');
        await page.goto(config.url, {
            waitUntil: 'networkidle',
            timeout: DEFAULT_TIMEOUT
        });
        console.log('✅ Page loaded');

        // Step 3: Take before screenshot
        const beforePath = getScreenshotPath('modal_before');
        await page.screenshot({ path: beforePath, fullPage: false });
        console.log(`📸 Before screenshot: ${beforePath}`);

        // Step 4: Find and click trigger element
        console.log(`\n🖱️  Looking for trigger: ${config.selector}`);

        // Wait for element to be visible
        await page.waitForSelector(config.selector, { state: 'visible', timeout: 10000 });
        console.log('✅ Trigger element found');

        // Click the trigger
        await page.click(config.selector);
        console.log('✅ Trigger clicked');

        // Wait for modal animation
        await page.waitForTimeout(config.waitAfterClick);

        // Step 5: Verify modal opened
        const modalVisible = await page.evaluate(() => {
            const modals = document.querySelectorAll('.fixed.inset-0');
            return Array.from(modals).some(m => {
                const style = window.getComputedStyle(m);
                return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
            });
        });

        if (!modalVisible) {
            console.log('⚠️  WARNING: Modal might not be visible');
        } else {
            console.log('✅ Modal is visible');
        }

        // Step 6: Take modal screenshot
        const modalPath = getScreenshotPath('modal_open');
        await page.screenshot({ path: modalPath, fullPage: false });
        console.log(`📸 Modal screenshot: ${modalPath}`);

        // Step 7: Check x-teleport worked
        console.log('\n🔍 Checking x-teleport...');
        const teleportCheck = await page.evaluate(() => {
            const modals = document.querySelectorAll('.fixed.inset-0.z-50');
            const teleportedModals = Array.from(document.body.children).filter(el =>
                el.matches('.fixed.inset-0') || el.querySelector('.fixed.inset-0')
            );

            return {
                totalModals: modals.length,
                directBodyChildren: teleportedModals.length,
                bodyChildrenTags: teleportedModals.map(el => el.tagName + (el.className ? '.' + el.className.split(' ').join('.') : ''))
            };
        });

        console.log(`  Total modals found: ${teleportCheck.totalModals}`);
        console.log(`  Teleported to body: ${teleportCheck.directBodyChildren}`);
        console.log(`  Body children: ${teleportCheck.bodyChildrenTags.join(', ')}`);

        // Step 8: Check overlay dimensions
        console.log('\n📐 Checking overlay dimensions...');
        const overlayInfo = await checkOverlayDimensions(page);

        if (overlayInfo.found) {
            console.log(`  Position: ${overlayInfo.position}`);
            console.log(`  Z-Index: ${overlayInfo.zIndex}`);
            console.log(`  Dimensions: ${overlayInfo.width}x${overlayInfo.height}`);
            console.log(`  Top/Left: ${overlayInfo.top}/${overlayInfo.left}`);
            console.log(`  Display: ${overlayInfo.display}, Visibility: ${overlayInfo.visibility}, Opacity: ${overlayInfo.opacity}`);
            console.log(`  Background: ${overlayInfo.backgroundColor}`);
            console.log(`  Covers Viewport: ${overlayInfo.coversViewport ? '✅ YES' : '❌ NO'}`);
        } else {
            console.log('⚠️  Overlay element not found');
        }

        // Step 8: Get modal structure
        console.log('\n🏗️  Modal structure:');
        const structure = await getModalStructure(page);
        structure.forEach((modal, index) => {
            console.log(`\nModal ${index + 1}:`);
            console.log(`  Root: position=${modal.position}, z-index=${modal.zIndex}`);
            console.log(`  Overlay: position=${modal.overlayPosition}, z-index=${modal.overlayZIndex}`);
            console.log(`  Content: position=${modal.contentPosition}, z-index=${modal.contentZIndex}`);
        });

        // Step 9: Test ESC key
        const escWorks = await testEscapeKey(page);

        // Step 10: Reopen modal for overlay click test
        if (escWorks) {
            console.log('\n🔄 Reopening modal for overlay click test...');
            await page.click(config.selector);
            await page.waitForTimeout(config.waitAfterClick);
        }

        // Test overlay click
        const overlayClickWorks = await testOverlayClick(page);

        // Step 11: Final report
        console.log('\n' + '='.repeat(50));
        console.log('FINAL REPORT');
        console.log('='.repeat(50));
        console.log(`✅ Modal opens: ${modalVisible ? 'YES' : 'NO'}`);
        console.log(`✅ Overlay covers viewport: ${overlayInfo.coversViewport ? 'YES' : 'NO'}`);
        console.log(`✅ ESC key closes modal: ${escWorks ? 'YES' : 'NO'}`);
        console.log(`✅ Overlay click closes modal: ${overlayClickWorks ? 'YES' : 'NO'}`);
        console.log('='.repeat(50));

        // All tests passed?
        const allPassed = modalVisible && overlayInfo.coversViewport && escWorks && overlayClickWorks;
        console.log(`\n${allPassed ? '✅ ALL TESTS PASSED' : '⚠️  SOME TESTS FAILED'}\n`);

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);

        // Take error screenshot
        const errorPath = getScreenshotPath('modal_error');
        await page.screenshot({ path: errorPath, fullPage: true });
        console.log(`📸 Error screenshot: ${errorPath}`);
    } finally {
        await browser.close();
    }
}

// Run the test
const config = parseArgs();
testModal(config).catch(console.error);
