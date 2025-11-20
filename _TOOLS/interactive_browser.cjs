#!/usr/bin/env node
/**
 * PPM INTERACTIVE BROWSER TOOL
 *
 * Interactive REPL for testing, debugging, and verifying PPM application
 * Full control over browser with DevTools-like capabilities
 *
 * Usage:
 *   node interactive_browser.cjs [URL]
 *
 * Features:
 *   - Live browser window (visible)
 *   - Console monitoring
 *   - Network monitoring
 *   - Interactive commands (click, fill, screenshot, etc.)
 *   - JavaScript evaluation
 *   - Database queries
 *
 * Commands:
 *   click <selector>           Click element
 *   fill <selector> <value>    Fill input field
 *   check <selector>           Check checkbox
 *   uncheck <selector>         Uncheck checkbox
 *   screenshot [name]          Take screenshot
 *   console                    Show console logs
 *   network                    Show HTTP requests
 *   eval <code>                Execute JavaScript
 *   goto <url>                 Navigate to URL
 *   wait <ms>                  Wait X milliseconds
 *   reload                     Reload page
 *   db <query>                 Execute database query
 *   help                       Show all commands
 *   exit                       Close browser and exit
 */

const { chromium } = require('playwright');
const readline = require('readline');
const fs = require('fs');
const path = require('path');

// Colors for terminal output
const colors = {
    reset: '\x1b[0m',
    bright: '\x1b[1m',
    dim: '\x1b[2m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    magenta: '\x1b[35m',
    cyan: '\x1b[36m',
};

// Global state
let browser = null;
let page = null;
let context = null;
const consoleLogs = [];
const networkRequests = [];
const screenshotsDir = path.join(__dirname, 'screenshots');

// Ensure screenshots directory exists
if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
}

// Parse arguments
const args = process.argv.slice(2);
const initialUrl = args[0] || 'https://ppm.mpptrade.pl/admin/products/10969/edit';

// Setup readline interface
const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
    prompt: colors.cyan + 'PPM> ' + colors.reset
});

// Console monitoring
function setupConsoleMonitoring(page) {
    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        const timestamp = new Date().toISOString();

        consoleLogs.push({ type, text, timestamp });

        // Print immediately with color
        const icon = type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
        const color = type === 'error' ? colors.red : type === 'warning' ? colors.yellow : colors.dim;
        console.log(`${color}${icon} [${type}] ${text}${colors.reset}`);
    });

    page.on('pageerror', error => {
        consoleLogs.push({ type: 'pageerror', text: error.toString(), timestamp: new Date().toISOString() });
        console.log(`${colors.red}🔥 [PAGE ERROR] ${error.toString()}${colors.reset}`);
    });
}

// Network monitoring
function setupNetworkMonitoring(page) {
    page.on('request', request => {
        networkRequests.push({
            type: 'request',
            url: request.url(),
            method: request.method(),
            timestamp: new Date().toISOString()
        });
    });

    page.on('response', response => {
        const status = response.status();
        const url = response.url();

        networkRequests.push({
            type: 'response',
            url: url,
            status: status,
            ok: response.ok(),
            timestamp: new Date().toISOString()
        });

        // Print failed requests immediately
        if (!response.ok() && status !== 304) { // Ignore 304 Not Modified
            console.log(`${colors.red}❌ [${status}] ${url}${colors.reset}`);
        }
    });
}

// Command handlers
const commands = {
    async click(selector) {
        if (!selector) {
            console.log(`${colors.red}Error: Missing selector${colors.reset}`);
            console.log(`Usage: click <selector>`);
            return;
        }
        try {
            await page.click(selector);
            console.log(`${colors.green}✅ Clicked: ${selector}${colors.reset}`);
        } catch (error) {
            console.log(`${colors.red}❌ Error: ${error.message}${colors.reset}`);
        }
    },

    async fill(selector, value) {
        if (!selector || value === undefined) {
            console.log(`${colors.red}Error: Missing selector or value${colors.reset}`);
            console.log(`Usage: fill <selector> <value>`);
            return;
        }
        try {
            await page.fill(selector, value);
            console.log(`${colors.green}✅ Filled ${selector} with: ${value}${colors.reset}`);
        } catch (error) {
            console.log(`${colors.red}❌ Error: ${error.message}${colors.reset}`);
        }
    },

    async check(selector) {
        if (!selector) {
            console.log(`${colors.red}Error: Missing selector${colors.reset}`);
            return;
        }
        try {
            await page.check(selector);
            console.log(`${colors.green}✅ Checked: ${selector}${colors.reset}`);
        } catch (error) {
            console.log(`${colors.red}❌ Error: ${error.message}${colors.reset}`);
        }
    },

    async uncheck(selector) {
        if (!selector) {
            console.log(`${colors.red}Error: Missing selector${colors.reset}`);
            return;
        }
        try {
            await page.uncheck(selector);
            console.log(`${colors.green}✅ Unchecked: ${selector}${colors.reset}`);
        } catch (error) {
            console.log(`${colors.red}❌ Error: ${error.message}${colors.reset}`);
        }
    },

    async screenshot(name) {
        const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
        const filename = name ? `${name}_${timestamp}.png` : `screenshot_${timestamp}.png`;
        const filepath = path.join(screenshotsDir, filename);

        try {
            await page.screenshot({ path: filepath, fullPage: true });
            console.log(`${colors.green}✅ Screenshot saved: ${filepath}${colors.reset}`);
        } catch (error) {
            console.log(`${colors.red}❌ Error: ${error.message}${colors.reset}`);
        }
    },

    console() {
        console.log(`\n${colors.cyan}=== CONSOLE LOGS (${consoleLogs.length} total) ===${colors.reset}`);
        if (consoleLogs.length === 0) {
            console.log(`${colors.dim}No console logs yet${colors.reset}`);
        } else {
            const recent = consoleLogs.slice(-20); // Last 20 logs
            recent.forEach((log, i) => {
                const color = log.type === 'error' ? colors.red : log.type === 'warning' ? colors.yellow : colors.dim;
                console.log(`${color}${i + 1}. [${log.type}] ${log.text}${colors.reset}`);
            });
            if (consoleLogs.length > 20) {
                console.log(`${colors.dim}... showing last 20 of ${consoleLogs.length} logs${colors.reset}`);
            }
        }
        console.log('');
    },

    network() {
        console.log(`\n${colors.cyan}=== NETWORK REQUESTS (${networkRequests.length} total) ===${colors.reset}`);

        const failedRequests = networkRequests.filter(r => r.type === 'response' && !r.ok);
        const successRequests = networkRequests.filter(r => r.type === 'response' && r.ok);

        console.log(`${colors.green}Successful: ${successRequests.length}${colors.reset}`);
        console.log(`${colors.red}Failed: ${failedRequests.length}${colors.reset}`);

        if (failedRequests.length > 0) {
            console.log(`\n${colors.red}Failed Requests:${colors.reset}`);
            failedRequests.forEach((req, i) => {
                console.log(`  ${i + 1}. [${req.status}] ${req.url}`);
            });
        }
        console.log('');
    },

    async eval(code) {
        if (!code) {
            console.log(`${colors.red}Error: Missing JavaScript code${colors.reset}`);
            return;
        }
        try {
            const result = await page.evaluate(code);
            console.log(`${colors.green}✅ Result:${colors.reset}`, result);
        } catch (error) {
            console.log(`${colors.red}❌ Error: ${error.message}${colors.reset}`);
        }
    },

    async goto(url) {
        if (!url) {
            console.log(`${colors.red}Error: Missing URL${colors.reset}`);
            return;
        }
        try {
            await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
            console.log(`${colors.green}✅ Navigated to: ${url}${colors.reset}`);
        } catch (error) {
            console.log(`${colors.red}❌ Error: ${error.message}${colors.reset}`);
        }
    },

    async wait(ms) {
        if (!ms) {
            console.log(`${colors.red}Error: Missing milliseconds${colors.reset}`);
            return;
        }
        const milliseconds = parseInt(ms);
        if (isNaN(milliseconds)) {
            console.log(`${colors.red}Error: Invalid milliseconds${colors.reset}`);
            return;
        }
        await page.waitForTimeout(milliseconds);
        console.log(`${colors.green}✅ Waited ${milliseconds}ms${colors.reset}`);
    },

    async reload() {
        try {
            await page.reload({ waitUntil: 'networkidle' });
            console.log(`${colors.green}✅ Page reloaded${colors.reset}`);
        } catch (error) {
            console.log(`${colors.red}❌ Error: ${error.message}${colors.reset}`);
        }
    },

    async db(query) {
        console.log(`${colors.yellow}⚠️ Database query feature not yet implemented${colors.reset}`);
        console.log(`Will execute: ${query}`);
        // TODO: Implement SSH command execution for database queries
    },

    help() {
        console.log(`\n${colors.cyan}${colors.bright}=== PPM INTERACTIVE BROWSER COMMANDS ===${colors.reset}\n`);
        console.log(`${colors.green}Navigation:${colors.reset}`);
        console.log(`  goto <url>              Navigate to URL`);
        console.log(`  reload                  Reload current page`);
        console.log(`  wait <ms>               Wait X milliseconds`);
        console.log(``);
        console.log(`${colors.green}Interaction:${colors.reset}`);
        console.log(`  click <selector>        Click element`);
        console.log(`  fill <selector> <value> Fill input field`);
        console.log(`  check <selector>        Check checkbox`);
        console.log(`  uncheck <selector>      Uncheck checkbox`);
        console.log(``);
        console.log(`${colors.green}Inspection:${colors.reset}`);
        console.log(`  screenshot [name]       Take screenshot`);
        console.log(`  console                 Show console logs`);
        console.log(`  network                 Show network requests`);
        console.log(`  eval <code>             Execute JavaScript`);
        console.log(``);
        console.log(`${colors.green}Database:${colors.reset}`);
        console.log(`  db <query>              Execute database query (future)`);
        console.log(``);
        console.log(`${colors.green}System:${colors.reset}`);
        console.log(`  help                    Show this help`);
        console.log(`  exit                    Close browser and exit`);
        console.log(``);
        console.log(`${colors.dim}Examples:${colors.reset}`);
        console.log(`  ${colors.cyan}click button[type="submit"]${colors.reset}`);
        console.log(`  ${colors.cyan}fill input[name="email"] admin@mpptrade.pl${colors.reset}`);
        console.log(`  ${colors.cyan}check input[type="checkbox"]${colors.reset}`);
        console.log(`  ${colors.cyan}screenshot product-edit${colors.reset}`);
        console.log(`  ${colors.cyan}eval document.querySelectorAll('.tab-enterprise').length${colors.reset}`);
        console.log(``);
    },

    async exit() {
        console.log(`${colors.yellow}Closing browser...${colors.reset}`);
        if (browser) {
            await browser.close();
        }
        console.log(`${colors.green}✅ Browser closed. Goodbye!${colors.reset}`);
        process.exit(0);
    }
};

// Process command
async function processCommand(input) {
    const trimmed = input.trim();
    if (!trimmed) return;

    const parts = trimmed.split(' ');
    const command = parts[0].toLowerCase();
    const args = parts.slice(1);

    if (commands[command]) {
        await commands[command](...args);
    } else {
        console.log(`${colors.red}Unknown command: ${command}${colors.reset}`);
        console.log(`Type ${colors.cyan}help${colors.reset} for available commands`);
    }
}

// Main
(async () => {
    console.log(`${colors.cyan}${colors.bright}\n╔════════════════════════════════════════╗`);
    console.log(`║  PPM INTERACTIVE BROWSER TOOL         ║`);
    console.log(`╚════════════════════════════════════════╝${colors.reset}\n`);

    console.log(`${colors.dim}Starting browser...${colors.reset}`);

    // Launch browser
    browser = await chromium.launch({
        headless: false, // Always visible
        args: ['--disable-blink-features=AutomationControlled']
    });

    context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });

    page = await context.newPage();

    // Setup monitoring
    setupConsoleMonitoring(page);
    setupNetworkMonitoring(page);

    console.log(`${colors.green}✅ Browser launched${colors.reset}\n`);

    // Auto-login
    console.log(`${colors.dim}Logging in...${colors.reset}`);
    await page.goto('https://ppm.mpptrade.pl/login', { waitUntil: 'networkidle' });
    await page.fill('input[name="email"]', 'admin@mpptrade.pl');
    await page.fill('input[name="password"]', 'Admin123!MPP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    console.log(`${colors.green}✅ Logged in${colors.reset}\n`);

    // Navigate to initial URL
    console.log(`${colors.dim}Navigating to: ${initialUrl}${colors.reset}`);
    await page.goto(initialUrl, { waitUntil: 'networkidle' });
    console.log(`${colors.green}✅ Page loaded${colors.reset}\n`);

    console.log(`${colors.yellow}Type ${colors.cyan}help${colors.yellow} for available commands${colors.reset}`);
    console.log(`${colors.yellow}Type ${colors.cyan}exit${colors.yellow} to quit${colors.reset}\n`);

    // Start REPL
    rl.prompt();

    rl.on('line', async (line) => {
        await processCommand(line);
        rl.prompt();
    }).on('close', async () => {
        await commands.exit();
    });

})().catch(error => {
    console.error(`${colors.red}Fatal error: ${error.message}${colors.reset}`);
    process.exit(1);
});
